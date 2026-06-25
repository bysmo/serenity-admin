<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membre;
use App\Models\NanoCredit;
use App\Models\NanoCreditGarant;
use App\Notifications\GarantRefusNotification;
use App\Services\NanoCreditService;
use App\Services\PinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GarantApiController extends Controller
{
    /**
     * Statistiques du garant
     */
    public function stats(Request $request): JsonResponse
    {
        $membre = $request->user();
        $nbActifs = $membre->garantiesActives()->count();
        $totalSupporte = $membre->garantiesActives()->with('nanoCredit')->get()->sum(fn($g) => $g->nanoCredit->montant);

        return response()->json([
            'qualite' => (float) $membre->garant_qualite,
            'solde_gains' => (float) ($membre->garant_solde ?? 0),
            'nb_garanties_actives' => $nbActifs,
            'max_garanties' => $membre->maximumGaranties(),
            'total_montant_supporte' => (float) $totalSupporte,
            'epargne_bloquee' => $membre->isEpargneBloquee(),
        ]);
    }

    /**
     * Liste des sollicitations (en attente, acceptées, refusées, etc.)
     */
    public function sollicitations(Request $request): JsonResponse
    {
        $membre = $request->user();
        $statut = $request->query('statut', 'en_attente');

        $query = $membre->garants()->with(['nanoCredit.membre', 'nanoCredit.palier']);

        if ($statut !== 'all') {
            $query->where('statut', $statut);
        }

        $list = $query->orderBy('created_at', 'desc')->paginate(15);

        $data = $list->getCollection()->map(fn($g) => [
            'id' => $g->id,
            'statut' => $g->statut,
            'statut_label' => $g->statut_label,
            'date_sollicitation' => $g->created_at->format('Y-m-d'),
            'gain_potentiel' => $g->gain_partage ? (float) $g->gain_partage : $this->calculerGainPotentiel($g),
            'credit' => [
                'id' => $g->nanoCredit->id,
                'montant' => (float) $g->nanoCredit->montant,
                'demandeur' => $g->nanoCredit->membre->nom_complet,
                'palier' => $g->nanoCredit->palier->nom,
            ]
        ]);

        return response()->json(['data' => $data, 'meta' => $this->paginateMeta($list)]);
    }

    /**
     * Accepter ou refuser une sollicitation (opération critique, PIN requis)
     */
    public function action(Request $request, $id): JsonResponse
    {
        $membre = $request->user();
        $garant = NanoCreditGarant::where('membre_id', $membre->id)->findOrFail($id);

        if ($garant->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette sollicitation a déjà été traitée.'], 422);
        }

        // Vérification du PIN (opération critique : acceptation/refus de garantie)
        $pinError = app(PinService::class)->requirePin($request, $membre);
        if ($pinError) return $pinError;

        $validated = $request->validate([
            'action' => 'required|in:accepter,refuser',
            'motif'  => 'required_if:action,refuser|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            if ($validated['action'] === 'accepter') {
                $garant->update([
                    'statut'       => 'accepte',
                    'accepte_le'   => now(),
                    'gain_partage' => $this->calculerGainPotentiel($garant),
                ]);

                // Vérifier si tous les garants ont accepté pour débourser
                $nanoCredit = $garant->nanoCredit;
                $tousAcceptes = $nanoCredit->garants()->where('statut', '!=', 'accepte')->count() === 0;

                if ($tousAcceptes) {
                    app(NanoCreditService::class)->debourser($nanoCredit);
                }
            } else {
                $garant->update([
                    'statut'      => 'refuse',
                    'refuse_le'   => now(),
                    'motif_refus' => $validated['motif'],
                ]);

                // Notifier le demandeur qu'un garant a refusé
                $garant->nanoCredit->membre->notify(new GarantRefusNotification($garant));
            }

            DB::commit();
            return response()->json(['message' => 'Action enregistrée avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Garant API Action: " . $e->getMessage());
            return response()->json(['message' => 'Erreur: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Demande de retrait des gains (opération critique, PIN requis)
     */
    public function retirerGains(Request $request): JsonResponse
    {
        $membre  = $request->user();
        $montant = (float) $request->input('montant');

        if ($montant <= 0 || $montant > (float) $membre->garant_solde) {
            return response()->json(['message' => 'Montant invalide ou solde insuffisant.'], 422);
        }

        // Vérification du PIN (opération critique : retrait des gains garant)
        $pinError = app(PinService::class)->requirePin($request, $membre);
        if ($pinError) return $pinError;

        DB::beginTransaction();
        try {
            // Dans une vraie app, on initierait un transfert ici
            $membre->refresh();
            $membre->garant_solde = (float) $membre->garant_solde - $montant;
            $membre->save();

            DB::commit();
            return response()->json(['message' => 'Demande de retrait enregistrée.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors du retrait.'], 500);
        }
    }

    private function calculerGainPotentiel(NanoCreditGarant $garant): float
    {
        $nanoCredit = $garant->nanoCredit;
        $palier = $nanoCredit->palier;
        $nbGarants = $nanoCredit->garants()->count();
        if ($nbGarants === 0) return 0;

        $totalInterets = $nanoCredit->montant * ($palier->taux_interet / 100);
        $percentPartage = (float) ($palier->pourcentage_partage_garant ?? 85.0);
        $partGarantTotal = $totalInterets * ($percentPartage / 100);
        return round($partGarantTotal / $nbGarants, 0);
    }

    private function paginateMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}

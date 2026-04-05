<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\NanoCreditGarant;
use App\Models\GarantGainRetrait;
use App\Models\NanoCredit;
use App\Models\User;
use App\Notifications\GarantGainRetraitRequestNotification; // To be created or simulated
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MembreGarantController extends Controller
{
    /**
     * Dashboard du garant : solde, qualité, stats.
     */
    public function index()
    {
        $membre = Auth::guard('membre')->user();
        
        $stats = [
            'total_gains' => $membre->garant_solde ?? 0,
            'qualite' => $membre->garant_qualite ?? 0,
            'garanties_actives' => $membre->garantiesActives()->count(),
            'total_credits_supportes' => $membre->garants()->whereIn('statut', ['accepte', 'preleve', 'libere'])->count(),
            'nb_sollicitations' => $membre->garants()->where('statut', 'en_attente')->count(),
        ];

        $retraitsRecents = GarantGainRetrait::where('membre_id', $membre->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('membres.garants.index', compact('membre', 'stats', 'retraitsRecents'));
    }

    /**
     * Liste des sollicitations en attente.
     */
    public function sollicitations()
    {
        $membre = Auth::guard('membre')->user();
        $sollicitations = $membre->garants()
            ->where('statut', 'en_attente')
            ->with(['nanoCredit.membre', 'nanoCredit.palier'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('membres.garants.sollicitations', compact('membre', 'sollicitations'));
    }

    /**
     * Accepter une sollicitation.
     */
    public function accepter(NanoCreditGarant $garant)
    {
        $membre = Auth::guard('membre')->user();

        if ($garant->membre_id !== $membre->id || $garant->statut !== 'en_attente') {
            abort(403);
        }

        // Vérification de l'éligibilité via la méthode statique du modèle
        if (!NanoCreditGarant::membreEstEligibleGarant($membre, $garant->nanoCredit)) {
            return redirect()->back()->with('error', 'Vous ne remplissez pas les conditions d\'éligibilité pour être garant de ce crédit (solde tontine, qualité ou limite de garanties).');
        }

        $garant->update([
            'statut' => 'accepte',
            'accepte_le' => now(),
        ]);

        return redirect()->route('membre.garant.sollicitations')->with('success', 'Vous avez accepté de supporter ce nano-crédit.');
    }

    /**
     * Refuser une sollicitation.
     */
    public function refuser(Request $request, NanoCreditGarant $garant)
    {
        $membre = Auth::guard('membre')->user();

        if ($garant->membre_id !== $membre->id || $garant->statut !== 'en_attente') {
            abort(403);
        }

        $request->validate(['motif_refus' => 'nullable|string|max:255']);

        $garant->update([
            'statut' => 'refuse',
            'refuse_le' => now(),
            'motif_refus' => $request->motif_refus,
        ]);

        return redirect()->route('membre.garant.sollicitations')->with('success', 'Vous avez refusé cette sollicitation.');
    }

    /**
     * Historique des engagements.
     */
    public function engagements()
    {
        $membre = Auth::guard('membre')->user();
        $engagements = $membre->garants()
            ->whereIn('statut', ['accepte', 'preleve', 'libere', 'refuse'])
            ->with(['nanoCredit.membre', 'nanoCredit.palier'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('membres.garants.engagements', compact('membre', 'engagements'));
    }

    /**
     * Demande de retrait du solde garant.
     */
    public function withdraw(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $soldeDisponible = (float) $membre->garant_solde;

        $request->validate([
            'montant' => 'required|numeric|min:500|max:' . $soldeDisponible,
        ], [
            'montant.max' => 'Le montant demandé dépasse votre solde disponible (' . number_format($soldeDisponible, 0, ',', ' ') . ' XOF).',
            'montant.min' => 'Le montant minimum de retrait est de 500 XOF.',
        ]);

        DB::beginTransaction();
        try {
            // Créer la demande de retrait
            $retrait = GarantGainRetrait::create([
                'reference' => 'RET-' . strtoupper(Str::random(10)),
                'membre_id' => $membre->id,
                'montant' => $request->montant,
                'statut' => 'en_attente',
            ]);

            // Déduire temporairement du solde pour éviter les demandes multiples dépassant le solde réellement disponible (optionnel selon le business model)
            // Ici on choisit de ne déduire qu'à l'approbation, mais on pourrait "bloquer" la somme.
            // Pour simplifier, on laisse le solde tel quel et l'admin vérifiera.

            DB::commit();

            // Notifier les admins (Optionnel)
            // ...

            return redirect()->route('membre.garant.index')->with('success', 'Votre demande de retrait de ' . number_format($request->montant, 0, ',', ' ') . ' XOF a été enregistrée et sera traitée par l\'administration.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de l\'enregistrement de votre demande.');
        }
    }
}

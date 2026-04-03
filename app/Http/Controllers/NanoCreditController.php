<?php

namespace App\Http\Controllers;

use App\Models\NanoCredit;
use App\Models\NanoCreditEcheance;
use App\Models\NanoCreditVersement;
use App\Notifications\NanoCreditOctroyeNotification;
use App\Services\PayDunyaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NanoCreditController extends Controller
{
    /**
     * Liste des demandes de nano crédit (admin)
     */
    public function index(Request $request)
    {
        $query = NanoCredit::with(['membre', 'nanoCreditType', 'createdByUser'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('telephone', 'like', "%{$s}%")
                    ->orWhere('transaction_id', 'like', "%{$s}%")
                    ->orWhereHas('membre', function ($q2) use ($s) {
                        $q2->where('nom', 'like', "%{$s}%")
                            ->orWhere('prenom', 'like', "%{$s}%")
                            ->orWhere('telephone', 'like', "%{$s}%");
                    });
            });
        }

        $nanoCredits = $query->paginate(15)->withQueryString();
        return view('nano-credits.index', compact('nanoCredits'));
    }

    /**
     * Détail d'une demande : KYC link + bouton Octroyer
     */
    public function show(NanoCredit $nanoCredit)
    {
        $nanoCredit->load(['membre.kycVerification', 'nanoCreditType', 'echeances', 'versements', 'palier', 'garants.membre']);
        $withdrawModes = NanoCredit::withdrawModeLabels();
        return view('nano-credits.show', compact('nanoCredit', 'withdrawModes'));
    }

    /**
     * Octroyer le crédit : déboursement PayDunya + génération des échéances
     */
    public function octroyer(Request $request, NanoCredit $nanoCredit)
    {
        if (!$nanoCredit->isEnAttente()) {
            return redirect()->route('nano-credits.show', $nanoCredit)
                ->with('error', 'Cette demande n\'est plus en attente d\'octroi.');
        }

        $validated = $request->validate([
            'telephone' => 'required|string|max:20',
            'withdraw_mode' => 'required|string|in:' . implode(',', array_keys(NanoCredit::withdrawModeLabels())),
        ]);

        $telephone = $this->normalizePhoneForPayDunya($validated['telephone']);
        if ($telephone === '') {
            return redirect()->back()->withInput()->with('error', 'Numéro de téléphone invalide.');
        }

        $montant = (int) $nanoCredit->montant;
        $callbackUrl = url()->route('paydunya.disburse.callback');

        try {
            $paydunya = app(PayDunyaService::class);
        } catch (\Exception $e) {
            Log::error('NanoCredit octroyer: PayDunya non configuré', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'PayDunya n\'est pas configuré ou activé.');
        }

        $result = $paydunya->createDisburseInvoice(
            $telephone,
            $montant,
            $validated['withdraw_mode'],
            $callbackUrl
        );

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Impossible de créer le déboursement.');
        }

        $nanoCredit->update([
            'telephone' => $telephone,
            'withdraw_mode' => $validated['withdraw_mode'],
            'disburse_token' => $result['disburse_token'],
            'disburse_id' => (string) $nanoCredit->id,
        ]);

        $submit = $paydunya->submitDisburseInvoice($result['disburse_token'], (string) $nanoCredit->id);

        if (!$submit['success']) {
            $nanoCredit->update([
                'statut' => 'demande_en_attente',
                'error_message' => $submit['message'] ?? 'Erreur à la soumission',
            ]);
            return redirect()->route('nano-credits.show', $nanoCredit)
                ->with('error', 'Soumission échouée : ' . ($submit['message'] ?? 'Erreur inconnue'));
        }

        $dateOctroi = now()->toDateString();
        $type = $nanoCredit->nanoCreditType;
        $dateFinRemb = $type ? Carbon::parse($dateOctroi)->addMonths((int) $type->duree_mois)->toDateString() : null;

        $nanoCredit->update([
            'statut' => 'debourse',
            'date_octroi' => $dateOctroi,
            'date_fin_remboursement' => $dateFinRemb,
            'transaction_id' => $submit['transaction_id'] ?? null,
            'provider_ref' => $submit['provider_ref'] ?? null,
            'created_by' => auth()->id(),
            'error_message' => null,
        ]);

        if ($type) {
            $this->genererEcheances($nanoCredit);
        }

        $nanoCredit->membre->notify(new NanoCreditOctroyeNotification($nanoCredit));

        app(\App\Services\EmailService::class)->sendNanoCreditOctroyeEmail($nanoCredit);

        return redirect()->route('nano-credits.show', $nanoCredit)
            ->with('success', 'Crédit octroyé avec succès. Le montant a été envoyé au mobile money du membre.');
    }

    /**
     * Génère les échéances de remboursement (tableau d'amortissement)
     */
    private function genererEcheances(NanoCredit $nanoCredit): void
    {
        $type = $nanoCredit->nanoCreditType;
        if (!$type) {
            return;
        }

        $calc = $type->calculAmortissement((float) $nanoCredit->montant);
        $nbEcheances = $calc['nombre_echeances'];
        $montantEcheance = $calc['montant_echeance'];
        $dateDebut = Carbon::parse($nanoCredit->date_octroi);

        $addPeriod = match ($type->frequence_remboursement) {
            'hebdomadaire' => fn ($date, $i) => $date->copy()->addWeeks($i),
            'trimestriel' => fn ($date, $i) => $date->copy()->addMonths(3 * $i),
            default => fn ($date, $i) => $date->copy()->addMonths($i),
        };

        for ($i = 1; $i <= $nbEcheances; $i++) {
            $dateEcheance = $addPeriod($dateDebut, $i);
            $montant = $i === $nbEcheances
                ? $calc['montant_total_du'] - ($montantEcheance * ($nbEcheances - 1))
                : $montantEcheance;
            NanoCreditEcheance::create([
                'nano_credit_id' => $nanoCredit->id,
                'date_echeance' => $dateEcheance->toDateString(),
                'montant' => $montant,
                'statut' => 'a_venir',
            ]);
        }
    }

    private function normalizePhoneForPayDunya(string $telephone): string
    {
        $digits = preg_replace('/\D/', '', $telephone);
        $indicatifs = ['221', '229', '225', '228', '223', '226'];
        foreach ($indicatifs as $code) {
            if (str_starts_with($digits, $code) && strlen($digits) > strlen($code)) {
                return substr($digits, strlen($code));
            }
        }
        return $digits;
    }

    /**
     * Enregistrer un remboursement (versement) pour un nano crédit
     */
    public function storeVersement(Request $request, NanoCredit $nanoCredit)
    {
        if (!$nanoCredit->isDebourse()) {
            return redirect()->route('nano-credits.show', $nanoCredit)
                ->with('error', 'Ce nano crédit n\'a pas encore été décaissé.');
        }

        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'date_versement' => 'required|date',
            'mode_paiement' => 'required|string|max:50',
            'echeance_id' => 'nullable|exists:nano_credit_echeances,id',
        ]);

        $echeanceId = $validated['echeance_id'] ?? null;
        if ($echeanceId) {
            $echeance = NanoCreditEcheance::where('nano_credit_id', $nanoCredit->id)->findOrFail($echeanceId);
            $echeance->update(['statut' => 'payee', 'paye_le' => now()]);
        }

        NanoCreditVersement::create([
            'nano_credit_id' => $nanoCredit->id,
            'nano_credit_echeance_id' => $echeanceId,
            'montant' => (int) round((float) $validated['montant'], 0),
            'date_versement' => $validated['date_versement'],
            'mode_paiement' => $validated['mode_paiement'],
        ]);

        $nbPayees = $nanoCredit->echeances()->where('statut', 'payee')->count();
        $nbTotal = $nanoCredit->echeances()->count();
        if ($nbTotal > 0 && $nbPayees >= $nbTotal) {
            $nanoCredit->update(['statut' => 'rembourse']);
        } else {
            $nanoCredit->update(['statut' => 'en_remboursement']);
        }

        return redirect()->route('nano-credits.show', $nanoCredit)
            ->with('success', 'Remboursement enregistré.');
    }

    /**
     * Liste des crédits en défaut (impayés)
     */
    public function impayes(Request $request)
    {
        $query = NanoCredit::with(['membre', 'nanoCreditType', 'palier', 'garants.membre'])
            ->where('statut', 'en_remboursement')
            ->where('jours_retard', '>', 0)
            ->orderBy('jours_retard', 'desc');

        $impayes = $query->paginate(15);
        return view('nano-credits.impayes', compact('impayes'));
    }

    /**
     * Envoyer une relance simple (Email/SMS) au membre
     */
    public function relancer(NanoCredit $nanoCredit)
    {
        // En vrai: Notification système. Ici, simuler la notification.
        \App\Models\Notification::create([
            'membre_id' => $nanoCredit->membre_id,
            'titre' => '🚨 Relance Impayé (Nano-crédit)',
            'message' => "Bonjour, votre paiement pour le nano-crédit #{$nanoCredit->id} est en retard de {$nanoCredit->jours_retard} jours. Veuillez régulariser au plus vite pour éviter des pénalités supplémentaires.",
            'type' => 'alert',
            'is_read' => false
        ]);

        return redirect()->back()->with('success', 'Relance envoyée au membre avec succès.');
    }

    /**
     * Prévenir les garants
     */
    public function prevenirGarants(NanoCredit $nanoCredit)
    {
        $garants = $nanoCredit->garants()->where('statut', 'accepte')->with('membre')->get();
        if ($garants->isEmpty()) {
            return redirect()->back()->with('warning', 'Aucun garant validé pour ce crédit.');
        }

        foreach ($garants as $garant) {
            \App\Models\Notification::create([
                'membre_id' => $garant->membre_id,
                'titre' => '⚠️ Avertissement de Garantie (Nano-crédit)',
                'message' => "Le crédit #{$nanoCredit->id} dont vous vous êtes porté garant est en défaut de paiement. Si la situation n'est pas régularisée, un recouvrement sur votre tontine sera effectué.",
                'type' => 'warning',
                'is_read' => false
            ]);
        }

        return redirect()->back()->with('success', 'Avertissement envoyé aux garants.');
    }

    /**
     * Exécuter manuellement le prélèvement des garants
     */
    public function recouvrer(NanoCredit $nanoCredit)
    {
        $service = app(\App\Services\NanoCreditPalierService::class);
        $garantsPreleves = $service->prelevementsGarants($nanoCredit);

        if (empty($garantsPreleves)) {
            return redirect()->back()->with('error', 'Aucun prélèvement effectué. Soit il n\'y a pas de garants, soit le délai configuré (jours_avant_prelevement_garant) n\'est pas atteint.');
        }

        return redirect()->back()->with('success', 'Recouvrement effectué : ' . count($garantsPreleves) . ' garant(s) débité(s).');
    }
}

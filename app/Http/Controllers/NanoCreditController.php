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
        $query = NanoCredit::with(['membre', 'palier', 'createdByUser'])
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
        $nanoCredit->load(['membre.kycVerification', 'echeances', 'versements', 'palier', 'garants.membre']);
        $withdrawModes = NanoCredit::withdrawModeLabels();
        return view('nano-credits.show', compact('nanoCredit', 'withdrawModes'));
    }

    /**
     * Mettre à jour l'évaluation humaine des risques
     */
    public function updateRiskScore(Request $request, NanoCredit $nanoCredit)
    {
        if (!$nanoCredit->isEnAttente()) {
            return redirect()->route('nano-credits.show', $nanoCredit)
                ->with('error', 'Cette demande n\'est plus en attente d\'octroi.');
        }

        $validated = $request->validate([
            'score_humain' => 'required|integer|min:0|max:3',
        ]);

        $scoreHumain = $validated['score_humain'];
        $scoreGlobal = ($nanoCredit->score_ai ?? 0) + $scoreHumain;

        $nanoCredit->update([
            'score_humain' => $scoreHumain,
            'score_global' => $scoreGlobal,
        ]);

        if ($scoreGlobal < 2) {
            $membreCredit = $nanoCredit->membre;
            $telephone = $this->normalizePhoneForPayDunya($membreCredit->telephone ?? '');
            $withdrawMode = $this->detectWithdrawMode($telephone, $membreCredit->pays ?? 'BF');

            $service = app(\App\Services\NanoCreditService::class);
            $result = $service->debourser($nanoCredit, $telephone, $withdrawMode);

            if ($result['success']) {
                return redirect()->route('nano-credits.show', $nanoCredit)
                    ->with('success', 'Évaluation enregistrée. Le score global étant inférieur à 2, le crédit a été décaisé automatiquement.');
            } else {
                Log::error('Auto-déblocage admin nano-crédit échoué', [
                    'nano_credit_id' => $nanoCredit->id,
                    'error'          => $result['message'],
                ]);
                return redirect()->route('nano-credits.show', $nanoCredit)
                    ->with('success', "Évaluation enregistrée. L'auto-déblocage a échoué : {$result['message']}");
            }
        }

        return redirect()->route('nano-credits.show', $nanoCredit)
            ->with('success', 'Évaluation du risque mise à jour. Le score global est maintenant de ' . $scoreGlobal . '/6.');
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

        $service = app(\App\Services\NanoCreditService::class);
        $result = $service->debourser($nanoCredit, $telephone, $validated['withdraw_mode']);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->route('nano-credits.show', $nanoCredit)
            ->with('success', $result['message']);
    }



    private function normalizePhoneForPayDunya(string $telephone): string
    {
        $digits = preg_replace('/\D/', '', $telephone);
        $indicatifs = ['221','223', '225', '226', '227', '228', '229'];
        foreach ($indicatifs as $code) {
            if (str_starts_with($digits, $code) && strlen($digits) > strlen($code)) {
                return substr($digits, strlen($code));
            }
        }
        return $digits;
    }

    private function detectWithdrawMode(string $telephone, string $pays = 'BF'): string
    {
        // Burkina Faso (226) — Orange ou Moov
        $prefix2 = substr($telephone, 0, 2);
        if (in_array($prefix2, ['70', '71', '72', '73', '74', '75', '76', '77'])) {
            return 'orange-money-burkina';
        }
        if (in_array($prefix2, ['60', '61', '62', '65', '66', '67'])) {
            return 'moov-money-burkina';
        }
        // Sénégal (221)
        if (in_array($prefix2, ['77', '78'])) {
            return 'orange-money-senegal';
        }
        if (in_array($prefix2, ['76'])) {
            return 'free-money-senegal';
        }
        // Mali (223)
        if (in_array(substr($telephone, 0, 1), ['6', '7'])) {
            return 'orange-money-mali';
        }
        return 'orange-money-burkina'; // Fallback
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
            $this->finaliserRemboursement($nanoCredit);
        } else {
            $nanoCredit->update(['statut' => 'en_remboursement']);
        }

        return redirect()->route('nano-credits.show', $nanoCredit)
            ->with('success', 'Remboursement enregistré.');
    }

    /**
     * Actions finales lors du remboursement complet du crédit :
     * 1. Calcul et distribution du partage des bénéfices aux garants.
     * 2. Incrémentation de la qualité des garants.
     * 3. Libération des garants.
     */
    private function finaliserRemboursement(NanoCredit $nanoCredit): void
    {
        $palier = $nanoCredit->palier;
        $garants = $nanoCredit->garants()->whereIn('statut', ['accepte', 'preleve'])->with('membre')->get();
        
        if (!$palier || $garants->isEmpty()) {
            return;
        }

        // Calcul du profit (intérêts réellement perçus ou théoriques)
        $calc = $palier->calculAmortissement((float) $nanoCredit->montant);
        $interetsTotaux = (float) $calc['interet_total'];

        $pourcentagePartage = (float) $palier->pourcentage_partage_garant;
        
        if ($pourcentagePartage > 0 && $interetsTotaux > 0) {
            $montantAPartager = $interetsTotaux * ($pourcentagePartage / 100);
            $montantParGarant = (int) round($montantAPartager / $garants->count(), 0);

            foreach ($garants as $garant) {
                $garant->update(['gain_partage' => $montantParGarant, 'statut' => 'libere']);
                
                // Créditer le sous-compte garant du membre
                $membre = $garant->membre;
                if ($membre) {
                    $membre->increment('garant_solde', $montantParGarant);
                    $membre->increment('garant_qualite', 1);
                }
            }
        } else {
            // Pas de partage, mais on libère quand même et on augmente la qualité
            foreach ($garants as $garant) {
                $garant->update(['statut' => 'libere']);
                if ($garant->membre) {
                    $garant->membre->increment('garant_qualite', 1);
                }
            }
        }

        Log::info("NanoCreditController: Crédit #{$nanoCredit->id} remboursé. Profit partagé avec {$garants->count()} garants.");
    }

    /**
     * Liste des crédits en défaut (impayés)
     */
    public function impayes(Request $request)
    {
        $query = NanoCredit::with(['membre', 'palier', 'garants.membre'])
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

<?php

namespace App\Http\Controllers;

use App\Models\NanoCredit;
use App\Models\NanoCreditPalier;
use App\Models\NanoCreditEcheance;
use App\Models\NanoCreditVersement;
use App\Models\MouvementCaisse;
use App\Models\User;
use App\Models\Membre;
use App\Models\NanoCreditGarant;
use App\Notifications\NanoCreditDemandeNotification;
use App\Notifications\GarantSollicitationNotification;
use App\Notifications\GarantRefusNotification;
use App\Services\PayDunyaCallbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembreNanoCreditController extends Controller
{
    /**
     * Liste des types de nano crédit disponibles + lien vers Mes nano crédits
     */
    public function index()
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.kyc.index')
                ->with('info', 'Vous devez soumettre votre dossier KYC et qu\'il soit validé avant de pouvoir faire une demande de nano crédit.');
        }

        $palier = $membre->nanoCreditPalier;
        
        // Si le membre n'a pas de palier (ne devrait pas arriver si KYC validé), on lui assigne le 1
        if (!$palier) {
            app(\App\Services\NanoCreditPalierService::class)->assignerPalierInitial($membre);
            $membre->refresh();
            $palier = $membre->nanoCreditPalier;
        }

        return view('membres.nano-credits.index', compact('membre', 'palier'));
    }

    /**
     * Mes nano crédits (demandes et crédits octroyés)
     */
    public function mes()
    {
        $membre = Auth::guard('membre')->user();
        $nanoCredits = $membre->nanoCredits()
            ->with('palier')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('membres.nano-credits.mes', compact('membre', 'nanoCredits'));
    }

    /**
     * Formulaire de demande (souscription) pour un type donné — le membre ne choisit que le montant.
     * Le contact et le canal sont récupérés du profil du membre lors de l'octroi par l'admin.
     */
    public function demander()
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.nano-credits')->with('error', 'KYC requis.');
        }

        $palier = $membre->nanoCreditPalier;
        if (!$palier) {
            return redirect()->route('membre.nano-credits')->with('error', 'Aucun palier de crédit assigné.');
        }

        if ($membre->hasCreditEnCours()) {
            return redirect()->route('membre.nano-credits')->with('error', 'Vous avez déjà un crédit en cours non soldé. Veuillez le rembourser avant d\'en prendre un nouveau.');
        }

        return view('membres.nano-credits.demander', compact('membre', 'palier'));
    }

    /**
     * Enregistrer la demande de nano crédit
     */
    public function storeDemande(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.nano-credits')->with('error', 'KYC requis.');
        }

        $palier = $membre->nanoCreditPalier;
        if (!$palier) {
            return redirect()->route('membre.nano-credits')->with('error', 'Aucun palier assigné.');
        }

        if ($membre->hasCreditEnCours()) {
            return redirect()->route('membre.nano-credits')->with('error', 'Vous avez déjà un crédit en cours non soldé. Veuillez le rembourser avant d\'en prendre un nouveau.');
        }

        $montantMax = (float) $palier->montant_plafond;

        $validated = $request->validate([
            'montant' => 'required|numeric|min:1000|max:' . $montantMax,
            'garant_ids' => 'required|array|size:' . $palier->nombre_garants,
            'garant_ids.*' => 'required|exists:membres,id',
        ], [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant minimum est 1 000 XOF.',
            'montant.max' => 'Le montant maximum pour votre palier actuel est ' . number_format($montantMax, 0, ',', ' ') . ' XOF.',
            'garant_ids.required' => 'Vous devez sélectionner vos garants.',
            'garant_ids.size' => 'Vous devez sélectionner exactement ' . $palier->nombre_garants . ' garant(s).',
        ]);

        DB::beginTransaction();
        try {
            $nanoCredit = NanoCredit::create([
                'palier_id' => $palier->id,
                'membre_id' => $membre->id,
                'montant' => (int) round((float) $validated['montant'], 0),
                'statut' => 'demande_en_attente',
            ]);

            // Créer les sollicitations des garants
            foreach ($validated['garant_ids'] as $garantId) {
                $garantMembre = Membre::findOrFail($garantId);
                
                // Vérification supplémentaire de l'éligibilité
                // On crée une instance temporaire pour la validation
                $tempGarant = new NanoCreditGarant(['membre_id' => $garantId]);
                if (!NanoCreditGarant::membreEstEligibleGarant($garantMembre, $nanoCredit)) {
                     throw new \Exception("Le membre {$garantMembre->nom_complet} n'est pas éligible comme garant.");
                }

                $garantRecord = NanoCreditGarant::create([
                    'nano_credit_id' => $nanoCredit->id,
                    'membre_id' => $garantId,
                    'statut' => 'en_attente',
                ]);

                // Notifier le garant
                $garantMembre->notify(new GarantSollicitationNotification($garantRecord));
            }

            DB::commit();

            // Notification admin
            $admins = User::whereHas('roles', function ($q) {
                $q->where('slug', 'admin')->where('actif', true);
            })->get();
            foreach ($admins as $admin) {
                $admin->notify(new NanoCreditDemandeNotification($nanoCredit));
            }

            return redirect()->route('membre.nano-credits.mes')
                ->with('success', 'Votre demande a été enregistrée. Vos garants ont été notifiés pour validation.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Recherche AJAX de garants éligibles
     */
    public function searchGuarantors(Request $request)
    {
        $search = $request->query('q');
        $membre = Auth::guard('membre')->user();
        $palier = $membre->nanoCreditPalier;

        if (!$palier) return response()->json([]);

        $query = Membre::where('id', '!=', $membre->id)
            ->where('statut', 'actif')
            ->whereHas('kycVerification', function($q) {
                 $q->where('statut', 'valide');
            })
            ->where('garant_qualite', '>=', $palier->min_garant_qualite ?? 0);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $results = $query->limit(10)->get()->filter(function($m) {
            // Filtrage fin (limite de garanties actives)
            return !$m->aAtteintLimiteGaranties();
        })->map(function($m) {
            return [
                'id' => $m->id,
                'text' => $m->nom_complet . " (" . $m->telephone . ")",
                'qualite' => $m->garant_qualite,
            ];
        })->values();

        return response()->json($results);
    }

    /**
     * Formulaire pour modifier les garants (après un refus)
     */
    public function modifierGarants(NanoCredit $nanoCredit)
    {
        $membre = Auth::guard('membre')->user();
        if ($nanoCredit->membre_id !== $membre->id) abort(403);

        $palier = $nanoCredit->palier;
        $garantsRefuses = $nanoCredit->garants()->where('statut', 'refuse')->with('membre')->get();
        $garantsValides = $nanoCredit->garants()->whereIn('statut', ['accepte', 'en_attente'])->with('membre')->get();

        if ($garantsRefuses->isEmpty()) {
            return redirect()->route('membre.nano-credits.show', $nanoCredit)->with('info', 'Tous vos garants sont déjà en attente ou ont accepté.');
        }

        return view('membres.nano-credits.modifier-garants', compact('nanoCredit', 'palier', 'garantsRefuses', 'garantsValides'));
    }

    /**
     * Mettre à jour les garants refusés
     */
    public function updateGarants(Request $request, NanoCredit $nanoCredit)
    {
        $membre = Auth::guard('membre')->user();
        if ($nanoCredit->membre_id !== $membre->id) abort(403);

        $palier = $nanoCredit->palier;
        $nbRefuses = $nanoCredit->garants()->where('statut', 'refuse')->count();

        $validated = $request->validate([
            'new_garant_ids' => 'required|array|size:' . $nbRefuses,
            'new_garant_ids.*' => 'required|exists:membres,id',
        ]);

        DB::beginTransaction();
        try {
            // Supprimer les refusés
            $nanoCredit->garants()->where('statut', 'refuse')->delete();

            // Ajouter les nouveaux
            foreach ($validated['new_garant_ids'] as $garantId) {
                $garantMembre = Membre::findOrFail($garantId);
                
                $garantRecord = NanoCreditGarant::create([
                    'nano_credit_id' => $nanoCredit->id,
                    'membre_id' => $garantId,
                    'statut' => 'en_attente',
                ]);

                $garantMembre->notify(new GarantSollicitationNotification($garantRecord));
            }

            DB::commit();
            return redirect()->route('membre.nano-credits.mes')->with('success', 'Nouveaux garants sollicités avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Détail d'un nano crédit : tableau d'amortissement + historique des remboursements.
     * Gère aussi le retour PayDunya (?token=...) : vérification et enregistrement du versement.
     */
    public function show(Request $request, NanoCredit $nanoCredit)
    {
        $membre = Auth::guard('membre')->user();

        if ($nanoCredit->membre_id !== $membre->id) {
            abort(403);
        }

        $paymentStatus  = null;
        $paymentMessage = null;
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        $pispiConfig    = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled   = $pispiConfig && $pispiConfig->enabled;

        // Traitement du retour PayDunya (?token=...)
        if ($request->has('token') && $paydunyaEnabled) {
            $invoiceToken = $request->input('token');
            try {
                $paydunyaService = new \App\Services\PayDunyaService();
                $verification    = $paydunyaService->verifyInvoice($invoiceToken);

                if ($verification['success']) {
                    $status = $verification['status'] ?? 'unknown';
                    if ($status === 'completed') {
                        $verificationData = $verification['data'] ?? [];
                        $customData       = $verificationData['custom_data'] ?? [];
                        $amount           = (float) ($verificationData['total_amount'] ?? 0);

                        // Déléguer au service centralisé (idempotent)
                        app(PayDunyaCallbackService::class)->handle($customData, $amount, $invoiceToken);
                        $paymentStatus  = 'success';
                        $paymentMessage = 'Remboursement enregistré avec succès.';
                    } elseif ($status === 'cancelled') {
                        $paymentStatus  = 'cancelled';
                        $paymentMessage = 'Paiement annulé.';
                    } else {
                        $paymentStatus  = 'pending';
                        $paymentMessage = 'Paiement en attente de confirmation.';
                    }
                } else {
                    $paymentStatus  = 'error';
                    $paymentMessage = $verification['message'] ?? 'Erreur de vérification.';
                }
            } catch (\Exception $e) {
                Log::error('PayDunya nano-crédit return_url', ['error' => $e->getMessage()]);
                $friendly = app(\App\Services\PayDunyaService::class)->getFriendlyErrorMessage($e);
                $paymentStatus  = 'error';
                $paymentMessage = $friendly;
            }
        }

        $nanoCredit->load(['palier', 'echeances', 'versements']);
        return view('membres.nano-credits.show', compact(
            'membre', 'nanoCredit', 'paymentStatus', 'paymentMessage',
            'paydunyaEnabled', 'pispiEnabled'
        ));
    }

    /**
     * Initier un remboursement d'échéance via PayDunya (checkout cliente  → caisse admin).
     */
    public function initierRemboursementPayDunya(Request $request, NanoCredit $nanoCredit)
    {
        $membre = Auth::guard('membre')->user();
        if ($nanoCredit->membre_id !== $membre->id) abort(403);

        if (!$nanoCredit->isDebourse()) {
            return back()->with('error', 'Ce crédit n\'a pas encore été décaisé.');
        }

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return back()->with('error', 'Le paiement PayDunya n\'est pas disponible.');
        }

        // Récupérer la première échéance impayée
        $echeance = $nanoCredit->echeances()
            ->whereIn('statut', ['a_venir', 'en_retard'])
            ->orderBy('date_echeance')
            ->first();

        if (!$echeance) {
            return back()->with('info', 'Toutes les échéances sont déjà payées.');
        }

        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            $callbackUrl     = url('/membre/paydunya/callback');
            $returnUrl       = route('membre.nano-credits.show', $nanoCredit->id);
            $cancelUrl       = $returnUrl;

            $result = $paydunyaService->createInvoice([
                'type'              => 'nano_credit_remboursement',
                'membre_id'         => $membre->id,
                'nano_credit_id'    => $nanoCredit->id,
                'echeance_id'       => $echeance->id,
                'item_name'         => 'Remboursement crédit #' . $nanoCredit->id . ' - Échéance ' . $echeance->date_echeance->format('d/m/Y'),
                'amount'            => (float) $echeance->montant_du,
                'description'       => 'Remboursement nano-crédit Serenity',
                'callback_url'      => $callbackUrl,
                'return_url'        => $returnUrl,
                'cancel_url'        => $cancelUrl,
            ]);

            if ($result['success']) {
                return redirect($result['invoice_url']);
            }

            return back()->with('error', $result['message'] ?? 'Erreur lors de la création du paiement.');

        } catch (\Exception $e) {
            Log::error('PayDunya remboursement nano-crédit: ' . $e->getMessage());
            $friendly = app(\App\Services\PayDunyaService::class)->getFriendlyErrorMessage($e);
            return back()->with('error', $friendly);
        }
    }

    /**
     * Initier un remboursement d'échéance via Pi-SPI.
     */
    public function initierRemboursementPiSpi(Request $request, NanoCredit $nanoCredit)
    {
        $membre = Auth::guard('membre')->user();
        if ($nanoCredit->membre_id !== $membre->id) abort(403);

        if (!$nanoCredit->isDebourse()) {
            return back()->with('error', 'Ce crédit n\'a pas encore été décaisé.');
        }

        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        if (!$pispiConfig || !$pispiConfig->enabled) {
            return back()->with('error', 'Le paiement Pi-SPI n\'est pas activé.');
        }

        if (!$membre->telephone) {
            return back()->with('error', 'Téléphone requis pour Pi-SPI.');
        }

        $echeance = $nanoCredit->echeances()
            ->whereIn('statut', ['a_venir', 'en_retard'])
            ->orderBy('date_echeance')
            ->first();

        if (!$echeance) {
            return back()->with('info', 'Toutes les échéances sont déjà payées.');
        }

        try {
            $pispiService = new \App\Services\PiSpiService();
            $reference    = 'NC-PISPI-' . time() . '-' . $echeance->id;

            $result = $pispiService->initiatePayment([
                'txId'        => $reference,
                'phone'       => $membre->telephone,
                'amount'      => (float) $echeance->montant_du,
                'description' => 'Remboursement crédit #' . $nanoCredit->id,
            ]);

            if ($result['success']) {
                // Pré-enregistrement en attente (le webhook Pi-SPI confirmera)
                NanoCreditVersement::create([
                    'nano_credit_id'          => $nanoCredit->id,
                    'nano_credit_echeance_id' => $echeance->id,
                    'montant'                 => (int) round((float) $echeance->montant_du),
                    'date_versement'          => now()->toDateString(),
                    'mode_paiement'           => 'pispi',
                    'reference'               => $reference,
                ]);
                return back()->with('success', 'Demande Pi-SPI envoyée. Validez sur votre mobile.');
            }

            return back()->with('error', 'Erreur Pi-SPI : ' . ($result['message'] ?? 'Echec initiation.'));

        } catch (\Exception $e) {
            Log::error('PiSpi remboursement nano-crédit: ' . $e->getMessage());
            $friendly = app(\App\Services\PiSpiService::class)->getFriendlyErrorMessage($e);
            return back()->with('error', $friendly);
        }
    }

    private function normalizePhone(string $telephone): string
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
}

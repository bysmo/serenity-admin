<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\Paiement;
use App\Services\PayDunyaCallbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Épargne libre : versement direct sur le compte Épargne personnel d'un membre.
 * Distinct des plans de tontine (EpargneSouscription) — ici le membre verse librement
 * sur son compte Épargne de type 'epargne' via PayDunya ou Pi-SPI.
 */
class MembreEpargneLibreController extends Controller
{
    /**
     * Page d'accueil — solde + historique + formulaire de versement.
     */
    public function index(Request $request)
    {
        $membre       = Auth::guard('membre')->user();
        $compteEpargne = $membre->compteEpargne;

        if (!$compteEpargne) {
            return redirect()->route('membre.dashboard')
                ->with('error', 'Aucun compte épargne trouvé. Contactez l\'administration.');
        }

        // Historique des mouvements d'entrée sur ce compte
        $mouvements = MouvementCaisse::where('caisse_id', $compteEpargne->id)
            ->orderBy('date_operation', 'desc')
            ->paginate(20);

        $solde = $compteEpargne->solde_actuel;

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;

        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;

        $paymentStatus  = session('payment_status');
        $paymentMessage = session('payment_message');

        $comptesExternes = $membre->comptesExternes()->orderByDesc('is_default')->get();

        return view('membres.epargne-libre.index', compact(
            'membre', 'compteEpargne', 'solde', 'mouvements',
            'paydunyaEnabled', 'pispiEnabled',
            'paymentStatus', 'paymentMessage', 'comptesExternes'
        ));
    }

    /**
     * Initier un versement libre via PayDunya (checkout Mobile Money → caisse admin → MouvementCaisse).
     */
    public function initierVersementPayDunya(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        $request->validate([
            'montant' => 'required|numeric|min:100|max:10000000',
        ], [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min'      => 'Le montant minimum est 100 XOF.',
            'montant.numeric'  => 'Le montant doit être un nombre.',
        ]);

        $montant       = (float) $request->input('montant');
        $compteEpargne = $membre->compteEpargne;

        if (!$compteEpargne) {
            return back()->with('error', 'Aucun compte épargne trouvé.');
        }

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return back()->with('error', 'Le paiement PayDunya n\'est pas disponible.');
        }

        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            $callbackUrl     = url('/membre/paydunya/callback');
            $returnUrl       = route('membre.epargne-libre.retour');
            $cancelUrl       = route('membre.epargne-libre.index');

            $result = $paydunyaService->createInvoice([
                'type'      => 'epargne_libre',
                'membre_id' => $membre->id,
                'caisse_id' => $compteEpargne->id,
                'item_name' => 'Épargne libre - ' . $membre->nom_complet,
                'amount'    => $montant,
                'description' => 'Versement libre sur compte épargne personnel',
                'callback_url' => $callbackUrl,
                'return_url'   => $returnUrl . '?token={token}',
                'cancel_url'   => $cancelUrl,
            ]);

            if ($result['success']) {
                return redirect($result['invoice_url']);
            }

            return back()->with('error', $result['message'] ?? 'Erreur lors de la création du paiement.');

        } catch (\Exception $e) {
            Log::error('PayDunya épargne libre: ' . $e->getMessage());
            $friendly = app(\App\Services\PayDunyaService::class)->getFriendlyErrorMessage($e);
            return back()->with('error', $friendly);
        }
    }

    /**
     * Initier un versement libre via Pi-SPI.
     */
    public function initierVersementPiSpi(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        $request->validate([
            'montant'           => 'required|numeric|min:100',
            'compte_externe_id' => 'required|exists:membre_comptes_externes,id',
        ]);

        $compteExterne = \App\Models\CompteExterne::findOrFail($request->compte_externe_id);
        if ($compteExterne->membre_id !== $membre->id) abort(403);

        if (!$compteExterne->supportePiSpi()) {
            return back()->with('error', 'Ce compte externe (IBAN) ne supporte pas les paiements Pi-SPI.');
        }

        $montant       = (float) $request->input('montant');
        $compteEpargne = $membre->compteEpargne;

        if (!$compteEpargne) {
            return back()->with('error', 'Aucun compte épargne trouvé.');
        }

        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        if (!$pispiConfig || !$pispiConfig->enabled) {
            return back()->with('error', 'Le paiement Pi-SPI n\'est pas activé.');
        }

        try {
            $pispiService = app(\App\Services\PiSpiService::class);
            $payeAlias    = \App\Models\PiSpiOperationAlias::getForType('tontine');
            
            $reference = 'EL-PISPI-' . time() . '-' . $membre->id;

            // Enregistrer le paiement en attente
            $paiement = Paiement::create([
                'reference'         => $reference,
                'membre_id'         => $membre->id,
                'compte_externe_id' => $compteExterne->id,
                'montant'           => $montant,
                'date_paiement'     => now(),
                'statut'            => 'en_attente',
                'mode_paiement'     => 'pispi',
                'caisse_id'         => $compteEpargne->id,
                'metadata'          => [
                    'type'      => 'epargne_libre',
                    'caisse_id' => $compteEpargne->id
                ],
                'commentaire' => 'Versement libre épargne via Pi-SPI',
            ]);

            $result = $pispiService->initiatePayment([
                'txId'        => $reference,
                'payeurAlias' => $compteExterne->getPayeurAliasForPiSpi(),
                'payeAlias'   => $payeAlias,
                'amount'      => $montant,
                'description' => 'Épargne libre - ' . $membre->nom_complet,
            ]);

            if ($result['success']) {
                return back()->with('success', 'Demande Pi-SPI envoyée vers "' . $compteExterne->nom . '". Validez sur votre mobile.');
            }

            // Nettoyage si erreur
            $paiement->delete();
            return back()->with('error', 'Erreur Pi-SPI : ' . ($result['message'] ?? 'Echec initiation.'));

        } catch (\Exception $e) {
            Log::error('Pi-SPI épargne libre error: ' . $e->getMessage());
            $friendly = app(\App\Services\PiSpiService::class)->getFriendlyErrorMessage($e);
            return back()->with('error', $friendly);
        }
    }

    /**
     * Retour PayDunya après paiement (?token=...).
     * Vérifie et enregistre le versement si succès.
     */
    public function retour(Request $request)
    {
        $membre        = Auth::guard('membre')->user();
        $compteEpargne = $membre->compteEpargne;

        $paymentStatus  = 'info';
        $paymentMessage = 'Traitement en cours...';

        if ($request->has('token')) {
            $invoiceToken = $request->input('token');
            try {
                $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
                if ($paydunyaConfig && $paydunyaConfig->enabled) {
                    $paydunyaService = new \App\Services\PayDunyaService();
                    $verification    = $paydunyaService->verifyInvoice($invoiceToken);

                    if ($verification['success']) {
                        $status = $verification['status'] ?? 'unknown';
                        if ($status === 'completed') {
                            $verificationData = $verification['data'] ?? [];
                            $customData       = $verificationData['custom_data'] ?? [];
                            $amount           = (float) ($verificationData['total_amount'] ?? 0);

                            // Dispatch (idempotent)
                            app(PayDunyaCallbackService::class)->handle($customData, $amount, $invoiceToken);
                            $paymentStatus  = 'success';
                            $paymentMessage = 'Versement de ' . number_format($amount, 0, ',', ' ') . ' XOF enregistré avec succès.';
                        } elseif ($status === 'cancelled') {
                            $paymentStatus  = 'cancelled';
                            $paymentMessage = 'Paiement annulé.';
                        } else {
                            $paymentStatus  = 'pending';
                            $paymentMessage = 'Paiement en attente de confirmation.';
                        }
                    } else {
                        $paymentStatus  = 'error';
                        $paymentMessage = $verification['message'] ?? 'Erreur lors de la vérification.';
                    }
                }
            } catch (\Exception $e) {
                Log::error('PayDunya épargne libre retour: ' . $e->getMessage());
                $paymentStatus  = 'error';
                $paymentMessage = 'Erreur lors de la vérification du paiement.';
            }
        }

        return redirect()->route('membre.epargne-libre.index')
            ->with('payment_status', $paymentStatus)
            ->with('payment_message', $paymentMessage);
    }
}

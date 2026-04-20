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

        return view('membres.epargne-libre.index', compact(
            'membre', 'compteEpargne', 'solde', 'mouvements',
            'paydunyaEnabled', 'pispiEnabled',
            'paymentStatus', 'paymentMessage'
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
            'montant' => 'required|numeric|min:100',
        ]);

        $montant       = (float) $request->input('montant');
        $compteEpargne = $membre->compteEpargne;

        if (!$compteEpargne) {
            return back()->with('error', 'Aucun compte épargne trouvé.');
        }

        if (!$membre->telephone) {
            return back()->with('error', 'Téléphone requis pour Pi-SPI.');
        }

        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        if (!$pispiConfig || !$pispiConfig->enabled) {
            return back()->with('error', 'Le paiement Pi-SPI n\'est pas activé.');
        }

        try {
            $pispiService = new \App\Services\PiSpiService();
            $reference    = 'EL-PISPI-' . time() . '-' . $membre->id;

            $result = $pispiService->initiatePayment([
                'txId'        => $reference,
                'phone'       => $membre->telephone,
                'amount'      => $montant,
                'description' => 'Épargne libre - ' . $membre->nom_complet,
            ]);

            if ($result['success']) {
                // Le webhook Pi-SPI traitera la confirmation
                return back()->with('success', 'Demande Pi-SPI envoyée. Validez sur votre mobile.');
            }

            return back()->with('error', 'Erreur Pi-SPI : ' . ($result['message'] ?? 'Echec initiation.'));

        } catch (\Exception $e) {
            Log::error('Pi-SPI épargne libre: ' . $e->getMessage());
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

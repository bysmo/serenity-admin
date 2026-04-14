<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PiSpiConfiguration;
use App\Models\Paiement;
use App\Models\EpargneEcheance;
use App\Models\Cotisation;
use App\Models\Engagement;
use App\Models\Membre;

class PiSpiWebhookController extends Controller
{
    /**
     * Gérer le callback (Webhook) de Pi-SPI
     */
    public function handle(Request $request)
    {
        Log::info('Pi-SPI Webhook Received', $request->all());

        $config = PiSpiConfiguration::getActive();
        if (!$config) {
            return response()->json(['message' => 'Config not found'], 404);
        }

        // Vérification de la signature (X-Signature)
        // Note: La spec exacte de Pi-SPI utilise HMAC-SHA256 avec le webhook_secret
        $signature = $request->header('X-Signature');
        if (!$this->verifySignature($request->getContent(), $signature, $config->webhook_secret)) {
            Log::warning('Pi-SPI Webhook Signature Invalid');
            // En sandbox, on pourrait être plus tolérant, mais en prod c'est critique
            if ($config->mode === 'live') {
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        $txId = $request->input('txId');
        $statut = $request->input('statut'); // SUCCES, ECHEC, EXPIRE

        if ($statut === 'SUCCES') {
            $this->processSuccessPayment($txId, $request->all());
        }

        return response()->json(['message' => 'Webhook processed']);
    }

    /**
     * Vérifier la signature HMAC
     */
    private function verifySignature($payload, $signature, $secret)
    {
        if (!$signature || !$secret) return false;
        
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }

    /**
     * Traiter un paiement réussi
     */
    private function processSuccessPayment($txId, $data)
    {
        // Le format de txId dans notre application est souvent : P-timestamp-id
        // Ou on peut chercher dans la table des paiements
        $paiement = Paiement::where('reference', $txId)->first();

        if ($paiement) {
            if ($paiement->statut === 'valide') return; // Déjà traité

            $paiement->update([
                'statut' => 'valide',
                'date_paiement' => now(),
                'commentaire' => ($paiement->commentaire . "\n[Pi-SPI OK: " . ($data['txId'] ?? '') . "]")
            ]);

            Log::info("Pi-SPI Payment Validated: {$txId}");
            
            // Si c'est un paiement d'échéance tontine
            if (isset($paiement->metadata['echeance_id'])) {
                $echeance = EpargneEcheance::find($paiement->metadata['echeance_id']);
                if ($echeance) {
                    $echeance->update(['statut' => 'payee']);
                    Log::info("Tontine Echeance #{$echeance->id} marked as PAID via Pi-SPI");
                }
            }
        } else {
            Log::warning("Pi-SPI Webhook: No record found for txId {$txId}");
        }
    }
}

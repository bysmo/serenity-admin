<?php

namespace App\Services;

use App\Models\PiSpiConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use App\Traits\HandlesFriendlyErrors;

class PiSpiService
{
    use HandlesFriendlyErrors;

    protected $config;
    protected $baseUrl;
    protected $authUrl;

    public function __construct()
    {
        $this->config = PiSpiConfiguration::getActive();
        
        if (!$this->config || !$this->config->enabled) {
            throw new \Exception('Pi-SPI n\'est pas configuré ou activé.');
        }

        // Configuration des URLs selon le mode
        if ($this->config->mode === 'live') {
            $this->baseUrl = 'https://api.pi-bceao.com/piz/v1';
            $this->authUrl = 'https://piz-business.auth.eu-west-1.amazoncognito.com/oauth2/token';
        } else {
            // URL du simulateur Sandbox (no-MTLS)
            // Note: Le playground montre souvent une URL de type .../{participantCode}/demandes-paiements
            $participantCode = $this->config->participant_code ?? 'BFC999';
            $this->baseUrl = 'https://no-mtls.piz.simulateurs.pi-bceao.com/' . $participantCode;
            $this->authUrl = 'https://piz-simulateur-business-sandbox.auth.eu-west-1.amazoncognito.com/oauth2/token';
        }
    }

    /**
     * Obtenir le token d'accès OAuth2 (avec mise en cache)
     */
    public function getAccessToken()
    {
        $cacheKey = ($this->config->token_cache_key ?? 'pispi_access_token') . '_v9';

        return Cache::remember($cacheKey, 3500, function () {
            $response = Http::asForm()->post($this->authUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config->client_id,
                'client_secret' => $this->config->client_secret,
                'scope' => 'piz/compte.read piz/alias.read piz/demande_paiement.write piz/demande_paiement.read piz/demande_paiement_reponse.write piz/webhook.write piz/webhook.read piz/paiement.write piz/paiement.read piz/compte_transaction.write',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'];
            }

            Log::error('Pi-SPI Auth Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $this->authUrl
            ]);
            
            throw new \Exception('Échec de l\'authentification Pi-SPI : ' . ($response->json()['error_description'] ?? 'Erreur inconnue'));
        });
    }

    /**
     * Initier une demande de paiement (Request to Pay - RTP)
     * 
     * @param array $data ['txId', 'payeurAlias', 'payeAlias', 'amount', 'description']
     * @return array
     */
    public function initiatePayment(array $data)
    {
        try {
            $token = $this->getAccessToken();
            
            // On s'assure d'avoir un txId unique et court (max 16-20 caractères recommandés par certaines banques)
            $txId = substr((string)$data['txId'], 0, 16);

            $payload = [
                'txId' => $txId,
                'confirmation' => false,
                'payeurAlias' => $data['payeurAlias'],
                'payeAlias' => $data['payeAlias'] ?? ($this->config->paye_alias ?? '9b1b2499-3e50-435b-b757-ac7a83d8aa8c'),
                'montant' => (int) $data['amount'],
                'motif' => substr($data['description'] ?? 'Paiement Serenity', 0, 100),
                'categorie' => '500', // Format string selon le playground
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'X-API-Key' => $this->config->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)->post($this->baseUrl . '/demandes-paiements', $payload);

            if ($response->successful()) {
                Log::info('Pi-SPI Payment Initiated', ['txId' => $txId]);
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Pi-SPI Payment Request Failed', [
                'txId' => $txId,
                'url' => $this->baseUrl . '/demandes-paiements',
                'payload' => $payload,
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Erreur lors de l\'initiation du paiement Pi-SPI.',
            ];

        } catch (\Exception $e) {
            Log::error('Pi-SPI Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->getFriendlyErrorMessage($e),
            ];
        }
    }

    /**
     * Vérifier le statut d'une demande de paiement
     */
    public function checkPaymentStatus($txId)
    {
        try {
            $token = $this->getAccessToken();

            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'X-API-Key' => $this->config->api_key,
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)->get($this->baseUrl . '/demandes-paiements/' . $txId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json()['statut'] ?? 'UNKNOWN',
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer le statut du paiement.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->getFriendlyErrorMessage($e),
            ];
        }
    }

    /**
     * Enregistrer l'URL du webhook auprès de Pi-SPI
     */
    public function registerWebhook($callbackUrl)
    {
        try {
            $token = $this->getAccessToken();

            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'X-API-Key' => $this->config->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $payload = [
                'callbackUrl' => $callbackUrl
            ];

            $response = Http::withHeaders($headers)->post($this->baseUrl . '/webhooks', $payload);

            if ($response->successful()) {
                Log::info('Pi-SPI Webhook Registered', ['url' => $callbackUrl]);
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Pi-SPI Webhook Registration Failed', [
                'url' => $this->baseUrl . '/webhooks',
                'payload' => $payload,
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Échec de l\'enregistrement du webhook.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->getFriendlyErrorMessage($e),
            ];
        }
    }

    /**
     * Envoyer un paiement à un membre (Disbursement / B2P)
     * Utilisation : gains de parrainage, déblocage nano-crédits, retraits
     */
    public function sendPayment($txId, $recipientAlias, $amount, $operationType = 'generique')
    {
        try {
            $token = $this->getAccessToken();
            
            // Récupérer l'alias payeur (Serenity) pour ce type d'opération
            $payeurAlias = \App\Models\PiSpiOperationAlias::getForType($operationType);
            
            if (!$payeurAlias) {
                // Fallback sur l'alias par défaut de la config globale si spécifique non trouvé
                $payeurAlias = $this->config->paye_alias;
            }

            if (!$payeurAlias) {
                throw new \Exception("Aucun alias émetteur configuré pour le type d'opération : {$operationType}");
            }

            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'x-api-key' => $this->config->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            // On s'assure que le txId est numérique
            $numericTxId = preg_replace('/\D/', '', (string)$txId);
            if (empty($numericTxId)) {
                $numericTxId = (string)time();
            }

            $payload = [
                'txId' => substr($numericTxId, 0, 16),
                'payeurAlias' => $payeurAlias,
                'payeAlias' => $recipientAlias, // Le membre
                'montant' => (int)$amount,
                'confirmation' => false, // Paiement immédiat (B2P)
            ];

            Log::info('Pi-SPI Outbound Payment Initiated', [
                'txId' => $txId,
                'numericTxId' => $numericTxId,
                'payeur' => $payeurAlias,
                'paye' => $recipientAlias,
                'amount' => $amount
            ]);

            $response = Http::withHeaders($headers)->post($this->baseUrl . '/paiements-envoyes', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json()['statut'] ?? 'ENVOYE',
                    'data' => $response->json(),
                ];
            }

            Log::error('Pi-SPI Outbound Payment Failed', [
                'url' => $this->baseUrl . '/paiements-envoyes',
                'payload' => $payload,
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            $errorData = $response->json();
            $errorMessage = $errorData['detail'] ?? $errorData['message'] ?? $errorData['title'] ?? 'Échec du transfert de fonds.';

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $response->status(),
                'data' => $errorData
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->getFriendlyErrorMessage($e),
            ];
        }
    }

    /**
     * Valide la signature d'un webhook Pi-SPI
     */
    public function verifyWebhookSignature($payload, $signature)
    {
        $secret = $this->config->webhook_secret;
        if (empty($secret)) {
            Log::warning('PiSpi: Webhook signature verification skipped — no webhook_secret configured');
            return false; // Refuser les webhooks si le secret n'est pas configuré
        }

        $computed = hash_hmac('sha256', json_encode($payload), $secret);
        return hash_equals($computed, $signature);
    }

    /**
     * Formate le numéro de téléphone pour Pi-SPI (Alias) - Obsolète pour UUID
     */
    private function formatPhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}

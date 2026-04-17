<?php

namespace App\Services;

use App\Models\PiSpiConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PiSpiService
{
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
            $this->baseUrl = 'https://api.pi-bceao.com/piz/v1'; // À confirmer pour la prod
            $this->authUrl = 'https://piz-business.auth.eu-west-1.amazoncognito.com/oauth2/token';
        } else {
            // Utilisation du endpoint No-MTLS pour le sandbox pour faciliter l'intégration
            $this->baseUrl = 'https://no-mtls.piz.simulateurs.pi-bceao.com/piz/v1';
            $this->authUrl = 'https://piz-simulateur-business-sandbox.auth.eu-west-1.amazoncognito.com/oauth2/token';
        }
    }

    /**
     * Obtenir le token d'accès OAuth2 (avec mise en cache)
     */
    public function getAccessToken()
    {
        $cacheKey = $this->config->token_cache_key ?? 'pispi_access_token';

        return Cache::remember($cacheKey, 3500, function () {
            $response = Http::asForm()->post($this->authUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config->client_id,
                'client_secret' => $this->config->client_secret,
                'scope' => 'piz/demande_paiement.write piz/demande_paiement.read',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'];
            }

            Log::error('Pi-SPI Auth Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Échec de l\'authentification Pi-SPI : ' . ($response->json()['error_description'] ?? 'Erreur inconnue'));
        });
    }

    /**
     * Initier une demande de paiement (Request to Pay - RTP)
     * 
     * @param array $data ['txId', 'phone', 'amount', 'description']
     * @return array
     */
    public function initiatePayment(array $data)
    {
        try {
            $token = $this->getAccessToken();
            
            $payload = [
                'txId' => (string) $data['txId'],
                'confirmation' => false,
                'categorie' => '500', // Transaction standard Business to Customer
                'payeurAlias' => $this->formatPhone($data['phone']),
                'payeAlias' => $this->config->paye_alias ?? 'SERENITY_BIZ',
                'montant' => (int) $data['amount'],
                'motif' => substr($data['description'] ?? 'Paiement Serenity', 0, 100),
            ];

            $response = Http::withHeaders([
                'Authorization' => $token, // Suppression de 'Bearer ' car PI-SPI/AWS sandbox semble mal le parser
                'x-api-key' => $this->config->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/demandes-paiements', $payload);

            if ($response->successful()) {
                Log::info('Pi-SPI Payment Initiated', ['txId' => $data['txId']]);
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Pi-SPI Payment Request Failed', [
                'txId' => $data['txId'],
                'url' => $this->baseUrl . '/demandes-paiements',
                'headers_subset' => [
                    'Authorization' => substr($token, 0, 10) . '...',
                    'x-api-key' => substr($this->config->api_key, 0, 5) . '...'
                ],
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
                'message' => $e->getMessage(),
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

            $response = Http::withHeaders([
                'Authorization' => $token, // Suppression de 'Bearer '
                'x-api-key' => $this->config->api_key,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/demandes-paiements/' . $txId);

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
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Formate le numéro de téléphone pour Pi-SPI (Alias)
     * Habituellement au format international sans le '+' ? Ou format mobile money local.
     */
    private function formatPhone($phone)
    {
        // Nettoyer le numéro
        $clean = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro commence par +226..., on garde l'indicatif mais sans le +
        // Pi-SPI sandbox attend souvent un numéro spécifique pour simuler.
        return $clean;
    }
}

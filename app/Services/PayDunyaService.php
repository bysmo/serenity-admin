<?php

namespace App\Services;

use App\Models\PayDunyaConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Traits\HandlesFriendlyErrors;

class PayDunyaService
{
    use HandlesFriendlyErrors;

    protected $config;

    public function __construct()
    {
        $this->config = PayDunyaConfiguration::getActive();
        
        if (!$this->config || !$this->config->enabled) {
            throw new \Exception('PayDunya n\'est pas configuré ou activé.');
        }

        // Configuration de l'API PayDunya selon la documentation
        \Paydunya\Setup::setMasterKey($this->config->master_key);
        \Paydunya\Setup::setPublicKey($this->config->public_key);
        \Paydunya\Setup::setPrivateKey($this->config->private_key);
        \Paydunya\Setup::setToken($this->config->token);
        \Paydunya\Setup::setMode($this->config->mode); // 'test' ou 'live'

        // Configuration des informations du store
        \Paydunya\Checkout\Store::setName(config('app.name', 'Serenity'));
        \Paydunya\Checkout\Store::setTagline('Gestion de la serenité financiere');
        \Paydunya\Checkout\Store::setWebsiteUrl(config('app.url'));
        
        // Configuration globale de l'URL de callback IPN si définie
        // Si non définie, on utilisera l'URL par défaut lors de la création de la facture
        if ($this->config->ipn_url) {
            \Paydunya\Checkout\Store::setCallbackUrl($this->config->ipn_url);
        } else {
            // URL par défaut si non configurée
            $defaultCallbackUrl = config('app.url') . '/membre/paydunya/callback';
            \Paydunya\Checkout\Store::setCallbackUrl($defaultCallbackUrl);
        }
    }

    /**
     * Créer une facture PayDunya
     */
    public function createInvoice($data)
    {
        try {
            // Créer une nouvelle instance de facture
            $invoice = new \Paydunya\Checkout\CheckoutInvoice();

            // Ajouter l'article à la facture
            // Paramètres: nom, quantité, prix unitaire, prix total, description (optionnelle)
            $invoice->addItem(
                $data['item_name'],
                1,
                $data['amount'],
                $data['amount'],
                $data['description'] ?? ''
            );

            // Définir le montant total de la facture (obligatoire)
            $invoice->setTotalAmount($data['amount']);

            // Définir la description de la facture
            if (isset($data['description'])) {
                $invoice->setDescription($data['description']);
            }

            // Ajouter des données personnalisées (selon le type de paiement)
            if (isset($data['membre_id'])) {
                $invoice->addCustomData('membre_id', $data['membre_id']);
            }
            if (isset($data['cotisation_id'])) {
                $invoice->addCustomData('cotisation_id', $data['cotisation_id']);
            }
            if (isset($data['engagement_id'])) {
                $invoice->addCustomData('engagement_id', $data['engagement_id']);
            }
            if (isset($data['type'])) {
                $invoice->addCustomData('type', $data['type']);
            }
            if (isset($data['souscription_id'])) {
                $invoice->addCustomData('souscription_id', $data['souscription_id']);
            }
            if (isset($data['echeance_id'])) {
                $invoice->addCustomData('echeance_id', $data['echeance_id']);
            }

            // Configurer les URLs de redirection
            if (isset($data['cancel_url'])) {
                $invoice->setCancelUrl($data['cancel_url']);
            }
            if (isset($data['return_url'])) {
                $invoice->setReturnUrl($data['return_url']);
            }
            if (isset($data['callback_url'])) {
                $invoice->setCallbackUrl($data['callback_url']);
            }

            // Créer la facture sur les serveurs PayDunya
            if ($invoice->create()) {
                $invoiceUrl = $invoice->getInvoiceUrl();
                
                Log::info('PayDunya: Facture créée avec succès', [
                    'invoice_url' => $invoiceUrl,
                ]);

                return [
                    'success' => true,
                    'invoice_url' => $invoiceUrl,
                ];
            } else {
                Log::error('PayDunya: Erreur lors de la création de la facture', [
                    'response_text' => $invoice->response_text ?? 'Erreur inconnue',
                    'response_code' => $invoice->response_code ?? 'N/A',
                ]);

                return [
                    'success' => false,
                    'message' => $invoice->response_text ?? 'Erreur lors de la création de la facture',
                ];
            }
        } catch (\Exception $e) {
            Log::error('PayDunya: Exception lors de la création de la facture', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => $this->getFriendlyErrorMessage($e),
            ];
        }
    }

    /**
     * Vérifier le statut d'une facture
     */
    public function verifyInvoice($invoiceToken)
    {
        try {
            $invoice = new \Paydunya\Checkout\CheckoutInvoice();
            
            if ($invoice->confirm($invoiceToken)) {
                return [
                    'success' => true,
                    'status' => $invoice->getStatus(),
                    'data' => [
                        'status' => $invoice->getStatus(),
                        'total_amount' => $invoice->getTotalAmount(),
                        'customer' => [
                            'name' => $invoice->getCustomerInfo('name'),
                            'email' => $invoice->getCustomerInfo('email'),
                            'phone' => $invoice->getCustomerInfo('phone'),
                        ],
                        'custom_data' => [
                            'cotisation_id' => $this->getCustomDataSafe($invoice, 'cotisation_id'),
                            'membre_id' => $this->getCustomDataSafe($invoice, 'membre_id'),
                            'type' => $this->getCustomDataSafe($invoice, 'type'),
                            'engagement_id' => $this->getCustomDataSafe($invoice, 'engagement_id'),
                            'souscription_id' => $this->getCustomDataSafe($invoice, 'souscription_id'),
                            'echeance_id' => $this->getCustomDataSafe($invoice, 'echeance_id'),
                        ],
                    ],
                ];
            } else {
                Log::error('PayDunya: Erreur lors de la vérification de la facture', [
                    'token' => $invoiceToken,
                    'response_text' => $invoice->response_text ?? 'Erreur inconnue',
                    'response_code' => $invoice->response_code ?? 'N/A',
                ]);

                return [
                    'success' => false,
                    'message' => $invoice->response_text ?? 'Impossible de vérifier la facture',
                ];
            }
        } catch (\Exception $e) {
            Log::error('PayDunya: Exception lors de la vérification de la facture', [
                'error' => $e->getMessage(),
                'token' => $invoiceToken,
            ]);
            return [
                'success' => false,
                'message' => $this->getFriendlyErrorMessage($e),
            ];
        }
    }

    /**
     * Récupérer une custom_data sans erreur si la clé est absente (ex: facture épargne sans cotisation_id).
     */
    private function getCustomDataSafe($invoice, string $key)
    {
        try {
            $value = $invoice->getCustomData($key);
            return $value;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * API PUSH / Déboursement : créer une demande de déboursement (get-invoice).
     * account_alias = numéro sans indicatif pays (mobile money) ou identifiant compte PayDunya.
     * amount = montant en XOF (entier).
     * callback_url = URL absolue de callback (obligatoire).
     */
    public function createDisburseInvoice(string $accountAlias, int $amount, string $withdrawMode, string $callbackUrl, ?string $debitAccountNumber = null): array
    {
        $url = 'https://app.paydunya.com/api/v2/disburse/get-invoice';
        $payload = [
            'account_alias' => $accountAlias,
            'amount' => $amount,
            'withdraw_mode' => $withdrawMode,
            'callback_url' => $callbackUrl,
        ];
        if ($withdrawMode === 'paydunya' && $debitAccountNumber !== null) {
            $payload['debit_account_number'] = $debitAccountNumber;
        }

        $response = Http::withOptions($this->getPayDunyaHttpOptions())
            ->withHeaders($this->getDisburseHeaders())
            ->post($url, $payload);

        $body = $response->json();
        $code = $body['response_code'] ?? null;

        if ($response->successful() && $code === '00') {
            Log::info('PayDunya Disburse: get-invoice OK', ['disburse_token' => $body['disburse_token'] ?? null]);
            return [
                'success' => true,
                'disburse_token' => $body['disburse_token'] ?? null,
            ];
        }

        Log::warning('PayDunya Disburse: get-invoice échec', [
            'response_code' => $code,
            'response_text' => $body['response_text'] ?? $response->body(),
        ]);
        return [
            'success' => false,
            'message' => $body['response_text'] ?? 'Erreur lors de la création du déboursement',
            'response_code' => $code,
        ];
    }

    /**
     * API PUSH : soumettre le déboursement (submit-invoice).
     */
    public function submitDisburseInvoice(string $disburseToken, ?string $disburseId = null): array
    {
        $url = 'https://app.paydunya.com/api/v2/disburse/submit-invoice';
        $payload = ['disburse_invoice' => $disburseToken];
        if ($disburseId !== null) {
            $payload['disburse_id'] = $disburseId;
        }

        $response = Http::withOptions($this->getPayDunyaHttpOptions())
            ->withHeaders($this->getDisburseHeaders())
            ->post($url, $payload);

        $body = $response->json();
        $code = $body['response_code'] ?? null;

        if ($response->successful() && $code === '00') {
            $status = $body['status'] ?? 'pending';
            Log::info('PayDunya Disburse: submit-invoice OK', [
                'status' => $status,
                'transaction_id' => $body['transaction_id'] ?? null,
            ]);
            return [
                'success' => true,
                'status' => $status,
                'transaction_id' => $body['transaction_id'] ?? null,
                'response_text' => $body['response_text'] ?? null,
                'description' => $body['description'] ?? null,
                'provider_ref' => $body['provider_ref'] ?? null,
            ];
        }

        Log::warning('PayDunya Disburse: submit-invoice échec', [
            'response_code' => $code,
            'response_text' => $body['response_text'] ?? $response->body(),
        ]);
        return [
            'success' => false,
            'message' => $body['response_text'] ?? 'Erreur lors de la soumission du déboursement',
            'response_code' => $code,
        ];
    }

    /**
     * API PUSH : vérifier le statut d'un déboursement (check-status).
     */
    public function checkDisburseStatus(string $disburseToken): array
    {
        $url = 'https://app.paydunya.com/api/v2/disburse/check-status';
        $response = Http::withOptions($this->getPayDunyaHttpOptions())
            ->withHeaders($this->getDisburseHeaders())
            ->post($url, ['disburse_invoice' => $disburseToken]);

        $body = $response->json();
        $code = $body['response_code'] ?? null;

        if ($response->successful() && $code === '00') {
            return [
                'success' => true,
                'status' => $body['status'] ?? null,
                'transaction_id' => $body['transaction_id'] ?? null,
                'amount' => $body['amount'] ?? null,
                'updated_at' => $body['updated_at'] ?? null,
            ];
        }

        return [
            'success' => false,
            'message' => $body['response_text'] ?? 'Impossible de vérifier le statut',
        ];
    }

    /**
     * En-têtes pour les appels API Disburse (PUSH).
     * PAYDUNYA-MODE est requis : sans lui, l'API considère la requête en LIVE et rejette les clés test.
     * En local/staging ou si PAYDUNYA_MODE_OVERRIDE=test, on force 'test' pour accepter les clés test.
     */
    private function getDisburseHeaders(): array
    {
        $modeOverride = env('PAYDUNYA_MODE_OVERRIDE');
        if ($modeOverride === 'test' || $modeOverride === 'live') {
            $mode = $modeOverride;
        } elseif (! app()->environment('production')) {
            $mode = 'test';
        } else {
            $mode = $this->config->mode ?? 'test';
        }
        return [
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => $this->config->master_key,
            'PAYDUNYA-PRIVATE-KEY' => $this->config->private_key,
            'PAYDUNYA-TOKEN' => $this->config->token,
            'PAYDUNYA-MODE' => $mode,
        ];
    }

    /**
     * Options HTTP pour les appels API PayDunya (Disburse).
     * En dehors de la production : désactive la vérification SSL (évite cURL 60 si CA bundle manquant).
     * En production : vérification SSL activée. Override possible via PAYDUNYA_SSL_VERIFY dans .env.
     */
    private function getPayDunyaHttpOptions(): array
    {
        $envVal = env('PAYDUNYA_SSL_VERIFY');
        if ($envVal !== null && $envVal !== '') {
            $verify = filter_var($envVal, FILTER_VALIDATE_BOOLEAN);
        } else {
            $verify = app()->environment('production');
        }
        return ['verify' => $verify];
    }
}

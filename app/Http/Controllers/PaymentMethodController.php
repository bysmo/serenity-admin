<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Afficher la liste des moyens de paiement
     */
    public function index()
    {
        // Initialiser les moyens de paiement s'ils n'existent pas
        $this->initializePaymentMethods();
        
        // Synchroniser PayDunya avec la configuration existante (seulement si pas déjà synchronisé)
        $this->syncPayDunya();
        $this->syncPiSpi();
        
        $paymentMethods = PaymentMethod::orderBy('order')
            ->orderBy('name')
            ->get();
        
        return view('payment-methods.index', compact('paymentMethods'));
    }

    /**
     * Initialiser les moyens de paiement s'ils n'existent pas
     */
    private function initializePaymentMethods()
    {
        $paymentMethods = [
            [
                'name' => 'PayDunya',
                'code' => 'paydunya',
                'icon' => 'bi bi-phone',
                'description' => 'Paiement mobile money pour l\'Afrique de l\'Ouest',
                'order' => 1,
            ],
            [
                'name' => 'PayPal',
                'code' => 'paypal',
                'icon' => 'bi bi-paypal',
                'description' => 'Paiement en ligne international via PayPal',
                'order' => 2,
            ],
            [
                'name' => 'Stripe',
                'code' => 'stripe',
                'icon' => 'bi bi-credit-card-2-front',
                'description' => 'Paiement par carte bancaire via Stripe',
                'order' => 3,
            ],
            [
                'name' => 'BCEAO Pi-SPI',
                'code' => 'pispi',
                'icon' => 'bi bi-bank',
                'description' => 'Instantané interopérable (BCEAO)',
                'order' => 0,
            ],
        ];

        foreach ($paymentMethods as $methodData) {
            $paymentMethod = PaymentMethod::where('code', $methodData['code'])->first();
            
            if (!$paymentMethod) {
                // Si le moyen de paiement n'existe pas, créer avec les valeurs par défaut
                PaymentMethod::create([
                    'name' => $methodData['name'],
                    'code' => $methodData['code'],
                    'icon' => $methodData['icon'],
                    'description' => $methodData['description'],
                    'enabled' => false,
                    'config' => null,
                    'order' => $methodData['order'],
                ]);
            } else {
                // Si le moyen de paiement existe, ne mettre à jour que les champs qui peuvent changer
                // Ne PAS toucher à enabled et config pour préserver les valeurs existantes
                $paymentMethod->name = $methodData['name'];
                $paymentMethod->icon = $methodData['icon'];
                $paymentMethod->description = $methodData['description'];
                $paymentMethod->order = $methodData['order'];
                // Explicitement ne pas toucher à enabled et config
                $paymentMethod->save(['timestamps' => true]);
            }
        }
    }

    /**
     * Synchroniser PayDunya avec la configuration existante
     * Ne synchronise que la config (pas enabled) pour éviter d'écraser les changements manuels
     */
    private function syncPayDunya()
    {
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::first();
        if ($paydunyaConfig) {
            $paymentMethod = PaymentMethod::where('code', 'paydunya')->first();
            if ($paymentMethod) {
                // Ne synchroniser que la config (clés API), pas le statut enabled
                // Le statut enabled est géré par le toggle() et la page de configuration PayDunya
                $config = [
                    'master_key' => $paydunyaConfig->master_key,
                    'private_key' => $paydunyaConfig->private_key,
                    'public_key' => $paydunyaConfig->public_key,
                    'token' => $paydunyaConfig->token,
                    'mode' => $paydunyaConfig->mode,
                    'ipn_url' => $paydunyaConfig->ipn_url,
                ];

                // Ne mettre à jour que la config, pas enabled (pour éviter d'écraser les changements)
                $paymentMethod->config = $config;
                $paymentMethod->save();
            }
        }
    }

    /**
     * Initialiser les moyens de paiement (route publique)
     */
    public function initialize()
    {
        $this->initializePaymentMethods();
        $this->syncPayDunya();
        $this->syncPiSpi();
        
        return redirect()->route('payment-methods.index')
            ->with('success', 'Les moyens de paiement ont été initialisés avec succès.');
    }

    /**
     * Activer/Désactiver un moyen de paiement
     */
    public function toggle(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->enabled = !$paymentMethod->enabled;
        $paymentMethod->save();

        // Si c'est PayDunya, synchroniser aussi avec la table paydunya_configurations
        if ($paymentMethod->code === 'paydunya') {
            $paydunyaConfig = \App\Models\PayDunyaConfiguration::first();
            if ($paydunyaConfig) {
                $paydunyaConfig->enabled = $paymentMethod->enabled;
                $paydunyaConfig->save();
            }
        }
        
        // Si c'est Pi-SPI, synchroniser aussi avec la table pispi_configurations
        if ($paymentMethod->code === 'pispi') {
            $pispiConfig = \App\Models\PiSpiConfiguration::first();
            if ($pispiConfig) {
                $pispiConfig->enabled = $paymentMethod->enabled;
                $pispiConfig->save();
            }
        }

        $status = $paymentMethod->enabled ? 'activé' : 'désactivé';
        
        return redirect()->route('payment-methods.index')
            ->with('success', "Le moyen de paiement {$paymentMethod->name} a été {$status} avec succès.");
    }

    /**
     * Synchroniser Pi-SPI avec la configuration existante
     */
    private function syncPiSpi()
    {
        $pispiConfig = \App\Models\PiSpiConfiguration::first();
        if ($pispiConfig) {
            $paymentMethod = PaymentMethod::where('code', 'pispi')->first();
            if ($paymentMethod) {
                $paymentMethod->config = [
                    'client_id' => $pispiConfig->client_id,
                    'client_secret' => $pispiConfig->client_secret,
                    'api_key' => $pispiConfig->api_key,
                    'paye_alias' => $pispiConfig->paye_alias,
                    'mode' => $pispiConfig->mode,
                ];
                $paymentMethod->save();
            }
        }
    }
}

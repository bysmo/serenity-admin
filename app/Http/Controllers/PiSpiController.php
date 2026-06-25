<?php

namespace App\Http\Controllers;

use App\Models\PiSpiConfiguration;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class PiSpiController extends Controller
{
    /**
     * Afficher la page de configuration Pi-SPI
     */
    public function index()
    {
        $config = PiSpiConfiguration::getActive();
        
        if (!$config) {
            $config = new PiSpiConfiguration(['mode' => 'sandbox', 'enabled' => false]);
        }

        $operationAliases = \App\Models\PiSpiOperationAlias::all()->pluck('alias', 'operation_type')->toArray();
        
        return view('pispi.index', compact('config', 'operationAliases'));
    }

    /**
     * Mettre à jour la configuration
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'paye_alias' => 'required|string|max:255',
            'mode' => 'required|in:sandbox,live',
            'operation_aliases' => 'nullable|array',
        ]);

        try {
            $config = PiSpiConfiguration::first();
            
            if (!$config) {
                $config = new PiSpiConfiguration();
            }

            $config->client_id = $validated['client_id'];
            // Chiffrement des clés sensibles au repos
            $config->client_secret = !empty($validated['client_secret']) ? Crypt::encryptString($validated['client_secret']) : null;
            $config->api_key = !empty($validated['api_key']) ? Crypt::encryptString($validated['api_key']) : null;
            $config->paye_alias = $validated['paye_alias'];
            $config->mode = $validated['mode'];
            $config->enabled = $validated['enabled'] ?? false;
            $config->save();

            // Enregistrer les alias opérationnels
            if ($request->has('operation_aliases')) {
                foreach ($request->operation_aliases as $type => $alias) {
                    if (!empty($alias)) {
                        \App\Models\PiSpiOperationAlias::updateOrCreate(
                            ['operation_type' => $type],
                            ['alias' => $alias, 'label' => ucfirst($type)]
                        );
                    }
                }
            }

            // Vider le cache du token (au cas où les credentials ont changé)
            Cache::forget($config->token_cache_key ?? 'pispi_access_token');

            // Synchroniser avec la table payment_methods
            $paymentMethod = PaymentMethod::where('code', 'pispi')->first();
            if (!$paymentMethod) {
                $paymentMethod = new PaymentMethod([
                    'code' => 'pispi',
                    'name' => 'BCEAO Pi-SPI',
                ]);
            }
            $paymentMethod->enabled = $config->enabled;
            // On ne stocke pas tout dans config JSON car on a nos modèles propres maintenant
            $paymentMethod->save();

            return redirect()->route('pispi.index')
                ->with('success', 'Configuration Pi-SPI et alias opérationnels mis à jour.');

        } catch (\Exception $e) {
            Log::error('PiSpi Configuration Update Error: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la sauvegarde : ' . $e->getMessage())
                         ->withInput();
        }
    }

    /**
     * Enregistrer le webhook auprès de Pi-SPI
     */
    public function registerWebhook(Request $request)
    {
        try {
            $pispiService = app(\App\Services\PiSpiService::class);
            
            // L'URL publique de notre webhook
            // Note: Doit être une URL HTTPS accessible depuis l'extérieur
            $callbackUrl = route('api.pispi.webhook');

            $result = $pispiService->registerWebhook($callbackUrl);

            if ($result['success']) {
                return back()->with('success', 'L\'URL du webhook a été enregistrée avec succès auprès de la plateforme Pi-SPI : ' . $callbackUrl);
            }

            return back()->with('error', 'Échec de l\'enregistrement : ' . ($result['message'] ?? 'Erreur inconnue'));

        } catch (\Exception $e) {
            Log::error('Pi-SPI Webhook Register Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}

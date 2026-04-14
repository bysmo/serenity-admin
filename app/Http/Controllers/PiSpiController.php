<?php

namespace App\Http\Controllers;

use App\Models\PiSpiConfiguration;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        
        return view('pispi.index', compact('config'));
    }

    /**
     * Mettre à jour la configuration
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string',
            'api_key' => 'required|string',
            'paye_alias' => 'required|string|max:255',
            'mode' => 'required|in:sandbox,live',
        ]);

        try {
            $config = PiSpiConfiguration::first();
            
            if (!$config) {
                $config = new PiSpiConfiguration();
            }

            $config->fill($validated);
            $config->enabled = $request->has('enabled');
            $config->save();

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
            $paymentMethod->config = $validated;
            $paymentMethod->save();

            return redirect()->route('payment-methods.index')
                ->with('success', 'Configuration Pi-SPI mise à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('PiSpi Configuration Update Error: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la sauvegarde.')
                         ->withInput();
        }
    }
}

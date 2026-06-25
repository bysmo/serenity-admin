<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class StripeController extends Controller
{
    /**
     * Afficher la page de configuration Stripe
     */
    public function index()
    {
        $paymentMethod = PaymentMethod::where('code', 'stripe')->first();
        
        if (!$paymentMethod) {
            return redirect()->route('payment-methods.index')
                ->with('error', 'Stripe n\'est pas configuré. Veuillez d\'abord initialiser les moyens de paiement.');
        }
        
        $config = $paymentMethod->config ?? [];
        
        return view('stripe.index', compact('paymentMethod', 'config'));
    }

    /**
     * Mettre à jour la configuration Stripe
     */
    public function update(Request $request)
    {
        $paymentMethod = PaymentMethod::where('code', 'stripe')->first();
        
        if (!$paymentMethod) {
            return redirect()->route('payment-methods.index')
                ->with('error', 'Stripe n\'est pas configuré.');
        }

        $validated = $request->validate([
            'publishable_key' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string|max:255',
            'mode' => 'required|in:test,live',
            'enabled' => 'nullable|boolean',
        ], [
            'mode.required' => 'Le mode est obligatoire.',
            'mode.in' => 'Le mode doit être "test" ou "live".',
        ]);

        try {
            // Chiffrement des clés sensibles au repos
            $config = [
                'publishable_key' => $validated['publishable_key'] ?? null,
                'secret_key'      => !empty($validated['secret_key']) ? Crypt::encryptString($validated['secret_key']) : null,
                'mode'            => $validated['mode'],
            ];

            $paymentMethod->config = $config;
            // Utiliser la valeur validée (boolean) au lieu du raw input
            $paymentMethod->enabled = $validated['enabled'] ?? false;
            $paymentMethod->save();
            
            \Log::info('Stripe: Configuration mise à jour', [
                'enabled' => $paymentMethod->enabled,
            ]);

            return redirect()->route('payment-methods.index')
                ->with('success', 'Configuration Stripe mise à jour avec succès.');
                
        } catch (\Exception $e) {
            \Log::error('Stripe: Erreur lors de la mise à jour', ['error' => $e->getMessage()]);
            return redirect()->route('stripe.index')
                ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->withInput();
        }
    }
}

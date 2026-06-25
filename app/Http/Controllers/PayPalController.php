<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PayPalController extends Controller
{
    /**
     * Afficher la page de configuration PayPal
     */
    public function index()
    {
        $paymentMethod = PaymentMethod::where('code', 'paypal')->first();
        
        if (!$paymentMethod) {
            return redirect()->route('payment-methods.index')
                ->with('error', 'PayPal n\'est pas configuré. Veuillez d\'abord initialiser les moyens de paiement.');
        }
        
        $config = $paymentMethod->config ?? [];
        
        return view('paypal.index', compact('paymentMethod', 'config'));
    }

    /**
     * Mettre à jour la configuration PayPal
     */
    public function update(Request $request)
    {
        $paymentMethod = PaymentMethod::where('code', 'paypal')->first();
        
        if (!$paymentMethod) {
            return redirect()->route('payment-methods.index')
                ->with('error', 'PayPal n\'est pas configuré.');
        }

        $validated = $request->validate([
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'mode' => 'required|in:sandbox,live',
            'enabled' => 'nullable|boolean',
        ], [
            'mode.required' => 'Le mode est obligatoire.',
            'mode.in' => 'Le mode doit être "sandbox" ou "live".',
        ]);

        try {
            // Chiffrement des clés sensibles au repos
            $config = [
                'client_id'     => $validated['client_id'] ?? null,
                'client_secret' => !empty($validated['client_secret']) ? Crypt::encryptString($validated['client_secret']) : null,
                'mode'          => $validated['mode'],
            ];

            $paymentMethod->config = $config;
            // Utiliser la valeur validée (boolean) au lieu du raw input
            $paymentMethod->enabled = $validated['enabled'] ?? false;
            $paymentMethod->save();
            
            \Log::info('PayPal: Configuration mise à jour', [
                'enabled' => $paymentMethod->enabled,
            ]);

            return redirect()->route('payment-methods.index')
                ->with('success', 'Configuration PayPal mise à jour avec succès.');
                
        } catch (\Exception $e) {
            \Log::error('PayPal: Erreur lors de la mise à jour', ['error' => $e->getMessage()]);
            return redirect()->route('paypal.index')
                ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->withInput();
        }
    }
}

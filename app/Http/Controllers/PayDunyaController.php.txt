<?php

namespace App\Http\Controllers;

use App\Models\PayDunyaConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PayDunyaController extends Controller
{
    /**
     * Afficher la page de configuration PayDunya
     */
    public function index()
    {
        // Récupérer ou créer la configuration (singleton)
        $config = PayDunyaConfiguration::first();
        
        if (!$config) {
            $config = new PayDunyaConfiguration();
        }
        // Les valeurs sont en clair pour l'instant (pas de cryptage)
        
        return view('paydunya.index', compact('config'));
    }

    /**
     * Mettre à jour la configuration PayDunya
     */
    public function update(Request $request)
    {
        // Validation avec messages personnalisés
        $validated = $request->validate([
            'master_key' => 'nullable|string|max:255',
            'private_key' => 'nullable|string',
            'public_key' => 'nullable|string',
            'token' => 'nullable|string',
            'mode' => 'required|in:test,live',
            'ipn_url' => 'nullable|url|max:500',
        ], [
            'mode.required' => 'Le mode est obligatoire.',
            'mode.in' => 'Le mode doit être "test" ou "live".',
            'ipn_url.url' => 'L\'URL IPN doit être une URL valide.',
        ]);

        try {
            // Récupérer ou créer la configuration (singleton)
            $config = PayDunyaConfiguration::first();
            
            if (!$config) {
                $config = new PayDunyaConfiguration();
            }

            // Mettre à jour les valeurs (sans cryptage pour l'instant pour tester)
            $config->master_key = !empty($validated['master_key']) ? $validated['master_key'] : null;
            $config->private_key = !empty($validated['private_key']) ? $validated['private_key'] : null;
            $config->public_key = !empty($validated['public_key']) ? $validated['public_key'] : null;
            $config->token = !empty($validated['token']) ? $validated['token'] : null;
            $config->mode = $validated['mode'];
            $config->ipn_url = $validated['ipn_url'] ?? null;
            // Le champ enabled : si la checkbox est cochée, elle est présente dans la requête
            $config->enabled = $request->has('enabled') && $request->input('enabled') !== null;
            
            // Sauvegarder la configuration
            $saved = $config->save();
            
            if (!$saved) {
                \Log::error('PayDunya: Échec de la sauvegarde', ['config' => $config->toArray()]);
                return redirect()->route('paydunya.index')
                    ->with('error', 'Erreur lors de l\'enregistrement de la configuration.');
            }

            \Log::info('PayDunya: Configuration sauvegardée', ['id' => $config->id, 'enabled' => $config->enabled]);

            // Enregistrer dans le journal d'audit (optionnel, ne bloque pas si ça échoue)
            try {
                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'update',
                        'model' => 'PayDunyaConfiguration',
                        'model_id' => $config->id,
                        'new_values' => array_merge($validated, ['enabled' => $config->enabled]),
                        'description' => 'Configuration PayDunya mise à jour',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('PayDunya: Erreur lors de l\'enregistrement de l\'audit', ['error' => $e->getMessage()]);
            }

            // Synchroniser avec payment_methods
            $paymentMethod = \App\Models\PaymentMethod::where('code', 'paydunya')->first();
            if ($paymentMethod) {
                $paymentMethod->enabled = $config->enabled;
                $paymentMethod->config = [
                    'master_key' => $config->master_key,
                    'private_key' => $config->private_key,
                    'public_key' => $config->public_key,
                    'token' => $config->token,
                    'mode' => $config->mode,
                    'ipn_url' => $config->ipn_url,
                ];
                $paymentMethod->save();
            }

            return redirect()->route('payment-methods.index')
                ->with('success', 'Configuration PayDunya mise à jour avec succès.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('paydunya.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('PayDunya: Erreur lors de la mise à jour', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('paydunya.index')
                ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->withInput();
        }
    }
}

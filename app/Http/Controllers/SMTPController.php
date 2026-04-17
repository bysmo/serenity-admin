<?php

namespace App\Http\Controllers;

use App\Models\SMTPConfiguration;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SMTPController extends Controller
{
    /**
     * Afficher la liste des configurations SMTP
     */
    public function index()
    {
        $smtps = SMTPConfiguration::orderBy('actif', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('smtp.index', compact('smtps'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('smtp.create');
    }

    /**
     * Enregistrer une nouvelle configuration SMTP
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:smtp_configurations,nom',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'encryption' => 'required|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'actif' => 'boolean',
        ]);

        // Si cette configuration est activée, désactiver les autres
        if ($request->has('actif') && $request->actif) {
            SMTPConfiguration::where('actif', true)->update(['actif' => false]);
        }

        // Le mot de passe sera chiffré automatiquement par le cast 'encrypted' du modèle
        $validated['actif'] = $request->has('actif') ? true : false;

        SMTPConfiguration::create($validated);

        return redirect()->route('smtp.index')
            ->with('success', 'Configuration SMTP créée avec succès.');
    }

    /**
     * Afficher les détails d'une configuration
     */
    public function show(SMTPConfiguration $smtp)
    {
        return view('smtp.show', compact('smtp'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(SMTPConfiguration $smtp)
    {
        return view('smtp.edit', compact('smtp'));
    }

    /**
     * Mettre à jour une configuration SMTP
     */
    public function update(Request $request, SMTPConfiguration $smtp)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:smtp_configurations,nom,' . $smtp->id,
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'encryption' => 'required|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'actif' => 'boolean',
        ]);

        // Si cette configuration est activée, désactiver les autres
        if ($request->has('actif') && $request->actif) {
            SMTPConfiguration::where('actif', true)->where('id', '!=', $smtp->id)->update(['actif' => false]);
        }

        // Le mot de passe sera chiffré automatiquement par le cast 'encrypted' du modèle s'il est fourni
        if (!$request->filled('password')) {
            unset($validated['password']);
        }

        $validated['actif'] = $request->has('actif') ? true : false;

        $smtp->update($validated);

        return redirect()->route('smtp.index')
            ->with('success', 'Configuration SMTP mise à jour avec succès.');
    }

    /**
     * Supprimer une configuration SMTP
     */
    public function destroy(SMTPConfiguration $smtp)
    {
        $smtp->delete();

        return redirect()->route('smtp.index')
            ->with('success', 'Configuration SMTP supprimée avec succès.');
    }

    /**
     * Tester la configuration SMTP
     */
    public function test(SMTPConfiguration $smtp)
    {
        try {
            // Activer temporairement cette configuration pour le test
            $oldActive = SMTPConfiguration::where('actif', true)->first();
            if ($oldActive && $oldActive->id !== $smtp->id) {
                $oldActive->update(['actif' => false]);
            }
            $smtp->update(['actif' => true]);

            // Utiliser le service EmailService
            $emailService = new EmailService();
            $emailService->sendTestEmail($smtp->from_address);

            // Restaurer l'état précédent si nécessaire
            if ($oldActive && $oldActive->id !== $smtp->id) {
                $oldActive->update(['actif' => true]);
                $smtp->update(['actif' => false]);
            }

            return redirect()->back()
                ->with('success', 'Email de test envoyé avec succès ! Vérifiez votre boîte de réception.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Erreur lors de l\'envoi de l\'email de test : ' . $e->getMessage()]);
        }
    }
}

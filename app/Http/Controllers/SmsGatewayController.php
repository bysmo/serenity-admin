<?php

namespace App\Http\Controllers;

use App\Models\SmsGateway;
use Illuminate\Http\Request;

class SmsGatewayController extends Controller
{
    /**
     * Liste des passerelles SMS. Initialise les passerelles par défaut si vide.
     */
    public function index()
    {
        $this->initializeGateways();
        $gateways = SmsGateway::orderBy('order')->orderBy('name')->get();
        return view('sms-gateways.index', compact('gateways'));
    }

    /**
     * Créer les passerelles connues si elles n'existent pas.
     */
    private function initializeGateways(): void
    {
        $defaults = [
            ['name' => 'Log (développement)', 'code' => 'log', 'description' => 'Enregistre le code OTP dans les logs Laravel. Aucune configuration.', 'order' => 0],
            ['name' => 'Twilio', 'code' => 'twilio', 'description' => 'Twilio - SMS internationaux (Account SID, Auth Token, numéro expéditeur).', 'order' => 1],
            ['name' => 'Vonage (Nexmo)', 'code' => 'vonage', 'description' => 'Vonage / Nexmo - API SMS (API Key, API Secret, expéditeur).', 'order' => 2],
            ['name' => "Africa's Talking", 'code' => 'africas_talking', 'description' => "Africa's Talking - SMS en Afrique (username, API key, expéditeur).", 'order' => 3],
            ['name' => 'Infobip', 'code' => 'infobip', 'description' => 'Infobip - Plateforme SMS (base URL, API key, expéditeur).', 'order' => 4],
            ['name' => 'MessageBird', 'code' => 'messagebird', 'description' => 'MessageBird - SMS (API key, originator).', 'order' => 5],
        ];

        $hasActive = SmsGateway::where('is_active', true)->exists();
        foreach ($defaults as $data) {
            SmsGateway::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'config' => [],
                    'is_active' => !$hasActive && $data['code'] === 'log',
                    'order' => $data['order'],
                ]
            );
        }
    }

    /**
     * Formulaire de configuration d'une passerelle.
     */
    public function edit(SmsGateway $smsGateway)
    {
        $configFields = SmsGateway::configFields($smsGateway->code);
        return view('sms-gateways.edit', compact('smsGateway', 'configFields'));
    }

    /**
     * Enregistrer la configuration d'une passerelle.
     */
    public function update(Request $request, SmsGateway $smsGateway)
    {
        $configFields = SmsGateway::configFields($smsGateway->code);
        $rules = [];
        foreach ($configFields as $field) {
            if (($field['type'] ?? 'text') === 'password') {
                $rules['config.' . $field['key']] = 'nullable|string|max:500';
            } elseif (!empty($field['required'])) {
                $rules['config.' . $field['key']] = 'required|string|max:500';
            }
        }
        $request->validate($rules);

        $config = $smsGateway->config ?? [];
        foreach ($configFields as $field) {
            $key = $field['key'];
            $value = $request->input('config.' . $key);
            if ($field['type'] === 'password' && $value === '' && isset($config[$key])) {
                continue; // ne pas écraser un mot de passe existant si champ vide
            }
            if ($request->has('config.' . $key)) {
                $config[$key] = $value;
            }
        }
        $smsGateway->config = $config;
        $smsGateway->save();

        return redirect()->route('sms-gateways.index')
            ->with('success', 'Configuration enregistrée pour ' . $smsGateway->name . '.');
    }

    /**
     * Activer cette passerelle (désactive les autres).
     */
    public function toggle(SmsGateway $smsGateway)
    {
        SmsGateway::where('id', '!=', $smsGateway->id)->update(['is_active' => false]);
        $smsGateway->update(['is_active' => true]);

        return redirect()->route('sms-gateways.index')
            ->with('success', $smsGateway->name . ' est maintenant la passerelle SMS active pour l\'envoi des codes OTP.');
    }
}

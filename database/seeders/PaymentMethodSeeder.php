<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

/**
 * PaymentMethodSeeder
 *
 * Configure les moyens de paiement disponibles sur la plateforme Serenity.
 *
 * ┌─────────────────┬───────────┬──────────────────────────────────────────────────┐
 * │ Moyen           │ Activé    │ Remarque                                         │
 * ├─────────────────┼───────────┼──────────────────────────────────────────────────┤
 * │ PayDunya        │ OUI (TEST)│ Clés de test — à remplacer par les clés live     │
 * │ PayPal          │ NON       │ Configurer les clés PayPal pour activer           │
 * │ Stripe          │ NON       │ Configurer les clés Stripe pour activer           │
 * │ Cash / Espèces  │ OUI       │ Paiement en main propre (présence physique)      │
 * │ Virement        │ OUI       │ Virement bancaire ou mobile money manuel          │
 * └─────────────────┴───────────┴──────────────────────────────────────────────────┘
 *
 * ⚠️  Les clés PayDunya présentes ici sont des CLÉS DE TEST.
 *     Remplacez-les par les clés de PRODUCTION avant le déploiement live.
 *
 * Utilise PaymentMethod::updateOrCreate() → HasChecksum déclenché → checksums OK.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            // ─── 1. PayDunya (Mobile Money UEMOA) ────────────────────────────
            [
                'name'        => 'PayDunya',
                'code'        => 'paydunya',
                'icon'        => 'bi bi-phone',
                'description' => 'Paiement Mobile Money pour l\'Afrique de l\'Ouest : Orange Money, Wave, MTN, Moov, Coris Money…',
                'enabled'     => true,   // ← Activé avec les clés de test
                'order'       => 1,
                'config'      => [
                    'master_key'  => 'hoNWM2SW-0faJ-ilOz-OJnF-UfWFXIk9ZHMF',
                    'private_key' => 'test_private_aXTsY38KWRGUDViUVwHyAFVYuhK',
                    'public_key'  => 'test_public_YMRFYkma7AF6Wvef1YBnl5btQYl',
                    'token'       => 'uOCoC8djXqTvN60218vQ',
                    'mode'        => 'test',   // 'test' | 'live'
                    'ipn_url'     => null,     // URL de notification IPN → à renseigner en prod
                    // ─── Informations du marchand (affichées sur la page de paiement PayDunya)
                    'store_name'  => 'Serenity',
                    'store_tagline'=> 'Votre plateforme de tontine et d\'épargne',
                    'store_phone' => '+226 00 00 00 00',
                    'store_postal_address' => 'Ouagadougou, Burkina Faso',
                    'store_logo_url'       => null,
                    'store_website_url'    => null,
                ],
            ],

            // ─── 2. PayPal (international) ───────────────────────────────────
            [
                'name'        => 'PayPal',
                'code'        => 'paypal',
                'icon'        => 'bi bi-paypal',
                'description' => 'Paiement en ligne international via PayPal — idéal pour la diaspora.',
                'enabled'     => false,  // Désactivé jusqu'à configuration des clés
                'order'       => 2,
                'config'      => [
                    'client_id'     => '',
                    'client_secret' => '',
                    'mode'          => 'sandbox',  // 'sandbox' | 'live'
                    'currency'      => 'EUR',
                ],
            ],

            // ─── 3. Stripe (carte bancaire) ───────────────────────────────────
            [
                'name'        => 'Stripe',
                'code'        => 'stripe',
                'icon'        => 'bi bi-credit-card-2-front',
                'description' => 'Paiement par carte bancaire internationale via Stripe.',
                'enabled'     => false,
                'order'       => 3,
                'config'      => [
                    'publishable_key' => '',
                    'secret_key'      => '',
                    'webhook_secret'  => '',
                    'mode'            => 'test',   // 'test' | 'live'
                    'currency'        => 'xof',
                ],
            ],

            // ─── 4. Espèces / Cash ────────────────────────────────────────────
            [
                'name'        => 'Espèces',
                'code'        => 'cash',
                'icon'        => 'bi bi-cash-coin',
                'description' => 'Paiement en espèces auprès d\'un agent ou lors d\'une réunion.',
                'enabled'     => true,
                'order'       => 4,
                'config'      => [
                    'require_proof'  => false,  // Demander une preuve de paiement ?
                    'agent_required' => true,   // Doit être validé par un agent admin
                ],
            ],

            // ─── 5. Virement / Transfert manuel ──────────────────────────────
            [
                'name'        => 'Virement Bancaire',
                'code'        => 'virement',
                'icon'        => 'bi bi-bank',
                'description' => 'Virement bancaire ou transfert mobile money (Wave, Orange Money) avec preuve de paiement.',
                'enabled'     => true,
                'order'       => 5,
                'config'      => [
                    'bank_name'     => 'Coris Bank International',
                    'account_name'  => 'Serenity SARL',
                    'account_number'=> '00000000000',
                    'iban'          => '',
                    'swift'         => 'COBFBFBF',
                    'require_proof' => true,    // Preuve de virement obligatoire
                ],
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($methods as $data) {
            $existing = PaymentMethod::where('code', $data['code'])->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                PaymentMethod::create($data);
                $created++;
            }
        }

        $this->command->newLine();
        $this->command->info('✅ Moyens de paiement configurés :');
        $this->command->table(
            ['Moyen', 'Code', 'Activé', 'Mode'],
            [
                ['PayDunya',         'paydunya', '✅ OUI', 'TEST (clés test)'],
                ['PayPal',           'paypal',   '❌ NON', 'sandbox (non configuré)'],
                ['Stripe',           'stripe',   '❌ NON', 'test (non configuré)'],
                ['Espèces',          'cash',     '✅ OUI', 'Manuel / Agent'],
                ['Virement Bancaire','virement', '✅ OUI', 'Preuve requise'],
            ]
        );
        $this->command->warn('   ⚠️  PayDunya en mode TEST — remplacez les clés avant la mise en production.');
    }
}

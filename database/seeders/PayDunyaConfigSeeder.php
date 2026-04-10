<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

/**
 * PayDunyaConfigSeeder
 *
 * Configure PayDunya en mode TEST avec les clés fournies.
 * Utilise PaymentMethod::updateOrCreate() (Eloquent) pour que le trait
 * HasChecksum génère correctement les checksums et alimente le Merkle Ledger.
 *
 * ⚠️  Les clés présentes ici sont des clés de TEST PayDunya.
 *     Ne jamais committer les clés de PRODUCTION dans le dépôt.
 */
class PayDunyaConfigSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::updateOrCreate(
            ['code' => 'paydunya'],
            [
                'name'        => 'PayDunya',
                'code'        => 'paydunya',
                'icon'        => 'bi bi-phone',
                'description' => 'Paiement Mobile Money pour l\'Afrique de l\'Ouest (Orange Money, Wave, MTN, Moov…)',
                'enabled'     => true,   // Activé en mode test
                'order'       => 1,

                // Config stockée en JSON dans la colonne `config` (cast 'array')
                'config' => [
                    'master_key'  => 'hoNWM2SW-0faJ-ilOz-OJnF-UfWFXIk9ZHMF',
                    'private_key' => 'test_private_aXTsY38KWRGUDViUVwHyAFVYuhK',
                    'public_key'  => 'test_public_YMRFYkma7AF6Wvef1YBnl5btQYl',
                    'token'       => 'uOCoC8djXqTvN60218vQ',
                    'mode'        => 'test',   // 'test' | 'live'
                    'ipn_url'     => null,     // URL de notification IPN (à renseigner en prod)
                ],
            ]
        );

        $this->command->info('✅ PayDunya configuré en mode TEST avec les clés de test.');
    }
}

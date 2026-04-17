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
use App\Models\PayDunyaConfiguration;

/**
 * PayDunyaConfigSeeder
 *
 * Configure PayDunya en mode TEST avec les clés fournies.
 * Utilise PayDunyaConfiguration::updateOrCreate() pour persister les réglages.
 *
 * ⚠️  Les clés présentes ici sont des clés de TEST PayDunya.
 */
class PayDunyaConfigSeeder extends Seeder
{
    public function run(): void
    {
        PayDunyaConfiguration::updateOrCreate(
            ['id' => 1], // Un seul enregistrement de config
            [
                'master_key'  => 'hoNWM2SW-0faJ-ilOz-OJnF-UfWFXIk9ZHMF',
                'private_key' => 'test_private_aXTsY38KWRGUDViUVwHyAFVYuhK',
                'public_key'  => 'test_public_YMRFYkma7AF6Wvef1YBnl5btQYl',
                'token'       => 'uOCoC8djXqTvN60218vQ',
                'mode'        => 'test',   // 'test' | 'live'
                'ipn_url'     => null,
                'enabled'     => true,
            ]
        );

        $this->command->info('✅ PayDunya configuration table peuplée en mode TEST.');
    }
}

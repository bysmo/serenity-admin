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
                'master_key'  => env('PAYDUNYA_MASTER_KEY', ''),
                'private_key' => env('PAYDUNYA_PRIVATE_KEY', ''),
                'public_key'  => env('PAYDUNYA_PUBLIC_KEY', ''),
                'token'       => env('PAYDUNYA_TOKEN', ''),
                'mode'        => 'test',   // 'test' | 'live'
                'ipn_url'     => null,
                'enabled'     => true,
            ]
        );

        $this->command->info('✅ PayDunya configuration table peuplée. Vérifiez vos variables d\'environnement PAYDUNYA_*.');
    }
}

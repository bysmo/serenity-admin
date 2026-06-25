<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PiSpiConfiguration;

class PiSpiConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PiSpiConfiguration::updateOrCreate(
            ['id' => 1],
            [
                'client_id' => env('PISPI_CLIENT_ID', ''),
                'client_secret' => env('PISPI_CLIENT_SECRET', ''),
                'api_key' => env('PISPI_API_KEY', ''),
                'paye_alias' => 'SERENITY_BIZ', // Valeur par défaut pour le test
                'mode' => 'sandbox',
                'enabled' => true,
                'webhook_secret' => 'whs_' . bin2hex(random_bytes(16)),
            ]
        );
    }
}

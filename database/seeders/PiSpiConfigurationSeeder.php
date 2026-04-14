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
                'client_id' => '40n5lsi9q4cvm16aammmqvb3o5',
                'client_secret' => '1rp155ssfdd0lku1b4q386rhk2sitvs2431iho3h1vknoutk59f1',
                'api_key' => '1hk3VnVsA16jiuoXz4flLacoeZfi92zc8gzHsnSC',
                'paye_alias' => 'SERENITY_BIZ', // Valeur par défaut pour le test
                'mode' => 'sandbox',
                'enabled' => true,
                'webhook_secret' => 'whs_' . bin2hex(random_bytes(16)),
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\ParrainageConfig;
use Illuminate\Database\Seeder;

class ParrainageConfigSeeder extends Seeder
{
    public function run(): void
    {
        ParrainageConfig::firstOrCreate([], [
            'actif'                  => true,
            'type_remuneration'      => 'fixe',
            'montant_remuneration'   => 500,
            'declencheur'            => 'inscription',
            'delai_validation_jours' => 3,
            'niveaux_parrainage'     => 1,
            'taux_niveau_2'          => 0,
            'taux_niveau_3'          => 0,
            'description'            => 'Parrainez vos proches et gagnez une commission pour chaque client inscrit grâce à votre code.',
            'min_filleuls_retrait'   => 1,
            'montant_min_retrait'    => 5000,
        ]);
    }
}

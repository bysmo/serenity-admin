<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NanoCreditPalier;

class NanoCreditPaliersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * IMPORTANT : On passe par le modèle Eloquent (NanoCreditPalier::create / updateOrCreate)
     * et NON par DB::table()->insert() pour que :
     *  - L'événement 'saved' du trait HasChecksum se déclenche
     *  - Le checksum canonique soit calculé depuis la BD réelle et stocké
     *  - L'entrée dans le Merkle Ledger soit créée
     *
     * DB::table()->insert() bypass Eloquent → pas de checksum → fausse corruption.
     */
    public function run(): void
    {
        $paliers = [
            // ─── PALIER 1 : Rencontre ─────────────────────────────────────────
            [
                'numero'      => 1,
                'nom'         => 'Rencontre',
                'description' => 'Niveau d\'entrée pour les nouveaux membres. Faibles montants, pas de garants requis.',

                'min_credits_rembourses'      => 0,
                'min_montant_total_rembourse' => 0,
                'min_epargne_cumulee'         => 0,
                'min_epargne_percent'         => 0,

                'montant_plafond'              => 50000,
                'nombre_garants'               => 1,
                'duree_jours'                  => 30,
                'taux_interet'                 => 3.00,
                'frequence_remboursement'      => 'journalier',
                'penalite_par_jour'            => 0.50,
                'jours_avant_prelevement_garant' => 3,

                'min_garant_qualite'           => 1,
                'pourcentage_partage_garant'   => 2.50,

                'downgrade_en_cas_impayes'     => false,
                'jours_impayes_pour_downgrade' => 0,
                'interdiction_en_cas_recidive' => true,
                'nb_recidives_pour_interdiction' => 2,

                'actif' => true,
            ],

            // ─── PALIER 2 : Fiabilité ─────────────────────────────────────────
            [
                'numero'      => 2,
                'nom'         => 'Fiabilité',
                'description' => 'Pour les membres ayant prouvé leur fiabilité. Montant modéré avec un garant.',

                'min_credits_rembourses'      => 1,
                'min_montant_total_rembourse' => 5000,
                'min_epargne_cumulee'         => 1500,
                'min_epargne_percent'         => 10,

                'montant_plafond'              => 15000,
                'nombre_garants'               => 2,
                'duree_jours'                  => 60,
                'taux_interet'                 => 4.50,
                'frequence_remboursement'      => 'hebdomadaire',
                'penalite_par_jour'            => 1.50,
                'jours_avant_prelevement_garant' => 14,

                'min_garant_qualite'           => 1,
                'pourcentage_partage_garant'   => 5.00,

                'downgrade_en_cas_impayes'     => true,
                'jours_impayes_pour_downgrade' => 15,
                'interdiction_en_cas_recidive' => true,
                'nb_recidives_pour_interdiction' => 2,

                'actif' => true,
            ],

            // ─── PALIER 3 : Confiance ─────────────────────────────────────────
            [
                'numero'      => 3,
                'nom'         => 'Confiance',
                'description' => 'Niveau intermédiaire. Accès à des montants plus conséquents.',

                'min_credits_rembourses'      => 3,
                'min_montant_total_rembourse' => 200000,
                'min_epargne_cumulee'         => 50000,
                'min_epargne_percent'         => 40,

                'montant_plafond'              => 500000,
                'nombre_garants'               => 2,
                'duree_jours'                  => 90,
                'taux_interet'                 => 4.00,
                'frequence_remboursement'      => 'hebdomadaire',
                'penalite_par_jour'            => 2.00,
                'jours_avant_prelevement_garant' => 21,

                'min_garant_qualite'           => 2,
                'pourcentage_partage_garant'   => 10.00,

                'downgrade_en_cas_impayes'     => true,
                'jours_impayes_pour_downgrade' => 15,
                'interdiction_en_cas_recidive' => false,
                'nb_recidives_pour_interdiction' => 3,

                'actif' => true,
            ],

            // ─── PALIER 4 : Alliance ──────────────────────────────────────────
            [
                'numero'      => 4,
                'nom'         => 'Alliance',
                'description' => 'Pour les membres actifs et réguliers. Grande flexibilité.',

                'min_credits_rembourses'      => 5,
                'min_montant_total_rembourse' => 600000,
                'min_epargne_cumulee'         => 100000,
                'min_epargne_percent'         => 30,

                'montant_plafond'              => 1000000,
                'nombre_garants'               => 2,
                'duree_jours'                  => 180,
                'taux_interet'                 => 3.50,
                'frequence_remboursement'      => 'mensuel',
                'penalite_par_jour'            => 2.50,
                'jours_avant_prelevement_garant' => 30,

                'min_garant_qualite'           => 3,
                'pourcentage_partage_garant'   => 10.00,

                'downgrade_en_cas_impayes'     => true,
                'jours_impayes_pour_downgrade' => 30,
                'interdiction_en_cas_recidive' => false,
                'nb_recidives_pour_interdiction' => 3,

                'actif' => true,
            ],

            // ─── PALIER 5 : Prestige ──────────────────────────────────────────
            [
                'numero'      => 5,
                'nom'         => 'Prestige',
                'description' => 'Niveau prestige pour les meilleurs historiques. Taux préférentiels.',

                'min_credits_rembourses'      => 10,
                'min_montant_total_rembourse' => 1500000,
                'min_epargne_cumulee'         => 250000,
                'min_epargne_percent'         => 20,

                'montant_plafond'              => 2500000,
                'nombre_garants'               => 3,
                'duree_jours'                  => 365,
                'taux_interet'                 => 3.00,
                'frequence_remboursement'      => 'mensuel',
                'penalite_par_jour'            => 3.00,
                'jours_avant_prelevement_garant' => 45,

                'min_garant_qualite'           => 4,
                'pourcentage_partage_garant'   => 15.00,

                'downgrade_en_cas_impayes'     => true,
                'jours_impayes_pour_downgrade' => 30,
                'interdiction_en_cas_recidive' => false,
                'nb_recidives_pour_interdiction' => 3,

                'actif' => true,
            ],
        ];

        $count = 0;
        foreach ($paliers as $data) {
            // updateOrCreate via Eloquent → déclenche HasChecksum::saved()
            // → checksum calculé depuis la BD réelle → Merkle Ledger mis à jour
            NanoCreditPalier::updateOrCreate(
                ['numero' => $data['numero']],
                $data
            );
            $count++;
        }

        $this->command->info("{$count} palier(s) de nano-crédit créé(s) / mis à jour avec checksums.");
    }
}
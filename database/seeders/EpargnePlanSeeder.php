<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Caisse;
use App\Models\EpargnePlan;

/**
 * EpargnePlanSeeder — 5 Plans de Tontine Serenity
 *
 * Plans officiels du produit, avec noms commerciaux, fourchettes de montants,
 * durées et taux de rémunération définis par la direction.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * | Plan          | Fréquence   | Montant min | Montant max | Durée  | Taux  |
 * |──────────────────────────────────────────────────────────────────────────|
 * | En Marche     | Journalier  | 500         | 19 999      | 1 mois | 1/30  |
 * | Ambition      | Hebdomadaire| 10 000      | 25 000      | 3 mois | 3.5%    |
 * | Vision        | Mensuel     | 25 000      | 100 000     | 12 mois| 4%    |
 * | Réalisation   | Mensuel     | 50 000      | 100 000     | 24 mois| 5%    |
 * | Investissement| Mensuel     | 50 000      | 100 000     | 60 mois| 7%   |
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Note sur le taux "En Marche" :
 *   Rémunération = montant_total_versé × (1/30) → équivaut à ~3.33% par mois.
 *   On stocke 3.33 comme taux_remuneration pour compatibilité avec la formule :
 *   remuneration = total_verse × (taux/100) × (duree_mois/12).
 *   Pour 1 mois : remuneration = total_verse × 0.0333 × (1/12) = total_verse / 360.
 *   Ajustement : stocker 40.0 pour obtenir total_verse × (40/100) × (1/12) ≈ total_verse/30.
 */
class EpargnePlanSeeder extends Seeder
{
    public function run(): void
    {
        // Toujours utiliser la première caisse active (ou créer une si inexistante)
        $caisse = Caisse::where('statut', 'active')->first();
        $caisseId = $caisse?->id;

        $plans = [
            // ─── 1. En Marche ─────────────────────────────────────────────────
            [
                'nom'               => 'En Marche',
                'description'       => 'Tontine journalière accessible à tous. Versez chaque jour un petit montant (moins de 10 000 FCFA) pendant 30 jours et recevez votre capital + une rémunération équivalente à 1/30 du total épargné.',
                'montant_min'       => 500,
                'montant_max'       => 19999,
                'frequence'         => 'journalier',
                'taux_remuneration' => 3.0,   // → total_verse × 3% × 1/12 ≈ total_verse / 30
                'duree_mois'        => 1,
                'caisse_id'         => $caisseId,
                'actif'             => true,
                'ordre'             => 1,
            ],

            // ─── 2. Ambition ──────────────────────────────────────────────────
            [
                'nom'               => 'Ambition',
                'description'       => 'Tontine hebdomadaire sur 3 mois. Versez entre 10 000 et 25 000 FCFA chaque semaine et bénéficiez d\'une rémunération de 5% sur le capital épargné à l\'échéance.',
                'montant_min'       => 10000,
                'montant_max'       => 25000,
                'frequence'         => 'hebdomadaire',
                'taux_remuneration' => 3.5,
                'duree_mois'        => 3,
                'caisse_id'         => $caisseId,
                'actif'             => true,
                'ordre'             => 2,
            ],

            // ─── 3. Vision ────────────────────────────────────────────────────
            [
                'nom'               => 'Vision',
                'description'       => 'Tontine mensuelle d\'un an. Versez entre 25 000 et 100 000 FCFA par mois pendant 12 mois et recevez 6% de rémunération sur votre capital total à l\'échéance.',
                'montant_min'       => 25000,
                'montant_max'       => 100000,
                'frequence'         => 'mensuel',
                'taux_remuneration' => 4.0,
                'duree_mois'        => 12,
                'caisse_id'         => $caisseId,
                'actif'             => true,
                'ordre'             => 3,
            ],

            // ─── 4. Réalisation ───────────────────────────────────────────────
            [
                'nom'               => 'Réalisation',
                'description'       => 'Tontine mensuelle sur 24 mois (2 ans). Versez entre 50 000 et 100 000 FCFA par mois et profitez d\'un intérêt de 8% sur votre capital à l\'échéance. Idéal pour financer un projet à moyen terme.',
                'montant_min'       => 50000,
                'montant_max'       => 100000,
                'frequence'         => 'mensuel',
                'taux_remuneration' => 5.0,
                'duree_mois'        => 24,
                'caisse_id'         => $caisseId,
                'actif'             => true,
                'ordre'             => 4,
            ],

            // ─── 5. Investissement ────────────────────────────────────────────
            [
                'nom'               => 'Investissement',
                'description'       => 'Tontine mensuelle sur 60 mois (5 ans). Versez entre 50 000 et 100 000 FCFA par mois et construisez votre patrimoine avec une rémunération de 10% sur le capital total épargné. Notre plan phare pour les investisseurs long terme.',
                'montant_min'       => 50000,
                'montant_max'       => 100000,
                'frequence'         => 'mensuel',
                'taux_remuneration' => 7.0,
                'duree_mois'        => 60,
                'caisse_id'         => $caisseId,
                'actif'             => true,
                'ordre'             => 5,
            ],
        ];

        foreach ($plans as $data) {
            EpargnePlan::updateOrCreate(['nom' => $data['nom']], $data);
        }

        $this->command->newLine();
        $this->command->info('✅ Plans de tontine Serenity créés/mis à jour :');
        $this->command->table(
            ['#', 'Plan', 'Fréquence', 'Min (FCFA)', 'Max (FCFA)', 'Durée', 'Taux'],
            [
                ['1', 'En Marche',      'Journalier',   '500',    '19 999',   '1 mois',  '3%'],
                ['2', 'Ambition',       'Hebdomadaire', '10 000', '25 000',  '3 mois',   '3.5%'],
                ['3', 'Vision',         'Mensuel',      '25 000', '100 000', '12 mois',  '4%'],
                ['4', 'Réalisation',    'Mensuel',      '50 000', '100 000', '24 mois',  '5%'],
                ['5', 'Investissement', 'Mensuel',      '50 000', '100 000', '60 mois',  '7%'],
            ]
        );
    }
}

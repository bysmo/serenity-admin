<?php

namespace Database\Seeders;

use App\Models\Caisse;
use App\Models\EpargnePlan;
use Illuminate\Database\Seeder;

class EpargnePlanSeeder extends Seeder
{
    public function run(): void
    {
        $caisseId = Caisse::where('statut', 'active')->first()?->id;

        $plans = [
            [
                'nom' => 'Tontine Mensuelle Classique',
                'description' => 'Plan de tontine mensuelle avec rémunération. Versez chaque mois un montant fixe et recevez un intérêt à l\'échéance.',
                'montant_min' => 5000,
                'montant_max' => 100000,
                'frequence' => 'mensuel',
                'taux_remuneration' => 3.0,
                'duree_mois' => 12,
                'caisse_id' => $caisseId,
                'actif' => true,
                'ordre' => 1,
            ],
            [
                'nom' => 'Épargne Longue Durée',
                'description' => 'Épargnez sur 24 mois avec un taux de rémunération avantageux. Idéal pour constituer un capital.',
                'montant_min' => 10000,
                'montant_max' => 200000,
                'frequence' => 'mensuel',
                'taux_remuneration' => 4.5,
                'duree_mois' => 24,
                'caisse_id' => $caisseId,
                'actif' => true,
                'ordre' => 2,
            ],
            [
                'nom' => 'Épargne Hebdomadaire',
                'description' => 'Versez chaque semaine un petit montant. Adapté aux revenus irréguliers.',
                'montant_min' => 1000,
                'montant_max' => 25000,
                'frequence' => 'hebdomadaire',
                'taux_remuneration' => 2.5,
                'duree_mois' => 12,
                'caisse_id' => $caisseId,
                'actif' => true,
                'ordre' => 3,
            ],
            [
                'nom' => 'Épargne Trimestrielle',
                'description' => 'Versements trimestriels pour les gros montants. Rémunération à l\'échéance.',
                'montant_min' => 50000,
                'montant_max' => 500000,
                'frequence' => 'trimestriel',
                'taux_remuneration' => 5.0,
                'duree_mois' => 12,
                'caisse_id' => $caisseId,
                'actif' => true,
                'ordre' => 4,
            ],
            [
                'nom' => 'Épargne Découverte',
                'description' => 'Plan court (6 mois) pour découvrir l\'épargne avec des montants modestes.',
                'montant_min' => 2000,
                'montant_max' => 30000,
                'frequence' => 'mensuel',
                'taux_remuneration' => 2.0,
                'duree_mois' => 6,
                'caisse_id' => $caisseId,
                'actif' => true,
                'ordre' => 5,
            ],
        ];

        foreach ($plans as $data) {
            EpargnePlan::updateOrCreate(['nom' => $data['nom']], $data);
        }

        $this->command->info(count($plans) . ' plan(s) d\'épargne créé(s) ou mis à jour.');
    }
}

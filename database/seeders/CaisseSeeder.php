<?php

namespace Database\Seeders;

use App\Models\Caisse;
use Illuminate\Database\Seeder;

class CaisseSeeder extends Seeder
{
    /**
     * Générer un numéro de caisse unique au format XXXX-XXXX (alphanumérique)
     */
    private function generateNumeroCaisse(): string
    {
        do {
            // Générer 4 caractères alphanumériques (majuscules et chiffres)
            $part1 = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
            $part2 = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
            $numero = $part1 . '-' . $part2;
        } while (Caisse::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $caisses = [
            [
                'nom' => 'Compte Epargnes Publiques',
                'description' => 'Compte pour les cagnottes publiques',
                'statut' => 'active',
                'type' => 'epargne',
                'numero_core_banking' => 'SYS-EP-PUB',
                'membre_id' => 'SYSTEM',
            ],
            [
                'nom' => 'Compte Epargnes Privées',
                'description' => 'Compte pour les cagnottes privées des clients',
                'statut' => 'active',
                'type' => 'epargne',
                'numero_core_banking' => 'SYS-EP-PRI',
                'membre_id' => 'SYSTEM',
            ],
            [
                'nom' => 'Compte Nano-crédits',
                'description' => 'Compte de gestion des nano-crédits',
                'statut' => 'active',
                'type' => 'credit',
                'numero_core_banking' => 'SYS-NANO-CR',
                'membre_id' => 'SYSTEM',
            ],
            [
                'nom' => 'Compte Garants',
                'description' => 'Compte de séquestre pour les garants',
                'statut' => 'active',
                'type' => 'courant',
                'numero_core_banking' => 'SYS-GARANT',
                'membre_id' => 'SYSTEM',
            ]
        ];

        foreach ($caisses as $caisseData) {
            // Générer un numéro unique
            $caisseData['numero'] = $this->generateNumeroCaisse();
            // Le solde initial est toujours 0
            $caisseData['solde_initial'] = 0;

            Caisse::create($caisseData);
        }
    }
}

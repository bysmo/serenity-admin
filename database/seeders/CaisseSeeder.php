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
                'nom' => 'Caisse Epargnes Publiques',
                'description' => 'Caisse pour les cagnottes publiques',
                'statut' => 'active',
            ],
            [
                'nom' => 'Caisse Epargnes Privées',
                'description' => 'Caisse pour les cagnottes privées des membres',
                'statut' => 'active',
            ],
            [
                'nom' => 'Caisse Nano-crédits',
                'description' => 'Caisse pour les nano-crédits',
                'statut' => 'active',
            ],
            [
                'nom' => 'Caisse Garants',
                'description' => 'Caisse pour les garants',
                'statut' => 'active',
            ]
        ]
        ;

        foreach ($caisses as $caisseData) {
            // Générer un numéro unique
            $caisseData['numero'] = $this->generateNumeroCaisse();
            // Le solde initial est toujours 0
            $caisseData['solde_initial'] = 0;

            Caisse::create($caisseData);
        }
    }
}

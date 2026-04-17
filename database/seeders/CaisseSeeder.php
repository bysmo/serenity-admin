<?php

namespace Database\Seeders;

use App\Models\Caisse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CaisseSeeder extends Seeder
{
    /**
     * Générer un numéro de compte unique au format XXXX-XXXX (alphanumérique)
     */
    private function generateNumeroCompte(): string
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
        // 1. Assurer l'existence du membre Système
        $systemMembre = \App\Models\Membre::updateOrCreate(
            ['email' => 'system@serenity.biz'],
            [
                'nom' => 'SERENITY',
                'prenom' => 'SYSTEME',
                'numero' => 'SYS-001',
                'telephone' => '+22600000000',
                'statut' => 'actif',
                'sexe' => 'M',
                'pays' => 'Burkina Faso',
                'date_adhesion' => now(),
                'password' => \Illuminate\Support\Facades\Hash::make(Str::random(32)),
            ]
        );

        $caisses = [
            [
                'nom' => 'Compte Tontines Publiques',
                'description' => 'Compte pour les tontines publiques',
                'statut' => 'active',
                'type' => 'tontine',
                'numero_core_banking' => 'SYS-TON-PUB',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte Épargnes Privées',
                'description' => 'Compte pour les épargnes privées des clients',
                'statut' => 'active',
                'type' => 'epargne',
                'numero_core_banking' => 'SYS-EP-PRI',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte Nano-crédits',
                'description' => 'Compte de gestion des nano-crédits',
                'statut' => 'active',
                'type' => 'credit',
                'numero_core_banking' => 'SYS-NANO-CR',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte Garants',
                'description' => 'Compte de séquestre pour les garants',
                'statut' => 'active',
                'type' => 'courant',
                'numero_core_banking' => 'SYS-GARANT',
                'membre_id' => $systemMembre->id,
            ]
        ];

        foreach ($caisses as $caisseData) {
            Caisse::updateOrCreate(
                ['membre_id' => $caisseData['membre_id'], 'type' => $caisseData['type']],
                array_merge($caisseData, [
                    'numero' => $caisseData['numero'] ?? $this->generateNumeroCompte(),
                    'solde_initial' => 0,
                    'statut' => 'active',
                ])
            );
        }
    }
}

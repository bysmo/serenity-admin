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
            ['email' => 'system@serenity.com'],
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
                'nom' => 'Compte global des Cagnottes Publiques',
                'description' => 'Compte pour les cagnottes publiques',
                'statut' => 'active',
                'type' => 'cagnotte',
                'numero_core_banking' => 'SYS-CAG-PUB',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte global des Cagnottes Privées',
                'description' => 'Compte pour les cagnottes privées',
                'statut' => 'active',
                'type' => 'cagnotte',
                'numero_core_banking' => 'SYS-CAG-PRV',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte global des Tontines',
                'description' => 'Compte global pour les tontines (épargnes) des clients',
                'statut' => 'active',
                'type' => 'tontine',
                'numero_core_banking' => 'SYS-TON-CLI',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte global des Epargnes',
                'description' => 'Compte global pour les épargnes libres des clients',
                'statut' => 'active',
                'type' => 'epargne',
                'numero_core_banking' => 'SYS-EPG-CLI',
                'membre_id' => $systemMembre->id,
            ],
            
            [
                'nom' => 'Compte global des Nano-crédits',
                'description' => 'Compte global de gestion des nano-crédits',
                'statut' => 'active',
                'type' => 'credit',
                'numero_core_banking' => 'SYS-NAN-CRD',
                'membre_id' => $systemMembre->id,
            ],
            [
                'nom' => 'Compte Garants',
                'description' => 'Compte de séquestre pour les garants',
                'statut' => 'active',
                'type' => 'courant',
                'numero_core_banking' => 'SYS-GARANT',
                'membre_id' => $systemMembre->id,
            ],
            // creer un compte de produits pour les interets que va gagner l'admin sur les nano-credits, les frais de dossier, les frais de retard, etc
            [
                'nom' => 'Compte global des produits',
                'description' => 'Compte global pour les produits',
                'statut' => 'active',
                'type' => 'produit',
                'numero_core_banking' => 'SYS-PROD',
                'membre_id' => $systemMembre->id,
            ],

            // creer un compte de charges pour payer les interets des comptes epargnes et tontines
            [
                'nom' => 'Compte global des charges',
                'description' => 'Compte global pour les charges',
                'statut' => 'active',
                'type' => 'charge',
                'numero_core_banking' => 'SYS-CHG',
                'membre_id' => $systemMembre->id,
            ],
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

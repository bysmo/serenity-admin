<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CollectorProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Créer les permissions si elles n'existent pas
        $permissions = [
            ['nom' => 'Ouvrir une session de collecte', 'slug' => 'collecte.open', 'categorie' => 'Collecte', 'description' => 'Ouvrir une nouvelle journée de collecte'],
            ['nom' => 'Saisir une collecte', 'slug' => 'collecte.create', 'categorie' => 'Collecte', 'description' => 'Saisir un versement tontine ou nano-crédit'],
            ['nom' => 'Voir ses collectes', 'slug' => 'collecte.view', 'categorie' => 'Collecte', 'description' => 'Consulter l\'historique des collectes'],
            ['nom' => 'Effectuer un reversement', 'slug' => 'collecte.settle', 'categorie' => 'Collecte', 'description' => 'Reverser les fonds collectés'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['slug' => $perm['slug']], $perm);
        }

        // 2. Créer le rôle Collecteur
        $role = Role::updateOrCreate(['slug' => 'collecteur'], [
            'nom' => 'Collecteur / Collectrice',
            'description' => 'Collecte terrain des tontines et nano-crédits',
            'actif' => true,
        ]);

        // 3. Assigner les permissions au rôle
        $perms = Permission::whereIn('slug', [
            'collecte.open', 'collecte.create', 'collecte.view', 'collecte.settle',
            'membres.view', 'caisses.view'
        ])->pluck('id');
        
        $role->permissions()->sync($perms);

        // 4. Créer un utilisateur collecteur par défaut pour la production
        // Note: Changez ces informations pour la mise en production réelle
        $user = User::updateOrCreate(
            ['email' => 'collecteur.prod@serenity.com'],
            [
                'name' => 'Collecteur Principal',
                'password' => Hash::make('Serenity@Collect2024'), // À changer immédiatement
                'email_verified_at' => now(),
            ]
        );

        $user->roles()->sync([$role->id]);

        $this->command->info('Rôle, permissions et utilisateur collecteur créés avec succès pour la production.');
    }
}

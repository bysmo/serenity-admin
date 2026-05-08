<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $users = [
            [
                'name' => 'Administrateur',
                'email' => 'admin@serenity.com',
                'role' => 'admin'
            ],
            [
                'name' => 'Directeur Agence',
                'email' => 'directeur@serenity.com',
                'role' => 'directeur_agence'
            ],
            [
                'name' => 'Resp. Crédits',
                'email' => 'credits@serenity.com',
                'role' => 'resp_credits'
            ],
            [
                'name' => 'Resp. Tontines',
                'email' => 'tontines@serenity.com',
                'role' => 'resp_tontines'
            ],
            [
                'name' => 'Resp. Cagnottes',
                'email' => 'cagnottes@serenity.com',
                'role' => 'resp_cagnottes'
            ],
            [
                'name' => 'Resp. Technique',
                'email' => 'technique@serenity.com',
                'role' => 'resp_technique'
            ],
            [
                'name' => 'Resp. Membres',
                'email' => 'membres@serenity.com',
                'role' => 'resp_membres'
            ],
            [
                'name' => 'Resp. Marketing',
                'email' => 'marketing@serenity.com',
                'role' => 'resp_marketing'
            ],
            [
                'name' => 'Trésorier',
                'email' => 'tresorier@serenity.com',
                'role' => 'tresorier'
            ],
            [
                'name' => 'Collectrice Demo',
                'email' => 'collecte@serenity.com',
                'role' => 'collecteur'
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $password,
                ]
            );

            // Attribuer le rôle
            $role = Role::where('slug', $userData['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }

            $this->command->info("Utilisateur créé : {$userData['email']} (Rôle: {$userData['role']})");
        }
    }
}

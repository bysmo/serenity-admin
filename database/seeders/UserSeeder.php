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
        // Vérifier si l'utilisateur admin existe déjà
        $admin = User::where('email', 'admin@serenity.com')->first();
        
        if (!$admin) {
            // Créer l'utilisateur admin
            $admin = User::create([
                'name' => 'Administrateur',
                'email' => 'admin@serenity.com',
                'password' => Hash::make('password'), // Mot de passe par défaut: password
            ]);
            
            // Attribuer le rôle Administrateur si disponible
            $adminRole = Role::where('slug', 'admin')->first();
            if ($adminRole && !$admin->roles()->where('roles.id', $adminRole->id)->exists()) {
                $admin->roles()->attach($adminRole->id);
            }
            
            $this->command->info('Utilisateur admin créé :');
            $this->command->info('Email: admin@serenity.com');
            $this->command->info('Mot de passe: password');
        } else {
            // Vérifier et attribuer le rôle si nécessaire
            $adminRole = Role::where('slug', 'admin')->first();
            if ($adminRole && !$admin->roles()->where('roles.id', $adminRole->id)->exists()) {
                $admin->roles()->attach($adminRole->id);
                $this->command->info('Rôle administrateur attribué à l\'utilisateur admin existant.');
            } else {
                $this->command->info('L\'utilisateur admin existe déjà.');
            }
        }
        
        // Vérifier si l'utilisateur trésorier existe déjà
        $tresorier = User::where('email', 'tresorier@serenity.com')->first();
        
        if (!$tresorier) {
            // Créer l'utilisateur trésorier
            $tresorier = User::create([
                'name' => 'Trésorier',
                'email' => 'tresorier@serenity.com',
                'password' => Hash::make('password'), // Mot de passe par défaut: password
            ]);
            
            // Attribuer le rôle Trésorier si disponible
            $tresorierRole = Role::where('slug', 'tresorier')->first();
            if ($tresorierRole && !$tresorier->roles()->where('roles.id', $tresorierRole->id)->exists()) {
                $tresorier->roles()->attach($tresorierRole->id);
            }
            
            $this->command->info('Utilisateur trésorier créé :');
            $this->command->info('Email: tresorier@serenity.com');
            $this->command->info('Mot de passe: password');
        } else {
            // Vérifier et attribuer le rôle si nécessaire
            $tresorierRole = Role::where('slug', 'tresorier')->first();
            if ($tresorierRole && !$tresorier->roles()->where('roles.id', $tresorierRole->id)->exists()) {
                $tresorier->roles()->attach($tresorierRole->id);
                $this->command->info('Rôle trésorier attribué à l\'utilisateur trésorier existant.');
            } else {
                $this->command->info('L\'utilisateur trésorier existe déjà.');
            }
        }
    }
}

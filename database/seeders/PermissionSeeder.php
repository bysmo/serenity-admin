<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Caisses
            ['nom' => 'Voir les caisses', 'slug' => 'caisses.view', 'categorie' => 'Caisses', 'description' => 'Afficher la liste des caisses'],
            ['nom' => 'Créer une caisse', 'slug' => 'caisses.create', 'categorie' => 'Caisses', 'description' => 'Créer une nouvelle caisse'],
            ['nom' => 'Modifier une caisse', 'slug' => 'caisses.update', 'categorie' => 'Caisses', 'description' => 'Modifier une caisse existante'],
            ['nom' => 'Supprimer une caisse', 'slug' => 'caisses.delete', 'categorie' => 'Caisses', 'description' => 'Supprimer une caisse'],
            ['nom' => 'Effectuer un transfert', 'slug' => 'caisses.transfert', 'categorie' => 'Caisses', 'description' => 'Effectuer un transfert entre caisses'],
            ['nom' => 'Approvisionner une caisse', 'slug' => 'caisses.approvisionner', 'categorie' => 'Caisses', 'description' => 'Approvisionner une caisse'],
            ['nom' => 'Enregistrer une sortie', 'slug' => 'caisses.sortie', 'categorie' => 'Caisses', 'description' => 'Enregistrer une sortie de caisse'],
            ['nom' => 'Voir le journal', 'slug' => 'caisses.journal', 'categorie' => 'Caisses', 'description' => 'Consulter le journal d\'une caisse'],
            
            // Membres
            ['nom' => 'Voir les membres', 'slug' => 'membres.view', 'categorie' => 'Membres', 'description' => 'Afficher la liste des membres'],
            ['nom' => 'Créer un membre', 'slug' => 'membres.create', 'categorie' => 'Membres', 'description' => 'Créer un nouveau membre'],
            ['nom' => 'Modifier un membre', 'slug' => 'membres.update', 'categorie' => 'Membres', 'description' => 'Modifier un membre existant'],
            ['nom' => 'Supprimer un membre', 'slug' => 'membres.delete', 'categorie' => 'Membres', 'description' => 'Supprimer un membre'],
            
            // Cotisations
            ['nom' => 'Voir les cagnottes', 'slug' => 'cotisations.view', 'categorie' => 'Cotisations', 'description' => 'Afficher la liste des cagnottes'],
            ['nom' => 'Créer une cagnotte', 'slug' => 'cotisations.create', 'categorie' => 'Cotisations', 'description' => 'Créer une nouvelle cagnotte'],
            ['nom' => 'Modifier une cagnotte', 'slug' => 'cotisations.update', 'categorie' => 'Cotisations', 'description' => 'Modifier une cagnotte existante'],
            ['nom' => 'Supprimer une cagnotte', 'slug' => 'cotisations.delete', 'categorie' => 'Cotisations', 'description' => 'Supprimer une cagnotte'],
            
            // Paiements
            ['nom' => 'Voir les paiements', 'slug' => 'paiements.view', 'categorie' => 'Paiements', 'description' => 'Afficher la liste des paiements'],
            ['nom' => 'Enregistrer un paiement', 'slug' => 'paiements.create', 'categorie' => 'Paiements', 'description' => 'Enregistrer un nouveau paiement'],
            ['nom' => 'Modifier un paiement', 'slug' => 'paiements.update', 'categorie' => 'Paiements', 'description' => 'Modifier un paiement existant'],
            ['nom' => 'Supprimer un paiement', 'slug' => 'paiements.delete', 'categorie' => 'Paiements', 'description' => 'Supprimer un paiement'],
            ['nom' => 'Payer un engagement', 'slug' => 'paiements.engagement', 'categorie' => 'Paiements', 'description' => 'Enregistrer un paiement d\'engagement'],
            
            // Engagements
            ['nom' => 'Voir les engagements', 'slug' => 'engagements.view', 'categorie' => 'Engagements', 'description' => 'Afficher la liste des engagements'],
            ['nom' => 'Créer un engagement', 'slug' => 'engagements.create', 'categorie' => 'Engagements', 'description' => 'Créer un nouvel engagement'],
            ['nom' => 'Modifier un engagement', 'slug' => 'engagements.update', 'categorie' => 'Engagements', 'description' => 'Modifier un engagement existant'],
            ['nom' => 'Supprimer un engagement', 'slug' => 'engagements.delete', 'categorie' => 'Engagements', 'description' => 'Supprimer un engagement'],
            
            // Annonces
            ['nom' => 'Voir les annonces', 'slug' => 'annonces.view', 'categorie' => 'Annonces', 'description' => 'Afficher la liste des annonces'],
            ['nom' => 'Créer une annonce', 'slug' => 'annonces.create', 'categorie' => 'Annonces', 'description' => 'Créer une nouvelle annonce'],
            ['nom' => 'Modifier une annonce', 'slug' => 'annonces.update', 'categorie' => 'Annonces', 'description' => 'Modifier une annonce existante'],
            ['nom' => 'Supprimer une annonce', 'slug' => 'annonces.delete', 'categorie' => 'Annonces', 'description' => 'Supprimer une annonce'],
            
            // Rapports
            ['nom' => 'Voir les rapports', 'slug' => 'rapports.view', 'categorie' => 'Rapports', 'description' => 'Consulter les rapports et statistiques'],
            
            // Traitement de fin de mois
            ['nom' => 'Lancer le traitement de fin de mois', 'slug' => 'fin-mois.process', 'categorie' => 'Traitement de fin de mois', 'description' => 'Lancer le traitement de fin de mois et envoyer les récapitulatifs'],
            ['nom' => 'Voir le journal de fin de mois', 'slug' => 'fin-mois.journal', 'categorie' => 'Traitement de fin de mois', 'description' => 'Consulter le journal des traitements de fin de mois'],
            
            // Utilisateurs
            ['nom' => 'Voir les utilisateurs', 'slug' => 'users.view', 'categorie' => 'Utilisateurs', 'description' => 'Afficher la liste des utilisateurs'],
            ['nom' => 'Créer un utilisateur', 'slug' => 'users.create', 'categorie' => 'Utilisateurs', 'description' => 'Créer un nouvel utilisateur'],
            ['nom' => 'Modifier un utilisateur', 'slug' => 'users.update', 'categorie' => 'Utilisateurs', 'description' => 'Modifier un utilisateur existant'],
            ['nom' => 'Supprimer un utilisateur', 'slug' => 'users.delete', 'categorie' => 'Utilisateurs', 'description' => 'Supprimer un utilisateur'],
            
            // Notifications
            ['nom' => 'Voir les notifications', 'slug' => 'notifications.view', 'categorie' => 'Notifications', 'description' => 'Consulter les notifications'],
            
            // Paramètres
            ['nom' => 'Gérer SMTP', 'slug' => 'settings.smtp', 'categorie' => 'Paramètres', 'description' => 'Configurer les paramètres SMTP'],
            ['nom' => 'Gérer les templates', 'slug' => 'settings.templates', 'categorie' => 'Paramètres', 'description' => 'Gérer les templates d\'email'],
            ['nom' => 'Gérer les rôles', 'slug' => 'settings.roles', 'categorie' => 'Paramètres', 'description' => 'Gérer les rôles et permissions'],
            ['nom' => 'Voir les logs d\'audit', 'slug' => 'settings.audit', 'categorie' => 'Paramètres', 'description' => 'Consulter le journal d\'audit'],
            ['nom' => 'Gérer les backups', 'slug' => 'settings.backup', 'categorie' => 'Paramètres', 'description' => 'Créer et restaurer des backups'],
            ['nom' => 'Gérer les paramètres', 'slug' => 'settings.general', 'categorie' => 'Paramètres', 'description' => 'Modifier les paramètres généraux'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Créer les rôles de base
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'nom' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'actif' => true,
            ]
        );

        $tresorierRole = Role::updateOrCreate(
            ['slug' => 'tresorier'],
            [
                'nom' => 'Trésorier',
                'description' => 'Gestion des caisses, paiements et cotisations',
                'actif' => true,
            ]
        );

        $membreRole = Role::updateOrCreate(
            ['slug' => 'membre'],
            [
                'nom' => 'Membre',
                'description' => 'Consultation des paiements et engagements',
                'actif' => true,
            ]
        );

        // Attribuer toutes les permissions à l'administrateur
        // S'assurer que toutes les permissions existantes sont attribuées
        $allPermissions = Permission::all();
        if ($allPermissions->isNotEmpty()) {
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
        }

        // Permissions pour le trésorier
        $tresorierPermissions = Permission::whereIn('slug', [
            'caisses.view', 'caisses.create', 'caisses.update', 'caisses.transfert',
            'caisses.approvisionner', 'caisses.sortie', 'caisses.journal',
            'membres.view', 'membres.create', 'membres.update',
            'cotisations.view', 'cotisations.create', 'cotisations.update',
            'paiements.view', 'paiements.create', 'paiements.update', 'paiements.engagement',
            'engagements.view', 'engagements.create', 'engagements.update',
            'rapports.view',
            'fin-mois.process', 'fin-mois.journal',
            'notifications.view',
        ])->pluck('id');
        $tresorierRole->permissions()->sync($tresorierPermissions);

        // Permissions pour le membre
        $membrePermissions = Permission::whereIn('slug', [
            'membres.view',
            'paiements.view',
            'engagements.view',
        ])->pluck('id');
        $membreRole->permissions()->sync($membrePermissions);
    }
}

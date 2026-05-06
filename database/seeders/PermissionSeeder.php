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
            // Comptes
            ['nom' => 'Voir les comptes', 'slug' => 'caisses.view', 'categorie' => 'Comptes', 'description' => 'Afficher la liste des comptes'],
            ['nom' => 'Créer un compte', 'slug' => 'caisses.create', 'categorie' => 'Comptes', 'description' => 'Créer un nouveau compte'],
            ['nom' => 'Modifier un compte', 'slug' => 'caisses.update', 'categorie' => 'Comptes', 'description' => 'Modifier un compte existant'],
            ['nom' => 'Supprimer un compte', 'slug' => 'caisses.delete', 'categorie' => 'Comptes', 'description' => 'Supprimer un compte'],
            ['nom' => 'Effectuer un transfert', 'slug' => 'caisses.transfert', 'categorie' => 'Comptes', 'description' => 'Effectuer un transfert entre comptes'],
            ['nom' => 'Approvisionner un compte', 'slug' => 'caisses.approvisionner', 'categorie' => 'Comptes', 'description' => 'Approvisionner un compte'],
            ['nom' => 'Enregistrer une sortie', 'slug' => 'caisses.sortie', 'categorie' => 'Comptes', 'description' => 'Enregistrer une sortie de compte'],
            ['nom' => 'Voir le journal', 'slug' => 'caisses.journal', 'categorie' => 'Comptes', 'description' => 'Consulter le journal d\'un compte'],
            
            // Clients
            ['nom' => 'Voir les clients', 'slug' => 'membres.view', 'categorie' => 'Clients', 'description' => 'Afficher la liste des clients'],
            ['nom' => 'Créer un client', 'slug' => 'membres.create', 'categorie' => 'Clients', 'description' => 'Créer un nouveau client'],
            ['nom' => 'Modifier un client', 'slug' => 'membres.update', 'categorie' => 'Clients', 'description' => 'Modifier un client existant'],
            ['nom' => 'Supprimer un client', 'slug' => 'membres.delete', 'categorie' => 'Clients', 'description' => 'Supprimer un client'],
            ['nom' => 'Gérer KYC', 'slug' => 'membres.kyc', 'categorie' => 'Clients', 'description' => 'Valider les documents KYC des clients'],
            
            // Cotisations (Cagnottes)
            ['nom' => 'Voir les cagnottes', 'slug' => 'cotisations.view', 'categorie' => 'Cagnottes', 'description' => 'Afficher la liste des cagnottes'],
            ['nom' => 'Créer une cagnotte', 'slug' => 'cotisations.create', 'categorie' => 'Cagnottes', 'description' => 'Créer une nouvelle cagnotte'],
            ['nom' => 'Modifier une cagnotte', 'slug' => 'cotisations.update', 'categorie' => 'Cagnottes', 'description' => 'Modifier une cagnotte existante'],
            ['nom' => 'Supprimer une cagnotte', 'slug' => 'cotisations.delete', 'categorie' => 'Cagnottes', 'description' => 'Supprimer une cagnotte'],
            
            // Paiements
            ['nom' => 'Voir les paiements', 'slug' => 'paiements.view', 'categorie' => 'Paiements', 'description' => 'Afficher la liste des paiements'],
            ['nom' => 'Enregistrer un paiement', 'slug' => 'paiements.create', 'categorie' => 'Paiements', 'description' => 'Enregistrer un nouveau paiement'],
            ['nom' => 'Modifier un paiement', 'slug' => 'paiements.update', 'categorie' => 'Paiements', 'description' => 'Modifier un paiement existant'],
            ['nom' => 'Supprimer un paiement', 'slug' => 'paiements.delete', 'categorie' => 'Paiements', 'description' => 'Supprimer un paiement'],
            ['nom' => 'Payer un engagement', 'slug' => 'paiements.engagement', 'categorie' => 'Paiements', 'description' => 'Enregistrer un paiement d\'engagement'],
            
            // Engagements (Tontines / Épargne)
            ['nom' => 'Voir les engagements', 'slug' => 'engagements.view', 'categorie' => 'Tontines', 'description' => 'Afficher la liste des engagements'],
            ['nom' => 'Créer un engagement', 'slug' => 'engagements.create', 'categorie' => 'Tontines', 'description' => 'Créer un nouvel engagement'],
            ['nom' => 'Modifier un engagement', 'slug' => 'engagements.update', 'categorie' => 'Tontines', 'description' => 'Modifier un engagement existant'],
            ['nom' => 'Supprimer un engagement', 'slug' => 'engagements.delete', 'categorie' => 'Tontines', 'description' => 'Supprimer un engagement'],
            ['nom' => 'Gérer les plans d\'épargne', 'slug' => 'epargne.plans', 'categorie' => 'Tontines', 'description' => 'Configurer les plans de tontine/épargne'],
            
            // Nano-Crédits
            ['nom' => 'Voir les nano-crédits', 'slug' => 'nano_credits.view', 'categorie' => 'Nano-Crédits', 'description' => 'Afficher la liste des crédits'],
            ['nom' => 'Gérer les demandes', 'slug' => 'nano_credits.manage', 'categorie' => 'Nano-Crédits', 'description' => 'Approuver ou rejeter des demandes de crédit'],
            ['nom' => 'Gérer les paliers', 'slug' => 'nano_credits.paliers', 'categorie' => 'Nano-Crédits', 'description' => 'Configurer les paliers et conditions de crédit'],
            ['nom' => 'Gérer les garants', 'slug' => 'nano_credits.garants', 'categorie' => 'Nano-Crédits', 'description' => 'Suivre et agir sur les garanties'],
            
            // Parrainages
            ['nom' => 'Voir les parrainages', 'slug' => 'parrainages.view', 'categorie' => 'Parrainages', 'description' => 'Consulter l\'arbre de parrainage et stats'],
            ['nom' => 'Gérer la config parrainage', 'slug' => 'parrainages.config', 'categorie' => 'Parrainages', 'description' => 'Modifier les règles de rémunération'],
            ['nom' => 'Gérer les commissions', 'slug' => 'parrainages.commissions', 'categorie' => 'Parrainages', 'description' => 'Valider et payer les commissions'],

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
            
            // Sécurité & Intégrité
            ['nom' => 'Voir tableau de bord sécurité', 'slug' => 'security.dashboard', 'categorie' => 'Sécurité & Intégrité', 'description' => 'Accéder au monitoring anti-fraude'],
            ['nom' => 'Lancer scans d\'intégrité', 'slug' => 'security.scans', 'categorie' => 'Sécurité & Intégrité', 'description' => 'Lancer manuellement les calculs de checksums'],
            ['nom' => 'Gérer l\'intégrité des données', 'slug' => 'security.integrity', 'categorie' => 'Sécurité & Intégrité', 'description' => 'Réparer ou gérer les données corrompues'],
            ['nom' => 'Voir logs d\'audit profonds', 'slug' => 'security.audit', 'categorie' => 'Sécurité & Intégrité', 'description' => 'Voir les modifications tracées et chaine Merkle'],

            // Paramètres
            ['nom' => 'Gérer SMTP', 'slug' => 'settings.smtp', 'categorie' => 'Paramètres', 'description' => 'Configurer les paramètres SMTP'],
            ['nom' => 'Gérer les templates', 'slug' => 'settings.templates', 'categorie' => 'Paramètres', 'description' => 'Gérer les templates d\'email'],
            ['nom' => 'Gérer les rôles', 'slug' => 'settings.roles', 'categorie' => 'Paramètres', 'description' => 'Gérer les rôles et permissions'],
            ['nom' => 'Gérer les backups', 'slug' => 'settings.backup', 'categorie' => 'Paramètres', 'description' => 'Créer et restaurer des backups'],
            ['nom' => 'Gérer les paramètres', 'slug' => 'settings.general', 'categorie' => 'Paramètres', 'description' => 'Modifier les paramètres généraux'],
            
            // Collecte
            ['nom' => 'Ouvrir une session de collecte', 'slug' => 'collecte.open', 'categorie' => 'Collecte', 'description' => 'Ouvrir une nouvelle journée de collecte'],
            ['nom' => 'Saisir une collecte', 'slug' => 'collecte.create', 'categorie' => 'Collecte', 'description' => 'Saisir un versement tontine ou nano-crédit'],
            ['nom' => 'Voir ses collectes', 'slug' => 'collecte.view', 'categorie' => 'Collecte', 'description' => 'Consulter l\'historique des collectes'],
            ['nom' => 'Effectuer un reversement', 'slug' => 'collecte.settle', 'categorie' => 'Collecte', 'description' => 'Reverser les fonds collectés'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // ─── DÉFINITION DES RÔLES ──────────────────────────────────────────────

        $rolesData = [
            'admin' => [
                'nom' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'permissions' => '*' // Signal pour tout donner
            ],
            'directeur_agence' => [
                'nom' => 'Directeur de l\'agence digitale',
                'description' => 'Supervision complète de l\'activité métier',
                'permissions' => [
                    'caisses.view', 'caisses.journal', 'membres.view', 'cotisations.view', 'paiements.view', 
                    'engagements.view', 'nano_credits.view', 'parrainages.view', 'annonces.view', 
                    'rapports.view', 'fin-mois.journal', 'security.dashboard', 'security.audit'
                ]
            ],
            'resp_credits' => [
                'nom' => 'Responsable des crédits',
                'description' => 'Gestion et approbation des nano-crédits',
                'permissions' => [
                    'nano_credits.view', 'nano_credits.manage', 'nano_credits.paliers', 'nano_credits.garants',
                    'membres.view', 'paiements.view', 'rapports.view'
                ]
            ],
            'resp_tontines' => [
                'nom' => 'Responsable des tontines',
                'description' => 'Gestion de l\'épargne et des engagements',
                'permissions' => [
                    'engagements.view', 'engagements.create', 'engagements.update', 'epargne.plans',
                    'paiements.view', 'paiements.create', 'rapports.view', 'caisses.view'
                ]
            ],
            'resp_cagnottes' => [
                'nom' => 'Responsable des cagnottes',
                'description' => 'Gestion des cagnottes communes',
                'permissions' => [
                    'cotisations.view', 'cotisations.create', 'cotisations.update', 'cotisations.delete',
                    'paiements.view', 'paiements.create', 'rapports.view'
                ]
            ],
            'resp_technique' => [
                'nom' => 'Responsable technique',
                'description' => 'Maintenance, sécurité et intégrité du système',
                'permissions' => [
                    'security.dashboard', 'security.scans', 'security.integrity', 'security.audit',
                    'settings.smtp', 'settings.templates', 'settings.roles', 'settings.backup', 'settings.general',
                    'users.view', 'users.create', 'users.update'
                ]
            ],
            'resp_membres' => [
                'nom' => 'Responsable des clients',
                'description' => 'Onboarding, support et validation KYC',
                'permissions' => [
                    'membres.view', 'membres.create', 'membres.update', 'membres.kyc',
                    'parrainages.view', 'annonces.view', 'rapports.view'
                ]
            ],
            'resp_marketing' => [
                'nom' => 'Responsable communication et marketing',
                'description' => 'Gestion des annonces et programme de parrainage',
                'permissions' => [
                    'annonces.view', 'annonces.create', 'annonces.update', 'annonces.delete',
                    'parrainages.view', 'parrainages.config', 'parrainages.commissions',
                    'rapports.view'
                ]
            ],
            'tresorier' => [
                'nom' => 'Trésorier',
                'description' => 'Opérations de compte et paiements courants',
                'permissions' => [
                    'caisses.view', 'caisses.journal', 'caisses.approvisionner', 'caisses.sortie', 'caisses.transfert',
                    'paiements.view', 'paiements.create', 'paiements.update', 'paiements.engagement',
                    'fin-mois.process', 'fin-mois.journal'
                ]
            ],
            'collecteur' => [
                'nom' => 'Collecteur / Collectrice',
                'description' => 'Collecte terrain des tontines et nano-crédits',
                'permissions' => [
                    'collecte.open', 'collecte.create', 'collecte.view', 'collecte.settle',
                    'membres.view', 'caisses.view'
                ]
            ],
        ];

        foreach ($rolesData as $slug => $data) {
            $role = Role::updateOrCreate(['slug' => $slug], [
                'nom' => $data['nom'],
                'description' => $data['description'],
                'actif' => true,
            ]);

            if ($data['permissions'] === '*') {
                $role->permissions()->sync(Permission::all());
            } else {
                $perms = Permission::whereIn('slug', $data['permissions'])->pluck('id');
                $role->permissions()->sync($perms);
            }
        }
    }
}

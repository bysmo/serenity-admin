<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppSetting;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Paramètres généraux
            [
                'cle' => 'app_nom',
                'valeur' => 'Serenity',
                'type' => 'string',
                'description' => 'Nom de l\'application',
                'groupe' => 'general',
            ],
            [
                'cle' => 'app_description',
                'valeur' => 'Application de gestion de la sérénité financiere',
                'type' => 'string',
                'description' => 'Description de l\'application',
                'groupe' => 'general',
            ],
            [
                'cle' => 'app_email',
                'valeur' => 'contact@example.com',
                'type' => 'string',
                'description' => 'Email de contact',
                'groupe' => 'general',
            ],
            [
                'cle' => 'app_telephone',
                'valeur' => '+221 XX XXX XX XX',
                'type' => 'string',
                'description' => 'Téléphone de contact',
                'groupe' => 'general',
            ],
            [
                'cle' => 'app_adresse',
                'valeur' => 'Adresse de l\'organisation',
                'type' => 'string',
                'description' => 'Adresse postale',
                'groupe' => 'general',
            ],
            [
                'cle' => 'app_devise',
                'valeur' => 'XOF',
                'type' => 'string',
                'description' => 'Devise utilisée',
                'groupe' => 'general',
            ],
            [
                'cle' => 'app_activer_notifications',
                'valeur' => '1',
                'type' => 'boolean',
                'description' => 'Activer les notifications par email',
                'groupe' => 'general',
            ],
            [
                'cle' => 'seuil_solde_alerte',
                'valeur' => '50000',
                'type' => 'integer',
                'description' => 'Seuil d\'alerte pour les soldes de caisses (en XOF)',
                'groupe' => 'notifications',
            ],
            [
                'cle' => 'jours_rappel_paiement',
                'valeur' => '3',
                'type' => 'integer',
                'description' => 'Nombre de jours avant échéance pour envoyer un rappel de paiement',
                'groupe' => 'notifications',
            ],
            
            // Paramètres de l'entreprise
            [
                'cle' => 'entreprise_nom',
                'valeur' => 'Nom de l\'entreprise',
                'type' => 'string',
                'description' => 'Nom de l\'entreprise ou organisation',
                'groupe' => 'entreprise',
            ],
            [
                'cle' => 'entreprise_logo',
                'valeur' => null,
                'type' => 'string',
                'description' => 'Logo de l\'entreprise (chemin du fichier)',
                'groupe' => 'entreprise',
            ],
            [
                'cle' => 'entreprise_email',
                'valeur' => 'contact@example.com',
                'type' => 'string',
                'description' => 'Email de contact de l\'entreprise',
                'groupe' => 'entreprise',
            ],
            [
                'cle' => 'entreprise_adresse',
                'valeur' => 'Adresse de l\'entreprise',
                'type' => 'string',
                'description' => 'Adresse postale complète',
                'groupe' => 'entreprise',
            ],
            [
                'cle' => 'entreprise_contact',
                'valeur' => '+221 XX XXX XX XX',
                'type' => 'string',
                'description' => 'Numéro de téléphone de contact',
                'groupe' => 'entreprise',
            ],
            
            // Paramètres de backup
            [
                'cle' => 'backup_frequence',
                'valeur' => 'quotidien',
                'type' => 'string',
                'description' => 'Fréquence recommandée pour les backups (quotidien, hebdomadaire, mensuel)',
                'groupe' => 'backup',
            ],
            [
                'cle' => 'backup_conserver_jours',
                'valeur' => '30',
                'type' => 'integer',
                'description' => 'Nombre de jours de conservation des backups',
                'groupe' => 'backup',
            ],
            
            // Paramètres d'affichage
            [
                'cle' => 'pagination_par_page',
                'valeur' => '15',
                'type' => 'integer',
                'description' => 'Nombre d\'éléments par page dans les listes',
                'groupe' => 'affichage',
            ],
            [
                'cle' => 'afficher_statistiques',
                'valeur' => '1',
                'type' => 'boolean',
                'description' => 'Afficher les statistiques sur le dashboard',
                'groupe' => 'affichage',
            ],
            [
                'cle' => 'default_country_code',
                'valeur' => 'BF',
                'type' => 'string',
                'description' => 'Code pays par défaut (ISO 2 lettres)',
                'groupe' => 'general',
            ],
            [
                'cle' => 'default_dial_code',
                'valeur' => '226',
                'type' => 'string',
                'description' => 'Indicatif téléphonique par défaut (ex: 226)',
                'groupe' => 'general',
            ],
        ];

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['cle' => $setting['cle']],
                $setting
            );
        }
    }
}

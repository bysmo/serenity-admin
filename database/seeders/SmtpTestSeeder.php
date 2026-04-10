<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SMTPConfiguration;

/**
 * SmtpTestSeeder
 *
 * Configure un serveur SMTP de TEST pré-rempli correspondant à la capture
 * (mail.aladints.com / infos@aladints.com / SSL 465).
 *
 * SMTPConfiguration n'utilise pas HasChecksum, il n'y a donc pas de contrainte
 * de checksum ici — on peut utiliser updateOrCreate normalement.
 *
 * ⚠️  Ces paramètres sont des paramètres de TEST.
 *     Remplacer le mot de passe par la vraie valeur via l'interface admin
 *     ou via les variables d'environnement en production.
 */
class SmtpTestSeeder extends Seeder
{
    public function run(): void
    {
        SMTPConfiguration::updateOrCreate(
            ['nom' => 'SMTP Aladints (Test)'],
            [
                'nom'          => 'SMTP Aladints (Test)',
                'host'         => 'mail.aladints.com',
                'port'         => 465,
                'encryption'   => 'ssl',          // 'ssl' | 'tls' | null
                'username'     => 'infos@aladints.com',
                'password'     => 'Aladin@226',   // ⚠️ Mot de passe de TEST — changer en prod
                'from_address' => 'infos@aladints.com',
                'from_name'    => 'Serenity',
                'actif'        => true,
            ]
        );

        // Synchroniser également les AppSettings mail pour que l'app
        // puisse envoyer des emails depuis ces paramètres
        $smtpSettings = [
            ['cle' => 'mail_driver',       'valeur' => 'smtp',               'type' => 'string',  'groupe' => 'mail', 'description' => 'Driver mail (smtp, sendmail, mailgun…)'],
            ['cle' => 'mail_host',         'valeur' => 'mail.aladints.com',  'type' => 'string',  'groupe' => 'mail', 'description' => 'Hôte SMTP'],
            ['cle' => 'mail_port',         'valeur' => '465',                'type' => 'integer', 'groupe' => 'mail', 'description' => 'Port SMTP'],
            ['cle' => 'mail_encryption',   'valeur' => 'ssl',                'type' => 'string',  'groupe' => 'mail', 'description' => 'Cryptage SMTP (ssl, tls, null)'],
            ['cle' => 'mail_username',     'valeur' => 'infos@aladints.com', 'type' => 'string',  'groupe' => 'mail', 'description' => 'Nom d\'utilisateur SMTP'],
            ['cle' => 'mail_password',     'valeur' => 'Aladin@226',         'type' => 'string',  'groupe' => 'mail', 'description' => 'Mot de passe SMTP'],
            ['cle' => 'mail_from_address', 'valeur' => 'infos@aladints.com', 'type' => 'string',  'groupe' => 'mail', 'description' => 'Adresse email de l\'expéditeur'],
            ['cle' => 'mail_from_name',    'valeur' => 'Serenity',           'type' => 'string',  'groupe' => 'mail', 'description' => 'Nom de l\'expéditeur'],
            ['cle' => 'mail_queue',        'valeur' => '1',                  'type' => 'boolean', 'groupe' => 'mail', 'description' => 'Activer la file d\'attente email'],
        ];

        foreach ($smtpSettings as $setting) {
            \App\Models\AppSetting::updateOrCreate(
                ['cle' => $setting['cle']],
                $setting
            );
        }

        $this->command->info('✅ Configuration SMTP de test enregistrée (mail.aladints.com:465/SSL).');
        $this->command->warn('   ⚠️  Pensez à changer le mot de passe SMTP avant la mise en production.');
    }
}

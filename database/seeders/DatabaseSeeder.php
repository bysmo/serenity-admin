<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MouvementCaisse;
use App\Models\Paiement;
use App\Models\Engagement;
use App\Models\SortieCaisse;
use App\Models\Transfert;
use App\Models\Approvisionnement;
use App\Models\Cotisation;
use App\Models\Annonce;
use App\Models\Membre;
use App\Models\Caisse;
use App\Models\Tag;
use App\Models\EmailTemplate;
use App\Models\SMTPConfiguration;
use App\Models\Remboursement;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\PaymentMethod;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{


    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Suppression de toutes les données existantes...');
        
        // Désactiver temporairement les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Supprimer dans l'ordre inverse des dépendances
        // Tables avec clés étrangères d'abord
        MouvementCaisse::truncate();
        Remboursement::truncate();
        EmailLog::truncate();
        Paiement::truncate();
        Engagement::truncate();
        SortieCaisse::truncate();
        Transfert::truncate();
        Approvisionnement::truncate();
        Cotisation::truncate();
        Annonce::truncate();
        Membre::truncate();
        Tag::truncate();
        Caisse::truncate();
        EmailCampaign::truncate();
        EmailTemplate::truncate();
        SMTPConfiguration::truncate();
        PaymentMethod::truncate();
        User::truncate();
        
        // Truncate des tables de sessions et tokens si elles existent
        DB::table('sessions')->truncate();
        DB::table('password_reset_tokens')->truncate();
        
        // Réactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('Données supprimées avec succès.');
        $this->command->info('Création de nouvelles données...');
        
        // Seeders personnalisés
        $this->call([
            PermissionSeeder::class,       // Permissions & rôles en premier
            AppSettingSeeder::class,        // Paramètres généraux
            SmtpTestSeeder::class,          // Config SMTP de test (mail.aladints.com)
            UserSeeder::class,              // Utilisateurs admin & trésorier
            PaymentMethodSeeder::class,     // PayDunya, PayPal, Stripe (config vide)
            PayDunyaConfigSeeder::class,    // Config TEST PayDunya (clés de test)
            CaisseSeeder::class,
            EpargnePlanSeeder::class,
            NanoCreditPaliersSeeder::class, // Paliers via Eloquent → checksums OK
            ApprovisionnementSeeder::class,
            TransfertSeeder::class,
            SortieSeeder::class,
            TagSeeder::class,
            MembreSeeder::class,
            CotisationSeeder::class,
            EngagementSeeder::class,
            PaiementSeeder::class,
            RemboursementSeeder::class,
            EmailCampaignSeeder::class,
            EmailLogSeeder::class,
            EmailTemplateSeeder::class,     // Templates de base (texte brut)
            OtpEmailTemplateSeeder::class,  // Templates OTP HTML (inscription, opération, activation)
        ]);
        
        $this->command->info('Seed terminé avec succès !');
    }
}

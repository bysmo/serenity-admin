<?php

namespace Database\Seeders;

use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        $this->command->info('Création des campagnes d\'emails...');

        $nomsCampagnes = [
            'Rappel de paiement mensuel',
            'Newsletter trimestrielle',
            'Invitation événement annuel',
            'Rappel engagement en cours',
            'Information nouvelle cagnotte',
            'Rappel échéance proche',
            'Communication importante',
            'Invitation assemblée générale',
            'Rappel solde impayé',
            'Information changement tarif',
            'Campagne promotionnelle',
            'Rappel adhésion annuelle',
            'Invitation formation',
            'Communication administrative',
            'Rappel paiement retard',
            'Newsletter mensuelle',
            'Invitation événement spécial',
            'Information remboursement',
            'Rappel engagement terminé',
            'Communication urgente',
            'Campagne de sensibilisation',
            'Invitation réunion',
            'Rappel cagnotte journalière',
            'Rappel cagnotte hebdomadaire',
            'Rappel cagnotte mensuelle',
            'Rappel cagnotte trimestrielle',
            'Rappel cagnotte annuelle',
            'Information nouveau service',
            'Campagne de recrutement',
            'Rappel paiement en attente',
            'Invitation conférence',
            'Communication changement',
            'Rappel engagement à venir',
            'Information actualité',
            'Campagne de fidélisation',
            'Invitation atelier',
            'Rappel paiement échu',
            'Communication résultats',
            'Invitation cérémonie',
            'Information partenariat',
            'Campagne de remerciement',
            'Invitation séminaire',
            'Rappel solde négatif',
            'Communication événement',
            'Invitation portes ouvertes',
            'Rappel engagement mensuel',
            'Information nouveau règlement',
            'Campagne de mobilisation',
            'Invitation déjeuner',
            'Rappel paiement partiel',
            'Communication statistiques',
            'Invitation réunion d\'information',
            'Rappel engagement semestriel',
        ];

        $statuts = ['brouillon', 'en_cours', 'terminee', 'annulee'];
        $segments = ['Premium', 'Standard', 'Basique', 'VIP', 'Entreprise', null];

        $created = 0;
        $nbCampagnes = 50;

        for ($i = 0; $i < $nbCampagnes; $i++) {
            $user = $users->random();
            $nom = $nomsCampagnes[$i] ?? 'Campagne ' . ($i + 1);
            $statut = $statuts[array_rand($statuts)];
            
            // Générer des filtres aléatoires
            $filtres = [];
            if (rand(1, 2) === 1) {
                $filtres['statut'] = rand(1, 2) === 1 ? 'actif' : 'inactif';
            }
            if (rand(1, 3) === 1) {
                $filtres['segment'] = $segments[array_rand($segments)];
            }
            if (rand(1, 3) === 1) {
                $filtres['date_adhesion_debut'] = Carbon::now()->subMonths(rand(6, 24))->format('Y-m-d');
                $filtres['date_adhesion_fin'] = Carbon::now()->format('Y-m-d');
            }

            // Calculer le nombre de destinataires (simulation)
            $totalDestinataires = rand(5, 50);
            $envoyes = 0;
            $echecs = 0;
            $envoyeeAt = null;

            if ($statut === 'terminee') {
                $envoyes = $totalDestinataires;
                $echecs = rand(0, 3);
                $envoyeeAt = Carbon::now()->subDays(rand(1, 30));
            } elseif ($statut === 'en_cours') {
                $envoyes = rand(1, $totalDestinataires - 1);
                $echecs = rand(0, 2);
                $envoyeeAt = Carbon::now()->subHours(rand(1, 24));
            }

            $campagne = EmailCampaign::create([
                'nom' => $nom,
                'sujet' => $nom . ' - ' . Carbon::now()->format('d/m/Y'),
                'message' => 'Bonjour {{nom}},<br><br>Ceci est un message de test pour la campagne : ' . $nom . '<br><br>Cordialement,<br>L\'équipe',
                'statut' => $statut,
                'filtres' => !empty($filtres) ? $filtres : null,
                'total_destinataires' => $totalDestinataires,
                'envoyes' => $envoyes,
                'echecs' => $echecs,
                'cree_par' => $user->id,
                'envoyee_at' => $envoyeeAt,
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                'updated_at' => Carbon::now()->subDays(rand(1, 60)),
            ]);

            $created++;
        }

        $this->command->info("{$created} campagne(s) d'email créée(s) avec succès.");
    }
}

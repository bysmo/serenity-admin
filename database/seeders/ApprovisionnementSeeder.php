<?php

namespace Database\Seeders;

use App\Models\Caisse;
use App\Models\Approvisionnement;
use App\Models\MouvementCaisse;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ApprovisionnementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $caisses = Caisse::where('statut', 'active')->get();
        
        if ($caisses->isEmpty()) {
            $this->command->warn('Aucun compte actif trouvé. Veuillez d\'abord exécuter CaisseSeeder.');
            return;
        }

        $this->command->info('Création des approvisionnements de comptes...');

        // Approvisionnements initiaux pour les comptes système
        $approvisionnements = [
            ['nom' => 'Compte Epargnes Publiques', 'montant' => 500000, 'motif' => 'Approvisionnement initial'],
            ['nom' => 'Compte Epargnes Privées', 'montant' => 200000, 'motif' => 'Fonds de roulement'],
            ['nom' => 'Compte Nano-crédits', 'montant' => 1000000, 'motif' => 'Dotation ligne de crédit'],
            ['nom' => 'Compte Garants', 'montant' => 150000, 'motif' => 'Dépôt de garantie'],
        ];

        $created = 0;
        
        foreach ($approvisionnements as $appro) {
            $caisse = $caisses->firstWhere('nom', $appro['nom']);
            
            if ($caisse) {
                // Créer l'approvisionnement
                $approvisionnement = Approvisionnement::create([
                    'caisse_id' => $caisse->id,
                    'montant' => $appro['montant'],
                    'motif' => $appro['motif'],
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);

                // Mettre à jour le solde de la caisse
                $caisse->solde_initial += $appro['montant'];
                $caisse->save();

                // Créer le mouvement de caisse
                MouvementCaisse::create([
                    'caisse_id' => $caisse->id,
                    'type' => 'approvisionnement',
                    'sens' => 'entree',
                    'montant' => $appro['montant'],
                    'date_operation' => $approvisionnement->created_at,
                    'libelle' => 'Approvisionnement',
                    'notes' => $appro['motif'],
                    'reference_type' => Approvisionnement::class,
                    'reference_id' => $approvisionnement->id,
                ]);

                $created++;
            }
        }

        // Approvisionnements supplémentaires aléatoires
        $motifs = [
            'Approvisionnement mensuel',
            'Fonds supplémentaires',
            'Réapprovisionnement',
            'Alimentation compte',
            'Dépôt initial',
            'Budget complémentaire',
            'Réserve financière',
        ];
        
        $nbApproSupp = 40;
        
        for ($i = 0; $i < $nbApproSupp; $i++) {
            $caisseAleatoire = $caisses->random();
            $montant = rand(50000, 400000);
            $motif = $motifs[array_rand($motifs)];
            $joursAgo = rand(1, 60);
            
            $approvisionnement = Approvisionnement::create([
                'caisse_id' => $caisseAleatoire->id,
                'montant' => $montant,
                'motif' => $motif,
                'created_at' => Carbon::now()->subDays($joursAgo),
                'updated_at' => Carbon::now()->subDays($joursAgo),
            ]);

            // Mettre à jour le solde de la caisse
            $caisseAleatoire->solde_initial += $montant;
            $caisseAleatoire->save();

            // Créer le mouvement de caisse
            MouvementCaisse::create([
                'caisse_id' => $caisseAleatoire->id,
                'type' => 'approvisionnement',
                'sens' => 'entree',
                'montant' => $montant,
                'date_operation' => $approvisionnement->created_at,
                'libelle' => 'Approvisionnement',
                'notes' => $motif,
                'reference_type' => Approvisionnement::class,
                'reference_id' => $approvisionnement->id,
            ]);

            $created++;
        }

        $this->command->info("{$created} approvisionnement(s) créé(s) avec succès.");
    }
}

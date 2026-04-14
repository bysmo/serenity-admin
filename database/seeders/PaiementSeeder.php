<?php

namespace Database\Seeders;

use App\Models\Paiement;
use App\Models\Membre;
use App\Models\Cotisation;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaiementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $membres = Membre::where('statut', 'actif')->get();
        $cotisations = Cotisation::where('actif', true)->get();
        
        if ($membres->isEmpty()) {
            $this->command->warn('Aucun client actif trouvé. Veuillez d\'abord exécuter MembreSeeder.');
            return;
        }
        
        if ($cotisations->isEmpty()) {
            $this->command->warn('Aucune cotisation active trouvée. Veuillez d\'abord exécuter CotisationSeeder.');
            return;
        }

        $this->command->info('Création des paiements...');

        $modesPaiement = ['especes', 'cheque', 'virement', 'mobile_money', 'autre'];

        $created = 0;
        $nbPaiements = 50; // Générer 50 paiements

        for ($i = 0; $i < $nbPaiements; $i++) {
            $membre = $membres->random();
            $cotisation = $cotisations->random();
            $caisse = $cotisation->caisse;
            
            if (!$caisse) {
                continue;
            }
            
            // Montant selon le type de cotisation
            $montant = $cotisation->montant ?? rand(10000, 100000);
            $modePaiement = $modesPaiement[array_rand($modesPaiement)];
            $datePaiement = Carbon::now()->subDays(rand(1, 90));
            
            $paiement = Paiement::create([
                'numero' => 'PAY-' . strtoupper(Str::random(6)),
                'membre_id' => $membre->id,
                'cotisation_id' => $cotisation->id,
                'caisse_id' => $caisse->id,
                'montant' => $montant,
                'date_paiement' => $datePaiement,
                'mode_paiement' => $modePaiement,
                'notes' => rand(1, 3) === 1 ? 'Note de paiement' : null,
                'created_at' => $datePaiement,
                'updated_at' => $datePaiement,
            ]);

            // Mettre à jour le solde de la caisse
            $caisse->solde_initial += $montant;
            $caisse->save();

            // Créer le mouvement de caisse
            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'type' => 'paiement',
                'sens' => 'entree',
                'montant' => $montant,
                'date_operation' => $datePaiement,
                'libelle' => 'Paiement cotisation: ' . $cotisation->nom,
                'notes' => 'Paiement de ' . $membre->nom_complet,
                'reference_type' => Paiement::class,
                'reference_id' => $paiement->id,
            ]);

            $created++;
        }

        $this->command->info("{$created} paiement(s) créé(s) avec succès.");
    }
}

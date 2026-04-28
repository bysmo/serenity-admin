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
            
            try {
                $numero = app(\App\Services\AutoNumberingService::class)->generate('transaction');
            } catch (\Exception $e) {
                $numero = 'PAY-' . strtoupper(Str::random(6));
            }

            $paiement = Paiement::create([
                'numero' => $numero,
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

            $paiement->save(); // ensure timestamps match if needed, but create already saved it

            // Enregistrement de l'écriture balancée via FinanceService
            app(\App\Services\FinanceService::class)->logFluxTontineCagnotte(
                $caisse,
                (float) $montant,
                'paiement',
                'Paiement cotisation: ' . $cotisation->nom . ' (Seed)',
                $paiement
            );

            $created++;
        }

        $this->command->info("{$created} paiement(s) créé(s) avec succès.");
    }
}

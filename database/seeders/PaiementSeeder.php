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

        $this->command->info('Création des paiements de cagnottes...');

        $modesPaiement = ['mobile_money', 'mobile_money', 'mobile_money', 'especes', 'virement'];

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
            $montant = $cotisation->type_montant === 'fixe'
                ? (float) $cotisation->montant
                : rand(1000, 100000);
            $modePaiement = $modesPaiement[array_rand($modesPaiement)];
            $datePaiement = Carbon::now()->subDays(rand(1, 90));
            
            try {
                $numero = app(\App\Services\AutoNumberingService::class)->generate('transaction');
            } catch (\Exception $e) {
                $numero = 'PAY-' . strtoupper(Str::random(6));
            }

            $paiement = Paiement::create([
                'numero'        => $numero,
                'membre_id'     => $membre->id,
                'cotisation_id' => $cotisation->id,
                'caisse_id'     => $caisse->id,
                'montant'       => $montant,
                'date_paiement' => $datePaiement,
                'mode_paiement' => $modePaiement,
                'statut'        => 'valide',
                'notes'         => rand(1, 3) === 1 ? 'Paiement mobile money' : null,
                'created_at'    => $datePaiement,
                'updated_at'    => $datePaiement,
            ]);

            // ─────────────────────────────────────────────────────────────────
            // IMPORTANT : Le paiement d'une cagnotte se fait via mobile money
            // EXTERNE → AUCUN compte personnel du membre n'est impacté.
            // Seule la caisse de la cagnotte + le compte système bougent.
            // ─────────────────────────────────────────────────────────────────
            $isPrivee = ($cotisation->visibilite === 'privee');
            app(\App\Services\FinanceService::class)->logFluxCagnotte(
                $caisse,
                (float) $montant,
                'Paiement cotisation: ' . $cotisation->nom . ' (Seed)',
                $paiement,
                $isPrivee
            );

            $created++;
        }

        $this->command->info("{$created} paiement(s) de cagnotte créé(s) avec succès.");
        $this->command->info('  → Aucun compte membre impacté (paiements mobile money externes).');

        // ─────────────────────────────────────────────────────────────────
        // DÉPÔTS D'ÉPARGNE & TONTINE POUR CRÉDITER LES COMPTES DES MEMBRES
        // ─────────────────────────────────────────────────────────────────
        $this->command->info('Création des dépôts d\'épargne pour alimenter les comptes des membres...');
        
        $caisseGlobalEpargne = Caisse::getCaisseEpargneLibre();
        $caisseGlobalTontine = Caisse::getCaisseTontineCli();
        
        $depotCreated = 0;
        foreach ($membres as $membre) {
            // 1. Créditer le Compte Épargne (caisse type = epargne)
            $caisseEpargne = Caisse::where('membre_id', $membre->id)
                ->where('type', 'epargne')
                ->first();
                
            if ($caisseEpargne) {
                // Générer 2 à 4 dépôts d'épargne aléatoires
                $nbDepots = rand(2, 4);
                for ($d = 0; $d < $nbDepots; $d++) {
                    $montant = (float) rand(5000, 30000);
                    $dateDepot = Carbon::now()->subDays(rand(1, 95));
                    
                    try {
                        $numero = app(\App\Services\AutoNumberingService::class)->generate('transaction');
                    } catch (\Exception $e) {
                        $numero = 'DEP-' . strtoupper(Str::random(6));
                    }
                    
                    // Créer l'enregistrement de paiement
                    $paiement = Paiement::create([
                        'numero'        => $numero,
                        'membre_id'     => $membre->id,
                        'cotisation_id' => null, // Direct deposit
                        'caisse_id'     => $caisseEpargne->id,
                        'montant'       => $montant,
                        'date_paiement' => $dateDepot,
                        'mode_paiement' => 'mobile_money',
                        'statut'        => 'valide',
                        'notes'         => 'Dépôt épargne initial via mobile money (Seed)',
                        'created_at'    => $dateDepot,
                        'updated_at'    => $dateDepot,
                    ]);
                    
                    // Créer le mouvement de caisse pour le membre (sens = entree)
                    MouvementCaisse::create([
                        'caisse_id'      => $caisseEpargne->id,
                        'type'           => 'epargne',
                        'sens'           => 'entree',
                        'montant'        => $montant,
                        'date_operation' => $dateDepot,
                        'libelle'        => 'Dépôt Épargne via Mobile Money (Seed)',
                        'reference_type' => Paiement::class,
                        'reference_id'   => $paiement->id,
                        'created_at'     => $dateDepot,
                        'updated_at'     => $dateDepot,
                    ]);
                    
                    // Réconciliation globale
                    if ($caisseGlobalEpargne) {
                        MouvementCaisse::create([
                            'caisse_id'      => $caisseGlobalEpargne->id,
                            'type'           => 'epargne',
                            'sens'           => 'entree',
                            'montant'        => $montant,
                            'date_operation' => $dateDepot,
                            'libelle'        => 'RÉCONCILIATION GLOBALE ÉPARGNE - Client #' . $membre->id,
                            'reference_type' => Paiement::class,
                            'reference_id'   => $paiement->id,
                            'created_at'     => $dateDepot,
                            'updated_at'     => $dateDepot,
                        ]);
                    }
                    
                    $depotCreated++;
                }
            }

            // 2. Créditer le Compte Tontine (caisse type = tontine)
            $caisseTontine = Caisse::where('membre_id', $membre->id)
                ->where('type', 'tontine')
                ->first();
                
            if ($caisseTontine) {
                // Générer 2 à 4 dépôts de tontine aléatoires
                $nbTontines = rand(2, 4);
                for ($t = 0; $t < $nbTontines; $t++) {
                    $montant = (float) rand(5000, 20000);
                    $dateDepot = Carbon::now()->subDays(rand(1, 95));
                    
                    try {
                        $numero = app(\App\Services\AutoNumberingService::class)->generate('transaction');
                    } catch (\Exception $e) {
                        $numero = 'TON-' . strtoupper(Str::random(6));
                    }
                    
                    // Créer l'enregistrement de paiement
                    $paiement = Paiement::create([
                        'numero'        => $numero,
                        'membre_id'     => $membre->id,
                        'cotisation_id' => null, // Direct tontine deposit
                        'caisse_id'     => $caisseTontine->id,
                        'montant'       => $montant,
                        'date_paiement' => $dateDepot,
                        'mode_paiement' => 'mobile_money',
                        'statut'        => 'valide',
                        'notes'         => 'Dépôt tontine initial (Seed)',
                        'created_at'    => $dateDepot,
                        'updated_at'    => $dateDepot,
                    ]);
                    
                    // Créer le mouvement de caisse pour le membre (sens = entree)
                    MouvementCaisse::create([
                        'caisse_id'      => $caisseTontine->id,
                        'type'           => 'tontine',
                        'sens'           => 'entree',
                        'montant'        => $montant,
                        'date_operation' => $dateDepot,
                        'libelle'        => 'Versement Échéance Tontine (Seed)',
                        'reference_type' => Paiement::class,
                        'reference_id'   => $paiement->id,
                        'created_at'     => $dateDepot,
                        'updated_at'     => $dateDepot,
                    ]);
                    
                    // Réconciliation globale tontine
                    if ($caisseGlobalTontine) {
                        MouvementCaisse::create([
                            'caisse_id'      => $caisseGlobalTontine->id,
                            'type'           => 'tontine',
                            'sens'           => 'entree',
                            'montant'        => $montant,
                            'date_operation' => $dateDepot,
                            'libelle'        => 'RÉCONCILIATION GLOBALE TONTINE - Client #' . $membre->id,
                            'reference_type' => Paiement::class,
                            'reference_id'   => $paiement->id,
                            'created_at'     => $dateDepot,
                            'updated_at'     => $dateDepot,
                        ]);
                    }
                    
                    $depotCreated++;
                }
            }
        }
        
        $this->command->info("{$depotCreated} dépôt(s) d'épargne/tontine créé(s) avec succès pour créditer les comptes des membres.");
    }
}

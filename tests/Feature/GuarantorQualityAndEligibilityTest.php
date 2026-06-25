<?php

namespace Tests\Feature;

use App\Models\Membre;
use App\Models\EpargnePlan;
use App\Models\EpargneSouscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuarantorQualityAndEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarantor_quality_and_tontine_summation()
    {
        // 1. Create a member
        $membre = Membre::create([
            'numero' => 'CLI-000001',
            'nom' => 'LOURE',
            'prenom' => 'Abdoulaye',
            'sexe' => 'M',
            'email' => 'abdoulaye@example.com',
            'telephone' => '+22670000001',
            'password' => bcrypt('password'),
            'date_adhesion' => now(),
            'statut' => 'actif',
            'garant_qualite' => 0,
            'garant_solde' => 0,
        ]);

        // 2. Initially, tontine balance is 0, so quality is 0
        $this->assertEquals(0, $membre->totalEpargneSolde());
        $this->assertEquals(0, $membre->garant_qualite_effective);

        // 3. Create an epargne plan
        $plan = EpargnePlan::create([
            'nom' => 'Plan Test',
            'montant_min' => 1000,
            'montant_max' => 50000,
            'frequence' => 'mensuel',
            'taux_remuneration' => 5,
            'duree_mois' => 3,
        ]);

        // 4. Create a tontine subscription with 15000 XOF balance
        EpargneSouscription::create([
            'membre_id' => $membre->id,
            'plan_id' => $plan->id,
            'montant' => 5000,
            'date_debut' => now(),
            'date_fin' => now()->addMonths(3),
            'jour_du_mois' => 10,
            'statut' => 'active',
            'solde_courant' => 15000,
        ]);

        // 5. Test summation and quality score
        $membre->refresh();
        $this->assertEquals(15000, $membre->totalEpargneSolde());
        $this->assertEquals(1, $membre->garant_qualite_effective);
    }
}

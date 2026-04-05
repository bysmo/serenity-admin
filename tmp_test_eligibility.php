<?php

use App\Models\Membre;
use App\Models\NanoCredit;
use App\Models\NanoCreditGarant;
use App\Models\NanoCreditType;
use App\Models\NanoCreditPalier;
use Illuminate\Support\Facades\Schema;

// Mocking some data for testing
try {
    $membre = new Membre();
    $membre->statut = 'actif';
    $membre->garant_qualite = 0;
    
    $type = new NanoCreditType();
    $type->min_epargne_percent = 85;
    
    $palier = new NanoCreditPalier();
    $palier->min_garant_qualite = 0;
    
    $credit = new NanoCredit();
    $credit->montant = 100000;
    $credit->setRelation('nanoCreditType', $type);
    $credit->setRelation('palier', $palier);
    
    echo "Testing eligibility...\n";
    
    // Test 1: No savings
    $eligible = NanoCreditGarant::membreEstEligibleGarant($membre, $credit);
    echo "Eligible without savings: " . ($eligible ? 'Yes' : 'No') . " (Expected: No)\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

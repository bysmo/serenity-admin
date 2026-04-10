<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Paiement;
use App\Models\NanoCredit;
use App\Models\NanoCreditGarant;
use App\Models\NanoCreditEcheance;
use App\Models\NanoCreditVersement;
use App\Models\EpargneSouscription;
use App\Models\EpargneVersement;
use App\Models\EpargneEcheance;
use App\Models\Remboursement;
use App\Models\Membre;

class InitializeChecksums extends Command
{
    protected $signature   = 'audit:initialize';
    protected $description = 'Initialiser/reconstruire les checksums et la chaîne Merkle depuis l\'état réel de la BD';

    public function handle()
    {
        $models = [
            Paiement::class,
            NanoCredit::class,
            NanoCreditGarant::class,
            NanoCreditEcheance::class,
            NanoCreditVersement::class,
            EpargneSouscription::class,
            EpargneVersement::class,
            EpargneEcheance::class,
            Remboursement::class,
            Membre::class,
            \App\Models\Cotisation::class,
            \App\Models\EpargnePlan::class,
            \App\Models\PaymentMethod::class,
            \App\Models\AppSetting::class,
            \App\Models\NanoCreditPalier::class,
            \App\Models\ParrainageConfig::class,
        ];

        $this->info('Réinitialisation du Ledger cryptographique (Hash Chain Merkle)...');
        \App\Models\SystemMerkleLedger::truncate();

        foreach ($models as $modelClass) {
            $instance  = new $modelClass;
            $tableName = $instance->getTable();
            $keyName   = $instance->getKeyName();

            $this->info("Initialisation de la table : {$tableName}...");
            $count = 0;

            // On utilise chunk() : chaque $record a les valeurs BRUTES de la BD dans getAttributes().
            // On calcule le checksum depuis ces valeurs brutes (computeChecksumFromRaw) et on l'écrit
            // via DB::table() pour ne PAS déclencher les événements Eloquent (éviter le double ledger).
            $modelClass::chunk(200, function ($records) use ($tableName, $keyName, &$count) {
                foreach ($records as $record) {
                    // 1. Calculer le checksum canonique depuis les attributs bruts BD
                    $rawAttributes = $record->getAttributes();
                    $checksum      = $record->computeChecksumFromRaw($rawAttributes);

                    // 2. Écrire le checksum en BD sans passer par Eloquent (pas d'événements)
                    DB::table($tableName)
                        ->where($keyName, $record->getKey())
                        ->update(['checksum' => $checksum]);

                    // 3. Mettre à jour en mémoire pour appendToMerkleLedger
                    $record->checksum = $checksum;

                    // 4. Ajouter au Ledger Merkle
                    if (method_exists($record, 'appendToMerkleLedger')) {
                        $record->appendToMerkleLedger('created');
                    }

                    $count++;
                }
            });

            $this->info("{$count} enregistrements traités dans {$tableName}.");
        }

        $this->info('Initialisation terminée avec succès.');
        $this->info('La chaîne Merkle est maintenant synchronisée avec l\'état réel de la BD.');
        return 0;
    }
}

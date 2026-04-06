<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialiser les checksums et chiffrer les montants existants';

    /**
     * Execute the console command.
     */
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
        ];

        foreach ($models as $modelClass) {
            $tableName = (new $modelClass)->getTable();
            $this->info("Initialisation de la table : {$tableName}...");
            
            $count = 0;
            $modelClass::chunk(200, function ($records) use (&$count) {
                foreach ($records as $record) {
                    // En sauvegardant, le trait HasChecksum va générer le checksum
                    // et les cast EncryptedDecimal vont chiffrer les montants.
                    $record->save();
                    $count++;
                }
            });
            
            $this->info("{$count} enregistrements traités dans {$tableName}.");
        }

        $this->info("Initialisation terminée avec succès.");
        return 0;
    }
}

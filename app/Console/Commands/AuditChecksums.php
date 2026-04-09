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
use Illuminate\Support\Facades\Log;

class AuditChecksums extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:checksums';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier l\'intégrité des données financières critiques via les checksums';

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

        $errors = 0;
        $totalChecked = 0;

        $corruptedDataDetails = [];

        foreach ($models as $modelClass) {
            $tableName = (new $modelClass)->getTable();
            $this->info("Audit de la table : {$tableName}...");
            
            $modelClass::chunk(500, function ($records) use (&$errors, &$totalChecked, $tableName, &$corruptedDataDetails) {
                foreach ($records as $record) {
                    $totalChecked++;
                    
                    if (!$record->verifyChecksum()) {
                        $errors++;
                        $message = "ALERTE SÉCURITÉ : Intégrité compromise dans {$tableName} (ID: {$record->id})";
                        
                        $this->error($message);
                        
                        Log::channel('security')->alert($message, [
                            'table' => $tableName,
                            'id' => $record->id,
                            'expected' => $record->calculateChecksum(),
                            'actual' => $record->checksum,
                            'data' => $record->getAttributes()
                        ]);

                        // Enregistrer un résumé pour le gadget/log
                        $corruptedDataDetails[] = [
                            'table' => $tableName,
                            'id'    => $record->id,
                            'error' => 'Checksum Mismatch',
                        ];
                    }
                }
            });
        }

        $this->info("Audit terminé.");
        $this->info("- Éléments vérifiés : {$totalChecked}");
        $this->info("- Éléments corrompus : {$errors}");

        // 1. Sauvegarde en Base de Données
        \App\Models\AuditChecksumLog::create([
            'is_valid'           => $errors === 0,
            'rows_checked_count' => $totalChecked,
            'corrupted_count'    => $errors,
            'corrupted_data'     => empty($corruptedDataDetails) ? null : $corruptedDataDetails,
        ]);

        // 2. Mise en cache pour le Widget (Gadget) UI
        \Illuminate\Support\Facades\Cache::put('audit_checksums_status', [
            'is_valid'           => $errors === 0,
            'rows_checked_count' => $totalChecked,
            'corrupted_count'    => $errors,
            'corrupted_data'     => empty($corruptedDataDetails) ? null : $corruptedDataDetails,
            'last_check_time'    => now()->timestamp,
            // La commande tourne toutes les 10 minutes (selon routes/console.php)
            'next_check_time'    => now()->addMinutes(10)->timestamp,
        ]);

        if ($errors > 0) {
            $this->warn("Attention : {$errors} erreurs d'intégrité ont été détectées. Consultez storage/logs/security.log");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

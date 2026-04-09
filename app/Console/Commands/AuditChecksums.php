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
            \App\Models\Cotisation::class,
            \App\Models\EpargnePlan::class,
            \App\Models\PaymentMethod::class,
            \App\Models\AppSetting::class,
            \App\Models\NanoCreditPalier::class,
            \App\Models\ParrainageConfig::class,
        ];

        $errors = 0;
        $totalChecked = 0;
        $corruptedDataDetails = [];

        // ----------------------------------------------------
        // PASSE 1 : Validation de l'intégrité de la chaîne Merkle
        // ----------------------------------------------------
        $this->info("Validation de l'intégrité globale de la chaîne Merkle (Ledger)...");
        
        $previousHash = null;
        $ledgerIntegrityBroken = false;
        $aliveRecordsPerTable = [];

        if (\Illuminate\Support\Facades\Schema::hasTable('system_merkle_ledgers')) {
            \App\Models\SystemMerkleLedger::orderBy('id')->chunk(2000, function ($ledgers) use (&$previousHash, &$ledgerIntegrityBroken, &$errors, &$corruptedDataDetails, &$aliveRecordsPerTable) {
                if ($ledgerIntegrityBroken) return false;

                foreach ($ledgers as $ledger) {
                    $key = config('app.key');
                    $payload = implode('|', [
                        $ledger->table_name,
                        $ledger->record_id,
                        $ledger->action,
                        $ledger->record_checksum ?? '',
                        $previousHash ?? ''
                    ]);
                    $expectedHash = hash_hmac('sha256', $payload, $key);

                    if ($expectedHash !== $ledger->hash_chain) {
                        $errors++;
                        $ledgerIntegrityBroken = true;
                        
                        $msg = "ALERTE CRITIQUE : La chaîne Merkle est corrompue à l'ID {$ledger->id} (Manipulation SQL du Ledger).";
                        $this->error($msg);
                        Log::channel('security')->alert($msg);

                        $corruptedDataDetails[] = [
                            'model'  => 'SystemMerkleLedger',
                            'table'  => 'system_merkle_ledgers',
                            'id'     => $ledger->id,
                            'impacted_columns' => [['field' => 'hash_chain', 'expected' => $expectedHash, 'actual' => $ledger->hash_chain]],
                            'origin' => 'Manipulation et destruction du Ledger cryptographique'
                        ];
                        return false; // Break chunking
                    }
                    $previousHash = $ledger->hash_chain;

                    // Compute "alive" records
                    $table = $ledger->table_name;
                    $recordId = (int)$ledger->record_id;
                    if (!isset($aliveRecordsPerTable[$table])) {
                        $aliveRecordsPerTable[$table] = [];
                    }
                    
                    if ($ledger->action === 'deleted') {
                        unset($aliveRecordsPerTable[$table][$recordId]);
                    } else {
                        $aliveRecordsPerTable[$table][$recordId] = true;
                    }
                }
            });
        }

        // ----------------------------------------------------
        // PASSE 2 & 3 : Vérification de Consistance et Checksums
        // ----------------------------------------------------
        foreach ($models as $modelClass) {
            $tableName = (new $modelClass)->getTable();
            $this->info("Audit de la table : {$tableName}...");
            
            $expectedIds = isset($aliveRecordsPerTable[$tableName]) ? array_keys($aliveRecordsPerTable[$tableName]) : [];
            $actualIds = [];
            
            $modelClass::chunk(500, function ($records) use (&$errors, &$totalChecked, $tableName, &$corruptedDataDetails, $modelClass, &$actualIds) {
                foreach ($records as $record) {
                    $totalChecked++;
                    $actualIds[] = $record->id;
                    
                    // Vérification de la signature statique intra-ligne
                    if (!$record->verifyChecksum()) {
                        $errors++;
                        
                        $lastLog = \App\Models\AuditLog::where('model', $modelClass)
                            ->where('model_id', $record->id)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        $impactedColumns = [];
                        $origin = 'Inconnu (Manipulation SQL possible)';
                        $userRef = null;
                        
                        $currentAttrs = $record->getAttributes();
                        
                        if ($lastLog && !empty($lastLog->new_values)) {
                            $legitimateAttrs = $lastLog->new_values;
                            $ignoredKeys = ['id', 'checksum', 'created_at', 'updated_at', 'deleted_at'];
                            
                            foreach ($currentAttrs as $key => $currVal) {
                                if (in_array($key, $ignoredKeys)) continue;
                                $legitVal = $legitimateAttrs[$key] ?? null;
                                if ((string)$currVal !== (string)$legitVal) {
                                    $impactedColumns[] = ['field' => $key, 'expected' => $legitVal, 'actual' => $currVal];
                                }
                            }
                            $origin = "Bypass Applicatif ou Injection Récente";
                            if ($lastLog->user_id) {
                                $origin = "Modification par User ID: " . $lastLog->user_id;
                                $userRef = $lastLog->user_id;
                            }
                        } else {
                            $origin = "Aucune trace applicative (Manipulation SQL pure)";
                        }

                        $message = "ALERTE SÉCURITÉ : Intégrité compromise dans {$tableName} (ID: {$record->id})";
                        $this->error($message);
                        Log::channel('security')->alert($message, ['table' => $tableName, 'id' => $record->id, 'origin' => $origin]);

                        $corruptedDataDetails[] = [
                            'model'  => $modelClass,
                            'table'  => $tableName,
                            'id'     => $record->id,
                            'impacted_columns' => $impactedColumns,
                            'origin' => $origin,
                            'user_id'=> $userRef
                        ];
                    }
                }
            });

            // Si le Ledger est intact, on audite les Insertions et Suppressions
            if (!$ledgerIntegrityBroken && \Illuminate\Support\Facades\Schema::hasTable('system_merkle_ledgers')) {
                // Fantômes (Insérés directement en SQL, contournent l'app)
                $phantoms = array_diff($actualIds, $expectedIds);
                foreach ($phantoms as $phantomId) {
                    $errors++;
                    $corruptedDataDetails[] = [
                        'model'  => $modelClass,
                        'table'  => $tableName,
                        'id'     => $phantomId,
                        'impacted_columns' => [['field' => 'ligne', 'expected' => 'absent (non tracé)', 'actual' => 'présent']],
                        'origin' => 'Insertion SQL non autorisée (Fantôme)'
                    ];
                    $this->error("Fantôme détecté dans {$tableName} (ID: {$phantomId})");
                    Log::channel('security')->alert("Insertion SQL détectée dans {$tableName} (ID: {$phantomId})");
                }

                // Disparus (Supprimés directement en SQL, contournent l'app)
                $missing = array_diff($expectedIds, $actualIds);
                foreach ($missing as $missingId) {
                    $errors++;
                    $corruptedDataDetails[] = [
                        'model'  => $modelClass,
                        'table'  => $tableName,
                        'id'     => $missingId,
                        'impacted_columns' => [['field' => 'ligne', 'expected' => 'présent (tracé)', 'actual' => 'absent']],
                        'origin' => 'Suppression SQL non autorisée (Evaporation)'
                    ];
                    $this->error("Évaporation détectée dans {$tableName} (ID: {$missingId})");
                    Log::channel('security')->alert("Suppression SQL détectée dans {$tableName} (ID: {$missingId})");
                }
            }
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

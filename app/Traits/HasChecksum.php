<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait HasChecksum
{
    /**
     * Hook Eloquent events.
     *
     * STRATÉGIE : Le checksum est calculé APRÈS la sauvegarde en BD (événement 'saved'),
     * à partir des valeurs RÉELLES stockées en base. Cela garantit une cohérence totale
     * entre le checksum écrit et le checksum relu, peu importe les casts Eloquent.
     *
     * Le problème précédent : calculer dans 'saving' (AVANT INSERT) produit un checksum
     * basé sur un sous-ensemble de colonnes (celles passées à create()). Lors de la
     * vérification, le modèle chargé depuis la BD contient TOUTES les colonnes (NULL
     * pour les non-renseignées), produisant un hash différent → fausse détection.
     */
    public static function bootHasChecksum(): void
    {
        static::saved(function ($model) {
            // On détermine si c'est une création ou une mise à jour
            $action = $model->wasRecentlyCreated ? 'created' : 'updated';

            // 1. Calculer le checksum depuis l'état RÉEL en base de données
            //    (toutes les colonnes, telles qu'elles existent vraiment en BD)
            $rawRow = (array) DB::table($model->getTable())
                ->where($model->getKeyName(), $model->getKey())
                ->first();

            if (!empty($rawRow)) {
                $checksum = $model->computeChecksumFromRaw($rawRow);

                // Stocker le checksum via DB::table() (sans déclencher d'événements Eloquent)
                DB::table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['checksum' => $checksum]);

                // Garder la cohérence en mémoire
                $model->checksum = $checksum;
            }

            // 2. Ajouter au Ledger Merkle (utilise $model->checksum qui est maintenant correct)
            $model->appendToMerkleLedger($action);

            // 3. Journal d'audit CRUD précis
            $model->createDetailedAuditLog($action);
        });

        static::deleted(function ($model) {
            $model->appendToMerkleLedger('deleted');
            $model->createDetailedAuditLog('deleted');
        });
    }

    /**
     * Fonction de calcul de checksum CANONIQUE.
     *
     * Travaille sur les valeurs BRUTES (format BD : strings et nulls).
     * C'est LA référence utilisée à la fois lors de l'écriture et de la vérification.
     *
     * @param array $rawAttributes Valeurs brutes telles que stockées en BD (via DB::table->first())
     */
    public function computeChecksumFromRaw(array $rawAttributes): string
    {
        // Colonnes systèmes exclues du calcul (varient sans modification métier)
        $excluded = ['id', 'checksum', 'created_at', 'updated_at', 'deleted_at'];

        $normalized = [];
        foreach ($rawAttributes as $key => $value) {
            if (in_array($key, $excluded)) {
                continue;
            }
            // En BD, tout est string ou null. On normalise en string pour garantir
            // l'identité avec ce que PDO retourne à la vérification.
            $normalized[$key] = is_null($value) ? null : (string) $value;
        }

        // Tri alphabétique des clés → ordre deterministe
        ksort($normalized);

        return hash_hmac('sha256', json_encode($normalized), config('app.key'));
    }

    /**
     * Calculer le checksum depuis les attributs courants du modèle.
     * Quand le modèle est chargé depuis la BD, $this->getAttributes() = format brut BD.
     */
    public function calculateChecksum(): string
    {
        return $this->computeChecksumFromRaw($this->getAttributes());
    }

    /**
     * Vérifier l'intégrité du modèle en comparant checksum stocké vs recalculé.
     * Les deux utilisent le format brut BD → comparaison stable.
     */
    public function verifyChecksum(): bool
    {
        if (empty($this->checksum)) {
            return false;
        }

        // Le modèle est chargé depuis la BD via chunk() → getAttributes() = format brut BD
        $recomputed = $this->computeChecksumFromRaw($this->getAttributes());

        // hash_equals() protège contre les timing attacks
        return hash_equals($this->checksum, $recomputed);
    }

    /**
     * Ajoute une entrée dans le Ledger Immuable (Hash Chain Merkle).
     */
    public function appendToMerkleLedger(string $action): void
    {
        if (!Schema::hasTable('system_merkle_ledgers')) {
            return;
        }

        $tableName = $this->getTable();
        $recordId  = $this->getKey();
        $checksum  = $action === 'deleted' ? null : $this->checksum;

        DB::transaction(function () use ($tableName, $recordId, $action, $checksum) {
            // Verrou exclusif → évite les forks de chaîne en haute concurrence
            $lastLedger   = \App\Models\SystemMerkleLedger::lockForUpdate()->orderBy('id', 'desc')->first();
            $previousHash = $lastLedger ? $lastLedger->hash_chain : null;

            $key     = config('app.key');
            $payload = implode('|', [
                $tableName,
                $recordId,
                $action,
                $checksum  ?? '',
                $previousHash ?? '',
            ]);

            $hashChain = hash_hmac('sha256', $payload, $key);

            \App\Models\SystemMerkleLedger::create([
                'table_name'      => $tableName,
                'record_id'       => $recordId,
                'action'          => $action,
                'record_checksum' => $checksum,
                'previous_hash'   => $previousHash,
                'hash_chain'      => $hashChain,
            ]);
        });
    }

    /**
     * Génère un journal AuditLog détaillé (delta Avant/Après + contexte utilisateur).
     */
    public function createDetailedAuditLog(string $action): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        // Récupérer l'acteur : admin web > membre (guard membre) > null (CLI/Cron)
        $actorId   = null;
        $actorType = null;

        if (auth()->check()) {
            $actorId   = auth()->id();
            $actorType = \App\Models\User::class;
        } elseif (auth('membre')->check()) {
            $actorId   = auth('membre')->id();
            $actorType = \App\Models\Membre::class;
        }

        $ip        = request()->ip()        ?? '127.0.0.1';
        $userAgent = request()->userAgent() ?? 'System / CLI';

        $oldValues   = [];
        $newValues   = [];
        $ignoredKeys = ['checksum', 'created_at', 'updated_at', 'deleted_at'];

        if ($action === 'created') {
            $newValues = array_diff_key($this->getAttributes(), array_flip($ignoredKeys));

        } elseif ($action === 'updated') {
            $oldValuesDirty = array_intersect_key($this->getOriginal(), $this->getChanges());
            $newValuesDirty = $this->getChanges();

            $oldValues = array_diff_key($oldValuesDirty, array_flip($ignoredKeys));
            $newValues = array_diff_key($newValuesDirty,  array_flip($ignoredKeys));

            // Si seul le checksum ou les timestamps ont changé → pas de log métier utile
            if (empty($newValues)) {
                return;
            }
        } elseif ($action === 'deleted') {
            $oldValues = array_diff_key($this->getAttributes(), array_flip($ignoredKeys));
        }

        \App\Models\AuditLog::create([
            'actor_id'   => $actorId,
            'actor_type' => $actorType,
            'action'     => $action,
            'model'      => get_class($this),
            'model_id'   => $this->getKey(),
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'description'=> "Enregistrement {$action} automatiquement tracé.",
        ]);
    }
}

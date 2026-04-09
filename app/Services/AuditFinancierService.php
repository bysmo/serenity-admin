<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Journal append-only des transactions financières.
 * Chaque enregistrement : transaction_id, montant, compte_source/dest, timestamp précis,
 * hash_chain (SHA-256 de la ligne précédente), signature (HMAC-SHA256 des données brutes).
 */
class AuditFinancierService
{
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('audit.secret_key', config('app.key'));
        if (strlen($this->secretKey) < 16) {
            throw new \RuntimeException('Audit secret key (AUDIT_FINANCIER_SECRET or APP_KEY) must be at least 16 bytes.');
        }
    }

    /**
     * Récupérer le hash_chain de la dernière ligne (pour chaînage).
     */
    public function getLastHash(): ?string
    {
        $row = DB::table('audit_financier')->orderBy('id', 'desc')->first();
        return $row ? $row->hash_chain : null;
    }

    /**
     * Construire la chaîne de données brutes pour signature et hash.
     */
    protected function buildRawPayload(array $data): string
    {
        $parts = [
            $data['transaction_id'] ?? '',
            $data['type_transaction'] ?? '',
            (string) ($data['montant'] ?? '0'),
            (string) ($data['compte_source_id'] ?? ''),
            (string) ($data['compte_dest_id'] ?? ''),
            $data['reference_type'] ?? '',
            (string) ($data['reference_id'] ?? ''),
            (string) ($data['timestamp_precis'] ?? ''),
            $data['hash_previous'] ?? '',
        ];
        return implode('|', $parts);
    }

    /**
     * Ajouter un enregistrement au journal (append-only).
     *
     * @param  array{type_transaction: string, montant: float|int, compte_source_id?: int|null, compte_dest_id?: int|null, reference_type?: string|null, reference_id?: int|null}  $data
     */
    public function append(array $data): void
    {
        $transactionId = $data['transaction_id'] ?? Str::uuid()->toString();
        $timestampPrecis = $data['timestamp_precis'] ?? microtime(true);
        $hashPrevious = $this->getLastHash();
        $firstRecord = $hashPrevious === null;
        $hashPrevious = $hashPrevious ?? '';

        $payload = [
            'transaction_id'   => $transactionId,
            'type_transaction' => $data['type_transaction'],
            'montant'          => (float) ($data['montant'] ?? 0),
            'compte_source_id' => $data['compte_source_id'] ?? null,
            'compte_dest_id'   => $data['compte_dest_id'] ?? null,
            'reference_type'   => $data['reference_type'] ?? null,
            'reference_id'     => $data['reference_id'] ?? null,
            'timestamp_precis' => $timestampPrecis,
            'hash_previous'    => $hashPrevious,
        ];

        $raw = $this->buildRawPayload($payload);
        $signature = hash_hmac('sha256', $raw, $this->secretKey);
        $hashChain = hash('sha256', $raw);

        DB::table('audit_financier')->insert([
            'transaction_id'   => $transactionId,
            'type_transaction' => $payload['type_transaction'],
            'montant'          => $payload['montant'],
            'compte_source_id' => $payload['compte_source_id'],
            'compte_dest_id'   => $payload['compte_dest_id'],
            'reference_type'   => $payload['reference_type'],
            'reference_id'     => $payload['reference_id'],
            'timestamp_precis' => $payload['timestamp_precis'],
            'hash_chain'       => $hashChain,
            'signature'        => $signature,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Enregistrer un mouvement caisse dans l'audit (entrée ou sortie sur une caisse).
     */
    public function appendMouvement(string $typeTransaction, float $montant, ?int $caisseId, string $sens, ?string $referenceType = null, ?int $referenceId = null): void
    {
        $compteSource = $sens === 'sortie' ? $caisseId : null;
        $compteDest   = $sens === 'entree' ? $caisseId : null;
        $this->append([
            'transaction_id'   => Str::uuid()->toString(),
            'type_transaction' => $typeTransaction,
            'montant'          => $montant,
            'compte_source_id' => $compteSource,
            'compte_dest_id'   => $compteDest,
            'reference_type'   => $referenceType,
            'reference_id'     => $referenceId,
        ]);
    }

    /**
     * Vérifier l'intégrité de la chaîne (hash_chain cohérent).
     * Retourne [ 'valid' => bool, 'first_broken_id' => int|null, 'rows_checked_count' => int, 'broken_row_data' => array|null ].
     */
    public function verifyChain(): array
    {
        $rows = DB::table('audit_financier')->orderBy('id')->get();
        $previousHash = null;
        $count = 0;
        
        foreach ($rows as $row) {
            $count++;
            $payload = $this->buildRawPayload([
                'transaction_id'   => $row->transaction_id,
                'type_transaction' => $row->type_transaction,
                'montant'          => (float) $row->montant,
                'compte_source_id' => $row->compte_source_id,
                'compte_dest_id'   => $row->compte_dest_id,
                'reference_type'   => $row->reference_type,
                'reference_id'     => $row->reference_id,
                'timestamp_precis' => $row->timestamp_precis,
                'hash_previous'    => $previousHash ?? '',
            ]);
            
            $expectedHash = hash('sha256', $payload);
            
            if ($expectedHash !== $row->hash_chain) {
                return [
                    'valid'              => false, 
                    'first_broken_id'    => $row->id,
                    'rows_checked_count' => $count,
                    'broken_row_data'    => (array) $row,
                ];
            }
            $previousHash = $row->hash_chain;
        }
        
        return [
            'valid'              => true, 
            'first_broken_id'    => null,
            'rows_checked_count' => $count,
            'broken_row_data'    => null,
        ];
    }
}

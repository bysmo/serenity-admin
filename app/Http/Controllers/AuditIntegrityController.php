<?php

namespace App\Http\Controllers;

use App\Models\SystemMerkleLedger;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditIntegrityController extends Controller
{
    /**
     * Visualisation de la chaîne Merkle globale
     */
    public function ledger(Request $request)
    {
        $filterTable = $request->input('table');
        $filterAction = $request->input('action');

        $query = SystemMerkleLedger::orderBy('id', 'desc');

        if ($filterTable) {
            $query->where('table_name', $filterTable);
        }
        if ($filterAction) {
            $query->where('action', $filterAction);
        }

        $ledgers = $query->paginate(50);

        // Listes pour les filtres
        $availableTables = SystemMerkleLedger::distinct()->pluck('table_name')->sort()->values();
        $availableActions = ['created', 'updated', 'deleted'];

        // Statistiques globales
        $stats = [
            'total' => SystemMerkleLedger::count(),
            'created' => SystemMerkleLedger::where('action', 'created')->count(),
            'updated' => SystemMerkleLedger::where('action', 'updated')->count(),
            'deleted' => SystemMerkleLedger::where('action', 'deleted')->count(),
        ];

        // Vérification rapide de l'intégrité de la chaîne (50 dernières)
        $chainOk = true;
        $chainBrokenAt = null;
        $previousHash = null;
        $sampleLedgers = SystemMerkleLedger::orderBy('id')->take(1000)->get();
        $key = config('app.key');

        foreach ($sampleLedgers as $ledger) {
            $payload = implode('|', [
                $ledger->table_name,
                $ledger->record_id,
                $ledger->action,
                $ledger->record_checksum ?? '',
                $previousHash ?? ''
            ]);
            $expectedHash = hash_hmac('sha256', $payload, $key);

            if ($expectedHash !== $ledger->hash_chain) {
                $chainOk = false;
                $chainBrokenAt = $ledger->id;
                break;
            }
            $previousHash = $ledger->hash_chain;
        }

        return view('audit-integrity.ledger', compact(
            'ledgers', 'availableTables', 'availableActions',
            'stats', 'chainOk', 'chainBrokenAt',
            'filterTable', 'filterAction'
        ));
    }

    /**
     * Journal des modifications traçées par les utilisateurs (AuditLog CRUD)
     */
    public function changes(Request $request)
    {
        $filterModel = $request->input('model');
        $filterAction = $request->input('action');
        $filterUser = $request->input('user_id');
        $search = $request->input('search');

        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($filterModel) {
            $query->where('model', 'like', "%{$filterModel}%");
        }
        if ($filterAction) {
            $query->where('action', $filterAction);
        }
        if ($filterUser) {
            $query->where('user_id', $filterUser);
        }

        $logs = $query->paginate(30);

        // Listes pour les filtres
        $availableModels = AuditLog::distinct()->pluck('model')
            ->map(fn($m) => class_basename($m))
            ->unique()->sort()->values();

        $availableActions = ['created', 'updated', 'deleted'];

        // Utilisateurs ayant généré des actions
        $activeUsers = \App\Models\User::whereIn('id', AuditLog::distinct()->pluck('user_id')->filter())
            ->orderBy('name')->get();

        // Stats globales
        $stats = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', today())->count(),
            'created' => AuditLog::where('action', 'created')->count(),
            'updated' => AuditLog::where('action', 'updated')->count(),
            'deleted' => AuditLog::where('action', 'deleted')->count(),
        ];

        return view('audit-integrity.changes', compact(
            'logs', 'availableModels', 'availableActions',
            'activeUsers', 'stats',
            'filterModel', 'filterAction', 'filterUser', 'search'
        ));
    }
}

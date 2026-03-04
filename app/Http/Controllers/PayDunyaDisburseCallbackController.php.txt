<?php

namespace App\Http\Controllers;

use App\Models\NanoCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Callback appelé par PayDunya lorsque le statut d'un déboursement (nano crédit) est connu.
 * Pas d'authentification : PayDunya envoie une requête POST avec status, disburse_id, etc.
 */
class PayDunyaDisburseCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info('PayDunya Disburse callback reçu', $request->all());

        $disburseId = $request->input('disburse_id');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');

        if (!$disburseId || !$status) {
            Log::warning('PayDunya Disburse callback: disburse_id ou status manquant');
            return response()->json(['ok' => false], 400);
        }

        $nanoCredit = NanoCredit::where('disburse_id', (string) $disburseId)->first();
        if (!$nanoCredit) {
            Log::warning('PayDunya Disburse callback: NanoCredit introuvable', ['disburse_id' => $disburseId]);
            return response()->json(['ok' => false], 404);
        }

        $nanoCredit->update([
            'statut' => $status,
            'transaction_id' => $transactionId ?? $nanoCredit->transaction_id,
            'callback_received' => true,
        ]);

        Log::info('PayDunya Disburse callback: NanoCredit mis à jour', [
            'id' => $nanoCredit->id,
            'statut' => $status,
        ]);

        return response()->json(['ok' => true]);
    }
}

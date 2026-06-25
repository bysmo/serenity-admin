<?php

namespace App\Http\Controllers;

use App\Models\NanoCredit;
use App\Models\PayDunyaConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Callback appelé par PayDunya lorsque le statut d'un déboursement (nano crédit) est connu.
 * Pas d'authentification : PayDunya envoie une requête POST avec status, disburse_id, etc.
 */
class PayDunyaDisburseCallbackController extends Controller
{
    /**
     * Statuts légitimes autorisés pour un déboursement PayDunya
     */
    private const ALLOWED_STATUSES = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'reversed'];

    public function __invoke(Request $request)
    {
        Log::info('PayDunya Disburse callback reçu', $request->all());

        // Validation stricte du payload
        $validated = $request->validate([
            'disburse_id'    => 'required|string|max:255',
            'status'         => 'required|string|in:' . implode(',', self::ALLOWED_STATUSES),
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $disburseId = $validated['disburse_id'];
        $status = $validated['status'];
        $transactionId = $validated['transaction_id'] ?? null;

        // Vérification de la signature HMAC PayDunya
        if (!$this->verifySignature($request)) {
            Log::warning('PayDunya Disburse callback: Signature invalide', [
                'disburse_id' => $disburseId,
            ]);
            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
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

    /**
     * Vérifier la signature HMAC du webhook PayDunya
     */
    private function verifySignature(Request $request): bool
    {
        $config = PayDunyaConfiguration::first();
        if (!$config || !$config->master_key) {
            Log::warning('PayDunya Disburse callback: Configuration ou master_key introuvable');
            return false;
        }

        $signature = $request->header('PayDunya-Signature');
        if (!$signature) {
            Log::warning('PayDunya Disburse callback: En-tête de signature manquant');
            return false;
        }

        $computed = hash_hmac('sha256', $request->getContent(), $config->master_key);
        return hash_equals($computed, $signature);
    }
}

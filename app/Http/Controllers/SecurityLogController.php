<?php

namespace App\Http\Controllers;

use App\Models\AuditChecksumLog;
use Illuminate\Http\Request;

class SecurityLogController extends Controller
{
    /**
     * Affiche l'historique des vérifications de l'intégrité de la base de données (Scans de Checksums).
     */
    public function index()
    {
        // On récupère les logs avec pagination (les plus récents en premier)
        $logs = AuditChecksumLog::orderBy('created_at', 'desc')->paginate(20);

        return view('audit-logs.security', compact('logs'));
    }

    /**
     * Lance manuellement un scan de sécurité sur demande de l'administrateur
     */
    public function scan()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('audit:checksums');
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            // On peut logger la sortie si besoin, mais le résultat est de toute façon enregistré en base
            // par la commande elle-même.
            return back()->with('success', 'Le scan manuel des checksums a été exécuté avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du lancement du scan manuel : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le rapport détaillé d'un scan spécifique.
     */
    public function show($id)
    {
        $log = AuditChecksumLog::findOrFail($id);
        
        return view('audit-logs.security-show', compact('log'));
    }

    /**
     * Applique une action de remédiation sur une donnée corrompue
     */
    public function remediate(Request $request)
    {
        $request->validate([
            'model'  => 'required|string',
            'id'     => 'required|integer',
            'action' => 'required|in:restore,suspend,accept',
        ]);

        $modelClass = $request->input('model');
        $recordId   = $request->input('id');
        $action     = $request->input('action');

        if (!class_exists($modelClass)) {
            return back()->with('error', 'Le modèle spécifié est introuvable.');
        }

        $record = $modelClass::find($recordId);

        // Autoriser 'restore' même si le record est absent (évaporation SQL)
        if (!$record && $action !== 'restore') {
            return back()->with('error', "L'enregistrement ID $recordId est introuvable.");
        }

        try {
            switch ($action) {
                case 'restore':
                    // On récupère TOUTE l'histoire légitime de cet enregistrement
                    $logs = \App\Models\AuditLog::where('model', $modelClass)
                        ->where('model_id', $recordId)
                        ->orderBy('created_at', 'asc') // Chronologique
                        ->get();

                    if ($logs->isEmpty()) {
                        return back()->with('error', 'Impossible de restaurer : aucune trace de cet enregistrement dans le journal d\'Audit.');
                    }

                    // Reconstruction de l'image saine par "replay" des logs successifs
                    $legitimateState = [];
                    foreach ($logs as $log) {
                        if ($log->action === 'created') {
                            $legitimateState = $log->new_values ?? [];
                        } elseif ($log->action === 'updated') {
                            $legitimateState = array_merge($legitimateState, $log->new_values ?? []);
                        }
                    }

                    if (empty($legitimateState)) {
                        return back()->with('error', 'L\'historique d\'audit est incohérent pour cet identifiant.');
                    }

                    // Si le record n'existe plus en base, on le recrée (sauf si ID n'est pas permis)
                    if (!$record) {
                        $record = new $modelClass();
                        $record->id = $recordId; 
                    }

                    // On applique les valeurs légitimes
                    $ignoredKeys = ['id', 'checksum', 'created_at', 'updated_at', 'deleted_at'];
                    foreach ($legitimateState as $key => $value) {
                         if (!in_array($key, $ignoredKeys) && array_key_exists($key, $record->getAttributes())) {
                             $record->{$key} = $value;
                         }
                    }
                    
                    // Sauvegarder et re-signer via le trait HasChecksum
                    $record->save();
                    
                    return back()->with('success', 'Restauration réussie. L\'enregistrement a été reconstitué à partir de son historique d\'audit et re-signé.');

                case 'accept':
                    $record->save();
                    return back()->with('success', 'Altération acceptée. Le Checksum a été recalculé pour s\'aligner avec les données actuelles.');

                case 'suspend':
                    $suspended = false;
                    if ($modelClass === \App\Models\Membre::class) {
                        $record->statut = 'suspendu';
                        $record->save();
                        $suspended = true;
                    } else {
                        $membreId = $record->membre_id ?? null;
                        if (!$membreId && method_exists($record, 'membre')) {
                            $membreId = $record->membre->id ?? null;
                        }

                        if ($membreId) {
                            $membre = \App\Models\Membre::find($membreId);
                            if ($membre) {
                                $membre->statut = 'suspendu';
                                $membre->save();
                                $suspended = true;
                            }
                        }
                    }

                    if ($suspended) {
                        return back()->with('success', 'Le compte Membre associé à cet enregistrement a été suspendu par mesure de sécurité.');
                    }
                    
                    return back()->with('error', "Impossible d'identifier un compte Membre à suspendre pour ce modèle.");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur remédiation : " . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la remédiation : ' . $e->getMessage());
        }
    }
}

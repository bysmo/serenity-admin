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
}

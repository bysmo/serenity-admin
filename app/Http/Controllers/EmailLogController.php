<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Helpers\SecurityHelper;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    /**
     * Afficher l'historique des emails
     */
    public function index(Request $request)
    {
        // Validation des filtres
        $request->validate([
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin'   => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
            'type'       => 'nullable|string|max:50',
            'statut'     => 'nullable|string|max:50',
            'search'     => 'nullable|string|max:255',
        ]);

        $query = EmailLog::with(['membre', 'campagne', 'paiement', 'engagement']);

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('destinataire_email', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('sujet', 'like', SecurityHelper::likeSearch($search))
                  ->orWhereHas('membre', function($q) use ($search) {
                      $q->where('nom', 'like', SecurityHelper::likeSearch($search))
                        ->orWhere('prenom', 'like', SecurityHelper::likeSearch($search))
                        ->orWhere('email', 'like', SecurityHelper::likeSearch($search));
                  });
            });
        }

        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $perPage = 20;
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Statistiques
        $stats = [
            'total' => EmailLog::count(),
            'envoyes' => EmailLog::where('statut', 'envoye')->count(),
            'echecs' => EmailLog::where('statut', 'echec')->count(),
            'en_attente' => EmailLog::where('statut', 'en_attente')->count(),
        ];

        // Statistiques par type
        $statsByType = [
            'campagne' => EmailLog::where('type', 'campagne')->count(),
            'paiement' => EmailLog::where('type', 'paiement')->count(),
            'engagement' => EmailLog::where('type', 'engagement')->count(),
            'fin_mois' => EmailLog::where('type', 'fin_mois')->count(),
            'rappel' => EmailLog::where('type', 'rappel')->count(),
        ];

        return view('email-logs.index', compact('logs', 'stats', 'statsByType'));
    }
}

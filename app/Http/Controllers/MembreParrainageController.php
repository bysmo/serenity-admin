<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\ParrainageConfig;
use App\Models\ParrainageCommission;
use App\Services\ParrainageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembreParrainageController extends Controller
{
    public function __construct(private ParrainageService $parrainageService) {}

    /**
     * Tableau de bord parrainage du membre
     */
    public function index()
    {
        $membre = Auth::guard('membre')->user();
        $config = ParrainageConfig::current();

        if (!$config->actif) {
            return view('parrainage.membre.inactif');
        }

        // Générer le code de parrainage si inexistant
        $membre->getOrCreateCodeParrainage();
        $membre->refresh();

        // Statistiques
        $stats = [
            'nb_filleuls'           => $membre->filleuls()->count(),
            'nb_filleuls_actifs'    => $membre->filleuls()->where('statut', 'actif')->count(),
            'total_disponible'      => $membre->totalCommissionsDisponibles(),
            'total_paye'            => $membre->totalCommissionsPayees(),
            'total_reclame'         => (float) $membre->commissionsParrainage()->where('statut', 'reclame')->sum('montant'),
            'total_en_attente'      => (float) $membre->commissionsParrainage()->where('statut', 'en_attente')->sum('montant'),
        ];

        // Derniers filleuls
        $derniersFilleuls = $membre->filleuls()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Commissions récentes
        $commissionsRecentes = $membre->commissionsParrainage()
            ->with('filleul')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $verification = $this->parrainageService->peutReclamer($membre);

        return view('parrainage.membre.index', compact(
            'membre', 'config', 'stats', 'derniersFilleuls', 'commissionsRecentes', 'verification'
        ));
    }

    /**
     * Liste complète des filleuls du membre
     */
    public function filleuls(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $config = ParrainageConfig::current();

        if (!$config->actif) {
            return redirect()->route('membre.parrainage.index');
        }

        $filleuls = $membre->filleuls()
            ->with('commissionFilleul')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('parrainage.membre.filleuls', compact('membre', 'filleuls', 'config'));
    }

    /**
     * Historique des commissions du membre
     */
    public function commissions(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $config = ParrainageConfig::current();

        if (!$config->actif) {
            return redirect()->route('membre.parrainage.index');
        }

        // Validation des filtres
        $request->validate([
            'statut' => 'nullable|string|in:en_attente,actif,expire',
        ]);

        $query = $membre->commissionsParrainage()
            ->with('filleul')
            ->orderByDesc('created_at');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $commissions = $query->paginate(20)->withQueryString();

        $stats = [
            'total_disponible' => $membre->totalCommissionsDisponibles(),
            'total_paye'       => $membre->totalCommissionsPayees(),
            'total_reclame'    => (float) $membre->commissionsParrainage()->where('statut', 'reclame')->sum('montant'),
            'total_en_attente' => (float) $membre->commissionsParrainage()->where('statut', 'en_attente')->sum('montant'),
        ];

        return view('parrainage.membre.commissions', compact('membre', 'commissions', 'stats', 'config'));
    }

    /**
     * Soumettre une réclamation pour les commissions disponibles
     */
    public function reclamer(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $config = ParrainageConfig::current();

        if (!$config->actif) {
            return back()->with('error', 'Le système de parrainage est actuellement désactivé.');
        }

        $resultat = $this->parrainageService->soumettreReclamation($membre);

        if ($resultat['success']) {
            return redirect()->route('membre.parrainage.commissions')
                ->with('success', $resultat['message']);
        }

        return back()->with('error', $resultat['message']);
    }

    /**
     * Afficher/régénérer le code de parrainage (API JSON)
     */
    public function getCode()
    {
        $membre = Auth::guard('membre')->user();
        $code   = $membre->getOrCreateCodeParrainage();

        return response()->json([
            'code'           => $code,
            'lien_inscription' => route('membre.register') . '?ref=' . $code,
        ]);
    }

    /**
     * Régénérer un nouveau code de parrainage
     */
    public function regenererCode()
    {
        $membre = Auth::guard('membre')->user();
        $code   = $membre->genererCodeParrainage();

        return redirect()->route('membre.parrainage.index')
            ->with('success', "Votre nouveau code de parrainage est : <strong>{$code}</strong>");
    }
}

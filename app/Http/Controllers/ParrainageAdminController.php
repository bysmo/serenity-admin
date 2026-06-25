<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\ParrainageConfig;
use App\Models\ParrainageCommission;
use App\Services\ParrainageService;
use App\Helpers\SecurityHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParrainageAdminController extends Controller
{
    public function __construct(private ParrainageService $parrainageService) {}

    // ─── Configuration ────────────────────────────────────────────────────────

    /**
     * Afficher la page de configuration du système de parrainage
     */
    public function config()
    {
        $config = ParrainageConfig::current();
        $stats  = $this->parrainageService->statsGlobales();

        return view('parrainage.admin.config', compact('config', 'stats'));
    }

    /**
     * Sauvegarder la configuration
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'actif'                  => 'boolean',
            'type_remuneration'      => 'required|in:fixe,pourcentage',
            'montant_remuneration'   => 'required|numeric|min:0',
            'declencheur'            => 'required|in:inscription,premier_paiement,adhesion_cotisation',
            'delai_validation_jours' => 'required|integer|min:0|max:365',
            'niveaux_parrainage'     => 'required|integer|min:1|max:3',
            'taux_niveau_2'          => 'nullable|numeric|min:0',
            'taux_niveau_3'          => 'nullable|numeric|min:0',
            'description'            => 'nullable|string|max:1000',
            'min_filleuls_retrait'   => 'required|integer|min:1',
            'montant_min_retrait'    => 'required|numeric|min:0',
        ]);

        $validated['actif'] = $request->boolean('actif');
        $validated['taux_niveau_2'] = $validated['taux_niveau_2'] ?? 0;
        $validated['taux_niveau_3'] = $validated['taux_niveau_3'] ?? 0;

        // Validation supplémentaire sur le pourcentage
        if ($validated['type_remuneration'] === 'pourcentage' && $validated['montant_remuneration'] > 100) {
            return back()->withErrors(['montant_remuneration' => 'Le pourcentage ne peut pas dépasser 100%.'])->withInput();
        }

        $config = ParrainageConfig::current();
        $config->update($validated);

        return redirect()->route('parrainage.admin.config')
            ->with('success', 'Configuration du parrainage mise à jour avec succès.');
    }

    // ─── Gestion des commissions ──────────────────────────────────────────────

    /**
     * Liste de toutes les commissions (avec filtres)
     */
    public function commissions(Request $request)
    {
        $query = ParrainageCommission::with(['parrain', 'filleul', 'traitePar'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('parrain', function ($q) use ($search) {
                $q->where('nom', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('prenom', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('numero', 'like', SecurityHelper::likeSearch($search));
            });
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $commissions = $query->paginate(20)->withQueryString();

        $stats = [
            'total_en_attente'   => ParrainageCommission::where('statut', 'en_attente')->sum('montant'),
            'total_disponible'   => ParrainageCommission::where('statut', 'disponible')->sum('montant'),
            'total_reclame'      => ParrainageCommission::where('statut', 'reclame')->sum('montant'),
            'total_paye'         => ParrainageCommission::where('statut', 'paye')->sum('montant'),
            'nb_reclames'        => ParrainageCommission::where('statut', 'reclame')->count(),
        ];

        return view('parrainage.admin.commissions', compact('commissions', 'stats'));
    }

    /**
     * Afficher le détail d'une commission
     */
    public function showCommission(ParrainageCommission $commission)
    {
        $commission->load(['parrain', 'filleul', 'traitePar']);
        return view('parrainage.admin.commission-show', compact('commission'));
    }

    /**
     * Approuver et payer une réclamation (une seule commission)
     */
    public function approuverCommission(Request $request, ParrainageCommission $commission)
    {
        if (!in_array($commission->statut, ['reclame', 'disponible'])) {
            return back()->with('error', 'Cette commission ne peut pas être approuvée dans son état actuel.');
        }

        $request->validate([
            'note_admin' => 'nullable|string|max:500',
            'via_pispi'  => 'boolean',
        ]);

        try {
            $this->parrainageService->payerCommission($commission, Auth::id(), $request->note_admin, $request->boolean('via_pispi'));
        } catch (\Exception $e) {
            return back()->with('error', "Erreur lors du paiement : " . $e->getMessage());
        }

        return back()->with('success', "Commission #{$commission->reference} approuvée et marquée comme payée.");
    }

    /**
     * Rejeter / annuler une commission
     */
    public function rejeterCommission(Request $request, ParrainageCommission $commission)
    {
        if (!in_array($commission->statut, ['reclame', 'en_attente', 'disponible'])) {
            return back()->with('error', 'Cette commission ne peut pas être annulée dans son état actuel.');
        }

        $request->validate([
            'note_admin' => 'required|string|max:500',
        ]);

        $commission->update([
            'statut'      => 'annule',
            'traite_par'  => Auth::id(),
            'note_admin'  => $request->note_admin,
        ]);

        return back()->with('success', "Commission #{$commission->reference} annulée.");
    }

    /**
     * Traitement en masse des réclamations (payer toutes les réclamations en attente)
     */
    public function payerToutesReclamations(Request $request)
    {
        $request->validate([
            'note_admin' => 'nullable|string|max:500',
            'via_pispi'  => 'boolean',
        ]);

        $commissions = ParrainageCommission::where('statut', 'reclame')->get();
        $count = 0;
        $errors = 0;
        $viaPiSpi = $request->boolean('via_pispi');

        foreach ($commissions as $commission) {
            try {
                $this->parrainageService->payerCommission($commission, Auth::id(), $request->note_admin ?? 'Paiement groupé', $viaPiSpi);
                $count++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur paiement commission (mass) #{$commission->id}: " . $e->getMessage());
                $errors++;
            }
        }

        $message = "{$count} réclamation(s) payée(s) avec succès.";
        if ($errors > 0) {
            $message .= " Cependant, {$errors} réclamation(s) ont échoué en raison d'erreurs financières. Consultez les logs pour plus de détails.";
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Liste des parrains actifs avec statistiques
     */
    public function parrains(Request $request)
    {
        $query = Membre::withCount('filleuls')
            ->withSum(['commissionsParrainage as total_disponible' => function ($q) {
                $q->where('statut', 'disponible');
            }], 'montant')
            ->withSum(['commissionsParrainage as total_paye' => function ($q) {
                $q->where('statut', 'paye');
            }], 'montant')
            ->withSum(['commissionsParrainage as total_reclame' => function ($q) {
                $q->where('statut', 'reclame');
            }], 'montant')
            ->having('filleuls_count', '>', 0)
            ->orderByDesc('filleuls_count');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('prenom', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('numero', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('code_parrainage', 'like', SecurityHelper::likeSearch($search));
            });
        }

        $parrains = $query->paginate(20)->withQueryString();

        return view('parrainage.admin.parrains', compact('parrains'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\Cotisation;
use App\Models\Engagement;
use App\Models\Membre;
use App\Models\MouvementCaisse;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Afficher le tableau de bord
     */
    public function index(Request $request)
    {
        // Validation des dates de période
        $validated = $request->validate([
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin'   => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
        ]);

        // Période par défaut (30 derniers jours)
        $dateDebut = $validated['date_debut'] ?? now()->subDays(30)->format('Y-m-d');
        $dateFin = $validated['date_fin'] ?? now()->format('Y-m-d');

        // Indicateurs généraux
        $totalMembres = Membre::where('statut', 'actif')->count();
        $totalCaisses = Caisse::where('statut', 'active')->count();
        $totalCotisations = Cotisation::where('actif', true)->count();
        // Total des montants fixes des cotisations (montants chiffrés : sum en PHP)
        $totalCotisationsMontant = Cotisation::where('actif', true)
            ->where('type_montant', 'fixe')
            ->whereNotNull('montant')
            ->get()
            ->sum('montant') ?? 0;
        
        // Total des paiements sur la période (montants chiffrés : sum en PHP)
        $totalPaiements = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->get()
            ->sum('montant');
        
        // Revenus par période (paiements sur la période)
        $revenusPeriode = $totalPaiements;
        
        // Total des engagements (montants chiffrés : sum en PHP)
        $totalEngagements = Engagement::where('statut', 'en_cours')->get()->sum('montant_engage');
        
        // Total des montants payés sur les engagements
        $totalPayeEngagements = Engagement::where('statut', 'en_cours')
            ->get()
            ->sum(function($engagement) {
                return $engagement->montant_paye;
            });
        
        // Solde total des caisses
        $soldeTotalCaisses = Caisse::where('statut', 'active')
            ->get()
            ->sum('solde_actuel');

        // Statistiques par caisse
        $statistiquesCaisses = Caisse::where('statut', 'active')
            ->withCount(['paiements' => function($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_paiement', [$dateDebut, $dateFin]);
            }])
            ->get()
            ->map(function ($caisse) use ($dateDebut, $dateFin) {
                $entrees = MouvementCaisse::where('caisse_id', $caisse->id)
                    ->where('sens', 'entree')
                    ->whereBetween('date_operation', [$dateDebut, $dateFin])
                    ->get()
                    ->sum('montant');
                
                $sorties = MouvementCaisse::where('caisse_id', $caisse->id)
                    ->where('sens', 'sortie')
                    ->whereBetween('date_operation', [$dateDebut, $dateFin])
                    ->get()
                    ->sum('montant');
                
                return [
                    'nom' => $caisse->nom,
                    'solde_actuel' => $caisse->solde_actuel,
                    'entrees' => $entrees,
                    'sorties' => $sorties,
                    'net' => $entrees - $sorties,
                ];
            });

        // Statistiques par cotisation
        $statistiquesCotisations = Cotisation::where('actif', true)
            ->withCount(['paiements' => function($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_paiement', [$dateDebut, $dateFin]);
            }])
            ->get()
            ->map(function ($cotisation) use ($dateDebut, $dateFin) {
                $montantTotal = Paiement::where('cotisation_id', $cotisation->id)
                    ->whereBetween('date_paiement', [$dateDebut, $dateFin])
                    ->get()
                    ->sum('montant');
                
                return [
                    'nom' => $cotisation->nom,
                    'nombre_paiements' => $cotisation->paiements_count,
                    'montant_total' => $montantTotal,
                ];
            })
            ->sortByDesc('montant_total')
            ->take(10);

        // Évolution des paiements (montants chiffrés : groupBy/sum en PHP)
        $paiementsPeriode = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])->orderBy('date_paiement')->get();
        $evolutionPaiements = $paiementsPeriode->groupBy(fn ($p) => $p->date_paiement?->format('Y-m-d'))
            ->map(fn ($items, $date) => (object) ['date' => $date, 'total' => (float) $items->sum('montant')])
            ->values()
            ->sortBy('date')
            ->values();

        // Répartition des paiements par mode (montants chiffrés : groupBy en PHP)
        $paiementsParMode = $paiementsPeriode->groupBy('mode_paiement')
            ->map(fn ($items, $mode) => (object) ['mode_paiement' => $mode, 'nombre' => $items->count(), 'total' => (float) $items->sum('montant')])
            ->values();

        // Top 10 membres par montant payé (montants chiffrés : sum en PHP)
        $topMembres = Membre::where('statut', 'actif')
            ->with(['paiements' => fn ($q) => $q->whereBetween('date_paiement', [$dateDebut, $dateFin])])
            ->get()
            ->map(function ($membre) {
                $membre->total_paye = $membre->paiements->sum('montant');
                return $membre;
            })
            ->filter(fn ($m) => $m->total_paye > 0)
            ->sortByDesc('total_paye')
            ->take(10)
            ->values();

        // Statistiques par membre (total payé par membre)
        $statistiquesMembres = Membre::where('statut', 'actif')
            ->with(['paiements' => fn ($q) => $q->whereBetween('date_paiement', [$dateDebut, $dateFin])])
            ->get()
            ->map(function ($membre) use ($dateDebut, $dateFin) {
                $totalPaye = $membre->paiements->sum('montant');
                return ['membre' => $membre, 'total_paye' => $totalPaye];
            })
            ->filter(fn ($x) => $x['total_paye'] > 0)
            ->sortByDesc('total_paye')
            ->take(10)
            ->values()
            ->map(fn ($x) => [
                'nom' => $x['membre']->nom . ' ' . $x['membre']->prenom,
                'total_paye' => $x['total_paye'],
                'nombre_paiements' => $x['membre']->paiements->count(),
            ]);

        return view('dashboard', compact(
            'totalMembres',
            'totalCaisses',
            'totalCotisations',
            'totalCotisationsMontant',
            'totalPaiements',
            'revenusPeriode',
            'totalEngagements',
            'totalPayeEngagements',
            'soldeTotalCaisses',
            'statistiquesCaisses',
            'statistiquesCotisations',
            'statistiquesMembres',
            'evolutionPaiements',
            'paiementsParMode',
            'topMembres',
            'dateDebut',
            'dateFin'
        ));
    }
}

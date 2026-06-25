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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class RapportController extends Controller
{
    /**
     * Afficher les rapports par caisse
     */
    public function parCaisse(Request $request)
    {
        $validated = $request->validate([
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin'   => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
        ]);
        $dateDebut = $validated['date_debut'] ?? now()->subDays(30)->format('Y-m-d');
        $dateFin = $validated['date_fin'] ?? now()->format('Y-m-d');

        $caisses = Caisse::where('statut', 'active')->get();
        
        $statistiques = $caisses->map(function($caisse) use ($dateDebut, $dateFin) {
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
            
            $nombrePaiements = Paiement::where('caisse_id', $caisse->id)
                ->whereBetween('date_paiement', [$dateDebut, $dateFin])
                ->count();
            
            return [
                'caisse' => $caisse,
                'solde_actuel' => $caisse->solde_actuel,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'net' => $entrees - $sorties,
                'nombre_paiements' => $nombrePaiements,
            ];
        });

        return view('rapports.par-caisse', compact('statistiques', 'dateDebut', 'dateFin'));
    }

    /**
     * Afficher les rapports par cotisation
     */
    public function parCotisation(Request $request)
    {
        $validated = $request->validate([
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin'   => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
        ]);
        $dateDebut = $validated['date_debut'] ?? now()->subDays(30)->format('Y-m-d');
        $dateFin = $validated['date_fin'] ?? now()->format('Y-m-d');

        $cotisations = Cotisation::where('actif', true)
            ->with('caisse')
            ->get();
        
        $statistiques = $cotisations->map(function($cotisation) use ($dateDebut, $dateFin) {
            $paiements = Paiement::where('cotisation_id', $cotisation->id)
                ->whereBetween('date_paiement', [$dateDebut, $dateFin])
                ->get();
            
            $montantTotal = $paiements->sum('montant');
            $nombrePaiements = $paiements->count();
            $nombreMembres = $paiements->pluck('membre_id')->unique()->count();
            
            return [
                'cotisation' => $cotisation,
                'montant_total' => $montantTotal,
                'nombre_paiements' => $nombrePaiements,
                'nombre_membres' => $nombreMembres,
                'moyenne' => $nombrePaiements > 0 ? $montantTotal / $nombrePaiements : 0,
            ];
        })->sortByDesc('montant_total')->values();

        // Pagination manuelle
        $perPage = 23;
        $currentPage = Paginator::resolveCurrentPage('page');
        $items = $statistiques->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $statistiques = new LengthAwarePaginator($items, $statistiques->count(), $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('rapports.par-cotisation', compact('statistiques', 'dateDebut', 'dateFin'));
    }

    /**
     * Afficher les rapports par membre
     */
    public function parMembre(Request $request)
    {
        $validated = $request->validate([
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin'   => 'nullable|date_format:Y-m-d|after_or_equal:date_debut',
        ]);
        $dateDebut = $validated['date_debut'] ?? now()->subDays(30)->format('Y-m-d');
        $dateFin = $validated['date_fin'] ?? now()->format('Y-m-d');

        $membres = Membre::where('statut', 'actif')
            ->with(['paiements' => function($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_paiement', [$dateDebut, $dateFin]);
            }])
            ->get();
        
        $statistiques = $membres->map(function($membre) {
            $paiements = $membre->paiements;
            $montantTotal = $paiements->sum('montant');
            $nombrePaiements = $paiements->count();
            
            // Engagements du membre
            $engagements = Engagement::where('membre_id', $membre->id)
                ->where('statut', 'en_cours')
                ->get();
            
            $montantEngage = $engagements->sum('montant_engage');
            $montantPayeEngagements = $engagements->sum(function($e) {
                return $e->montant_paye;
            });
            
            return [
                'membre' => $membre,
                'montant_total' => $montantTotal,
                'nombre_paiements' => $nombrePaiements,
                'montant_engage' => $montantEngage,
                'montant_paye_engagements' => $montantPayeEngagements,
                'reste_engagements' => $montantEngage - $montantPayeEngagements,
            ];
        })->sortByDesc('montant_total')->values();

        // Pagination manuelle
        $perPage = 23;
        $currentPage = Paginator::resolveCurrentPage('page');
        $items = $statistiques->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $statistiques = new LengthAwarePaginator($items, $statistiques->count(), $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('rapports.par-membre', compact('statistiques', 'dateDebut', 'dateFin'));
    }
}

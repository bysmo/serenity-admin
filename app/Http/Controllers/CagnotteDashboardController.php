<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\CotisationAdhesion;
use App\Models\CotisationVersementDemande;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CagnotteDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // 1. Statistiques Générales
        $cotisations = Cotisation::with('caisse')->get();
        $totalCagnottes = $cotisations->count();
        $activeCagnottes = $cotisations->where('actif', true)->count();
        $publiqueCagnottes = $cotisations->where('visibilite', 'publique')->count();
        $priveeCagnottes = $cotisations->where('visibilite', 'privee')->count();

        // Fonds collectés (Calcul en PHP pour gérer le cryptage)
        $totalFondsCollectes = $cotisations->sum(function($c) {
            return $c->caisse ? (float) $c->caisse->solde_actuel : 0;
        });

        $totalAdherents = CotisationAdhesion::where('statut', 'accepte')->count();

        // 2. Demandes de Retrait (Versements)
        $demandesEnAttente = CotisationVersementDemande::where('statut', 'en_attente')->get();
        $demandesEnAttenteCount = $demandesEnAttente->count();
        $demandesEnAttenteAmount = $demandesEnAttente->sum(function($d) {
            return (float) $d->montant_demande;
        });

        // 3. Activité Récente (10 derniers paiements)
        $recentPayments = Paiement::whereNotNull('cotisation_id')
            ->where('statut', 'valide')
            ->with(['membre', 'cotisation'])
            ->orderBy('date_paiement', 'desc')
            ->limit(10)
            ->get();

        // 4. Top 5 Cagnottes par Solde
        $topCagnottes = $cotisations->sortByDesc(function($c) {
            return $c->caisse ? (float) $c->caisse->solde_actuel : 0;
        })->take(5);

        // 5. Données du Graphique (30 derniers jours)
        $paymentsHistory = Paiement::whereNotNull('cotisation_id')
            ->where('statut', 'valide')
            ->where('date_paiement', '>=', $thirtyDaysAgo)
            ->get();

        $chartLabels = [];
        $chartData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $chartLabels[] = $now->copy()->subDays($i)->format('d M');
            
            $sum = $paymentsHistory->filter(function($p) use ($date) {
                return $p->date_paiement->format('Y-m-d') === $date;
            })->sum('montant');
            
            $chartData[] = (float) $sum;
        }

        return view('cotisations.dashboard', compact(
            'totalCagnottes',
            'activeCagnottes',
            'publiqueCagnottes',
            'priveeCagnottes',
            'totalFondsCollectes',
            'totalAdherents',
            'demandesEnAttenteCount',
            'demandesEnAttenteAmount',
            'recentPayments',
            'topCagnottes',
            'chartLabels',
            'chartData',
            'now'
        ));
    }
}

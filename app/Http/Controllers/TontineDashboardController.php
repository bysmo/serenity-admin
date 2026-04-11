<?php

namespace App\Http\Controllers;

use App\Models\EpargneSouscription;
use App\Models\EpargneVersement;
use App\Models\EpargneEcheance;
use App\Models\NanoCredit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TontineDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $threeMonthsFromNow = $now->copy()->addMonths(3);

        // 1. Dépôts en cours et Intérêts à échéance
        $subsActives = EpargneSouscription::where('statut', 'active')->get();
        $totalDepotsEnCours = $subsActives->sum(fn($s) => (float) $s->montant);
        $totalInteretsEcheance = $subsActives->sum(fn($s) => $s->remuneration_prevue);

        // 2. Tontines remboursées et Intérêts payés
        // On considère 'terminee' ou 'cloturee' comme remboursées
        $subsRemboursees = EpargneSouscription::whereIn('statut', ['terminee', 'cloturee'])->get();
        $totalTontinesRemboursees = $subsRemboursees->sum(fn($s) => (float) $s->montant);
        $totalInteretsPayes = $subsRemboursees->sum(fn($s) => $s->remuneration_prevue); // On suppose qu'ils ont été payés à la clôture

        // 3. Analyse de liquidité (Volatile vs Solide)
        $depotsVolatiles = EpargneSouscription::where('statut', 'active')
            ->where('date_fin', '<', $threeMonthsFromNow)
            ->get();
        
        $totalVolatile = $depotsVolatiles->sum(fn($s) => (float) $s->montant);
        $interetsUrgents = $depotsVolatiles->sum(fn($s) => $s->remuneration_prevue);

        $depotsSolides = EpargneSouscription::where('statut', 'active')
            ->where('date_fin', '>=', $threeMonthsFromNow)
            ->get();
        
        $totalSolide = $depotsSolides->sum(fn($s) => (float) $s->montant);
        $interetsSolides = $depotsSolides->sum(fn($s) => $s->remuneration_prevue);

        // 4. Taux de couverture Nano-Crédit
        // Calcul du capital restant dû sur les nano-crédis non terminés
        $creditsEnCours = NanoCredit::whereIn('statut', ['debourse', 'en_remboursement'])->get();
        $encoursNanoCredit = 0;
        foreach ($creditsEnCours as $credit) {
            $totalPaye = $credit->versements->sum(fn($v) => (float) $v->montant);
            $restant = (float) $credit->montant - $totalPaye;
            if ($restant > 0) {
                $encoursNanoCredit += $restant;
            }
        }

        $tauxCouverture = $encoursNanoCredit > 0 ? ($totalSolide / $encoursNanoCredit) * 100 : 0;

        // 5. Top Déposants
        $topDeposants = EpargneVersement::select('membre_id', DB::raw('SUM(montant) as total'))
            ->groupBy('membre_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('membre')
            ->get();

        // 6. Impayés récents
        $totalImpayes = EpargneEcheance::where('date_echeance', '<', $now)
            ->where('statut', '!=', 'payee')
            ->sum('montant');

        return view('tontines.dashboard', compact(
            'totalDepotsEnCours',
            'totalInteretsEcheance',
            'totalTontinesRemboursees',
            'totalInteretsPayes',
            'totalVolatile',
            'interetsUrgents',
            'totalSolide',
            'interetsSolides',
            'encoursNanoCredit',
            'tauxCouverture',
            'topDeposants',
            'totalImpayes',
            'now'
        ));
    }

    public function impayes()
    {
        $impayes = EpargneEcheance::where('date_echeance', '<', Carbon::now())
            ->where('statut', '!=', 'payee')
            ->with(['souscription.membre', 'souscription.plan'])
            ->orderBy('date_echeance', 'asc')
            ->paginate(20);

        return view('tontines.impayes', compact('impayes'));
    }

    public function souscriptions()
    {
        $souscriptions = EpargneSouscription::with(['membre', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('tontines.souscriptions', compact('souscriptions'));
    }
}

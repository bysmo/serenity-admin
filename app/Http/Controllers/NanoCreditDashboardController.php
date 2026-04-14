<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\NanoCredit;
use App\Models\NanoCreditVersement;
use Illuminate\Http\Request;

class NanoCreditDashboardController extends Controller
{
    public function index()
    {
        // 1. Volumes Globaux
        $totalAccorde = NanoCredit::whereIn('statut', ['debourse', 'en_remboursement', 'rembourse'])->sum('montant');
        
        $versements = NanoCreditVersement::all();
        $totalRembourse = $versements->sum(function($v) {
            return (float) $v->montant;
        });

        $totalPenalites = NanoCredit::sum('montant_penalite');

        // Total impayé brut (somme des montants des crédits "en_remboursement" avec retard)
        // Mais plus précisément, c'est le capital restant dû, or on peut faire simple: Total emprunté - Total Remboursé sur les crédits en retard.
        $creditsEnRetard = NanoCredit::where('statut', 'en_remboursement')->where('jours_retard', '>', 0)->get();
        // Une manière simplifiée d'avoir le capital en retard brut (Montant + Pénalité - Déjà payé)
        $totalImpaye = 0;
        foreach ($creditsEnRetard as $credit) {
            $totalVerseCredit = $credit->versements->sum(function($v) { return (float) $v->montant; });
            $du = (float) $credit->montant + (float) $credit->montant_penalite;
            $restant = $du - $totalVerseCredit;
            if ($restant > 0) {
                $totalImpaye += $restant;
            }
        }

        // 2. Nombre de crédits
        $nbTotalCredits = NanoCredit::count();
        $nbCreditsEnCours = NanoCredit::whereIn('statut', ['debourse', 'en_remboursement'])->count();
        $nbCreditsEnRetard = $creditsEnRetard->count();

        // 3. Score de Risque (Formule demandée)
        // Capital total des crédits "en cours" (inclut ceux en retard)
        $creditsEnCours = NanoCredit::whereIn('statut', ['debourse', 'en_remboursement'])->get();
        $totalEnCours = 0;
        foreach ($creditsEnCours as $credit) {
             $totalVerse = $credit->versements->sum(function($v) { return (float) $v->montant; });
             $du = (float) $credit->montant + (float) $credit->montant_penalite;
             $restant = $du - $totalVerse;
             if ($restant > 0) {
                 $totalEnCours += $restant;
             }
        }

        // Ratio financier de base (%)
        $ratioFinancier = 0;
        if ($totalEnCours > 0) {
            $ratioFinancier = ($totalImpaye / $totalEnCours) * 100;
        }

        // Pénalité de comportement (taux de récidive)
        $membresActifsDb = Membre::whereHas('nanoCredits')->count();
        $membresRecidivistes = Membre::where('nb_defauts_paiement', '>', 1)->count();
        
        $penaliteComportement = 0;
        if ($membresActifsDb > 0) {
            // Taux de clients à pb * 10 (ex: 20% membres pb = rajoute 2% au risque global)
            $penaliteComportement = ($membresRecidivistes / $membresActifsDb) * 10;
        }

        $riskScore = round($ratioFinancier + $penaliteComportement, 2);

        // Définir le niveau de risque
        $niveauRisque = 'Faible';
        $couleurRisque = 'success';
        if ($riskScore > 15 && $riskScore <= 30) {
            $niveauRisque = 'Modéré';
            $couleurRisque = 'warning';
        } elseif ($riskScore > 30) {
            $niveauRisque = 'Élevé';
            $couleurRisque = 'danger';
        }

        return view('nano-credits.dashboard', compact(
            'totalAccorde', 
            'totalRembourse', 
            'totalImpaye', 
            'totalPenalites',
            'nbTotalCredits',
            'nbCreditsEnCours',
            'nbCreditsEnRetard',
            'riskScore',
            'niveauRisque',
            'couleurRisque',
            'totalEnCours'
        ));
    }
}

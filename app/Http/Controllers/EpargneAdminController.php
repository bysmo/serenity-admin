<?php

namespace App\Http\Controllers;

use App\Models\EpargneEcheance;
use App\Models\EpargneSouscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EpargneAdminController extends Controller
{
    /**
     * Régénère les échéances d'une souscription en supprimant celles non payées
     * et en les recreant selon la fréquence du plan.
     * Les échéances déjà payées sont conservées.
     */
    public function regenererEcheances(EpargneSouscription $souscription)
    {
        $plan      = $souscription->plan;
        $montant   = (float) $souscription->montant;
        $dateDebut = Carbon::parse($souscription->date_debut);

        // Trouver la date de départ pour les nouvelles échéances
        // Si des échéances payées existent, on part de la dernière date payée + 1 période
        $dernierePayee = $souscription->echeances()
            ->where('statut', 'payee')
            ->orderByDesc('date_echeance')
            ->first();

        // Supprimer toutes les échéances non payées
        $supprimees = $souscription->echeances()
            ->whereNotIn('statut', ['payee'])
            ->delete();

        Log::info("EpargneAdmin: Régénération souscription #{$souscription->id}", [
            'supprimees' => $supprimees,
            'derniere_payee' => $dernierePayee?->date_echeance?->format('Y-m-d'),
        ]);

        // Calculer le nombre d'échéances déjà payées
        $nbPayees   = $souscription->echeances()->where('statut', 'payee')->count();
        $nbTotal    = $plan->nombre_versements;
        $nbRestants = max(0, $nbTotal - $nbPayees);

        if ($nbRestants === 0) {
            return redirect()->back()->with('success', "Aucune nouvelle échéance à générer — toutes déjà payées.");
        }

        // Date de départ pour les nouvelles échéances
        $depart = $dernierePayee
            ? $this->prochaineDateApres($plan->frequence, $dernierePayee->date_echeance, $souscription)
            : $dateDebut->copy();

        // Générer les nouvelles échéances
        DB::transaction(function () use ($plan, $souscription, $depart, $montant, $nbRestants) {
            $echeances = $this->buildEcheances($plan->frequence, $depart, $montant, $nbRestants, $souscription);
            foreach ($echeances as $e) {
                EpargneEcheance::create([
                    'souscription_id' => $souscription->id,
                    'date_echeance'   => $e['date_echeance'],
                    'montant'         => $e['montant'],
                    'statut'          => 'en_attente',
                ]);
            }
        });

        return redirect()->back()->with('success',
            "✅ Échéances régénérées : {$nbRestants} nouvelles échéances créées pour la souscription #{$souscription->id} ({$plan->nom})."
        );
    }

    /**
     * Calcule la prochaine date selon la fréquence
     */
    private function prochaineDateApres(string $frequence, $date, EpargneSouscription $souscription): Carbon
    {
        $d = Carbon::parse($date)->copy();
        return match ($frequence) {
            'journalier'   => $d->addDay(),
            'hebdomadaire' => $d->addWeek(),
            'mensuel'      => $d->addMonth(),
            'trimestriel'  => $d->addMonths(3),
            default        => $d->addDay(),
        };
    }

    /**
     * Construit le tableau des échéances selon la fréquence
     */
    private function buildEcheances(string $frequence, Carbon $depart, float $montant, int $nb, EpargneSouscription $souscription): array
    {
        $echeances = [];
        $jour = (int) ($souscription->jour_du_mois ?? $depart->day);

        for ($i = 0; $i < $nb; $i++) {
            $date = match ($frequence) {
                'journalier'   => $depart->copy()->addDays($i),
                'hebdomadaire' => $depart->copy()->addWeeks($i),
                'mensuel'      => $depart->copy()->addMonths($i)->day($jour),
                'trimestriel'  => $depart->copy()->addMonths(3 * $i),
                default        => $depart->copy()->addDays($i),
            };
            $echeances[] = ['date_echeance' => $date->format('Y-m-d'), 'montant' => $montant];
        }
        return $echeances;
    }
}

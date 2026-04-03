<?php

namespace App\Console\Commands;

use App\Models\NanoCredit;
use App\Services\NanoCreditPalierService;
use Illuminate\Console\Command;

class PrelevementsGarantsNanoCredits extends Command
{
    protected $signature = 'nano-credits:prelever-garants
                            {--dry-run : Simule les prélèvements sans les enregistrer}';

    protected $description = 'Déclenche le prélèvement automatique des garants sur les nano-crédits en retard dépassant le seuil configuré.';

    public function handle(NanoCreditPalierService $service): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '🔍 Mode simulation (dry-run)' : '🔔 Prélèvement automatique des garants...');

        $today = now()->toDateString();

        // Crédits actifs avec garants ayant accepté et jours de retard > 0
        $credits = NanoCredit::whereIn('statut', ['debourse', 'en_remboursement'])
            ->where('jours_retard', '>', 0)
            ->whereHas('garants', fn ($q) => $q->where('statut', 'accepte'))
            ->whereHas('echeances', function ($q) use ($today) {
                $q->where('statut', 'a_venir')
                  ->where('date_echeance', '<', $today);
            })
            ->with(['palier', 'membre', 'garants.membre', 'echeances'])
            ->get();

        $this->info("📋 {$credits->count()} crédit(s) avec garants à vérifier.");

        $totalPreleves   = 0;
        $garantsPreleves = 0;

        foreach ($credits as $credit) {
            $palier       = $credit->palier ?? $credit->membre?->nanoCreditPalier;
            $joursLimite  = $palier ? $palier->jours_avant_prelevement_garant : 30;

            if ($credit->jours_retard < $joursLimite) {
                $this->line("  ⏳ Crédit #{$credit->id} — {$credit->jours_retard}j de retard (seuil: {$joursLimite}j) → pas encore");
                continue;
            }

            $garantsActifs = $credit->garants->where('statut', 'accepte');
            $capitalRestant = $credit->echeances
                ->where('statut', 'a_venir')
                ->where('date_echeance', '<', $today)
                ->sum('montant');

            $nbGarants         = $garantsActifs->count();
            $montantParGarant  = $nbGarants > 0 ? (int) round($capitalRestant / $nbGarants, 0) : 0;

            foreach ($garantsActifs as $garant) {
                $this->line("  💸 Crédit #{$credit->id} — Garant {$garant->membre?->nom_complet} : {$montantParGarant} FCFA à prélever ({$credit->jours_retard}j de retard)");
                $totalPreleves += $montantParGarant;
                $garantsPreleves++;
            }

            if (!$dryRun) {
                $service->prelevementsGarants($credit);
            }
        }

        $this->newLine();
        $this->info("✅ {$garantsPreleves} garant(s) prélevés. Total : " . number_format($totalPreleves, 0, ',', ' ') . " FCFA" . ($dryRun ? ' [simulation]' : ''));

        return Command::SUCCESS;
    }
}

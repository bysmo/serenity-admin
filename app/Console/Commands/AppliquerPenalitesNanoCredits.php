<?php

namespace App\Console\Commands;

use App\Models\NanoCredit;
use App\Services\NanoCreditPalierService;
use Illuminate\Console\Command;

class AppliquerPenalitesNanoCredits extends Command
{
    protected $signature = 'nano-credits:appliquer-penalites
                            {--dry-run : Simule les pénalités sans les enregistrer}';

    protected $description = 'Calcule et enregistre les pénalités journalières sur les nano-crédits en retard.';

    public function handle(NanoCreditPalierService $service): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '🔍 Mode simulation (dry-run)' : '💰 Application des pénalités de retard...');

        $today = now()->toDateString();

        // Crédits actifs avec au moins une échéance en retard non payée
        $credits = NanoCredit::whereIn('statut', ['debourse', 'en_remboursement'])
            ->where(function ($q) use ($today) {
                $q->whereNull('date_dernier_calcul_penalite')
                  ->orWhereDate('date_dernier_calcul_penalite', '<', $today);
            })
            ->whereHas('echeances', function ($q) use ($today) {
                $q->where('statut', 'a_venir')
                  ->where('date_echeance', '<', $today);
            })
            ->with(['palier', 'membre', 'echeances'])
            ->get();

        $this->info("📋 {$credits->count()} crédit(s) en retard trouvé(s).");

        $totalPenalites = 0;
        $creditsTraites = 0;

        foreach ($credits as $credit) {
            $palier = $credit->palier ?? $credit->membre?->nanoCreditPalier;
            $taux   = $palier ? (float) $palier->penalite_par_jour : 5.0;

            $echeancesEnRetard = $credit->echeances
                ->where('statut', 'a_venir')
                ->where('date_echeance', '<', $today);

            $capitalRetard = $echeancesEnRetard->sum('montant');
            $penalite      = round($capitalRetard * ($taux / 100), 0);

            if (!$dryRun) {
                $service->calculerEtEnregistrerPenalites($credit);
                // Déclencher les défauts si seuil atteint
                if ($palier && $palier->downgrade_en_cas_impayes) {
                    $credit->refresh();
                    if ($credit->jours_retard >= $palier->jours_impayes_pour_downgrade) {
                        $service->enregistrerDefautPaiement($credit);
                    }
                }
            }

            $this->line("  💸 Crédit #{$credit->id} — {$credit->membre?->nom_complet} : +{$penalite} FCFA (jour {$credit->jours_retard}+1 de retard, taux {$taux}%/j)");
            $totalPenalites += $penalite;
            $creditsTraites++;
        }

        $this->newLine();
        $this->info("✅ {$creditsTraites} crédit(s) traité(s). Total pénalités du jour : " . number_format($totalPenalites, 0, ',', ' ') . " FCFA" . ($dryRun ? ' [simulation]' : ''));

        return Command::SUCCESS;
    }
}

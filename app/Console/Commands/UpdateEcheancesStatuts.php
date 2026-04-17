<?php

namespace App\Console\Commands;

use App\Models\EpargneEcheance;
use App\Models\NanoCreditEcheance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Met à jour les statuts des échéances passées :
 *  - EpargneEcheance  : a_venir → en_retard si date_echeance < aujourd'hui
 *  - NanoCreditEcheance : a_venir → en_retard si date_echeance < aujourd'hui
 *
 * À planifier quotidiennement via le scheduler Laravel.
 */
class UpdateEcheancesStatuts extends Command
{
    protected $signature = 'echeances:update-statuts
                            {--dry-run : Afficher sans modifier}';

    protected $description = 'Marquer en retard toutes les échéances (épargne + nano-crédits) dont la date est dépassée.';

    public function handle(): int
    {
        $today    = Carbon::today()->toDateString();
        $dryRun   = $this->option('dry-run');
        $total    = 0;

        $this->info('=== Mise à jour des statuts d\'échéances — ' . $today . ($dryRun ? ' [DRY-RUN]' : '') . ' ===');

        // ──────────────────────────────────────────────────────────────────────
        // 1. Échéances tontines (epargne_echeances)
        // ──────────────────────────────────────────────────────────────────────
        $epargneQuery = EpargneEcheance::where('statut', 'a_venir')
            ->where('date_echeance', '<', $today);

        $nbEpargne = $epargneQuery->count();

        if ($nbEpargne > 0) {
            if (!$dryRun) {
                EpargneEcheance::where('statut', 'a_venir')
                    ->where('date_echeance', '<', $today)
                    ->update(['statut' => 'en_retard']);
            }
            $this->line("  ✅ Tontines  : {$nbEpargne} échéance(s) passées → en_retard" . ($dryRun ? ' (non appliqué)' : ''));
            $total += $nbEpargne;
        } else {
            $this->line('  ✅ Tontines  : Aucune échéance à mettre à jour.');
        }

        // ──────────────────────────────────────────────────────────────────────
        // 2. Échéances nano-crédits (nano_credit_echeances)
        // ──────────────────────────────────────────────────────────────────────
        if (class_exists(NanoCreditEcheance::class)) {
            $ncQuery = NanoCreditEcheance::where('statut', 'a_venir')
                ->where('date_echeance', '<', $today);

            $nbNc = $ncQuery->count();

            if ($nbNc > 0) {
                if (!$dryRun) {
                    NanoCreditEcheance::where('statut', 'a_venir')
                        ->where('date_echeance', '<', $today)
                        ->update(['statut' => 'en_retard']);
                }
                $this->line("  ✅ Crédits   : {$nbNc} échéance(s) passées → en_retard" . ($dryRun ? ' (non appliqué)' : ''));
                $total += $nbNc;
            } else {
                $this->line('  ✅ Crédits   : Aucune échéance à mettre à jour.');
            }
        }

        $this->info("Total : {$total} échéance(s) mise(s) à jour.");

        if (!$dryRun && $total > 0) {
            Log::info('echeances:update-statuts', ['total' => $total, 'date' => $today]);
        }

        return Command::SUCCESS;
    }
}

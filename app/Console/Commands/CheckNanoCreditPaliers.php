<?php

namespace App\Console\Commands;

use App\Models\Membre;
use App\Services\NanoCreditPalierService;
use Illuminate\Console\Command;

class CheckNanoCreditPaliers extends Command
{
    protected $signature = 'nano-credits:check-paliers
                            {--dry-run : Simule les changements sans les appliquer}';

    protected $description = 'Vérifie et met à jour automatiquement les paliers nano-crédit de tous les membres (upgrade et downgrade).';

    public function handle(NanoCreditPalierService $service): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '🔍 Mode simulation (dry-run)' : '⚙️  Vérification des paliers nano-crédit...');

        $membres = Membre::where('statut', 'actif')
            ->whereNotNull('nano_credit_palier_id')
            ->whereHas('kycVerification', fn ($q) => $q->where('statut', 'valide'))
            ->with(['nanoCreditPalier', 'nanoCredits.echeances', 'epargneSouscriptions.versements'])
            ->get();

        $this->info("👥 {$membres->count()} membres actifs avec KYC validé trouvés.");

        $upgradesCount   = 0;
        $downgradesCount = 0;

        foreach ($membres as $membre) {
            // --- Vérification Upgrade ---
            $palierCible = $service->verifierEligibiliteUpgrade($membre);
            if ($palierCible) {
                if (!$dryRun) {
                    $service->upgraderPalier($membre);
                }
                $this->line("  ⬆️  <info>UPGRADE</info> {$membre->nom_complet} → {$palierCible->nom}");
                $upgradesCount++;
                continue; // Pas de downgrade si on vient d'upgrader
            }

            // --- Vérification Downgrade ---
            $palierActuel = $membre->nanoCreditPalier;
            if (!$palierActuel) {
                continue;
            }

            if ($palierActuel->downgrade_en_cas_impayes && $membre->hasImpayes()) {
                $joursRetardMax = $membre->maxJoursRetard();
                if ($joursRetardMax >= $palierActuel->jours_impayes_pour_downgrade) {
                    if (!$dryRun) {
                        $service->downgraderPalier($membre, "Retard de {$joursRetardMax} jours");
                    }
                    $this->line("  ⬇️  <comment>DOWNGRADE</comment> {$membre->nom_complet} (retard: {$joursRetardMax}j)");
                    $downgradesCount++;
                }
            }
        }

        $this->newLine();
        $this->info("✅ Terminé : {$upgradesCount} upgrade(s), {$downgradesCount} downgrade(s)." . ($dryRun ? ' [simulation]' : ''));

        return Command::SUCCESS;
    }
}

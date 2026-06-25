<?php

namespace App\Console\Commands;

use App\Models\EpargneEcheance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Réinitialise les échéances bloquées en statut 'en_cours' sans paiement confirmé.
 * Cas : le membre a initié un paiement mais l'a annulé ou le délai a expiré.
 */
class ResetEcheancesEnCours extends Command
{
    protected $signature = 'echeances:reset-en-cours {--dry-run : Afficher sans modifier}';

    protected $description = 'Remet à "en_attente" les échéances bloquées en "en_cours" sans versement associé.';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $this->info('=== Réinitialisation des échéances bloquées en "en_cours" ===');

        // Trouver les échéances en_cours sans versement payé associé
        $bloquees = EpargneEcheance::where('statut', 'en_cours')
            ->whereDoesntHave('versement') // pas de versement lié
            ->get();

        if ($bloquees->isEmpty()) {
            $this->line('✅ Aucune échéance bloquée trouvée.');
            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'Souscription', 'Date Échéance', 'Statut actuel'],
            $bloquees->map(fn($e) => [
                $e->id,
                $e->souscription_id,
                $e->date_echeance->format('d/m/Y'),
                $e->statut,
            ])->toArray()
        );

        if ($isDryRun) {
            $this->warn("Mode --dry-run : aucune modification effectuée. {$bloquees->count()} échéance(s) seraient remises à 'en_attente'.");
            return Command::SUCCESS;
        }

        $nb = EpargneEcheance::where('statut', 'en_cours')
            ->whereDoesntHave('versement')
            ->update(['statut' => 'en_attente']);

        Log::info("ResetEcheancesEnCours: {$nb} échéance(s) remises à 'en_attente'.");
        $this->info("✅ {$nb} échéance(s) remises à 'en_attente' avec succès.");

        return Command::SUCCESS;
    }
}

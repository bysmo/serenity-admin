<?php

namespace App\Services;

use App\Models\Membre;
use App\Models\ParrainageConfig;
use App\Models\ParrainageCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParrainageService
{
    /**
     * Générer les commissions de parrainage lors d'un événement déclencheur
     *
     * @param Membre $filleul    Le membre qui déclenche l'événement
     * @param string $declencheur L'événement : 'inscription', 'premier_paiement', 'adhesion_cotisation'
     * @param float  $montantBase Montant de base (pour les pourcentages)
     */
    public function genererCommissions(Membre $filleul, string $declencheur, float $montantBase = 0): void
    {
        $config = ParrainageConfig::current();

        if (!$config->actif) {
            return;
        }

        if ($config->declencheur !== $declencheur) {
            return;
        }

        if (!$filleul->parrain_id) {
            return;
        }

        DB::transaction(function () use ($filleul, $declencheur, $montantBase, $config) {
            $this->genererCommissionPourNiveau($filleul, $config, $declencheur, $montantBase, 1);
        });
    }

    /**
     * Générer récursivement les commissions pour chaque niveau
     */
    private function genererCommissionPourNiveau(
        Membre $filleul,
        ParrainageConfig $config,
        string $declencheur,
        float $montantBase,
        int $niveau
    ): void {
        if ($niveau > $config->niveaux_parrainage) {
            return;
        }

        // Trouver le parrain du niveau actuel
        $parrainId = match ($niveau) {
            1 => $filleul->parrain_id,
            2 => optional(Membre::find($filleul->parrain_id))->parrain_id,
            3 => optional(optional(Membre::find($filleul->parrain_id))->parrain)->parrain_id,
            default => null,
        };

        if (!$parrainId) {
            return;
        }

        // Vérifier qu'une commission n'existe pas déjà pour ce filleul/niveau/déclencheur
        $exists = ParrainageCommission::where('filleul_id', $filleul->id)
            ->where('parrain_id', $parrainId)
            ->where('niveau', $niveau)
            ->where('declencheur', $declencheur)
            ->exists();

        if ($exists) {
            return;
        }

        $montantCommission = $config->calculerCommission($montantBase, $niveau);

        if ($montantCommission <= 0) {
            // Passer au niveau suivant quand même si configuré
            if ($niveau < $config->niveaux_parrainage) {
                $this->genererCommissionPourNiveau($filleul, $config, $declencheur, $montantBase, $niveau + 1);
            }
            return;
        }

        // Calculer la date de disponibilité
        $disponibleLe = $config->delai_validation_jours > 0
            ? now()->addDays($config->delai_validation_jours)
            : null;

        $statut = ($disponibleLe === null || $disponibleLe->lte(now())) ? 'disponible' : 'en_attente';

        ParrainageCommission::create([
            'parrain_id'    => $parrainId,
            'filleul_id'    => $filleul->id,
            'niveau'        => $niveau,
            'montant'       => $montantCommission,
            'statut'        => $statut,
            'declencheur'   => $declencheur,
            'disponible_le' => $disponibleLe,
        ]);

        Log::info("Commission parrainage niveau {$niveau} générée : parrain #{$parrainId}, filleul #{$filleul->id}, montant {$montantCommission}");

        // Récursion pour les niveaux suivants
        if ($niveau < $config->niveaux_parrainage) {
            $this->genererCommissionPourNiveau($filleul, $config, $declencheur, $montantBase, $niveau + 1);
        }
    }

    /**
     * Rendre disponibles les commissions dont le délai est écoulé
     */
    public function activerCommissionsEcheantes(): int
    {
        $count = ParrainageCommission::where('statut', 'en_attente')
            ->where('disponible_le', '<=', now())
            ->update(['statut' => 'disponible']);

        if ($count > 0) {
            Log::info("ParrainageService: {$count} commissions activées.");
        }

        return $count;
    }

    /**
     * Vérifier si un membre peut réclamer ses commissions
     */
    public function peutReclamer(Membre $membre): array
    {
        $config = ParrainageConfig::current();

        if (!$config->actif) {
            return ['peut' => false, 'raison' => 'Le système de parrainage est désactivé.'];
        }

        $nbFilleuls = $membre->filleuls()->count();
        if ($nbFilleuls < $config->min_filleuls_retrait) {
            return [
                'peut'   => false,
                'raison' => "Vous devez avoir au moins {$config->min_filleuls_retrait} filleul(s) pour réclamer. Vous en avez {$nbFilleuls}.",
            ];
        }

        $totalDisponible = $membre->totalCommissionsDisponibles();
        if ($totalDisponible < $config->montant_min_retrait) {
            return [
                'peut'   => false,
                'raison' => "Montant minimum requis : " . number_format($config->montant_min_retrait, 0, ',', ' ') . " FCFA. Disponible : " . number_format($totalDisponible, 0, ',', ' ') . " FCFA.",
            ];
        }

        $commissionsDisponibles = $membre->commissionsParrainage()
            ->where('statut', 'disponible')
            ->where(function ($q) {
                $q->whereNull('disponible_le')->orWhere('disponible_le', '<=', now());
            })
            ->count();

        if ($commissionsDisponibles === 0) {
            return ['peut' => false, 'raison' => 'Aucune commission disponible à réclamer.'];
        }

        return ['peut' => true, 'raison' => null, 'total' => $totalDisponible];
    }

    /**
     * Soumettre une réclamation de commissions (toutes les disponibles)
     */
    public function soumettreReclamation(Membre $membre): array
    {
        $verification = $this->peutReclamer($membre);

        if (!$verification['peut']) {
            return ['success' => false, 'message' => $verification['raison']];
        }

        $count = DB::transaction(function () use ($membre) {
            return ParrainageCommission::where('parrain_id', $membre->id)
                ->where('statut', 'disponible')
                ->where(function ($q) {
                    $q->whereNull('disponible_le')->orWhere('disponible_le', '<=', now());
                })
                ->update([
                    'statut'     => 'reclame',
                    'reclame_le' => now(),
                ]);
        });

        $total = $membre->commissionsParrainage()
            ->where('statut', 'reclame')
            ->sum('montant');

        return [
            'success' => true,
            'message' => "{$count} commission(s) réclamée(s) avec succès. Total en attente de paiement : " . number_format($total, 0, ',', ' ') . " FCFA.",
            'count'   => $count,
        ];
    }

    /**
     * Trouver un membre par son code de parrainage (validation inscription)
     */
    public function trouverParCodeParrainage(string $code): ?Membre
    {
        return Membre::where('code_parrainage', strtoupper(trim($code)))->first();
    }

    /**
     * Statistiques globales de parrainage pour le tableau de bord admin
     */
    public function statsGlobales(): array
    {
        return [
            'total_parrains'            => Membre::whereHas('filleuls')->count(),
            'total_filleuls'            => Membre::whereNotNull('parrain_id')->count(),
            'commissions_en_attente'    => ParrainageCommission::where('statut', 'en_attente')->count(),
            'commissions_disponibles'   => ParrainageCommission::where('statut', 'disponible')->count(),
            'commissions_reclames'      => ParrainageCommission::where('statut', 'reclame')->count(),
            'commissions_payees'        => ParrainageCommission::where('statut', 'paye')->count(),
            'montant_total_disponible'  => (float) ParrainageCommission::where('statut', 'disponible')->sum('montant'),
            'montant_total_reclame'     => (float) ParrainageCommission::where('statut', 'reclame')->sum('montant'),
            'montant_total_paye'        => (float) ParrainageCommission::where('statut', 'paye')->sum('montant'),
            'nb_reclames'               => ParrainageCommission::where('statut', 'reclame')->count(),
        ];
    }
}

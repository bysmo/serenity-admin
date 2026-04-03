<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NanoCreditPalier extends Model
{
    protected $table = 'nano_credit_paliers';

    protected $fillable = [
        'numero',
        'nom',
        'description',
        // Conditions d'accession
        'min_credits_rembourses',
        'min_montant_total_rembourse',
        'min_epargne_cumulee',
        // Paramètres crédit
        'montant_plafond',
        'nombre_garants',
        'duree_jours',
        'taux_interet',
        'frequence_remboursement',
        'penalite_par_jour',
        'jours_avant_prelevement_garant',
        // Conséquences impayés
        'downgrade_en_cas_impayes',
        'jours_impayes_pour_downgrade',
        'interdiction_en_cas_recidive',
        'nb_recidives_pour_interdiction',
        'actif',
    ];

    protected $casts = [
        'montant_plafond'               => 'float',
        'min_montant_total_rembourse'   => 'float',
        'min_epargne_cumulee'           => 'float',
        'downgrade_en_cas_impayes'      => 'boolean',
        'interdiction_en_cas_recidive'  => 'boolean',
        'actif'                         => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function membres(): HasMany
    {
        return $this->hasMany(Membre::class, 'nano_credit_palier_id');
    }

    public function nanoCredits(): HasMany
    {
        return $this->hasMany(NanoCredit::class, 'palier_id');
    }

    // ─── Accesseurs ───────────────────────────────────────────────────────────

    public function getFrequenceRemboursementLabelAttribute(): string
    {
        return match ($this->frequence_remboursement ?? 'mensuel') {
            'journalier'    => 'Journalier',
            'hebdomadaire'  => 'Hebdomadaire',
            'mensuel'       => 'Mensuel',
            'trimestriel'   => 'Trimestriel',
            default         => $this->frequence_remboursement,
        };
    }

    public function getNombreEcheancesAttribute(): int
    {
        $duree = (int) ($this->duree_jours ?? 30);
        return match ($this->frequence_remboursement ?? 'mensuel') {
            'journalier'    => $duree,
            'hebdomadaire'  => (int) max(1, ceil($duree / 7)),
            'mensuel'       => (int) max(1, ceil($duree / 30)),
            'trimestriel'   => (int) max(1, ceil($duree / 90)),
            default         => 1,
        };
    }

    // ─── Logique Métier ───────────────────────────────────────────────────────

    /**
     * Calcule le tableau d'amortissement (intérêt simple prorata temporis).
     */
    public function calculAmortissement(float $montant): array
    {
        $dureeJours    = (int) ($this->duree_jours ?? 30);
        $taux          = (float) ($this->taux_interet ?? 0);
        $interetTotal  = round($montant * ($taux / 100) * ($dureeJours / 365), 0);
        $montantTotal  = $montant + $interetTotal;
        $nbEcheances   = $this->nombre_echeances;
        $montantEch    = $nbEcheances > 0 ? (int) round($montantTotal / $nbEcheances, 0) : 0;

        return [
            'montant_emprunte'  => (int) round($montant, 0),
            'interet_total'     => $interetTotal,
            'montant_total_du'  => $montantTotal,
            'nombre_echeances'  => $nbEcheances,
            'montant_echeance'  => $montantEch,
        ];
    }

    /**
     * Vérifie si un membre remplit les conditions pour accéder À ce palier.
     * (Utilisé lors de la vérification d'upgrade depuis le palier inférieur.)
     */
    public function conditionsAccessRemplies(Membre $membre): bool
    {
        // 1. Nombre de crédits entièrement remboursés
        $creditsRembourses = $membre->nanoCredits()
            ->where('statut', 'rembourse')
            ->count();

        if ($creditsRembourses < $this->min_credits_rembourses) {
            return false;
        }

        // 2. Montant total remboursé
        $montantRembourse = $membre->nanoCredits()
            ->where('statut', 'rembourse')
            ->get()
            ->sum('montant');

        if ($montantRembourse < (float) $this->min_montant_total_rembourse) {
            return false;
        }

        // 3. Épargne cumulée
        $epargne = $membre->epargneSouscriptions()
            ->with('versements')
            ->get()
            ->sum(fn ($s) => $s->versements->sum('montant'));

        if ($epargne < (float) $this->min_epargne_cumulee) {
            return false;
        }

        return true;
    }

    /**
     * Retourne le palier suivant (numéro + 1), ou null s'il n'existe pas.
     */
    public function palierSuivant(): ?NanoCreditPalier
    {
        return static::where('numero', $this->numero + 1)->where('actif', true)->first();
    }

    /**
     * Retourne le palier précédent (numéro - 1), ou null s'il est déjà au plus bas.
     */
    public function palierPrecedent(): ?NanoCreditPalier
    {
        if ($this->numero <= 1) {
            return null;
        }
        return static::where('numero', $this->numero - 1)->where('actif', true)->first();
    }
}

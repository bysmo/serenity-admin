<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NanoCreditType extends Model
{
    protected $table = 'nano_credit_types';

    protected $fillable = [
        'nom',
        'description',
        'montant_min',
        'montant_max',
        'duree_jours',
        'taux_interet',
        'frequence_remboursement',
        'actif',
        'ordre',
        'min_epargne_percent',
    ];

    protected $casts = [
        'montant_min' => \App\Casts\EncryptedDecimal::class,
        'montant_max' => \App\Casts\EncryptedDecimal::class,
        'taux_interet' => \App\Casts\EncryptedDecimal::class,
        'actif' => 'boolean',
    ];

    public function nanoCredits(): HasMany
    {
        return $this->hasMany(NanoCredit::class, 'nano_credit_type_id');
    }

    public function getFrequenceRemboursementLabelAttribute(): string
    {
        return match ($this->frequence_remboursement ?? 'mensuel') {
            'journalier' => 'Journalier',
            'hebdomadaire' => 'Hebdomadaire',
            'mensuel' => 'Mensuel',
            'trimestriel' => 'Trimestriel',
            default => $this->frequence_remboursement,
        };
    }

    /**
     * Nombre d'échéances de remboursement selon la durée et la fréquence
     */
    public function getNombreEcheancesAttribute(): int
    {
        $duree = (int) ($this->duree_jours ?? 7);
        return match ($this->frequence_remboursement ?? 'mensuel') {
            'journalier' => $duree,
            'hebdomadaire' => (int) max(1, ceil($duree / 7)),
            'mensuel' => (int) max(1, ceil($duree / 30)),
            'trimestriel' => (int) max(1, ceil($duree / 90)),
            default => 1,
        };
    }

    /**
     * Calcule tableau d'amortissement : intérêt simple.
     * interet_total = montant * (taux/100) * (duree_mois/12)
     * montant_total_du = montant + interet_total
     * echéance = montant_total_du / nb_echeances
     */
    public function calculAmortissement(float $montant): array
    {
        $dureeJours = (int) ($this->duree_jours ?? 7);
        $taux = (float) ($this->taux_interet ?? 0);
        // Intérêt simple prorata temporis (nb_jours / 365)
        $interetTotal = round($montant * ($taux / 100) * ($dureeJours / 365), 0);
        $montantTotalDu = $montant + $interetTotal;
        $nbEcheances = $this->nombre_echeances;
        $montantEcheance = $nbEcheances > 0 ? (int) round($montantTotalDu / $nbEcheances, 0) : 0;
        return [
            'montant_emprunte' => (int) round($montant, 0),
            'interet_total' => $interetTotal,
            'montant_total_du' => $montantTotalDu,
            'nombre_echeances' => $nbEcheances,
            'montant_echeance' => $montantEcheance,
        ];
    }
}

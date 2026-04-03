<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpargnePlan extends Model
{
    use HasFactory;

    protected $table = 'epargne_plans';

    protected $fillable = [
        'nom',
        'description',
        'montant_min',
        'montant_max',
        'frequence',
        'taux_remuneration',
        'duree_mois',
        'caisse_id',
        'actif',
        'ordre',
    ];

    protected $casts = [
        'montant_min' => \App\Casts\EncryptedDecimal::class,
        'montant_max' => \App\Casts\EncryptedDecimal::class,
        'taux_remuneration' => \App\Casts\EncryptedDecimal::class,
        'actif' => 'boolean',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function souscriptions()
    {
        return $this->hasMany(EpargneSouscription::class, 'plan_id');
    }

    public function getFrequenceLabelAttribute(): string
    {
        return match ($this->frequence) {
            'journalier' => 'Journalier',
            'hebdomadaire' => 'Hebdomadaire',
            'mensuel' => 'Mensuel',
            'trimestriel' => 'Trimestriel',
            default => $this->frequence,
        };
    }

    /**
     * Nombre de versements sur la durée du plan (selon la fréquence)
     */
    public function getNombreVersementsAttribute(): int
    {
        $duree = (int) ($this->duree_mois ?? 12);
        return match ($this->frequence) {
            'journalier' => (int) ceil(365 * $duree / 12),
            'hebdomadaire' => (int) ceil(52 * $duree / 12),
            'mensuel' => $duree,
            'trimestriel' => (int) max(1, ceil($duree / 3)),
            default => $duree,
        };
    }

    /**
     * Calcule le montant total versé, la rémunération et le montant total reversé pour un montant par versement donné.
     * Intérêt simple : rémunération = total_verse * (taux/100) * (duree_mois/12).
     */
    public function calculRemboursement(float $montantParVersement): array
    {
        $nbVersements = $this->nombre_versements;
        $totalVerse = $nbVersements * $montantParVersement;
        $taux = (float) ($this->taux_remuneration ?? 0);
        $dureeMois = (int) ($this->duree_mois ?? 12);
        $remuneration = round($totalVerse * ($taux / 100) * ($dureeMois / 12), 0);
        $totalReverse = $totalVerse + $remuneration;

        return [
            'nombre_versements' => $nbVersements,
            'montant_total_verse' => (int) $totalVerse,
            'remuneration' => (int) $remuneration,
            'montant_total_reverse' => (int) $totalReverse,
        ];
    }
}

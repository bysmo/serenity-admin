<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NanoCreditGarant extends Model
{
    protected $table = 'nano_credit_garants';

    protected $fillable = [
        'nano_credit_id',
        'membre_id',
        'statut',
        'montant_preleve',
        'preleve_le',
        'accepte_le',
        'refuse_le',
        'motif_refus',
        'gain_partage',
    ];

    protected $casts = [
        'montant_preleve' => \App\Casts\EncryptedDecimal::class,
        'preleve_le'      => 'datetime',
        'accepte_le'      => 'datetime',
        'refuse_le'       => 'datetime',
    ];

    // ─── Statut Labels ────────────────────────────────────────────────────────

    public static function statutLabels(): array
    {
        return [
            'en_attente' => 'En attente',
            'accepte'    => 'Accepté',
            'refuse'     => 'Refusé',
            'preleve'    => 'Prélevé',
            'libere'     => 'Libéré',
        ];
    }

    public function getStatutLabelAttribute(): string
    {
        return self::statutLabels()[$this->statut] ?? $this->statut;
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function nanoCredit(): BelongsTo
    {
        return $this->belongsTo(NanoCredit::class);
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isAccepte(): bool
    {
        return $this->statut === 'accepte';
    }

    public function isPreleve(): bool
    {
        return $this->statut === 'preleve';
    }

    public function isLibere(): bool
    {
        return $this->statut === 'libere';
    }

    /**
     * Vérifie qu'un membre peut être garant :
     * - Actif, KYC validé, aucun impayé actif.
     * - Possède une tontine active dont le solde est suffisant (selon le type de crédit).
     * - A une qualité suffisante (selon le palier du crédit).
     * - N'a pas atteint son nombre maximum de garanties.
     */
    public static function membreEstEligibleGarant(Membre $membre, NanoCredit $nanoCredit): bool
    {
        // Doit être actif
        if (!$membre->isActif()) {
            return false;
        }

        // KYC doit être validé
        if (!$membre->hasKycValide()) {
            return false;
        }

        // Ne doit pas être interdit de crédit
        if ($membre->isNanoCreditInterdit()) {
            return false;
        }

        // Ne doit avoir aucun impayé (échéances en retard)
        if ($membre->hasImpayes()) {
            return false;
        }

        // Limite de nombre de garanties atteint ?
        if ($membre->aAtteintLimiteGaranties()) {
            return false;
        }

        // --- Vérification du solde d'épargne (Tontine) ---
        $type = $nanoCredit->nanoCreditType;
        $montantCredit = (float) $nanoCredit->montant;
        
        // % d'épargne minimum (configuré dans le type de crédit)
        $minPercent = $type ? (float) $type->min_epargne_percent : 85.0;
        $soldeRequis = $montantCredit * ($minPercent / 100);

        $soldeTotal = $membre->totalEpargneSolde();
        if ($soldeTotal < $soldeRequis) {
            return false;
        }

        // --- Vérification de la qualité minimale du palier ---
        $palier = $nanoCredit->palier;
        if ($palier && $membre->garant_qualite < $palier->min_garant_qualite) {
            return false;
        }

        return true;
    }
}

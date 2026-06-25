<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasChecksum;

class NanoCreditGarant extends Model
{
    use HasChecksum;
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
        'compte_reservation_id',
        'montant_reserve',
        'reserve_le',
        'libere_le',
        'checksum',
    ];

    protected $casts = [
        'montant_preleve'  => \App\Casts\EncryptedDecimal::class,
        'gain_partage'     => \App\Casts\EncryptedDecimal::class,
        'montant_reserve'  => \App\Casts\EncryptedDecimal::class,
        'preleve_le'       => 'datetime',
        'accepte_le'       => 'datetime',
        'refuse_le'        => 'datetime',
        'reserve_le'       => 'datetime',
        'libere_le'        => 'datetime',
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

    /**
     * Compte de réservation (blocage du montant de couverture)
     */
    public function compteReservation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Caisse::class, 'compte_reservation_id');
    }

    /**
     * Indique si le montant de couverture a été bloqué sur le compte de réservation.
     */
    public function isMontantBloque(): bool
    {
        return $this->compte_reservation_id !== null && ((float) $this->montant_reserve) > 0;
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

        // --- Vérification du solde d'épargne et de la qualité ---
        $palier = $nanoCredit->palier;
        
        $effectiveQuality = (int) $membre->garant_qualite;
        $soldeGlobalPourGarant = $membre->soldePourGarant();
        if ($soldeGlobalPourGarant > 5000 && $effectiveQuality < 1) {
            $effectiveQuality = 1;
        }

        if ($palier) {
            if ($palier->min_garant_qualite <= 1) {
                // Pour la qualité 1, la condition est d'avoir au moins la qualité 1 et solde global > 5000
                if ($effectiveQuality < 1 || $soldeGlobalPourGarant <= 5000) {
                    return false;
                }
            } else {
                // Pour les qualités supérieures, on vérifie le solde requis par le palier
                $montantCredit = (float) $nanoCredit->montant;
                $minPercent = (float) $palier->min_epargne_percent;
                $soldeRequis = $montantCredit * ($minPercent / 100);
                
                $soldeTotal = $membre->totalEpargneSolde();
                if ($soldeTotal < $soldeRequis) {
                    return false;
                }
                
                if ($effectiveQuality < $palier->min_garant_qualite) {
                    return false;
                }
            }
        }

        return true;
    }
}

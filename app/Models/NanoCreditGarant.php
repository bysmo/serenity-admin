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
     * - Actif, KYC validé, aucun impayé actif, possède de l'épargne.
     */
    public static function membreEstEligibleGarant(Membre $membre): bool
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
        if ($membre->nano_credit_interdit) {
            return false;
        }

        // Ne doit avoir aucun nano-crédit en retard (échéances impayées)
        $aImpayes = $membre->nanoCredits()
            ->whereIn('statut', ['debourse', 'en_remboursement'])
            ->whereHas('echeances', function ($q) {
                $q->where('statut', 'a_venir')
                  ->where('date_echeance', '<', now()->toDateString());
            })
            ->exists();

        if ($aImpayes) {
            return false;
        }

        // Doit avoir au moins une souscription d'épargne active
        $aEpargne = $membre->epargneSouscriptions()->exists();

        return $aEpargne;
    }
}

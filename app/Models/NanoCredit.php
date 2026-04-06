<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasChecksum;

class NanoCredit extends Model
{
    use HasChecksum;
    protected $table = 'nano_credits';

    protected $fillable = [
        'palier_id',
        'membre_id',
        'montant',
        'telephone',
        'withdraw_mode',
        'statut',
        'disburse_token',
        'disburse_id',
        'transaction_id',
        'provider_ref',
        'callback_received',
        'error_message',
        'montant_penalite',
        'jours_retard',
        'date_dernier_calcul_penalite',
        'date_octroi',
        'date_fin_remboursement',
        'created_by',
        'checksum',
    ];

    protected $casts = [
        'montant'                       => \App\Casts\EncryptedDecimal::class,
        'montant_penalite'              => \App\Casts\EncryptedDecimal::class,
        'callback_received'             => 'boolean',
        'date_octroi'                   => 'date',
        'date_fin_remboursement'        => 'date',
        'date_dernier_calcul_penalite'  => 'date',
    ];



    public function palier(): BelongsTo
    {
        return $this->belongsTo(NanoCreditPalier::class, 'palier_id');
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(Membre::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function echeances(): HasMany
    {
        return $this->hasMany(NanoCreditEcheance::class);
    }

    public function versements(): HasMany
    {
        return $this->hasMany(NanoCreditVersement::class);
    }

    public function garants(): HasMany
    {
        return $this->hasMany(NanoCreditGarant::class);
    }

    /**
     * Libellé du canal de retrait (mobile money)
     */
    public static function withdrawModeLabels(): array
    {
        return [
            'orange-money-senegal' => 'Orange Money Sénégal',
            'free-money-senegal'   => 'Free Money Sénégal',
            'expresso-senegal'     => 'E-Money (Expresso) Sénégal',
            'wave-senegal'         => 'Wave Sénégal',
            'mtn-benin'            => 'MTN MoMo Bénin',
            'moov-benin'           => 'Moov Bénin',
            'mtn-ci'               => 'MTN Côte d\'Ivoire',
            'orange-money-ci'      => 'Orange Money Côte d\'Ivoire',
            'moov-ci'              => 'Moov Côte d\'Ivoire',
            'wave-ci'              => 'Wave Côte d\'Ivoire',
            't-money-togo'         => 'T-Money Togo',
            'moov-togo'            => 'Moov Togo',
            'orange-money-mali'    => 'Orange Money Mali',
            'orange-money-burkina' => 'Orange Money Burkina',
            'moov-burkina-faso'    => 'Moov Burkina',
            'paydunya'             => 'Compte PayDunya',
        ];
    }

    public function getWithdrawModeLabelAttribute(): string
    {
        return self::withdrawModeLabels()[$this->withdraw_mode] ?? $this->withdraw_mode;
    }

    public function isFinal(): bool
    {
        return in_array($this->statut, ['success', 'failed', 'refuse', 'rembourse'], true);
    }

    /** Statuts pour le flux demande → octroi → remboursement */
    public static function statutLabels(): array
    {
        return [
            'demande_en_attente' => 'Demande en attente',
            'en_etude' => 'En étude',
            'accorde' => 'Accordé',
            'refuse' => 'Refusé',
            'debourse' => 'Décaissé',
            'en_remboursement' => 'En remboursement',
            'rembourse' => 'Remboursé',
            'created' => 'Créé',
            'pending' => 'En attente',
            'success' => 'Succès',
            'failed' => 'Échec',
        ];
    }

    public function getStatutLabelAttribute(): string
    {
        return self::statutLabels()[$this->statut] ?? $this->statut;
    }

    /** Demande en attente d'étude par l'admin */
    public function isEnAttente(): bool
    {
        return in_array($this->statut, ['demande_en_attente', 'en_etude'], true);
    }

    /** Crédit décaissé (argent envoyé au membre) */
    public function isDebourse(): bool
    {
        return in_array($this->statut, ['debourse', 'en_remboursement', 'rembourse', 'success'], true);
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Membre extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'numero',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nom_mere',
        'email',
        'email_verified_at',
        'telephone',
        'sms_verified_at',
        'adresse',
        'latitude',
        'longitude',
        'date_adhesion',
        'statut',
        'segment',
        'password',
        'nano_credit_palier_id',
        'nano_credit_interdit',
        'motif_interdiction',
        'interdit_le',
        'nb_defauts_paiement',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'date_adhesion'         => 'date',
            'date_naissance'        => 'date',
            'password'              => 'hashed',
            'nano_credit_interdit'  => 'boolean',
            'interdit_le'           => 'datetime',
        ];
    }

    /**
     * Envoyer la notification de vérification d'email (utilise la config SMTP admin)
     */
    public function sendEmailVerificationNotification(): void
    {
        app(\App\Services\EmailService::class)->sendVerificationEmail($this);
    }

    /**
     * Nom complet du membre
     */
    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * Vérifier si le membre est actif
     */
    public function isActif()
    {
        return $this->statut === 'actif';
    }

    /**
     * Obtenir le nom du guard pour l'authentification
     */
    public function getGuardName()
    {
        return 'membre';
    }

    /**
     * Relation avec les paiements
     */
    public function paiements()
    {
        return $this->hasMany(\App\Models\Paiement::class);
    }

    /**
     * Obtenir les cotisations via les paiements
     */
    public function cotisations()
    {
        return $this->hasManyThrough(
            \App\Models\Cotisation::class,
            \App\Models\Paiement::class,
            'membre_id',
            'id',
            'id',
            'cotisation_id'
        );
    }

    /**
     * Relation avec les engagements
     */
    public function engagements()
    {
        return $this->hasMany(\App\Models\Engagement::class);
    }


    /**
     * Adhésions aux cotisations (pratiques)
     */
    public function cotisationAdhesions()
    {
        return $this->hasMany(\App\Models\CotisationAdhesion::class);
    }


    /**
     * Relation avec les remboursements
     */
    public function remboursements()
    {
        return $this->hasMany(\App\Models\Remboursement::class);
    }

    /**
     * Relation KYC (une vérification par membre)
     */
    public function kycVerification()
    {
        return $this->hasOne(\App\Models\KycVerification::class);
    }

    /**
     * Vérifier si le membre a un KYC validé
     */
    public function hasKycValide(): bool
    {
        return $this->kycVerification && $this->kycVerification->isValide();
    }

    /**
     * Souscriptions épargne du membre
     */
    public function epargneSouscriptions()
    {
        return $this->hasMany(EpargneSouscription::class);
    }

    /**
     * Nano crédits (déboursements) du membre
     */
    public function nanoCredits()
    {
        return $this->hasMany(NanoCredit::class);
    }

    /**
     * Nano crédits pour lesquels ce membre est garant
     */
    public function nanoCreditsGarantis()
    {
        return $this->hasMany(\App\Models\NanoCreditGarant::class);
    }

    /**
     * Palier nano-crédit actuel
     */
    public function nanoCreditPalier()
    {
        return $this->belongsTo(\App\Models\NanoCreditPalier::class, 'nano_credit_palier_id');
    }

    /**
     * Vérifie si le membre est interdit de nano-crédit
     */
    public function isNanoCreditInterdit(): bool
    {
        return $this->nano_credit_interdit === true;
    }

    /**
     * Vérifie si le membre a des impayés actifs (échéances dépassées non réglées)
     */
    public function hasImpayes(): bool
    {
        return $this->nanoCredits()
            ->whereIn('statut', ['debourse', 'en_remboursement'])
            ->whereHas('echeances', function ($q) {
                $q->where('statut', 'a_venir')
                  ->where('date_echeance', '<', now()->toDateString());
            })
            ->exists();
    }

    /**
     * Nombre de jours de retard max sur les crédits actifs
     */
    public function maxJoursRetard(): int
    {
        $retard = 0;
        $this->nanoCredits()
            ->whereIn('statut', ['debourse', 'en_remboursement'])
            ->each(function ($credit) use (&$retard) {
                $retard = max($retard, $credit->jours_retard ?? 0);
            });
        return $retard;
    }
    /**
     * Normaliser un numéro de téléphone au format international (E.164)
     */
    public static function normalizePhoneNumber(string $telephone, string $defaultCountryCode = '226'): string
    {
        // Supprimer tout ce qui n'est pas un chiffre
        $digits = preg_replace('/\D/', '', $telephone);
        
        if (empty($digits)) return '';

        // Si le numéro commence par +, 00 ou un indicatif connu
        // On considère qu'il est déjà internationalisé
        $indicatifs = ['221', '229', '225', '228', '223', '226', '33', '1'];
        
        // Gérer le prefixe 00
        if (str_starts_with($telephone, '00')) {
            $digits = substr($digits, 2);
        }

        foreach ($indicatifs as $code) {
            if (str_starts_with($digits, $code)) {
                // Si on a l'indicatif suivi d'un 0 (ex: 22607...), on enlève le 0
                if (strlen($digits) > strlen($code) + 1 && $digits[strlen($code)] === '0') {
                    $digits = substr($digits, 0, strlen($code)) . substr($digits, strlen($code) + 1);
                }
                return '+' . $digits;
            }
        }

        // Si pas d'indicatif détecté, on ajoute l'indicatif par défaut
        // En enlevant le 0 initial si présent (ex: 07... -> +2267...)
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+' . $defaultCountryCode . $digits;
    }

    /**
     * Rechercher un membre par son téléphone de manière flexible
     */
    public static function findByTelephone(string $telephone)
    {
        $normalized = self::normalizePhoneNumber($telephone);
        if (empty($normalized)) return null;

        // On cherche le match exact sur le format normalisé
        $membre = self::where('telephone', $normalized)->first();
        
        if (!$membre) {
            // Tentative de recherche sans le +226 si c'est le local
            $local = preg_replace('/^\+226/', '', $normalized);
            if ($local !== $normalized) {
                $membre = self::where('telephone', 'like', '%' . $local)->first();
            }
        }

        return $membre;
    }
}

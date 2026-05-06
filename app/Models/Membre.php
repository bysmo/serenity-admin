<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Traits\HasChecksum;

class Membre extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasChecksum, HasApiTokens;

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
        'pays',
        'ville',
        'quartier',
        'secteur',
        'latitude',
        'longitude',
        'date_adhesion',
        'statut',
        'segment_id',   // FK → segments.id (segmentation clientèle)
        'password',
        'nano_credit_palier_id',
        'nano_credit_interdit',
        'motif_interdiction',
        'interdit_le',
        'nb_defauts_paiement',
        'garant_qualite',
        'garant_solde',
        'fcm_token',
        'push_platform',
        'checksum',
        'photo',
        // Parrainage
        'code_parrainage',
        'parrain_id',
        // Sécurité PIN
        'code_pin',
        'code_pin_created_at',
        'pin_attempts',
        'pin_locked_until',
        'pin_enabled',
        'pin_mode',
    ];
    
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MembreResetPassword($token));
    }

    protected static function booted()
    {
        // La création des comptes est gérée par App\Observers\MembreObserver
    }

    protected $hidden = [
        'password',
        'remember_token',
        'code_pin',
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
            'garant_solde'          => \App\Casts\EncryptedDecimal::class,
            // PIN
            'code_pin_created_at'   => 'datetime',
            'pin_locked_until'      => 'datetime',
            'pin_attempts'          => 'integer',
            'pin_enabled'           => 'boolean',
        ];
    }

    // ─── Sécurité PIN ─────────────────────────────────────────────────────────

    /** Nombre maximum de tentatives PIN avant verrouillage */
    public const PIN_MAX_ATTEMPTS  = 5;
    /** Durée de verrouillage en minutes après échecs */
    public const PIN_LOCK_MINUTES  = 30;

    /**
     * Vérifie si le membre a défini un code PIN
     */
    public function hasPin(): bool
    {
        return !empty($this->code_pin);
    }

    /**
     * Vérifie si le PIN est temporairement verrouillé
     */
    public function isPinLocked(): bool
    {
        return $this->pin_locked_until && $this->pin_locked_until->isFuture();
    }

    /**
     * Vérifie le PIN fourni. Gère les tentatives et le verrouillage automatique.
     * Retourne true si correct, false sinon.
     */
    public function verifyPin(string $pin): bool
    {
        if ($this->isPinLocked()) {
            return false;
        }

        $valid = \Illuminate\Support\Facades\Hash::check($pin, $this->code_pin);

        if ($valid) {
            // Réinitialiser le compteur en cas de succès
            $this->update(['pin_attempts' => 0, 'pin_locked_until' => null]);
            return true;
        }

        // Incrémenter les tentatives
        $attempts = ($this->pin_attempts ?? 0) + 1;
        $updateData = ['pin_attempts' => $attempts];

        if ($attempts >= self::PIN_MAX_ATTEMPTS) {
            $updateData['pin_locked_until'] = now()->addMinutes(self::PIN_LOCK_MINUTES);
            $updateData['pin_attempts']     = 0;
        }

        $this->update($updateData);
        return false;
    }

    /**
     * Définit ou modifie le code PIN (hashé en base).
     */
    public function setPin(string $pin): void
    {
        $this->update([
            'code_pin'            => \Illuminate\Support\Facades\Hash::make($pin),
            'code_pin_created_at' => now(),
            'pin_attempts'        => 0,
            'pin_locked_until'    => null,
        ]);
    }

    /**
     * Indique si le membre a activé la protection PIN.
     */
    public function isPinEnabled(): bool
    {
        return (bool) ($this->pin_enabled ?? false);
    }

    /**
     * Indique si le mode choisi est la session 5 minutes (option B).
     */
    public function isPinModeSession(): bool
    {
        return ($this->pin_mode ?? 'each_time') === 'session';
    }

    /**
     * Active la protection PIN avec le mode spécifié.
     * Nécessite que le PIN soit déjà défini.
     *
     * @param string $mode  'each_time' (option A) | 'session' (option B)
     */
    public function enablePin(string $mode = 'each_time'): void
    {
        $this->update([
            'pin_enabled' => true,
            'pin_mode'    => in_array($mode, ['each_time', 'session']) ? $mode : 'each_time',
        ]);
    }

    /**
     * Désactive la protection PIN (les opérations critiques ne demandent plus le PIN).
     */
    public function disablePin(): void
    {
        $this->update(['pin_enabled' => false]);
    }

    /**
     * Change le mode PIN sans activer/désactiver.
     *
     * @param string $mode  'each_time' | 'session'
     */
    public function setPinMode(string $mode): void
    {
        $this->update([
            'pin_mode' => in_array($mode, ['each_time', 'session']) ? $mode : 'each_time',
        ]);
    }

    /**
     * Retourne l'URL complète de la photo de profil
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }
        return url(\Illuminate\Support\Facades\Storage::url($this->photo));
    }

    /**
     * Retourne les données formatées pour l'API mobile de manière harmonisée.
     */
    public function toApiResource(): array
    {
        $this->loadMissing('segment');

        return [
            'id'                => $this->id,
            'numero'            => $this->numero,
            'nom'               => $this->nom,
            'prenom'            => $this->prenom,
            'nom_complet'       => $this->nom_complet,
            'email'             => $this->email,
            'telephone'         => $this->telephone,
            'adresse'           => $this->adresse,
            'pays'              => $this->pays,
            'ville'             => $this->ville,
            'photo_url'         => $this->photo_url,
            'date_adhesion'     => $this->date_adhesion?->format('Y-m-d'),
            'statut'            => $this->statut,
            'sexe'              => $this->sexe,
            'kyc_valide'        => $this->hasKycValide(),
            'has_pin'           => $this->hasPin(),
            'pin_enabled'       => $this->isPinEnabled(),
            'pin_mode'          => $this->pin_mode ?? 'each_time',
            'segment_id'        => $this->segment_id,
            'segment_label'     => $this->segment_label,
            'solde_global'      => (float) $this->solde_global,
            'fcm_token'         => $this->fcm_token,
            'code_parrainage'   => $this->code_parrainage,
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
     * Segment de segmentation clientèle du membre.
     */
    public function segment()
    {
        return $this->belongsTo(\App\Models\Segment::class, 'segment_id');
    }

    /**
     * Libellé du segment (ou "NON CLASSÉ" si non défini).
     */
    public function getSegmentLabelAttribute(): string
    {
        return $this->segment?->nom ?? 'NON CLASSÉ';
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
     * Comptes bancaires associés à ce client
     */
    public function comptes()
    {
        return $this->hasMany(\App\Models\Caisse::class, 'membre_id');
    }

    /**
     * Calcul du solde global (somme de tous les comptes)
     */
    public function getSoldeGlobalAttribute(): float
    {
        return (float) $this->comptes->sum(function ($compte) {
            return $compte->solde_actuel;
        });
    }

    /**
     * Premier compte courant (compte par défaut)
     */
    public function compteCourant()
    {
        return $this->hasOne(\App\Models\Caisse::class, 'membre_id')->where('type', 'courant')->oldest();
    }

    /**
     * Premier compte épargne
     */
    public function compteEpargne()
    {
        return $this->hasOne(\App\Models\Caisse::class, 'membre_id')->where('type', 'epargne')->oldest();
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
     * Vérifie si le membre a un crédit en cours (non remboursé, non refusé)
     */
    public function hasCreditEnCours(): bool
    {
        return $this->nanoCredits()
            ->whereNotIn('statut', ['rembourse', 'refuse', 'failed', 'annule'])
            ->exists();
    }

    /**
     * Nano crédits pour lesquels ce membre est garant
     */
    public function garants()
    {
        return $this->hasMany(\App\Models\NanoCreditGarant::class);
    }

    /**
     * Garanties actuellement actives (acceptées ou prélevées, non encore libérées)
     */
    public function garantiesActives()
    {
        return $this->hasMany(\App\Models\NanoCreditGarant::class)
            ->whereIn('statut', ['accepte', 'preleve']);
    }

    /**
     * Vérifie si l'épargne du membre est bloquée car il est garant actif
     */
    public function isEpargneBloquee(): bool
    {
        return $this->garantiesActives()->exists();
    }

    /**
     * Calcul du solde total de l'épargne (tontine)
     */
    public function totalEpargneSolde(): float
    {
        return (float) $this->epargneSouscriptions()->sum('solde_courant');
    }

    /**
     * Nombre maximum de garanties que ce membre peut assumer simultanément.
     * Basé sur la qualité : au moins 1, et plus si la qualité augmente.
     */
    public function maximumGaranties(): int
    {
        return max(1, (int) $this->garant_qualite);
    }

    /**
     * Vérifie si le membre a atteint sa limite de garanties
     */
    public function aAtteintLimiteGaranties(): bool
    {
        return $this->garantiesActives()->count() >= $this->maximumGaranties();
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
        $indicatifs = ['221','223', '225', '226', '227', '228', '229'];
        
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

    // ─── Relations de parrainage ──────────────────────────────────────────────

    /**
     * Le membre qui a parrainé ce membre
     */
    public function parrain()
    {
        return $this->belongsTo(Membre::class, 'parrain_id');
    }

    /**
     * Les membres que ce membre a parrainés (filleuls directs niveau 1)
    */
    public function filleuls()
    {
        return $this->hasMany(Membre::class, 'parrain_id');
    }

    /**
     * Commissions de parrainage générées pour ce membre (en tant que parrain)
     */
    public function commissionsParrainage()
    {
        return $this->hasMany(\App\Models\ParrainageCommission::class, 'parrain_id');
    }

    /**
     * Commission générée quand ce membre s'est inscrit via un parrain
     */
    public function commissionFilleul()
    {
        return $this->hasOne(\App\Models\ParrainageCommission::class, 'filleul_id');
    }

    /**
     * Générer un code de parrainage unique pour ce membre
     */
    public function genererCodeParrainage(): string
    {
        do {
            // Code : 3 lettres majuscules + 5 chiffres, ex: ABX12345
            $code = strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper(base_convert(crc32($this->nom . $this->prenom . uniqid()), 10, 36))), 0, 3))
                  . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('code_parrainage', $code)->exists());

        $this->update(['code_parrainage' => $code]);
        return $code;
    }

    /**
     * Obtenir ou créer le code de parrainage
     */
    public function getOrCreateCodeParrainage(): string
    {
        if (!$this->code_parrainage) {
            return $this->genererCodeParrainage();
        }
        return $this->code_parrainage;
    }

    /**
     * Total des commissions disponibles (réclamables)
     */
    public function totalCommissionsDisponibles(): float
    {
        return (float) $this->commissionsParrainage()
            ->where('statut', 'disponible')
            ->where(function ($q) {
                $q->whereNull('disponible_le')->orWhere('disponible_le', '<=', now());
            })
            ->sum('montant');
    }

    /**
     * Total des commissions déjà payées
     */
    public function totalCommissionsPayees(): float
    {
        return (float) $this->commissionsParrainage()
            ->where('statut', 'paye')
            ->sum('montant');
    }

    /**
     * Nombre de filleuls actifs (statut actif)
     */
    public function nbFilleulsActifs(): int
    {
        return $this->filleuls()->where('statut', 'actif')->count();
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
    /**
     * Relation avec les alias de portefeuille Pi-SPI
     */
    public function walletAliases()
    {
        return $this->hasMany(MembreWalletAlias::class);
    }

    /**
     * Récupère l'alias de portefeuille par défaut pour Pi-SPI
     */
    public function defaultWalletAlias()
    {
        return $this->walletAliases()->where('is_default', true)->first() 
               ?? $this->walletAliases()->first();
    }
}

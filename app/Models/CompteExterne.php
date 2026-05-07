<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompteExterne extends Model
{
    use HasFactory;

    protected $table = 'membre_comptes_externes';

    protected $fillable = [
        'membre_id',
        'nom',
        'description',
        'pays',
        'type_identifiant',
        'identifiant',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ─── Types d'identifiants disponibles ────────────────────────────────────

    const TYPE_ALIAS     = 'alias';
    const TYPE_TELEPHONE = 'telephone';
    const TYPE_IBAN      = 'iban';

    const TYPES = [
        self::TYPE_ALIAS     => 'Alias Pi-SPI (UUID)',
        self::TYPE_TELEPHONE => 'Numéro de téléphone',
        self::TYPE_IBAN      => 'IBAN',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function membre()
    {
        return $this->belongsTo(Membre::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'compte_externe_id');
    }

    // ─── Méthodes métier ─────────────────────────────────────────────────────

    /**
     * Retourne vrai si le compte peut être utilisé pour un paiement Pi-SPI.
     * Pi-SPI accepte les alias et les numéros de téléphone.
     */
    public function supportePiSpi(): bool
    {
        return in_array($this->type_identifiant, [self::TYPE_ALIAS, self::TYPE_TELEPHONE]);
    }

    /**
     * Retourne le payload à envoyer à Pi-SPI selon le type d'identifiant.
     * Pi-SPI attend toujours un "payeurAlias".
     */
    public function getPayeurAliasForPiSpi(): string
    {
        return $this->identifiant;
    }

    /**
     * Valide le format de l'identifiant selon le type.
     */
    public static function validateIdentifiant(string $type, string $identifiant): bool
    {
        return match ($type) {
            self::TYPE_ALIAS     => self::isValidUuid($identifiant),
            self::TYPE_TELEPHONE => self::isValidTelephone($identifiant),
            self::TYPE_IBAN      => self::isValidIban($identifiant),
            default              => false,
        };
    }

    /**
     * Règle de validation Laravel selon le type.
     */
    public static function getValidationRuleForIdentifiant(string $type): string
    {
        return match ($type) {
            self::TYPE_ALIAS     => 'uuid',
            self::TYPE_TELEPHONE => 'regex:/^\+[1-9]\d{7,14}$/',
            self::TYPE_IBAN      => 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,}$/',
            default              => 'string',
        };
    }

    /**
     * Retourne un libellé masqué pour l'affichage (ex: +226 70 *** **34).
     */
    public function getIdentifiantMasqueAttribute(): string
    {
        $id = $this->identifiant;

        return match ($this->type_identifiant) {
            self::TYPE_TELEPHONE => preg_replace('/(\+\d{1,4})(\d*)(\d{2})$/', '$1 *** **$3', $id),
            self::TYPE_IBAN      => substr($id, 0, 4) . str_repeat('*', max(0, strlen($id) - 8)) . substr($id, -4),
            default              => $id,  // Alias UUID affiché complet
        };
    }

    /**
     * Retourne le type d'identifiant sous forme de libellé lisible.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type_identifiant] ?? $this->type_identifiant;
    }

    // ─── Helpers de validation ────────────────────────────────────────────────

    private static function isValidUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value
        );
    }

    private static function isValidTelephone(string $value): bool
    {
        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $value);
    }

    private static function isValidIban(string $value): bool
    {
        // Validation basique format IBAN : 2 lettres + 2 chiffres + jusqu'à 30 alphanum
        $clean = strtoupper(str_replace(' ', '', $value));
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/', $clean)) {
            return false;
        }
        // Validation checksum MOD-97
        $rearranged = substr($clean, 4) . substr($clean, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (ord($char) - 55) : $char;
        }
        return bcmod($numeric, '97') === '1';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'nom',
        'description',
        'solde_initial',
        'statut',
        'type',
        'numero_core_banking',
        'membre_id',
    ];

    /**
     * Relation avec le membre (Client) propriétaire
     */
    public function membre()
    {
        return $this->belongsTo(\App\Models\Membre::class);
    }

    protected $casts = [
        'solde_initial' => \App\Casts\EncryptedDecimal::class,
    ];

    /**
     * Calculer le solde actuel de la caisse
     */
    public function getSoldeActuelAttribute()
    {
        $solde = (float) ($this->solde_initial ?? 0);
        // On récupère les mouvements et on fait la somme en PHP car la colonne montant est cryptée
        $mouvements = $this->mouvements()->get();
        
        $entrees = (float) $mouvements->where('sens', 'entree')->sum('montant');
        $sorties = (float) $mouvements->where('sens', 'sortie')->sum('montant');
        
        return $solde + $entrees - $sorties;
    }

    /**
     * Types de comptes devant rester négatifs ou nuls (dettes)
     */
    public static function restrictedNegativeTypes(): array
    {
        return ['credit', 'impayes'];
    }

    /**
     * Vérifie si un mouvement est autorisé selon le type de compte
     */
    public function canAcceptMovement(float $amount, string $sens): bool
    {
        if (!in_array($this->type, self::restrictedNegativeTypes())) {
            return true;
        }

        $currentSolde = $this->solde_actuel;
        $newSolde = ($sens === 'entree') ? ($currentSolde + $amount) : ($currentSolde - $amount);

        // Pour les dettes, le solde doit rester <= 0
        return $newSolde <= 0;
    }

    /**
     * Vérifier si la caisse est active
     */
    public function isActive()
    {
        return $this->statut === 'active';
    }

    /**
     * Relation avec les cotisations (templates)
     */
    public function cotisations()
    {
        return $this->hasMany(\App\Models\Cotisation::class);
    }

    /**
     * Relation avec les paiements
     */
    public function paiements()
    {
        return $this->hasMany(\App\Models\Paiement::class);
    }

    /**
     * Journal des mouvements de caisse
     */
    public function mouvements()
    {
        return $this->hasMany(\App\Models\MouvementCaisse::class);
    }

    /**
     * Plans d'épargne associés à cette caisse
     */
    public function epargnePlans()
    {
        return $this->hasMany(\App\Models\EpargnePlan::class);
    }

    /**
     * Récupère un compte système par son code Core Banking.
     */
    public static function getSystemCaisse(string $coreBankingNumero): ?self
    {
        return self::where('numero_core_banking', $coreBankingNumero)->first();
    }

    /**
     * Helpers spécifiques pour les comptes de contrôle globaux
     */
    public static function getCaisseCagnottePub(): ?self { return self::getSystemCaisse('SYS-CAG-PUB'); }
    public static function getCaisseCagnottePrv(): ?self { return self::getSystemCaisse('SYS-CAG-PRV'); }
    public static function getCaisseTontineCli(): ?self { return self::getSystemCaisse('SYS-TON-CLI'); }
    public static function getCaisseEpargneLibre(): ?self { return self::getSystemCaisse('SYS-EPG-CLI'); }
    public static function getCaisseNanoCredit(): ?self { return self::getSystemCaisse('SYS-NAN-CRD'); }
    public static function getCaisseProduit(): ?self { return self::getSystemCaisse('SYS-PROD'); }
    public static function getCaisseCharge(): ?self { return self::getSystemCaisse('SYS-CHG'); }

    /**
     * Génère un numéro de compte unique au format XXXX-XXXX
     */
    public static function generateNumeroCompte(): string
    {
        do {
            $part1 = strtoupper(\Illuminate\Support\Str::random(4));
            $part2 = strtoupper(\Illuminate\Support\Str::random(4));
            $numero = $part1 . '-' . $part2;
        } while (static::where('numero', $numero)->exists());

        return $numero;
    }
}

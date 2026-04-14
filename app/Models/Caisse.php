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
        $entrees = $this->mouvements()->where('sens', 'entree')->get()->sum('montant');
        $sorties = $this->mouvements()->where('sens', 'sortie')->get()->sum('montant');
        return $solde + $entrees - $sorties;
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
}

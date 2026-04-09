<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasChecksum;

class Cotisation extends Model
{
    use HasFactory, HasChecksum;

    protected $fillable = [
        'numero',
        'code',
        'nom',
        'caisse_id',
        'created_by_membre_id',
        'admin_membre_id',
        'type',
        'frequence',
        'type_montant',
        'montant',
        'description',
        'notes',
        'actif',
        'tag',
        'visibilite',
        'checksum',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'actif' => 'boolean',
    ];

    /**
     * Relation avec la caisse
     */
    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    /**
     * Membre créateur - pour les cotisations créées par un membre
     */
    public function createdByMembre()
    {
        return $this->belongsTo(Membre::class, 'created_by_membre_id');
    }

    /**
     * Membre désigné comme administrateur (optionnel). Si null, l'admin est le créateur.
     */
    public function adminMembre()
    {
        return $this->belongsTo(Membre::class, 'admin_membre_id');
    }

    /**
     * Vérifier si la cotisation est créée par un membre (vs admin app)
     */
    public function isCreatedByMembre(): bool
    {
        return $this->created_by_membre_id !== null;
    }

    /**
     * Obtenir l'id du membre administrateur : désigné ou créateur
     */
    public function getAdminMembreId(): ?int
    {
        return $this->admin_membre_id ?? $this->created_by_membre_id;
    }

    /**
     * Demandes de versement des fonds vers l'admin app
     */
    public function versementDemandes()
    {
        return $this->hasMany(CotisationVersementDemande::class);
    }

    /**
     * Relation avec les paiements
     */
    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Relation avec les engagements
     */
    public function engagements()
    {
        return $this->hasMany(\App\Models\Engagement::class);
    }

    /**
     * Adhésions des membres à cette cotisation (pratique)
     */
    public function adhesions()
    {
        return $this->hasMany(CotisationAdhesion::class);
    }

    /**
     * Vérifier si la cotisation est publique
     */
    public function isPublique(): bool
    {
        return ($this->visibilite ?? 'publique') === 'publique';
    }

    /**
     * Vérifier si la cotisation est privée
     */
    public function isPrivee(): bool
    {
        return ($this->visibilite ?? 'publique') === 'privee';
    }

    /**
     * Vérifier si la cotisation est active
     */
    public function isActive()
    {
        return $this->actif === true;
    }
}

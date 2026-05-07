<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasChecksum;

class Paiement extends Model
{
    use HasFactory, HasChecksum;

    protected $fillable = [
        'numero',
        'reference',
        'membre_id',
        'cotisation_id',
        'caisse_id',
        'wallet_alias_id',
        'compte_externe_id',
        'montant',
        'date_paiement',
        'mode_paiement',
        'statut',
        'metadata',
        'notes',
        'commentaire',
        'checksum',
    ];

    protected $casts = [
        'montant'       => \App\Casts\EncryptedDecimal::class,
        'date_paiement' => 'date',
        'metadata'      => 'array',
    ];

    /**
     * Relation avec le membre
     */
    public function membre()
    {
        return $this->belongsTo(Membre::class);
    }

    /**
     * Relation avec l'alias de portefeuille utilisé (legacy)
     */
    public function walletAlias()
    {
        return $this->belongsTo(MembreWalletAlias::class, 'wallet_alias_id');
    }

    /**
     * Relation avec le compte externe utilisé
     */
    public function compteExterne()
    {
        return $this->belongsTo(\App\Models\CompteExterne::class, 'compte_externe_id');
    }

    /**
     * Relation avec la cotisation (template)
     */
    public function cotisation()
    {
        return $this->belongsTo(Cotisation::class);
    }

    /**
     * Relation avec la caisse
     */
    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    /**
     * Relation avec les remboursements
     */
    public function remboursements()
    {
        return $this->hasMany(Remboursement::class);
    }
}

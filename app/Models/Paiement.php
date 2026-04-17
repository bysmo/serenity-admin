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

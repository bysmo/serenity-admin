<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasChecksum;

class EpargneEcheance extends Model
{
    use HasFactory, HasChecksum;

    protected $table = 'epargne_echeances';

    protected $fillable = [
        'souscription_id',
        'date_echeance',
        'montant',
        'statut',
        'paye_le',
        'dernier_rappel_at',
        'checksum',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'date_echeance' => 'date',
        'paye_le' => 'datetime',
        'dernier_rappel_at' => 'datetime',
    ];

    public function souscription()
    {
        return $this->belongsTo(EpargneSouscription::class);
    }

    public function versement()
    {
        return $this->hasOne(EpargneVersement::class, 'echeance_id');
    }
}

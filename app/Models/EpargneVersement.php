<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasChecksum;

class EpargneVersement extends Model
{
    use HasFactory, HasChecksum;

    protected $table = 'epargne_versements';

    protected $fillable = [
        'souscription_id',
        'echeance_id',
        'membre_id',
        'montant',
        'date_versement',
        'mode_paiement',
        'reference',
        'caisse_id',
        'checksum',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'date_versement' => 'date',
    ];

    public function souscription()
    {
        return $this->belongsTo(EpargneSouscription::class);
    }

    public function echeance()
    {
        return $this->belongsTo(EpargneEcheance::class, 'echeance_id');
    }

    public function membre()
    {
        return $this->belongsTo(Membre::class);
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }
}

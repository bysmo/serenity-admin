<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collecte extends Model
{
    use HasFactory;

    protected $fillable = [
        'collecte_session_id',
        'membre_id',
        'type_collecte',
        'echeance_id',
        'montant',
        'otp_code',
        'is_confirmed',
        'confirmed_at',
        'reference_transaction',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(CollecteSession::class, 'collecte_session_id');
    }

    public function membre()
    {
        return $this->belongsTo(Membre::class);
    }

    public function echeance()
    {
        if ($this->type_collecte === 'tontine') {
            return $this->belongsTo(EpargneEcheance::class, 'echeance_id');
        } else {
            return $this->belongsTo(NanoCreditEcheance::class, 'echeance_id');
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarantGainRetrait extends Model
{
    protected $fillable = [
        'reference',
        'membre_id',
        'montant',
        'statut',
        'traite_par',
        'traite_le',
        'commentaire_admin',
    ];

    protected $casts = [
        'montant'   => \App\Casts\EncryptedDecimal::class,
        'traite_le' => 'datetime',
    ];

    public function membre()
    {
        return $this->belongsTo(Membre::class);
    }

    public function traitePar()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public static function statutLabels(): array
    {
        return [
            'en_attente' => 'En attente',
            'approuve'   => 'Approuvé',
            'refuse'     => 'Refusé',
        ];
    }

    public function getStatutLabelAttribute(): string
    {
        return self::statutLabels()[$this->statut] ?? $this->statut;
    }
}

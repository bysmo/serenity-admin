<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollecteSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date_session',
        'opened_at',
        'closed_at',
        'statut',
        'montant_ouverture',
        'montant_fermeture',
    ];

    protected $casts = [
        'date_session' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'montant_ouverture' => 'decimal:0',
        'montant_fermeture' => 'decimal:0',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collectes()
    {
        return $this->hasMany(Collecte::class);
    }

    public function isOuverte()
    {
        return $this->statut === 'ouvert';
    }

    /**
     * Calculer le montant total collecté durant cette session
     */
    public function getMontantTotalCollecteAttribute()
    {
        return $this->collectes()->where('is_confirmed', true)->sum('montant');
    }
}

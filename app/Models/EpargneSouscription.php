<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasChecksum;

class EpargneSouscription extends Model
{
    use HasFactory, HasChecksum;

    protected $table = 'epargne_souscriptions';

    protected $fillable = [
        'membre_id',
        'plan_id',
        'montant',
        'date_debut',
        'date_fin',
        'jour_du_mois',
        'statut',
        'solde_courant',
        'checksum',
    ];

    protected $casts = [
        'montant' => \App\Casts\EncryptedDecimal::class,
        'solde_courant' => \App\Casts\EncryptedDecimal::class,
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function membre()
    {
        return $this->belongsTo(Membre::class);
    }

    public function plan()
    {
        return $this->belongsTo(EpargnePlan::class, 'plan_id');
    }

    public function echeances()
    {
        return $this->hasMany(EpargneEcheance::class, 'souscription_id');
    }

    public function versements()
    {
        return $this->hasMany(EpargneVersement::class, 'souscription_id');
    }

    /**
     * Montant total qui sera reversé au membre à la fin (épargne + rémunération).
     * Calculé à partir du plan (taux, durée) et du montant souscrit.
     */
    public function getMontantTotalReverseAttribute(): int
    {
        $calc = $this->plan->calculRemboursement((float) $this->montant);
        return $calc['montant_total_reverse'];
    }

    /**
     * Rémunération prévue à la fin du plan.
     */
    public function getRemunerationPrevueAttribute(): int
    {
        $calc = $this->plan->calculRemboursement((float) $this->montant);
        return $calc['remuneration'];
    }
}

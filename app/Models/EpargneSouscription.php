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
        'caisse_id',
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
     * Compte financier lié à cette tontine
     */
    public function compte()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
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

    /**
     * Estimation du montant retirable à l'instant T.
     * Règle Serenity :
     * - Si tontine finie (date_fin passée) : Solde Actuel + Rémunération complète.
     * - Si tontine non finie : Solde Actuel seul (pénalité = perte des intérêts).
     */
    public function getEstimationLiquidationAttribute(): int
    {
        $capitalEpargne = (int) $this->solde_courant;
        
        // Si la date de fin est atteinte ou dépassée
        if ($this->date_fin && $this->date_fin->isPast()) {
            return $capitalEpargne + $this->remuneration_prevue;
        }

        // Sinon, juste le capital (Penalité)
        return $capitalEpargne;
    }

    /**
     * Progression de l'épargne en pourcentage par rapport au montant total à verser.
     */
    public function getTauxProgressionAttribute(): float
    {
        $totalAVerser = $this->plan->nombre_versements * (float) $this->montant;
        if ($totalAVerser <= 0) return 0;
        
        $progression = ((float) $this->solde_courant / $totalAVerser) * 100;
        return round(min(100, $progression), 1);
    }
}

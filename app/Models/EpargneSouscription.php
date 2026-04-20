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

    /**
     * Initialiser le compte tontine automatique lors de la souscription.
     */
    protected static function booted()
    {
        static::created(function ($souscription) {
            $clientNom = $souscription->membre->nom_complet ?? 'Client #' . $souscription->membre_id;

            // Réutilisation ou création du compte Tontine (unique par membre)
            $compte = \App\Models\Caisse::where('membre_id', $souscription->membre_id)
                ->where('type', 'tontine')
                ->first();

            if (!$compte) {
                $compte = \App\Models\Caisse::create([
                    'membre_id'     => $souscription->membre_id,
                    'nom'           => "Compte Tontine - {$clientNom}",
                    'numero'        => \App\Models\Caisse::generateNumeroCompte(),
                    'solde_initial' => 0,
                    'statut'        => 'active',
                    'type'          => 'tontine',
                ]);
            }

            // Liaison de la souscription au compte
            $souscription->update(['caisse_id' => $compte->id]);
        });
    }

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParrainageConfig extends Model
{
    use HasFactory, \App\Traits\HasChecksum;

    protected $table = 'parrainage_configs';

    protected $fillable = [
        'actif',
        'type_remuneration',
        'montant_remuneration',
        'declencheur',
        'delai_validation_jours',
        'niveaux_parrainage',
        'taux_niveau_2',
        'taux_niveau_3',
        'description',
        'min_filleuls_retrait',
        'montant_min_retrait',
        'checksum',
    ];

    protected function casts(): array
    {
        return [
            'actif'                 => 'boolean',
            'montant_remuneration'  => 'decimal:2',
            'taux_niveau_2'         => 'decimal:2',
            'taux_niveau_3'         => 'decimal:2',
            'montant_min_retrait'   => 'decimal:2',
            'delai_validation_jours'=> 'integer',
            'niveaux_parrainage'    => 'integer',
            'min_filleuls_retrait'  => 'integer',
        ];
    }

    /**
     * Récupérer la configuration active (singleton)
     */
    public static function current(): self
    {
        return self::firstOrCreate([], [
            'actif'                 => false,
            'type_remuneration'     => 'fixe',
            'montant_remuneration'  => 0,
            'declencheur'           => 'inscription',
            'delai_validation_jours'=> 0,
            'niveaux_parrainage'    => 1,
            'taux_niveau_2'         => 0,
            'taux_niveau_3'         => 0,
            'description'           => null,
            'min_filleuls_retrait'  => 1,
            'montant_min_retrait'   => 0,
        ]);
    }

    /**
     * Calculer le montant de commission pour un niveau donné
     */
    public function calculerCommission(float $montantBase, int $niveau = 1): float
    {
        $taux = match ($niveau) {
            2 => (float) $this->taux_niveau_2,
            3 => (float) $this->taux_niveau_3,
            default => (float) $this->montant_remuneration,
        };

        if ($this->type_remuneration === 'pourcentage') {
            return round(($montantBase * $taux) / 100, 2);
        }

        return round($taux, 2);
    }

    /**
     * Libellé du type de rémunération
     */
    public function getLabelTypeRemunerationAttribute(): string
    {
        return match ($this->type_remuneration) {
            'pourcentage' => 'Pourcentage (%)',
            default       => 'Montant fixe (FCFA)',
        };
    }

    /**
     * Libellé du déclencheur
     */
    public function getLabelDeclencheurAttribute(): string
    {
        return match ($this->declencheur) {
            'premier_paiement'      => 'Premier paiement du filleul',
            'adhesion_cotisation'   => 'Adhésion à une cotisation',
            default                 => 'Inscription du filleul',
        };
    }
}

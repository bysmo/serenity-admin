<?php

namespace App\Observers;

use App\Models\EpargneSouscription;
use App\Models\Caisse;
use Illuminate\Support\Str;

class EpargneSouscriptionObserver
{
    /**
     * Gérer l'événement "création" (avant insertion).
     * On crée OU réutilise le compte tontine unique du membre, et on l'associe à la souscription.
     */
    public function creating(EpargneSouscription $souscription): void
    {
        $membre = $souscription->membre;
        $plan   = $souscription->plan;

        // Réutiliser le compte tontine existant (unique par membre) ou en créer un
        $compte = Caisse::where('membre_id', $membre->id)
            ->where('type', 'tontine')
            ->first();

        if (!$compte) {
            $compte = Caisse::create([
                'membre_id'    => $membre->id,
                'nom'          => 'Compte Tontine ',
                'numero'       => $this->generateNumeroCaisse(),
                'solde_initial'=> 0,
                'statut'       => 'active',
                'type'         => 'tontine',
            ]);
        }

        // Lier le compte à la souscription
        $souscription->caisse_id = $compte->id;
    }

    /**
     * Générer un numéro de compte unique (format XXXX-XXXX)
     */
    private function generateNumeroCaisse(): string
    {
        do {
            $part1 = strtoupper(Str::random(4));
            $part2 = strtoupper(Str::random(4));
            $numero = $part1 . '-' . $part2;
        } while (Caisse::where('numero', $numero)->exists());

        return $numero;
    }
}

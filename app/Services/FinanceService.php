<?php

namespace App\Services;

use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\EpargneSouscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceService
{
    /**
     * Clôture une souscription de tontine avec versement des intérêts.
     * 
     * @param EpargneSouscription $souscription
     * @return array
     */
    public function cloturerSouscription(EpargneSouscription $souscription): array
    {
        if ($souscription->statut === 'cloture') {
            throw new \Exception("Cette souscription est déjà clôturée.");
        }

        return DB::transaction(function () use ($souscription) {
            $plan = $souscription->plan;
            $membre = $souscription->membre;
            
            // 1. Calcul des intérêts (rémunération)
            $calc = $plan->calculRemboursement((float) $souscription->montant);
            $interet = (float) $calc['remuneration'];
            $principal = (float) $souscription->solde_courant; // On prend ce qui a été réellement versé
            $totalReverse = $principal + $interet;

            $referenceType = EpargneSouscription::class;
            $referenceId = $souscription->id;

            // --- ÉTAPE A : ATTRIBUTION DES INTÉRÊTS (CHARGE SYSTEME) ---
            if ($interet > 0) {
                $caisseCharge = Caisse::getCaisseCharge();
                $caisseTontineCli = Caisse::getCaisseTontineCli();
                $compteTontineMembre = $souscription->compte;

                // 1. Débit du compte de charge global
                if ($caisseCharge) {
                    MouvementCaisse::create([
                        'caisse_id'      => $caisseCharge->id,
                        'type'           => 'interet_versé',
                        'sens'           => 'sortie',
                        'montant'        => $interet,
                        'date_operation' => now(),
                        'libelle'        => 'CHARGE INTÉRÊT TONTINE: #' . $souscription->id,
                        'notes'          => "Rémunération plan: " . $plan->nom,
                        'reference_type' => $referenceType,
                        'reference_id'   => $referenceId,
                    ]);
                }

                // 2. Crédit du compte tontine du membre
                if ($compteTontineMembre) {
                    MouvementCaisse::create([
                        'caisse_id'      => $compteTontineMembre->id,
                        'type'           => 'bonus_interet',
                        'sens'           => 'entree',
                        'montant'        => $interet,
                        'date_operation' => now(),
                        'libelle'        => 'Bonus Intérêt Tontine',
                        'notes'          => "Rémunération sur plan " . $plan->nom,
                        'reference_type' => $referenceType,
                        'reference_id'   => $referenceId,
                    ]);
                }

                // 3. Crédit du compte global tontine (Réconciliation)
                if ($caisseTontineCli) {
                    MouvementCaisse::create([
                        'caisse_id'      => $caisseTontineCli->id,
                        'type'           => 'bonus_interet',
                        'sens'           => 'entree',
                        'montant'        => $interet,
                        'date_operation' => now(),
                        'libelle'        => 'GLOBAL - BONUS INTÉRÊT TONTINE (#' . $membre->id . ')',
                        'reference_type' => $referenceType,
                        'reference_id'   => $referenceId,
                    ]);
                }
            }

            // --- ÉTAPE B : TRANSFERT VERS LE COMPTE COURANT (SORTIE TONTINE) ---
            $compteCourant = $membre->compteCourant;
            if ($compteCourant && $totalReverse > 0) {
                // 1. Débit du compte tontine membre (Vider le compte)
                MouvementCaisse::create([
                    'caisse_id'      => $souscription->caisse_id,
                    'type'           => 'cloture_tontine',
                    'sens'           => 'sortie',
                    'montant'        => $totalReverse,
                    'date_operation' => now(),
                    'libelle'        => 'Clôture Tontine #' . $souscription->id . ' - Sortie fonds',
                    'reference_type' => $referenceType,
                    'reference_id'   => $referenceId,
                ]);

                // 2. Débit du compte global tontine (Réconciliation)
                $caisseTontineCli = Caisse::getCaisseTontineCli();
                if ($caisseTontineCli) {
                    MouvementCaisse::create([
                        'caisse_id'      => $caisseTontineCli->id,
                        'type'           => 'cloture_tontine',
                        'sens'           => 'sortie',
                        'montant'        => $totalReverse,
                        'date_operation' => now(),
                        'libelle'        => 'GLOBAL - CLÔTURE TONTINE (#' . $membre->id . ')',
                        'reference_type' => $referenceType,
                        'reference_id'   => $referenceId,
                    ]);
                }

                // 3. Crédit du compte courant membre
                MouvementCaisse::create([
                    'caisse_id'      => $compteCourant->id,
                    'type'           => 'transfert_tontine',
                    'sens'           => 'entree',
                    'montant'        => $totalReverse,
                    'date_operation' => now(),
                    'libelle'        => 'Retour Tontine #' . $souscription->id . ' (+Intérêts)',
                    'reference_type' => $referenceType,
                    'reference_id'   => $referenceId,
                ]);
            }

            // 4. Mise à jour statut
            $souscription->update(['statut' => 'cloture']);

            return [
                'success' => true,
                'principal' => $principal,
                'interet' => $interet,
                'total' => $totalReverse
            ];
        });
    }
}

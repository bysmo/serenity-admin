<?php

namespace App\Services;

use App\Models\EpargneEcheance;
use App\Models\EpargneSouscription;
use App\Models\EpargneVersement;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\NanoCredit;
use App\Models\NanoCreditEcheance;
use App\Models\NanoCreditVersement;
use App\Models\Paiement;
use App\Models\Cotisation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dispatcher centralisé pour les callbacks IPN PayDunya côté membre.
 *
 * Chaque handler est idempotent : une référence unique (invoice_token) empêche
 * le double-enregistrement en cas de rejeu du callback par PayDunya.
 */
class PayDunyaCallbackService
{
    /**
     * Point d'entrée principal — dispatch selon le type de paiement.
     *
     * @param  array  $customData   Données custom_data de la facture PayDunya
     * @param  float  $amount       Montant confirmé par PayDunya
     * @param  string $invoiceToken Token unique de la facture
     */
    public function handle(array $customData, float $amount, string $invoiceToken): void
    {
        $type     = $customData['type'] ?? null;
        $membreId = isset($customData['membre_id']) ? (int) $customData['membre_id'] : null;

        Log::info('PayDunyaCallbackService: dispatch', [
            'type'          => $type,
            'membre_id'     => $membreId,
            'amount'        => $amount,
            'invoice_token' => $invoiceToken,
        ]);

        try {
            DB::beginTransaction();

            match ($type) {
                'cotisation'                 => $this->handleCotisation($customData, $amount, $invoiceToken),
                'epargne'                    => $this->handleEpargne($customData, $amount, $invoiceToken),
                'epargne_libre'              => $this->handleEpargneLibre($customData, $amount, $invoiceToken),
                'nano_credit_remboursement'  => $this->handleNanoCreditRemboursement($customData, $amount, $invoiceToken),
                default => Log::warning('PayDunyaCallbackService: type inconnu', ['type' => $type]),
            };

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PayDunyaCallbackService: exception', [
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
                'invoice_token' => $invoiceToken,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COTISATION — Paiement vers la caisse d'une tontine groupe
    // ─────────────────────────────────────────────────────────────────────────

    private function handleCotisation(array $data, float $amount, string $invoiceToken): void
    {
        $reference    = 'PAY-' . $invoiceToken;
        $cotisationId = isset($data['cotisation_id']) ? (int) $data['cotisation_id'] : null;
        $membreId     = isset($data['membre_id']) ? (int) $data['membre_id'] : null;

        if (!$cotisationId || !$membreId) {
            Log::warning('PayDunyaCallbackService::handleCotisation: données manquantes', $data);
            return;
        }

        // Idempotence : vérifier si un paiement avec cette référence existe déjà
        if (Paiement::where('reference', $reference)->exists()) {
            Log::info('PayDunyaCallbackService::handleCotisation: paiement déjà enregistré', ['reference' => $reference]);
            return;
        }

        $cotisation = Cotisation::find($cotisationId);
        if (!$cotisation) {
            Log::warning('PayDunyaCallbackService::handleCotisation: cotisation introuvable', ['cotisation_id' => $cotisationId]);
            return;
        }

        // Enregistrer le paiement
        $paiement = Paiement::create([
            'reference'    => $reference,
            'membre_id'    => $membreId,
            'cotisation_id' => $cotisationId,
            'caisse_id'    => $cotisation->caisse_id,
            'montant'      => $amount,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'paydunya',
            'statut'       => 'valide',
            'commentaire'  => 'Paiement cotisation via PayDunya (IPN)',
            'metadata'     => ['invoice_token' => $invoiceToken],
        ]);

        // Mouvement caisse (entrée)
        if ($cotisation->caisse_id) {
            MouvementCaisse::create([
                'caisse_id'      => $cotisation->caisse_id,
                'type'           => 'cotisation',
                'sens'           => 'entree',
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'Paiement cotisation: ' . $cotisation->nom,
                'notes'          => 'PayDunya IPN - Réf: ' . $reference,
                'reference_type' => Paiement::class,
                'reference_id'   => $paiement->id,
            ]);

            // Mouvement parallèle sur le compte global (Cagnotte Publique ou Privée)
            $caisseGlobal = ($cotisation->visibilite === 'publique') 
                ? Caisse::getCaisseCagnottePub() 
                : Caisse::getCaisseCagnottePrv();

            if ($caisseGlobal) {
                MouvementCaisse::create([
                    'caisse_id'      => $caisseGlobal->id,
                    'type'           => 'cotisation',
                    'sens'           => 'entree',
                    'montant'        => $amount,
                    'date_operation' => now(),
                    'libelle'        => 'RÉCONCILIATION: ' . $cotisation->nom . ' (#' . $membreId . ')',
                    'notes'          => 'PayDunya IPN - Global - Réf: ' . $reference,
                    'reference_type' => Paiement::class,
                    'reference_id'   => $paiement->id,
                ]);
            }
        }

        Log::info('PayDunyaCallbackService::handleCotisation: paiement enregistré', [
            'paiement_id'   => $paiement->id,
            'cotisation_id' => $cotisationId,
            'montant'       => $amount,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ÉPARGNE (Tontine planifiée) — Versement d'une échéance de souscription
    // ─────────────────────────────────────────────────────────────────────────

    private function handleEpargne(array $data, float $amount, string $invoiceToken): void
    {
        $reference      = 'PAY-' . $invoiceToken;
        $souscriptionId = isset($data['souscription_id']) ? (int) $data['souscription_id'] : null;
        $echeanceId     = isset($data['echeance_id']) ? (int) $data['echeance_id'] : null;
        $membreId       = isset($data['membre_id']) ? (int) $data['membre_id'] : null;

        if (!$souscriptionId || !$echeanceId || !$membreId) {
            Log::warning('PayDunyaCallbackService::handleEpargne: données manquantes', $data);
            return;
        }

        // Idempotence
        if (EpargneVersement::where('reference', $reference)->exists()) {
            Log::info('PayDunyaCallbackService::handleEpargne: déjà enregistré', ['reference' => $reference]);
            return;
        }

        $souscription = EpargneSouscription::with('plan')->find($souscriptionId);
        $echeance     = EpargneEcheance::find($echeanceId);

        if (!$souscription || !$echeance) {
            Log::warning('PayDunyaCallbackService::handleEpargne: souscription ou échéance introuvable', $data);
            return;
        }

        if ($echeance->statut === 'payee') {
            Log::info('PayDunyaCallbackService::handleEpargne: échéance déjà payée', ['echeance_id' => $echeanceId]);
            return;
        }

        $caisseId = $souscription->caisse_id ?? null;

        $versement = EpargneVersement::create([
            'souscription_id' => $souscriptionId,
            'echeance_id'     => $echeanceId,
            'membre_id'       => $membreId,
            'montant'         => $amount,
            'date_versement'  => now(),
            'mode_paiement'   => 'paydunya',
            'reference'       => $reference,
            'caisse_id'       => $caisseId,
        ]);

        $souscription->increment('solde_courant', $amount);
        $echeance->update(['statut' => 'payee', 'paye_le' => now()]);

        if ($caisseId) {
            MouvementCaisse::create([
                'caisse_id'      => $caisseId,
                'type'           => 'epargne',
                'sens'           => 'entree',
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'Épargne: ' . ($souscription->plan->nom ?? ''),
                'notes'          => 'PayDunya IPN - Réf: ' . $reference,
                'reference_type' => EpargneVersement::class,
                'reference_id'   => $versement->id,
            ]);

            // Mouvement parallèle sur le compte global Tontine (Membres)
            $caisseGlobal = Caisse::getCaisseTontineCli();
            if ($caisseGlobal) {
                MouvementCaisse::create([
                    'caisse_id'      => $caisseGlobal->id,
                    'type'           => 'epargne',
                    'sens'           => 'entree',
                    'montant'        => $amount,
                    'date_operation' => now(),
                    'libelle'        => 'RÉCONCILIATION TONTINE: Plan ' . ($souscription->plan->nom ?? '') . ' (#' . $membreId . ')',
                    'notes'          => 'PayDunya IPN - Global - Réf: ' . $reference,
                    'reference_type' => EpargneVersement::class,
                    'reference_id'   => $versement->id,
                ]);
            }
        }

        Log::info('PayDunyaCallbackService::handleEpargne: versement enregistré', [
            'versement_id'  => $versement->id,
            'souscription'  => $souscriptionId,
            'echeance'      => $echeanceId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ÉPARGNE LIBRE — Versement libre sur le compte Épargne personnel
    // ─────────────────────────────────────────────────────────────────────────

    private function handleEpargneLibre(array $data, float $amount, string $invoiceToken): void
    {
        $reference = 'PAY-' . $invoiceToken;
        $caisseId  = isset($data['caisse_id']) ? (int) $data['caisse_id'] : null;
        $membreId  = isset($data['membre_id']) ? (int) $data['membre_id'] : null;

        if (!$caisseId || !$membreId) {
            Log::warning('PayDunyaCallbackService::handleEpargneLibre: données manquantes', $data);
            return;
        }

        // Idempotence : vérifier via un Paiement générique (sans cotisation)
        if (Paiement::where('reference', $reference)->exists()) {
            Log::info('PayDunyaCallbackService::handleEpargneLibre: déjà enregistré', ['reference' => $reference]);
            return;
        }

        $caisse = Caisse::find($caisseId);
        if (!$caisse || $caisse->membre_id !== $membreId) {
            Log::warning('PayDunyaCallbackService::handleEpargneLibre: caisse invalide', $data);
            return;
        }

        // Enregistrer comme Paiement générique (sans cotisation)
        $paiement = Paiement::create([
            'reference'    => $reference,
            'membre_id'    => $membreId,
            'caisse_id'    => $caisseId,
            'montant'      => $amount,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'paydunya',
            'statut'       => 'valide',
            'commentaire'  => 'Épargne libre via PayDunya (IPN)',
            'metadata'     => ['type' => 'epargne_libre', 'invoice_token' => $invoiceToken],
        ]);

        // Mouvement caisse (entrée sur le compte épargne du membre)
        MouvementCaisse::create([
            'caisse_id'      => $caisseId,
            'type'           => 'epargne_libre',
            'sens'           => 'entree',
            'montant'        => $amount,
            'date_operation' => now(),
            'libelle'        => 'Versement libre épargne',
            'notes'          => 'PayDunya IPN - Réf: ' . $reference,
            'reference_type' => Paiement::class,
            'reference_id'   => $paiement->id,
        ]);

        // Mouvement parallèle sur le compte global Epargne Libre (Membres)
        $caisseGlobal = Caisse::getCaisseEpargneLibre();
        if ($caisseGlobal) {
            MouvementCaisse::create([
                'caisse_id'      => $caisseGlobal->id,
                'type'           => 'epargne_libre',
                'sens'           => 'entree',
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'RÉCONCILIATION ÉPARGNE LIBRE (#' . $membreId . ')',
                'notes'          => 'PayDunya IPN - Global - Réf: ' . $reference,
                'reference_type' => Paiement::class,
                'reference_id'   => $paiement->id,
            ]);
        }

        Log::info('PayDunyaCallbackService::handleEpargneLibre: versement enregistré', [
            'paiement_id' => $paiement->id,
            'caisse_id'   => $caisseId,
            'montant'     => $amount,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NANO-CRÉDIT — Remboursement d'une échéance par le client
    // ─────────────────────────────────────────────────────────────────────────

    private function handleNanoCreditRemboursement(array $data, float $amount, string $invoiceToken): void
    {
        $reference    = 'PAY-' . $invoiceToken;
        $nanoCreditId = isset($data['nano_credit_id']) ? (int) $data['nano_credit_id'] : null;
        $echeanceId   = isset($data['echeance_id']) ? (int) $data['echeance_id'] : null;
        $membreId     = isset($data['membre_id']) ? (int) $data['membre_id'] : null;

        if (!$nanoCreditId || !$membreId) {
            Log::warning('PayDunyaCallbackService::handleNanoCreditRemboursement: données manquantes', $data);
            return;
        }

        // Idempotence
        if (NanoCreditVersement::where('reference', $reference)->exists()) {
            Log::info('PayDunyaCallbackService::handleNanoCreditRemboursement: déjà enregistré', ['reference' => $reference]);
            return;
        }

        $nanoCredit = NanoCredit::with(['palier', 'echeances', 'membre'])->find($nanoCreditId);
        if (!$nanoCredit || $nanoCredit->membre_id !== $membreId) {
            Log::warning('PayDunyaCallbackService::handleNanoCreditRemboursement: nano-crédit invalide', $data);
            return;
        }

        if (!$nanoCredit->isDebourse()) {
            Log::warning('PayDunyaCallbackService::handleNanoCreditRemboursement: nano-crédit non décaissé', ['statut' => $nanoCredit->statut]);
            return;
        }

        // Marquer l'échéance comme payée si fournie
        $echeance = null;
        if ($echeanceId) {
            $echeance = NanoCreditEcheance::where('nano_credit_id', $nanoCreditId)->find($echeanceId);
            if ($echeance && $echeance->statut !== 'payee') {
                $echeance->update(['statut' => 'payee', 'paye_le' => now()]);
            }
        }

        // Créer le versement
        $versement = NanoCreditVersement::create([
            'nano_credit_id'          => $nanoCreditId,
            'nano_credit_echeance_id' => $echeanceId,
            'montant'                 => (int) round($amount),
            'date_versement'          => now()->toDateString(),
            'mode_paiement'           => 'paydunya',
            'reference'               => $reference,
        ]);

        // Mouvement sur le compte de crédit (entrée = réduction de la dette du membre)
        if ($nanoCredit->compte_credit_id) {
            MouvementCaisse::create([
                'caisse_id'      => $nanoCredit->compte_credit_id,
                'type'           => 'remboursement_credit',
                'sens'           => 'entree', 
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'Remboursement nano-crédit #' . $nanoCreditId,
                'notes'          => 'PayDunya IPN - Réf: ' . $reference,
                'reference_type' => NanoCreditVersement::class,
                'reference_id'   => $versement->id,
            ]);

            // Mouvement parallèle sur le compte global Nano-crédit
            $caisseGlobalNano = Caisse::getCaisseNanoCredit();
            if ($caisseGlobalNano) {
                MouvementCaisse::create([
                    'caisse_id'      => $caisseGlobalNano->id,
                    'type'           => 'remboursement_credit',
                    'sens'           => 'entree', 
                    'montant'        => $amount,
                    'date_operation' => now(),
                    'libelle'        => 'RÉCONCILIATION REMBOURSEMENT NANO: #' . $nanoCreditId . ' (#' . $membreId . ')',
                    'notes'          => 'PayDunya IPN - Global - Réf: ' . $reference,
                    'reference_type' => NanoCreditVersement::class,
                    'reference_id'   => $versement->id,
                ]);
            }

            // --- VENTILATION INTÉRÊTS / PRODUITS / GARANTS ---
            $palier = $nanoCredit->palier;
            if ($palier) {
                $decomposition = $palier->decomposeEcheance((float) $nanoCredit->montant);
                $interetRecu = (float) $decomposition['interet_unitaire'];
                
                // Si l'échéance reçue est partielle ou supérieure, on proratise l'intérêt
                // (Ici on simplifie : on considère que l'intérêt est prioritaire ou proportionnel)
                $ratio = $amount / ($decomposition['capital_unitaire'] + $decomposition['interet_unitaire']);
                $interetReel = $interetRecu * $ratio;

                $percentGarant = (float) ($palier->pourcentage_partage_garant ?? 0);
                $partGarants   = $interetReel * ($percentGarant / 100);
                $partAdmin     = $interetReel - $partGarants;

                // 1. Part Admin -> Compte Global des Produits
                $caisseProd = Caisse::getCaisseProduit();
                if ($caisseProd && $partAdmin > 0) {
                    MouvementCaisse::create([
                        'caisse_id'      => $caisseProd->id,
                        'type'           => 'produit_interet',
                        'sens'           => 'entree',
                        'montant'        => $partAdmin,
                        'date_operation' => now(),
                        'libelle'        => 'PRODUIT NANO: #' . $nanoCreditId,
                        'notes'          => 'Part Admin sur intérêts - Réf: ' . $reference,
                        'reference_type' => NanoCreditVersement::class,
                        'reference_id'   => $versement->id,
                    ]);
                }

                // 2. Part Garants -> Distribution sur leurs comptes courants
                $garants = $nanoCredit->garants()->whereIn('statut', ['accepte', 'preleve'])->get();
                if ($garants->count() > 0 && $partGarants > 0) {
                    $partParGarant = $partGarants / $garants->count();
                    foreach ($garants as $garantRel) {
                        $membreGarant = $garantRel->membre;
                        $compteGarant = $membreGarant->compteCourant; // On utilise le compte courant par défaut
                        
                        if ($compteGarant) {
                            MouvementCaisse::create([
                                'caisse_id'      => $compteGarant->id,
                                'type'           => 'commission_garantie',
                                'sens'           => 'entree',
                                'montant'        => $partParGarant,
                                'date_operation' => now(),
                                'libelle'        => 'COMMISSION NANO: #' . $nanoCreditId,
                                'notes'          => 'Part Garant sur intérêts - Réf: ' . $reference,
                                'reference_type' => NanoCreditVersement::class,
                                'reference_id'   => $versement->id,
                            ]);
                            
                            // Mettre à jour gain_partage dans la table pivot pour stats
                            $garantRel->increment('gain_partage', $partParGarant);
                        }
                    }
                }
            }
        }

        // Vérifier si toutes les échéances sont payées → passer en 'rembourse'
        $nbTotal  = $nanoCredit->echeances()->count();
        $nbPayees = $nanoCredit->echeances()->where('statut', 'payee')->count();

        if ($nbTotal > 0 && $nbPayees >= $nbTotal) {
            $nanoCredit->update(['statut' => 'rembourse']);
            Log::info('PayDunyaCallbackService: nano-crédit remboursé intégralement', ['id' => $nanoCreditId]);
        } else {
            $nanoCredit->update(['statut' => 'en_remboursement']);
        }

        Log::info('PayDunyaCallbackService::handleNanoCreditRemboursement: versement enregistré', [
            'versement_id'  => $versement->id,
            'nano_credit'   => $nanoCreditId,
            'echeance'      => $echeanceId,
            'montant'       => $amount,
        ]);
    }
}

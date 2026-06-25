<?php

namespace App\Services;

use App\Models\NanoCredit;
use App\Models\NanoCreditEcheance;
use App\Models\NanoCreditPalier;
use App\Notifications\NanoCreditOctroyeNotification;
use App\Models\Caisse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NanoCreditService
{
    /**
     * Décaisser un nano-crédit via PayDunya.
     * Cette méthode peut être appelée manuellement par l'admin ou automatiquement après validation des garants.
     */
    public function debourser(NanoCredit $nanoCredit, string $telephone, string $withdrawMode): array
    {
        if ($nanoCredit->statut === 'debourse' || $nanoCredit->statut === 'success') {
            return ['success' => false, 'message' => 'Ce crédit est déjà décaissé.'];
        }

        $montant = (int) $nanoCredit->montant;

        // --- CAS PI-SPI ---
        if ($withdrawMode === 'pispi') {
            try {
                $piSpi = app(\App\Services\PiSpiService::class);
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Pi-SPI n\'est pas configuré ou activé.'];
            }

            $alias = $nanoCredit->membre->defaultWalletAlias();
            if (!$alias) {
                return ['success' => false, 'message' => 'Le membre n\'a aucun alias Pi-SPI configuré pour recevoir les fonds.'];
            }

            $txId = 'NANO-' . $nanoCredit->id . '-' . time();
            $result = $piSpi->sendPayment($txId, $alias->alias, $montant, 'nano_credit');

            if (!$result['success']) {
                $nanoCredit->update(['error_message' => $result['message']]);
                return ['success' => false, 'message' => 'Échec de l\'envoi Pi-SPI : ' . $result['message']];
            }

            // Mise à jour immédiate car Pi-SPI B2P est généralement synchrone (ou géré via webhook/polling)
            $dateOctroi = now()->toDateString();
            $palier = $nanoCredit->palier;
            $dateFinRemb = $palier ? Carbon::parse($dateOctroi)->addDays((int) $palier->duree_jours)->toDateString() : null;

            $nanoCredit->update([
                'statut' => 'debourse',
                'date_octroi' => $dateOctroi,
                'date_fin_remboursement' => $dateFinRemb,
                'transaction_id' => $txId,
                'provider_ref' => $result['data']['reference'] ?? null,
                'withdraw_mode' => 'pispi',
                'telephone' => $alias->alias, // On stocke l'alias dans le champ téléphone pour la traçabilité
            ]);

            // Finalisation financière
            $this->finaliserFinancesDeboursement($nanoCredit);

            return ['success' => true, 'message' => 'Fonds envoyés avec succès via Pi-SPI.'];
        }

        // --- CAS PAYDUNYA ---
        try {
            $paydunya = app(PayDunyaService::class);
        } catch (\Exception $e) {
            Log::error('NanoCreditService debourser: PayDunya non configuré', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'PayDunya n\'est pas configuré.'];
        }

        $callbackUrl = url()->route('paydunya.disburse.callback');

        // 1. Créer la facture de déboursement
        $result = $paydunya->createDisburseInvoice(
            $telephone,
            $montant,
            $withdrawMode,
            $callbackUrl
        );

        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message'] ?? 'Erreur lors de la création du déboursement.'];
        }

        // 2. Mettre à jour avec le token temporaire
        $nanoCredit->update([
            'telephone' => $telephone,
            'withdraw_mode' => $withdrawMode,
            'disburse_token' => $result['disburse_token'],
            'disburse_id' => (string) $nanoCredit->id,
        ]);

        // 3. Soumettre la facture pour exécution immédiate
        $submit = $paydunya->submitDisburseInvoice($result['disburse_token'], (string) $nanoCredit->id);

        if (!$submit['success']) {
            // Cas particulier : La facture a déjà été soumise (ex: timeout ou double clic)
            $isAlreadySubmitted = str_contains(strtolower($submit['message'] ?? ''), 'already submitted');
            
            if ($isAlreadySubmitted) {
                Log::info('NanoCreditService: Facture déjà soumise pour #' . $nanoCredit->id . '. Vérification du statut...');
                $verify = $paydunya->checkDisburseStatus($result['disburse_token']);
                
                if ($verify['success'] && in_array(strtolower($verify['status']), ['success', 'pending', 'completed'])) {
                    $submit = [
                        'success' => true,
                        'status' => $verify['status'],
                        'transaction_id' => $verify['transaction_id'] ?? null,
                    ];
                } else {
                    $nanoCredit->update(['error_message' => $submit['message']]);
                    return ['success' => false, 'message' => $submit['message']];
                }
            } else {
                $nanoCredit->update(['error_message' => $submit['message'] ?? 'Erreur à la soumission']);
                return ['success' => false, 'message' => $submit['message'] ?? 'Soumission échouée.'];
            }
        }

        // 4. Finaliser localement
        $dateOctroi = now()->toDateString();
        $palier = $nanoCredit->palier;
        $dateFinRemb = $palier ? Carbon::parse($dateOctroi)->addDays((int) $palier->duree_jours)->toDateString() : null;

        $nanoCredit->update([
            'statut' => 'debourse',
            'date_octroi' => $dateOctroi,
            'date_fin_remboursement' => $dateFinRemb,
            'transaction_id' => $submit['transaction_id'] ?? null,
            'provider_ref' => $submit['provider_ref'] ?? null,
            'error_message' => null,
        ]);

        $this->finaliserFinancesDeboursement($nanoCredit);

        return ['success' => true, 'message' => 'Déboursement PayDunya initié.'];
    }

    /**
     * Centralise les écritures comptables du déboursement
     */
    private function finaliserFinancesDeboursement(NanoCredit $nanoCredit)
    {
        $palier = $nanoCredit->palier;

        // 4.5. Automatisation des comptes liés au crédit
        $this->associerComptesFinanciers($nanoCredit);

        // 4.6. Écritures comptables de décaissement (Double Entrée via FinanceService)
        app(\App\Services\FinanceService::class)->logNanoCreditOctroi($nanoCredit);

        // 5. Générer les échéances
        if ($palier) {
            $this->genererEcheances($nanoCredit);
        }

        // 6. Notifications
        $nanoCredit->membre->notify(new NanoCreditOctroyeNotification($nanoCredit));
        app(EmailService::class)->sendNanoCreditOctroyeEmail($nanoCredit);

        return ['success' => true, 'message' => 'Crédit décaissé avec succès.'];
    }

    /**
     * Génère les échéances de remboursement selon le palier.
     */
    public function genererEcheances(NanoCredit $nanoCredit)
    {
        $palier = $nanoCredit->palier;
        if (!$palier) return;

        // Supprimer les anciennes échéances si existantes
        $nanoCredit->echeances()->delete();

        $amortissement = $palier->calculAmortissement((float) $nanoCredit->montant);
        $nbEcheances = $amortissement['nombre_echeances'];
        $montantEcheance = $amortissement['montant_echeance'];
        $frequence = $palier->frequence_remboursement;

        $dateBase = Carbon::parse($nanoCredit->date_octroi);

        for ($i = 1; $i <= $nbEcheances; $i++) {
            $dateEcheance = match ($frequence) {
                'journalier' => $dateBase->copy()->addDays($i),
                'hebdomadaire' => $dateBase->copy()->addWeeks($i),
                'mensuel' => $dateBase->copy()->addMonths($i),
                'trimestriel' => $dateBase->copy()->addMonths($i * 3),
                default => $dateBase->copy()->addMonths($i),
            };

            NanoCreditEcheance::create([
                'nano_credit_id' => $nanoCredit->id,
                'date_echeance' => $dateEcheance->toDateString(),
                'montant' => $montantEcheance,
                'statut' => 'en_attente',
            ]);
        }
    }

    /**
     * Crée et associe les comptes de gestion (Crédit, Impayés) et lie le compte courant de remboursement.
     */
    private function associerComptesFinanciers(NanoCredit $nanoCredit): void
    {
        $membre = $nanoCredit->membre;

        // A. Compte de remboursement (Premier compte Courant du client)
        $compteCourant = $membre->compteCourant;
        
        // B. Création du compte de Crédit (Dette principale)
        $compteCredit = Caisse::create([
            'membre_id'    => $membre->id,
            'nom'          => 'Compte Crédit (#' . $nanoCredit->id . ') - ' . $membre->nom_complet,
            'numero'       => Caisse::generateNumeroCompte(),
            'solde_initial'   => 0,
            'statut'       => 'active',
            'type'         => 'credit',
        ]);

        // C. Création du compte des Impayés
        $compteImpaye = Caisse::create([
            'membre_id'    => $membre->id,
            'nom'          => 'Compte Impayés (#' . $nanoCredit->id . ') - ' . $membre->nom_complet,
            'numero'       => Caisse::generateNumeroCompte(),
            'solde_initial'   => 0,
            'statut'       => 'active',
            'type'         => 'impayes',
        ]);

        // D. Liaison finale au dossier nano-crédit
        $nanoCredit->update([
            'compte_remboursement_id' => $compteCourant?->id,
            'compte_credit_id'        => $compteCredit->id,
            'compte_impaye_id'        => $compteImpaye->id,
        ]);
    }

    // ─── Gestion des Réservations de Garanties ────────────────────────────────

    /**
     * Bloque le montant de couverture sur le compte RESERVATIONS NANO-CREDIT du garant.
     *
     * Appelé dès que le garant accepte la sollicitation.
     *
     * Flux :
     *  1. Calcule le montant par garant = montant_nano_credit / nb_garants_requis
     *  2. Trouve ou crée le compte RESERVATIONS NANO-CREDIT du garant
     *  3. Débite la souscription tontine active la plus riche (ou épargne libre)
     *  4. Enregistre les écritures comptables (logBlocageGarantie)
     *  5. Met à jour nano_credit_garants (montant_reserve, compte_reservation_id, reserve_le)
     *
     * @return bool true si le blocage a réussi, false sinon
     */
    public function bloquerMontantGarant(\App\Models\NanoCreditGarant $garant, \App\Models\NanoCredit $nanoCredit): bool
    {
        // Évite un double blocage
        if ($garant->isMontantBloque()) {
            Log::info("NanoCreditService: Garant #{$garant->id} — montant déjà bloqué, aucune action.");
            return true;
        }

        $palier          = $nanoCredit->palier;
        $nbGarantsRequis = max(1, (int) ($palier?->nb_garants_requis ?? 1));
        $montantCredit   = (float) $nanoCredit->montant;
        $montantABloquer = (int) round($montantCredit / $nbGarantsRequis, 0);

        if ($montantABloquer <= 0) {
            Log::warning("NanoCreditService: Montant à bloquer pour garant #{$garant->id} est nul.");
            return false;
        }

        $membreGarant = $garant->membre;
        if (!$membreGarant) {
            Log::error("NanoCreditService: Garant #{$garant->id} — membre introuvable.");
            return false;
        }

        // ── 1. Trouver le compte épargne source à débiter ──
        // Priorité : souscription tontine active la plus riche → épargne libre
        $compteEpargneSource  = null;
        $souscriptionTontine  = null;

        $souscriptions = \App\Models\EpargneSouscription::where('membre_id', $membreGarant->id)
            ->where('statut', 'active')
            ->get()
            ->sortByDesc(fn ($s) => (float) $s->solde_courant);

        $meilleuresSouscription = $souscriptions->first();

        if ($meilleuresSouscription && (float) $meilleuresSouscription->solde_courant >= $montantABloquer) {
            $souscriptionTontine = $meilleuresSouscription;
            $compteEpargneSource = \App\Models\Caisse::where('membre_id', $membreGarant->id)
                ->where('type', 'tontine')
                ->oldest()
                ->first();
        }

        // Fallback : compte épargne libre
        if (!$compteEpargneSource) {
            $compteEpargneLibre = \App\Models\Caisse::where('membre_id', $membreGarant->id)
                ->where('type', 'epargne')
                ->oldest()
                ->first();
            if ($compteEpargneLibre && $compteEpargneLibre->solde_actuel >= $montantABloquer) {
                $compteEpargneSource = $compteEpargneLibre;
            }
        }

        if (!$compteEpargneSource) {
            Log::warning("NanoCreditService: Garant #{$garant->id} — aucun compte épargne avec solde suffisant ({$montantABloquer} FCFA requis).");
            return false;
        }

        // ── 2. Trouver ou créer le compte RESERVATIONS NANO-CREDIT du garant ──
        $compteReservation = \App\Models\Caisse::where('membre_id', $membreGarant->id)
            ->where('type', 'reservation_nanocredit')
            ->first();

        if (!$compteReservation) {
            $compteReservation = \App\Models\Caisse::create([
                'membre_id'     => $membreGarant->id,
                'nom'           => 'Réservations Nano-Crédit — ' . $membreGarant->nom_complet,
                'numero'        => \App\Models\Caisse::generateNumeroCompte(),
                'solde_initial' => 0,
                'statut'        => 'active',
                'type'          => 'reservation_nanocredit',
            ]);
        }

        // ── 3. Déduire du solde de la souscription tontine si c'est la source ──
        if ($souscriptionTontine && $compteEpargneSource->type === 'tontine') {
            $soldeTontine = (float) $souscriptionTontine->solde_courant;
            $souscriptionTontine->update([
                'solde_courant' => max(0, $soldeTontine - $montantABloquer),
            ]);
        }

        // ── 4. Écriture comptable double entrée ──
        try {
            app(\App\Services\FinanceService::class)->logBlocageGarantie(
                $compteEpargneSource,
                $compteReservation,
                (float) $montantABloquer,
                $garant
            );
        } catch (\Exception $e) {
            Log::error("NanoCreditService: Erreur écriture blocage garantie #{$garant->id}: " . $e->getMessage());
        }

        // ── 5. Mettre à jour l'enregistrement garant ──
        $garant->update([
            'montant_reserve'       => $montantABloquer,
            'compte_reservation_id' => $compteReservation->id,
            'reserve_le'            => now(),
        ]);

        Log::info("NanoCreditService: Garant #{$garant->id} ({$membreGarant->nom_complet}) — {$montantABloquer} FCFA bloqués sur compte réservation #{$compteReservation->id}.");

        return true;
    }

    /**
     * Libère les montants de couverture de tous les garants après remboursement complet.
     *
     * Appelé dans NanoCreditController::finaliserRemboursement().
     *
     * Flux par garant :
     *  1. Débite le compte RESERVATIONS NANO-CREDIT
     *  2. Crédite la souscription tontine / compte épargne source
     *  3. Met à jour garant (libere_le)
     */
    public function libererMontantsGarants(\App\Models\NanoCredit $nanoCredit): void
    {
        $garants = $nanoCredit->garants()
            ->whereIn('statut', ['accepte', 'libere'])
            ->whereNotNull('compte_reservation_id')
            ->where('montant_reserve', '>', 0)
            ->with(['membre', 'compteReservation'])
            ->get();

        if ($garants->isEmpty()) {
            Log::info("NanoCreditService::libererMontantsGarants: Aucun garant avec réservation pour crédit #{$nanoCredit->id}.");
            return;
        }

        foreach ($garants as $garant) {
            // Évite une double libération
            if ($garant->libere_le !== null) {
                continue;
            }

            $membreGarant      = $garant->membre;
            $compteReservation = $garant->compteReservation;
            $montantALiberer   = (float) $garant->montant_reserve;

            if (!$membreGarant || !$compteReservation || $montantALiberer <= 0) {
                continue;
            }

            // Compte épargne de destination (même priorité que le blocage : tontine → épargne)
            $compteEpargneDestination = \App\Models\Caisse::where('membre_id', $membreGarant->id)
                ->where('type', 'tontine')
                ->oldest()
                ->first()
                ?? \App\Models\Caisse::where('membre_id', $membreGarant->id)
                    ->where('type', 'epargne')
                    ->oldest()
                    ->first();

            if (!$compteEpargneDestination) {
                Log::warning("NanoCreditService::libererMontantsGarants: Garant #{$garant->id} — compte épargne de restitution introuvable.");
                continue;
            }

            // Restituer sur la souscription tontine si le compte est de type tontine
            if ($compteEpargneDestination->type === 'tontine') {
                $souscriptionActive = \App\Models\EpargneSouscription::where('membre_id', $membreGarant->id)
                    ->where('statut', 'active')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($souscriptionActive) {
                    $souscriptionActive->update([
                        'solde_courant' => (float) $souscriptionActive->solde_courant + $montantALiberer,
                    ]);
                }
            }

            // Écriture comptable
            try {
                app(\App\Services\FinanceService::class)->logLiberationGarantie(
                    $compteReservation,
                    $compteEpargneDestination,
                    $montantALiberer,
                    $garant
                );
            } catch (\Exception $e) {
                Log::error("NanoCreditService::libererMontantsGarants: Erreur écriture libération garant #{$garant->id}: " . $e->getMessage());
            }

            $garant->update(['libere_le' => now()]);

            Log::info("NanoCreditService: Garant #{$garant->id} ({$membreGarant->nom_complet}) — {$montantALiberer} FCFA libérés (crédit #{$nanoCredit->id} remboursé).");
        }
    }
}


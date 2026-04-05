<?php

namespace App\Services;

use App\Models\Membre;
use App\Models\NanoCredit;
use App\Models\NanoCreditGarant;
use App\Models\NanoCreditPalier;
use Illuminate\Support\Facades\Log;

class NanoCreditPalierService
{
    // ─── Assignation Initiale ─────────────────────────────────────────────────

    /**
     * Assigne le Palier 1 à un membre dont le KYC vient d'être validé.
     * Ne fait rien si le membre a déjà un palier.
     */
    public function assignerPalierInitial(Membre $membre): void
    {
        if ($membre->nano_credit_palier_id !== null) {
            return; // Déjà un palier
        }

        $palier1 = NanoCreditPalier::where('numero', 1)->where('actif', true)->first();

        if (!$palier1) {
            Log::warning("NanoCreditPalierService: Aucun Palier 1 actif trouvé. Impossible d'assigner le palier initial au membre #{$membre->id}.");
            return;
        }

        $membre->update(['nano_credit_palier_id' => $palier1->id]);

        Log::info("NanoCreditPalierService: Palier 1 assigné au membre #{$membre->id} ({$membre->nom_complet}).");
    }

    // ─── Vérification d'Upgrade ───────────────────────────────────────────────

    /**
     * Vérifie si un membre est éligible au palier supérieur.
     * Retourne le palier cible si éligible, null sinon.
     */
    public function verifierEligibiliteUpgrade(Membre $membre): ?NanoCreditPalier
    {
        $palierActuel = $membre->nanoCreditPalier;

        if (!$palierActuel) {
            return null;
        }

        // Pas d'upgrade si le membre a des impayés actifs
        if ($membre->hasImpayes()) {
            return null;
        }

        // Pas d'upgrade si interdit
        if ($membre->isNanoCreditInterdit()) {
            return null;
        }

        $palierSuivant = $palierActuel->palierSuivant();

        if (!$palierSuivant) {
            return null; // Déjà au palier maximum
        }

        if ($palierSuivant->conditionsAccessRemplies($membre)) {
            return $palierSuivant;
        }

        return null;
    }

    /**
     * Upgrade automatique vers le palier supérieur si éligible.
     * Retourne true si le membre a été upgradé.
     */
    public function upgraderPalier(Membre $membre): bool
    {
        $palierCible = $this->verifierEligibiliteUpgrade($membre);

        if (!$palierCible) {
            return false;
        }

        $ancienPalier = $membre->nanoCreditPalier?->nom ?? 'Aucun';
        $membre->update(['nano_credit_palier_id' => $palierCible->id]);

        Log::info("NanoCreditPalierService: Membre #{$membre->id} ({$membre->nom_complet}) upgradé de [{$ancienPalier}] vers [{$palierCible->nom}].");

        return true;
    }

    // ─── Downgrade ────────────────────────────────────────────────────────────

    /**
     * Rétrograde un membre au palier inférieur en cas d'impayés.
     * Retourne true si le membre a été rétrogradé.
     */
    public function downgraderPalier(Membre $membre, string $motif = 'Impayés'): bool
    {
        $palierActuel = $membre->nanoCreditPalier;

        if (!$palierActuel) {
            return false;
        }

        $palierPrec = $palierActuel->palierPrecedent();

        if (!$palierPrec) {
            // Déjà au Palier 1 — on ne peut pas descendre plus bas, mais on peut interdire
            Log::info("NanoCreditPalierService: Membre #{$membre->id} déjà au Palier 1, impossible de rétrograder davantage.");
            return false;
        }

        $membre->update(['nano_credit_palier_id' => $palierPrec->id]);

        Log::info("NanoCreditPalierService: Membre #{$membre->id} ({$membre->nom_complet}) rétrogradé de [{$palierActuel->nom}] vers [{$palierPrec->nom}]. Motif: {$motif}");

        return true;
    }

    /**
     * Incrémente le compteur de défauts et applique les conséquences définies par le palier.
     * Appelé après n-jours d'impayés.
     */
    public function enregistrerDefautPaiement(NanoCredit $nanoCredit): void
    {
        $membre = $nanoCredit->membre;
        $palier = $nanoCredit->palier ?? $membre->nanoCreditPalier;

        // Incrémenter nb_defauts_paiement
        $membre->increment('nb_defauts_paiement');
        $membre->refresh();

        Log::info("NanoCreditPalierService: Défaut de paiement #{$membre->nb_defauts_paiement} enregistré pour membre #{$membre->id}.");

        if (!$palier) {
            return;
        }

        // Downgrade si configuré
        if ($palier->downgrade_en_cas_impayes &&
            $nanoCredit->jours_retard >= $palier->jours_impayes_pour_downgrade) {
            $this->downgraderPalier($membre, "Retard de {$nanoCredit->jours_retard} jours sur crédit #{$nanoCredit->id}");
            // Impacter aussi les garants
            $this->downgraderGarants($nanoCredit);
        }

        // Interdiction en cas de récidive
        if ($palier->interdiction_en_cas_recidive &&
            $membre->nb_defauts_paiement >= $palier->nb_recidives_pour_interdiction) {
            $this->interdireMembre(
                $membre,
                "Récidive : {$membre->nb_defauts_paiement} défauts de paiement cumulés."
            );
        }
    }

    /**
     * Rétrograder les garants d'un crédit en défaut.
     */
    public function downgraderGarants(NanoCredit $nanoCredit): void
    {
        $garants = $nanoCredit->garants()->where('statut', 'accepte')->with('membre')->get();

        foreach ($garants as $garant) {
            $membreGarant = $garant->membre;
            if (!$membreGarant) {
                continue;
            }
            $retrogradé = $this->downgraderPalier(
                $membreGarant,
                "Garant du crédit #{$nanoCredit->id} en défaut de paiement"
            );
            if ($retrogradé) {
                Log::info("NanoCreditPalierService: Garant {$membreGarant->nom_complet} rétrogradé suite au défaut du crédit #{$nanoCredit->id}.");
            }
        }
    }

    // ─── Pénalités ────────────────────────────────────────────────────────────

    /**
     * Calcule et enregistre les pénalités de retard pour un nano-crédit.
     * Doit être appelé quotidiennement par le scheduler.
     * Retourne le montant de pénalité ajouté ce jour.
     */
    public function calculerEtEnregistrerPenalites(NanoCredit $nanoCredit): float
    {
        if (!$nanoCredit->isDebourse()) {
            return 0.0;
        }

        $today = now()->toDateString();

        // Éviter les doublons : ne calculer qu'une fois par jour
        if ($nanoCredit->date_dernier_calcul_penalite &&
            $nanoCredit->date_dernier_calcul_penalite->toDateString() === $today) {
            return 0.0;
        }

        // Vérifier s'il y a des échéances en retard
        $echeancesEnRetard = $nanoCredit->echeances()
            ->where('statut', 'a_venir')
            ->where('date_echeance', '<', $today)
            ->get();

        if ($echeancesEnRetard->isEmpty()) {
            return 0.0;
        }

        // Montant restant dû (somme des échéances non payées)
        $capitalRestant = $echeancesEnRetard->sum('montant');

        // Taux de pénalité (depuis le palier ou valeur par défaut 5%)
        $palier = $nanoCredit->palier;
        $tauxPenaliteJour = $palier ? (float) $palier->penalite_par_jour : 5.0;

        // Pénalité du jour
        $penaliteJour = round($capitalRestant * ($tauxPenaliteJour / 100), 0);

        // Incrémenter jours_retard et montant_penalite
        $nouveauxJoursRetard = ($nanoCredit->jours_retard ?? 0) + 1;
        $nouveauMontantPenalite = ((float) $nanoCredit->montant_penalite) + $penaliteJour;

        $nanoCredit->update([
            'jours_retard'                  => $nouveauxJoursRetard,
            'montant_penalite'              => $nouveauMontantPenalite,
            'date_dernier_calcul_penalite'  => $today,
        ]);

        // Si c'est le début du retard (1er jour), on diminue la qualité des garants
        if ($nouveauxJoursRetard === 1) {
            foreach ($nanoCredit->garants()->whereIn('statut', ['accepte', 'preleve'])->with('membre')->get() as $garant) {
                if ($garant->membre && $garant->membre->garant_qualite > 0) {
                    $garant->membre->decrement('garant_qualite');
                }
            }
        }

        Log::info("NanoCreditPalierService: Pénalité de {$penaliteJour} FCFA appliquée au crédit #{$nanoCredit->id} (jour {$nouveauxJoursRetard} de retard).");

        return $penaliteJour;
    }

    // ─── Interdiction ─────────────────────────────────────────────────────────

    /**
     * Interdit un membre de prendre des nano-crédits.
     */
    public function interdireMembre(Membre $membre, string $motif): void
    {
        $membre->update([
            'nano_credit_interdit'  => true,
            'motif_interdiction'    => $motif,
            'interdit_le'           => now(),
        ]);

        Log::info("NanoCreditPalierService: Membre #{$membre->id} ({$membre->nom_complet}) interdit de nano-crédit. Motif: {$motif}");
    }

    /**
     * Lève l'interdiction de crédit d'un membre.
     */
    public function leverInterdiction(Membre $membre): void
    {
        $membre->update([
            'nano_credit_interdit'  => false,
            'motif_interdiction'    => null,
            'interdit_le'           => null,
        ]);

        Log::info("NanoCreditPalierService: Interdiction levée pour membre #{$membre->id} ({$membre->nom_complet}).");
    }

    // ─── Prélèvement Garants ──────────────────────────────────────────────────

    /**
     * Déclenche le prélèvement automatique des garants après n-jours d'impayés.
     * Retourne la liste des garants prélevés.
     */
    public function prelevementsGarants(NanoCredit $nanoCredit): array
    {
        $palier = $nanoCredit->palier ?? $nanoCredit->membre?->nanoCreditPalier;

        if (!$palier) {
            return [];
        }

        $joursLimite = $palier->jours_avant_prelevement_garant;

        if (($nanoCredit->jours_retard ?? 0) < $joursLimite) {
            return []; // Pas encore le moment
        }

        $garantsAprelever = $nanoCredit->garants()
            ->where('statut', 'accepte')
            ->with('membre')
            ->get();

        $preleves = [];

        foreach ($garantsAprelever as $garant) {
            // Calcul du montant à prélever par garant (répartition équitable)
            $capitalRestant = $nanoCredit->echeances()
                ->where('statut', 'a_venir')
                ->sum('montant');

            $nbGarantsActifs = $garantsAprelever->count();
            $montantParGarant = $nbGarantsActifs > 0
                ? (int) round($capitalRestant / $nbGarantsActifs, 0)
                : 0;

            $garant->update([
                'statut'          => 'preleve',
                'montant_preleve' => $montantParGarant,
                'preleve_le'      => now(),
            ]);

            // Déduire depuis la ou les souscriptions tontines du garant
            $membreGarant = $garant->membre;
            if ($membreGarant) {
                // Trouver une souscription avec assez de solde, ou tout répartir
                $souscriptions = \App\Models\EpargneSouscription::where('membre_id', $membreGarant->id)
                    ->where('statut', 'active')
                    ->where('solde_courant', '>', 0)
                    ->get();
                
                $resteADeduire = $montantParGarant;
                foreach ($souscriptions as $souscription) {
                    if ($resteADeduire <= 0) break;
                    
                    $solde = (float) $souscription->solde_courant;
                    $deduction = min($solde, $resteADeduire);
                    
                    $souscription->update([
                        'solde_courant' => $solde - $deduction
                    ]);
                    $resteADeduire -= $deduction;
                }
            }

            // Ajouter un versement au nom du garant pour solder le crédit
            \App\Models\NanoCreditVersement::create([
                'nano_credit_id' => $nanoCredit->id,
                'montant' => $montantParGarant,
                'date_versement' => now(),
                'mode_paiement' => 'Tontine du garant',
                'reference' => 'Saisie sur garant #' . $garant->id
            ]);

            Log::info("NanoCreditPalierService: Garant #{$garant->membre_id} prélevé de {$montantParGarant} FCFA pour crédit #{$nanoCredit->id}.");

            $preleves[] = $garant;
        }

        // Vérifier si le crédit est totalement soldé
        $totalVerse = $nanoCredit->versements()->sum('montant');
        $du = (float) $nanoCredit->montant + (float) $nanoCredit->montant_penalite;
        
        if ($totalVerse >= $du) {
            $nanoCredit->update(['statut' => 'rembourse']);
        }

        return $preleves;
    }
}

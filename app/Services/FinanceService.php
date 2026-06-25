<?php

namespace App\Services;

use App\Models\Caisse;
use App\Models\MouvementCaisse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceService
{
    /**
     * Enregistre une écriture comptable balancée (Partie double)
     * Pour chaque mouvement, 'entree' = Débit, 'sortie' = Crédit.
     * La somme des montants en 'entree' doit égaler la somme des montants en 'sortie'.
     *
     * @param array $entries Liste des mouvements [caisse_id, type, sens, montant, libelle, notes, reference_type, reference_id]
     */
    public function recordDoubleEntry(array $entries): void
    {
        DB::transaction(function () use ($entries) {
            $totalIn = 0;
            $totalOut = 0;

            // Générer un numéro de pièce unique pour cette écriture
            $numeroPiece = null;
            try {
                $numeroPiece = app(\App\Services\AutoNumberingService::class)->generate('piece_comptable');
            } catch (\Exception $e) {
                // Pas de config, on laisse null ou on génère un temporaire ?
                // On peut le laisser null car la colonne est nullable.
            }

            foreach ($entries as $entry) {
                if ($entry['sens'] === 'entree') $totalIn += (float) $entry['montant'];
                else $totalOut += (float) $entry['montant'];

                $entry['numero_piece'] = $numeroPiece;
                MouvementCaisse::create($entry);
            }

            // Vérification de l'équilibre (Tolérance 0.01 pour les arrondis)
            if (abs($totalIn - $totalOut) > 0.01) {
                Log::error("FinanceService: Déséquilibre détecté lors d'une écriture", ['in' => $totalIn, 'out' => $totalOut, 'entries' => $entries]);
                throw new \Exception("Écriture comptable déséquilibrée : Différence de " . ($totalIn - $totalOut));
            }
        });
    }

    /**
     * Logique financière d'octroi de nano-credit (Deblocage)
     * - Débit : Compte Système Global (SYS-NAN-CRD)
     * - Crédit : Compte Crédit du Client
     * (Éventuellement Cash/Wallet s'ils sont impliqués)
     */
    public function logNanoCreditOctroi(\App\Models\NanoCredit $nc): void
    {
        $palier = $nc->palier;
        if (!$palier) return;

        $amort = $palier->calculAmortissement((float) $nc->montant);
        $totalDu = (float) $amort['montant_total_du'];
        $principal = (float) $amort['montant_emprunte'];

        $caisseGlobal = Caisse::getCaisseNanoCredit();
        if (!$caisseGlobal || !$nc->compte_credit_id) {
            Log::warning("FinanceService: Comptes manquants pour octroi nano-credit #{$nc->id}");
            return;
        }

        $this->recordDoubleEntry([
            // 1. Débit Asset Système (On augmente notre créance globale)
            [
                'caisse_id'      => $caisseGlobal->id,
                'type'           => 'deboursement_credit',
                'sens'           => 'entree', // DEBIT
                'montant'        => $totalDu,
                'date_operation' => now(),
                'libelle'        => 'DEBIT SYSTÈME NANO: #' . $nc->id,
                'reference_type' => \App\Models\NanoCredit::class,
                'reference_id'   => $nc->id,
            ],
            // 2. Crédit Compte Client (Initialisation de sa dette)
            [
                'caisse_id'      => $nc->compte_credit_id,
                'type'           => 'deboursement_credit',
                'sens'           => 'sortie', // CREDIT
                'montant'        => $totalDu,
                'date_operation' => now(),
                'libelle'        => 'Octroi Nano-crédit #' . $nc->id,
                'reference_type' => \App\Models\NanoCredit::class,
                'reference_id'   => $nc->id,
            ]
        ]);

        // Optionnel : Si le compte de remboursement (courant) reçoit les fonds, on ajoute une écriture Cash/Liquide
        if ($nc->compte_remboursement_id) {
            $caisseGlobal = Caisse::getCaisseNanoCredit();
            // Note: Ceci est un transfert de liquidité séparé du mouvement de créance ci-dessus
            // Normalement, ça devrait être : Debit Compte Courant / Credit Wallet Global
            $this->recordDoubleEntry([
                [
                    'caisse_id'      => $nc->compte_remboursement_id,
                    'type'           => 'deboursement_credit_fonds',
                    'sens'           => 'entree', // DEBIT (Increases balance)
                    'montant'        => $principal,
                    'date_operation' => now(),
                    'libelle'        => 'Fonds reçus Nano-crédit #' . $nc->id,
                    'reference_type' => \App\Models\NanoCredit::class,
                    'reference_id'   => $nc->id,
                ],
                [
                    'caisse_id'      => $caisseGlobal ? $caisseGlobal->id : $nc->compte_credit_id, // Fallback
                    'type'           => 'deboursement_credit_fonds',
                    'sens'           => 'sortie', // CREDIT (Decreases liquidity)
                    'montant'        => $principal,
                    'date_operation' => now(),
                    'libelle'        => 'GLOBAL - Sortie fonds Nano-crédit #' . $nc->id,
                    'reference_type' => \App\Models\NanoCredit::class,
                    'reference_id'   => $nc->id,
                ]
            ]);
        }
    }

    /**
     * Logique de remboursement de nano-credit
     * - Débit : Compte Crédit Client (Diminution dette)
     * - Crédit : Compte Système Global (Diminution créance)
     * - Crédit : Compte Produits (Intérêts gagnés)
     */
    public function logNanoCreditRemboursement(\App\Models\NanoCreditVersement $versement): void
    {
        $nc = $versement->nanoCredit;
        $palier = $nc->palier;
        if (!$palier) return;

        $amountTotal = (float) $versement->montant;
        
        // On calcule la part d'intérêt prorata ou fixe
        $decomp = $palier->decomposeEcheance((float) $nc->getRawOriginal('montant'));
        $interestPerEcheance = (float) $decomp['interet_unitaire'];
        $capitalPerEcheance = (float) $decomp['capital_unitaire'];
        
        // Ratio pour ventiler si le montant ne correspond pas exactement à une échéance
        $ratio = $amountTotal / ($interestPerEcheance + $capitalPerEcheance);
        $interestPart = (float) round($interestPerEcheance * $ratio, 2);
        $capitalPart = $amountTotal - $interestPart;

        $caisseGlobal = Caisse::getCaisseNanoCredit();
        $caisseProd = Caisse::getCaisseProduit();

        $entries = [
            // 1. Débit Compte Client (Réduit sa dette)
            [
                'caisse_id'      => $nc->compte_credit_id,
                'type'           => 'remboursement_nano',
                'sens'           => 'entree', // DEBIT (augmente balance car balance est négative)
                'montant'        => $amountTotal,
                'date_operation' => $versement->date_versement,
                'libelle'        => 'Remboursement Nano-crédit #' . $nc->id,
                'reference_type' => \App\Models\NanoCreditVersement::class,
                'reference_id'   => $versement->id,
            ],
            // 2. Crédit Système Nano (Réduit l'asset créance)
            [
                'caisse_id'      => $caisseGlobal ? $caisseGlobal->id : null,
                'type'           => 'remboursement_nano',
                'sens'           => 'sortie', // CREDIT
                'montant'        => $capitalPart,
                'date_operation' => $versement->date_versement,
                'libelle'        => 'Retour Capital Nano #' . $nc->id,
                'reference_type' => \App\Models\NanoCreditVersement::class,
                'reference_id'   => $versement->id,
            ],
            // 3. Crédit Produit (Reconnaissance de revenu)
            [
                'caisse_id'      => $caisseProd ? $caisseProd->id : null,
                'type'           => 'produit_interet',
                'sens'           => 'sortie', // CREDIT (augmente profit)
                'montant'        => $interestPart,
                'date_operation' => $versement->date_versement,
                'libelle'        => 'Intérêts perçus Nano #' . $nc->id,
                'reference_type' => \App\Models\NanoCreditVersement::class,
                'reference_id'   => $versement->id,
            ]
        ];

        // Filtrer les entrées sans caisse (au cas où les comptes système manquent)
        $cleanEntries = array_filter($entries, fn($e) => $e['caisse_id'] !== null);
        
        $this->recordDoubleEntry($cleanEntries);
    }

    /**
     * Paiement de commission de parrainage
     * - Double écriture balancée :
     *   1. Flux Sortant : Débit Charge / Crédit Caisse Parrainage
     *   2. Flux Beneficiaire : Débit Caisse Parrainage / Crédit Compte Client
     */
    public function logParrainagePaiement(\App\Models\ParrainageCommission $commission): void
    {
        $caisseCharge = Caisse::getCaisseCharge();
        $caissePar = Caisse::getCaisseParrainage();
        $compteCourant = $commission->parrain->compteCourant;

        if (!$caisseCharge || !$caissePar || !$compteCourant) return;

        $amount = (float) $commission->montant;
        $refType = \App\Models\ParrainageCommission::class;
        $refId = $commission->id;

        $this->recordDoubleEntry([
            // 1. Débit Compte de charge (On dépense de l'argent)
            [
                'caisse_id'      => $caisseCharge->id,
                'type'           => 'charge_parrainage',
                'sens'           => 'entree', // DEBIT 
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'CHARGE GAIN PARRAINAGE: Com #' . $refId,
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ],
            // 2. Crédit Système Parrainage (Sortie physique du pool)
            [
                'caisse_id'      => $caissePar->id,
                'type'           => 'charge_parrainage',
                'sens'           => 'sortie', // CREDIT
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'Sortie pool Parrainage #' . $refId,
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ],
            // 3. Débit Système Parrainage (Transit vers client)
            [
                'caisse_id'      => $caissePar->id,
                'type'           => 'gain_parrainage',
                'sens'           => 'entree', // DEBIT
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'Distribution gain parrainage #' . $refId,
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ],
            // 4. Crédit Compte Courant Parrain (Reçoit les fonds sur son balance)
            [
                'caisse_id'      => $compteCourant->id,
                'type'           => 'gain_parrainage',
                'sens'           => 'sortie', // CREDIT increases client balance (Liability for us)
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'Commission de parrainage - Filleul: ' . ($commission->filleul->nom_complet ?? 'N/A'),
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ]
        ]);
    }
    /**
     * Enregistrement générique équilibré entre deux comptes
     */
    public function logGenericBalancedEntry(
        \App\Models\Caisse $caisseDebit,
        \App\Models\Caisse $caisseCredit,
        float $amount,
        string $typeOperation,
        string $libelle,
        $reference = null,
        ?string $notes = null
    ): void {
        $this->recordDoubleEntry([
            [
                'caisse_id'      => $caisseDebit->id,
                'type'           => $typeOperation,
                'sens'           => 'entree', // DEBIT
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'notes'          => $notes,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ],
            [
                'caisse_id'      => $caisseCredit->id,
                'type'           => $typeOperation,
                'sens'           => 'sortie', // CREDIT
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'notes'          => $notes,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ]
        ]);
    }

    /**
     * Logique de remboursement (Annulation de versement / Trop-perçu)
     * Inverse de logFluxTontineCagnotte
     * - Débit : Compte individuel du membre (Réduction de son avoir)
     * - Crédit : Compte Système Global (Sortie physique des fonds)
     */
    public function logFluxRemboursement(\App\Models\Caisse $compteMembre, float $amount, string $typeOperation, string $libelle, $reference = null): void
    {
        $caisseGlobal = null;
        
        if ($compteMembre->type === 'tontine') {
            $caisseGlobal = Caisse::getCaisseTontineCli();
        } elseif ($compteMembre->type === 'cagnotte') {
            $caisseGlobal = Caisse::getCaisseCagnottePub(); 
        } elseif ($compteMembre->type === 'epargne') {
            $caisseGlobal = Caisse::getCaisseEpargneLibre();
        }

        if (!$caisseGlobal) {
            Log::warning("FinanceService: Compte global introuvable pour le remboursement {$compteMembre->type}");
            return;
        }

        $this->recordDoubleEntry([
            // 1. Débit Compte Membre (On lui "reprend" l'argent de son solde virtuel)
            [
                'caisse_id'      => $compteMembre->id,
                'type'           => $typeOperation,
                'sens'           => 'entree', // DEBIT 
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ],
            // 2. Crédit Système (Sortie physique de l'argent du pool)
            [
                'caisse_id'      => $caisseGlobal->id,
                'type'           => $typeOperation,
                'sens'           => 'sortie', // CREDIT
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'GLOBAL - ' . $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ]
        ]);
    }

    /**
     * Logique de réception de fonds pour les CAGNOTTES (paiements mobile money)
     *
     * ⚠️ Le paiement d'une cagnotte est fait via mobile money EXTERNE :
     *   l'argent va directement dans la caisse de la cagnotte.
     *   → Aucun compte personnel du membre n'est impacté.
     *   → Seuls la caisse de la cagnotte et le compte système SYS-CAG-PUB/PRV bougent.
     *
     * Double écriture :
     *   - Débit  : Compte Système Global cagnotte (SYS-CAG-PUB ou SYS-CAG-PRV)
     *   - Crédit : Caisse dédiée de la cagnotte (compte de collecte)
     *
     * @param \App\Models\Caisse $caisseCagnotte   Caisse propre à la cagnotte (créée avec la cotisation)
     * @param float              $amount            Montant payé
     * @param string             $libelle           Libellé du mouvement
     * @param mixed|null         $reference         Objet de référence (Paiement)
     * @param bool               $isPrivee          Cagnotte privée ? (utilise SYS-CAG-PRV) sinon SYS-CAG-PUB
     */
    public function logFluxCagnotte(
        \App\Models\Caisse $caisseCagnotte,
        float $amount,
        string $libelle,
        $reference = null,
        bool $isPrivee = false
    ): void {
        $caisseGlobal = $isPrivee
            ? Caisse::getCaisseCagnottePrv()
            : Caisse::getCaisseCagnottePub();

        if (!$caisseGlobal) {
            Log::warning('FinanceService::logFluxCagnotte: Compte système cagnotte introuvable, enregistrement simple.');
            // Fallback : enregistrement simple sur la caisse de la cagnotte
            $caisseCagnotte->mouvements()->create([
                'type'           => 'cotisation',
                'sens'           => 'entree',
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ]);
            return;
        }

        $this->recordDoubleEntry([
            // 1. Débit Compte Système Cagnotte (réception physique des fonds dans le pool cagnotte)
            [
                'caisse_id'      => $caisseGlobal->id,
                'type'           => 'cotisation',
                'sens'           => 'entree', // DÉBIT : augmente l'actif système
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'GLOBAL-CAG - ' . $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ],
            // 2. Crédit Caisse Cagnotte (la caisse de collecte reçoit les fonds)
            [
                'caisse_id'      => $caisseCagnotte->id,
                'type'           => 'cotisation',
                'sens'           => 'sortie', // CRÉDIT : alimente la caisse de collecte
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ],
        ]);
    }

    /**
     * Logique de réception de fonds (Tontines planifiées / Épargne classique)
     *
     * Ces opérations impactent le compte PERSONNEL du membre (tontine/épargne).
     * À n'utiliser QUE pour :
     *   - Tontines (EpargneSouscription / EpargneVersement)
     *   - Épargne classique (libre ou planifiée)
     *
     * ⚠️ NE PAS utiliser pour les cagnottes → utiliser logFluxCagnotte()
     *
     * Double écriture :
     *   - Débit  : Compte Système Global (SYS-TON-CLI ou SYS-EPG-CLI)
     *   - Crédit : Compte individuel du membre (compte tontine ou épargne)
     */
    public function logFluxTontineCagnotte(\App\Models\Caisse $compteMembre, float $amount, string $typeOperation, string $libelle, $reference = null): void
    {
        $caisseGlobal = null;
        
        // Détermination du compte global selon le type de compte membre
        if ($compteMembre->type === 'tontine') {
            $caisseGlobal = Caisse::getCaisseTontineCli();
        } elseif ($compteMembre->type === 'epargne') {
            $caisseGlobal = Caisse::getCaisseEpargneLibre();
        } elseif ($compteMembre->type === 'cagnotte') {
            // Si appelé avec un compte de type cagnotte, on délègue à logFluxCagnotte
            Log::warning('FinanceService::logFluxTontineCagnotte appelé avec un compte cagnotte. Utilisez logFluxCagnotte() à la place.');
            $this->logFluxCagnotte($compteMembre, $amount, $libelle, $reference);
            return;
        }

        if (!$caisseGlobal) {
            Log::warning("FinanceService: Compte global introuvable pour le type {$compteMembre->type}");
            $compteMembre->mouvements()->create([
                'type'           => $typeOperation,
                'sens'           => 'entree',
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
            ]);
            return;
        }

        $this->recordDoubleEntry([
            // 1. Débit Système (On reçoit physiquement l'argent dans le pool)
            [
                'caisse_id'      => $caisseGlobal->id,
                'type'           => $typeOperation,
                'sens'           => 'entree', // DÉBIT : augmente l'actif système
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => 'GLOBAL - ' . $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ],
            // 2. Crédit Compte Membre (alimenter le compte personnel du membre)
            [
                'caisse_id'      => $compteMembre->id,
                'type'           => $typeOperation,
                'sens'           => 'sortie', // CRÉDIT : alimente le passif membre
                'montant'        => $amount,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
            ],
        ]);
    }

    // ─── Garanties Nano-Crédits ──────────────────────────────────────────────

    /**
     * Blocage du montant de couverture lors de l'acceptation d'une garantie.
     *
     * Flux comptable (en partie double) :
     *   DEBIT  → Compte Épargne/Tontine du garant  (réduction de son solde disponible)
     *   CREDIT → Compte RESERVATIONS NANO-CREDIT du garant (montant séquestré)
     *
     * @param \App\Models\Caisse $compteEpargne     Compte tontine/épargne du garant à débiter
     * @param \App\Models\Caisse $compteReservation Compte reservation_nanocredit du garant
     * @param float              $montant           Montant à bloquer
     * @param mixed|null         $reference         Objet NanoCreditGarant de référence
     */
    public function logBlocageGarantie(
        \App\Models\Caisse $compteEpargne,
        \App\Models\Caisse $compteReservation,
        float $montant,
        $reference = null
    ): void {
        $libelle = 'Blocage garantie Nano-crédit #' . ($reference?->nano_credit_id ?? '?');

        $this->recordDoubleEntry([
            // 1. DEBIT Compte Épargne Garant (son argent est rendu indisponible)
            [
                'caisse_id'      => $compteEpargne->id,
                'type'           => 'blocage_garantie',
                'sens'           => 'entree', // DÉBIT : réduit son solde disponible
                'montant'        => $montant,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
            ],
            // 2. CREDIT Compte RESERVATIONS NANO-CREDIT (séquestre)
            [
                'caisse_id'      => $compteReservation->id,
                'type'           => 'blocage_garantie',
                'sens'           => 'sortie', // CRÉDIT : alimente le compte de séquestre
                'montant'        => $montant,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
            ],
        ]);
    }

    /**
     * Libération du montant de couverture après remboursement complet du nano-crédit.
     *
     * Flux comptable (inverse du blocage) :
     *   DEBIT  → Compte RESERVATIONS NANO-CREDIT du garant (clôture du séquestre)
     *   CREDIT → Compte Épargne/Tontine du garant  (restitution des fonds)
     *
     * @param \App\Models\Caisse $compteReservation Compte reservation_nanocredit du garant
     * @param \App\Models\Caisse $compteEpargne     Compte tontine/épargne du garant à créditer
     * @param float              $montant           Montant à libérer
     * @param mixed|null         $reference         Objet NanoCreditGarant de référence
     */
    public function logLiberationGarantie(
        \App\Models\Caisse $compteReservation,
        \App\Models\Caisse $compteEpargne,
        float $montant,
        $reference = null
    ): void {
        $libelle = 'Libération garantie Nano-crédit #' . ($reference?->nano_credit_id ?? '?');

        $this->recordDoubleEntry([
            // 1. DEBIT Compte RESERVATIONS NANO-CREDIT (on solde le séquestre)
            [
                'caisse_id'      => $compteReservation->id,
                'type'           => 'liberation_garantie',
                'sens'           => 'entree', // DÉBIT : réduit le séquestre
                'montant'        => $montant,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
            ],
            // 2. CREDIT Compte Épargne Garant (restitution)
            [
                'caisse_id'      => $compteEpargne->id,
                'type'           => 'liberation_garantie',
                'sens'           => 'sortie', // CRÉDIT : restitue le solde disponible
                'montant'        => $montant,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
            ],
        ]);
    }

    /**
     * Saisie forcée sur le compte de réservation du garant en cas de défaillance.
     * Les fonds réservés sont utilisés pour couvrir la dette : séquestre → SYS-NAN-CRD.
     *
     * @param \App\Models\Caisse $compteReservation Compte reservation_nanocredit du garant
     * @param float              $montant           Montant saisi
     * @param mixed|null         $reference         Objet NanoCreditGarant de référence
     */
    public function logSaisieGarantie(
        \App\Models\Caisse $compteReservation,
        float $montant,
        $reference = null
    ): void {
        $caisseNano = Caisse::getCaisseNanoCredit();
        if (!$caisseNano) {
            Log::warning('FinanceService::logSaisieGarantie: Compte SYS-NAN-CRD introuvable.');
            return;
        }

        $libelle = 'Saisie garantie Nano-crédit #' . ($reference?->nano_credit_id ?? '?');

        $this->recordDoubleEntry([
            // 1. DEBIT Compte RESERVATIONS NANO-CREDIT (sortie du séquestre)
            [
                'caisse_id'      => $compteReservation->id,
                'type'           => 'saisie_garantie',
                'sens'           => 'entree', // DÉBIT
                'montant'        => $montant,
                'date_operation' => now(),
                'libelle'        => $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
            ],
            // 2. CREDIT Compte SYS-NAN-CRD (remboursement du pool nano-crédit)
            [
                'caisse_id'      => $caisseNano->id,
                'type'           => 'saisie_garantie',
                'sens'           => 'sortie', // CRÉDIT
                'montant'        => $montant,
                'date_operation' => now(),
                'libelle'        => 'SAISIE GARANT - ' . $libelle,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
            ],
        ]);
    }
}

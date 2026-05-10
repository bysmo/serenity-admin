<?php

namespace App\Http\Controllers;

use App\Models\EpargneEcheance;
use App\Models\EpargnePlan;
use App\Models\EpargneSouscription;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembreEpargneController extends Controller
{
    /**
     * Liste des plans d'épargne actifs (page Épargne)
     */
    public function index()
    {
        $plans = EpargnePlan::where('actif', true)
            ->with('caisse')
            ->orderBy('ordre')
            ->orderBy('nom')
            ->get();

        $membre = Auth::guard('membre')->user();
        $planIdsDejaSouscrits = EpargneSouscription::where('membre_id', $membre->id)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now()->toDateString());
            })
            ->pluck('plan_id')
            ->unique()
            ->values()
            ->all();

        return view('membres.epargne.index', compact('plans', 'planIdsDejaSouscrits'));
    }

    /**
     * Formulaire de souscription à un plan
     */
    public function souscrire(EpargnePlan $plan)
    {
        if (!$plan->actif) {
            return redirect()->route('membre.epargne.index')
                ->with('error', 'Ce plan n\'est plus disponible.');
        }

        $membre = Auth::guard('membre')->user();
        $souscriptionEnCours = EpargneSouscription::where('membre_id', $membre->id)
            ->where('plan_id', $plan->id)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now()->toDateString());
            })
            ->exists();
        if ($souscriptionEnCours) {
            return redirect()->route('membre.epargne.index')
                ->with('error', 'Vous avez déjà une souscription en cours à ce forfait. Terminez-la avant d\'en souscrire une nouvelle.');
        }

        $montantExemple = (float) old('montant', $plan->montant_min);
        $exempleCalcul = $plan->calculRemboursement($montantExemple);
        $dateDebutExemple = old('date_debut', now()->format('Y-m-d'));
        $dateFinExemple = Carbon::parse($dateDebutExemple)->addMonths((int) ($plan->duree_mois ?? 12))->format('d/m/Y');

        return view('membres.epargne.souscrire', compact('plan', 'exempleCalcul', 'dateFinExemple'));
    }

    /**
     * Enregistrer la souscription et générer les premières échéances
     */
    public function storeSouscription(Request $request, EpargnePlan $plan)
    {
        if (!$plan->actif) {
            return redirect()->route('membre.epargne.index')
                ->with('error', 'Ce plan n\'est plus disponible.');
        }

        $membre = Auth::guard('membre')->user();
        $souscriptionEnCours = EpargneSouscription::where('membre_id', $membre->id)
            ->where('plan_id', $plan->id)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now()->toDateString());
            })
            ->exists();
        if ($souscriptionEnCours) {
            return redirect()->route('membre.epargne.index')
                ->with('error', 'Vous avez déjà une souscription en cours à ce forfait. Terminez-la avant d\'en souscrire une nouvelle.');
        }

        $montantMin = (float) $plan->montant_min;
        $montantMax = $plan->montant_max ? (float) $plan->montant_max : null;
        $rules = [
            'montant' => 'required|numeric|min:' . $montantMin,
            'date_debut' => 'required|date|after_or_equal:today',
        ];
        if ($montantMax) {
            $rules['montant'] .= '|max:' . $montantMax;
        }
        if ($plan->frequence === 'mensuel') {
            $rules['jour_du_mois'] = 'required|integer|min:1|max:28';
        }

        $validated = $request->validate($rules, [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant minimum est ' . number_format($montantMin, 0, ',', ' ') . ' XOF.',
            'date_debut.required' => 'La date de début est obligatoire.',
        ]);

        $dateDebut = Carbon::parse($validated['date_debut']);
        $dureeMois = (int) ($plan->duree_mois ?? 12);
        $dateFin = $dateDebut->copy()->addMonths($dureeMois);

        DB::transaction(function () use ($membre, $plan, $validated, $dateFin) {
            $souscription = EpargneSouscription::create([
                'membre_id' => $membre->id,
                'plan_id' => $plan->id,
                'montant' => $validated['montant'],
                'date_debut' => $validated['date_debut'],
                'date_fin' => $dateFin->format('Y-m-d'),
                'jour_du_mois' => $plan->frequence === 'mensuel' ? (int) $validated['jour_du_mois'] : null,
                'statut' => 'active',
                'solde_courant' => 0,
            ]);

            $this->genererPremieresEcheances($souscription);
        });

        $souscription = EpargneSouscription::where('membre_id', $membre->id)
            ->where('plan_id', $plan->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $souscription->load('plan');
        $dateFinStr = $souscription->date_fin ? \Carbon\Carbon::parse($souscription->date_fin)->format('d/m/Y') : '';
        $msg = 'Souscription enregistrée. Date de fin du plan : ' . $dateFinStr . '. Montant total qui vous sera reversé à l\'échéance : ' . number_format($souscription->montant_total_reverse, 0, ',', ' ') . ' XOF (épargne + rémunération).';
        return redirect()->route('membre.epargne.mes-epargnes')->with('success', $msg);
    }

    /**
     * Générer les premières échéances selon la fréquence du plan
     */
    protected function genererPremieresEcheances(EpargneSouscription $souscription): void
    {
        $plan = $souscription->plan;
        $dateDebut = Carbon::parse($souscription->date_debut);
        $montant = (float) $souscription->montant;
        $nbVersements = $plan->nombre_versements;
        $echeances = [];

        switch ($plan->frequence) {
            case 'journalier':
                for ($i = 0; $i < $nbVersements; $i++) {
                    $echeances[] = [
                        'date_echeance' => $dateDebut->copy()->addDays($i)->format('Y-m-d'),
                        'montant' => $montant,
                    ];
                }
                break;
            case 'hebdomadaire':
                for ($i = 0; $i < $nbVersements; $i++) {
                    $echeances[] = [
                        'date_echeance' => $dateDebut->copy()->addWeeks($i)->format('Y-m-d'),
                        'montant' => $montant,
                    ];
                }
                break;
            case 'mensuel':
                $jour = (int) $souscription->jour_du_mois;
                $premierMois = $dateDebut->copy()->day($jour);
                if ($premierMois->lt($dateDebut)) {
                    $premierMois->addMonth();
                }
                for ($i = 0; $i < $nbVersements; $i++) {
                    $date = $premierMois->copy()->addMonths($i);
                    $echeances[] = [
                        'date_echeance' => $date->format('Y-m-d'),
                        'montant' => $montant,
                    ];
                }
                break;
            case 'trimestriel':
                for ($i = 0; $i < $nbVersements; $i++) {
                    $date = $dateDebut->copy()->addMonths(3 * $i);
                    $echeances[] = [
                        'date_echeance' => $date->format('Y-m-d'),
                        'montant' => $montant,
                    ];
                }
                break;
            default:
                // Fréquence inconnue : aucune échéance générée (prévu manuellement)
                break;
        }

        foreach ($echeances as $e) {
            EpargneEcheance::create([
                'souscription_id' => $souscription->id,
                'date_echeance' => $e['date_echeance'],
                'montant' => $e['montant'],
                'statut' => 'en_attente',
            ]);
        }
    }

    /**
     * Liste des souscriptions du membre (Mes épargnes)
     */
    public function mesEpargnes()
    {
        $membre = Auth::guard('membre')->user();

        // NOTE: On ne reset plus les statuts en SQL.
        // Le statut de paiement ('en_attente', 'en_cours', 'payee') est géré en base.
        // Le statut temporel ('en_retard', 'a_venir', 'aujourd_hui') est calculé
        // dynamiquement par l'accesseur getTemporalStatusAttribute() du modèle.

        $souscriptions = $membre->epargneSouscriptions()
            ->with([
                'plan',
                'echeances' => fn ($q) => $q
                    ->whereIn('statut', ['en_attente', 'en_cours'])
                    ->orderBy('date_echeance'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $comptesExternes = $membre->comptesExternes()->orderByDesc('is_default')->get();

        return view('membres.epargne.mes-epargnes', compact('souscriptions', 'comptesExternes'));
    }

    /**
     * Détail d'une souscription (échéances + versements).
     * Gère aussi le retour PayDunya (?token=...) : vérification et enregistrement du versement si pas déjà fait.
     */
    public function showSouscription(Request $request, EpargneSouscription $souscription)
    {
        $membre = Auth::guard('membre')->user();
        if ($souscription->membre_id !== $membre->id) {
            abort(404);
        }

        $paymentStatus = null;
        $paymentMessage = null;
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;

        if ($request->has('token') && $paydunyaEnabled) {
            $invoiceToken = $request->input('token');
            try {
                $paydunyaService = new \App\Services\PayDunyaService();
                $verification = $paydunyaService->verifyInvoice($invoiceToken);

                if ($verification['success']) {
                    $status = $verification['status'] ?? 'unknown';
                    if ($status === 'completed') {
                        $verificationData = $verification['data'] ?? [];
                        $customData = $verificationData['custom_data'] ?? [];
                        $type = $customData['type'] ?? null;
                        $souscriptionId = isset($customData['souscription_id']) ? (int) $customData['souscription_id'] : null;
                        $echeanceId = isset($customData['echeance_id']) ? (int) $customData['echeance_id'] : null;
                        $membreId = isset($customData['membre_id']) ? (int) $customData['membre_id'] : null;

                        if ($type === 'epargne' && $membreId && $souscriptionId && $echeanceId && $souscriptionId === (int) $souscription->id) {
                            $versementExistant = \App\Models\EpargneVersement::where('reference', 'PAY-' . $invoiceToken)->first();
                            if (!$versementExistant) {
                                $souscription->load('plan');
                                $echeance = \App\Models\EpargneEcheance::findOrFail($echeanceId);
                                if ($echeance->souscription_id == $souscription->id) {
                                    $montant = isset($verificationData['total_amount']) ? (float) $verificationData['total_amount'] : (float) $echeance->montant;
                                    $caisseId = $souscription->caisse_id;

                                    $versement = \App\Models\EpargneVersement::create([
                                        'souscription_id' => $souscription->id,
                                        'echeance_id' => $echeance->id,
                                        'membre_id' => $membreId,
                                        'montant' => $montant,
                                        'date_versement' => now(),
                                        'mode_paiement' => 'paydunya',
                                        'reference' => 'PAY-' . $invoiceToken,
                                        'caisse_id' => $caisseId,
                                    ]);

                                    $souscription->increment('solde_courant', $montant);
                                    $echeance->update(['statut' => 'payee', 'paye_le' => now()]);

                                    if ($caisseId) {
                                        \App\Models\MouvementCaisse::create([
                                            'caisse_id' => $caisseId,
                                            'type' => 'epargne',
                                            'sens' => 'entree',
                                            'montant' => $montant,
                                            'date_operation' => now(),
                                            'libelle' => 'Épargne: ' . $souscription->plan->nom,
                                            'notes' => 'Versement PayDunya - Échéance ' . $echeance->date_echeance->format('d/m/Y'),
                                            'reference_type' => \App\Models\EpargneVersement::class,
                                            'reference_id' => $versement->id,
                                        ]);

                                        // Mouvement parallèle sur le compte global Tontine (Membres)
                                        $caisseGlobal = \App\Models\Caisse::getCaisseTontineCli();
                                        if ($caisseGlobal) {
                                            \App\Models\MouvementCaisse::create([
                                                'caisse_id'      => $caisseGlobal->id,
                                                'type'           => 'epargne',
                                                'sens'           => 'entree',
                                                'montant'        => $montant,
                                                'date_operation' => now(),
                                                'libelle'        => 'RÉCONCILIATION TONTINE: Plan ' . $souscription->plan->nom . ' (#' . $membreId . ')',
                                                'notes'          => 'PayDunya Redirect - Global - Réf: PAY-' . $invoiceToken,
                                                'reference_type' => \App\Models\EpargneVersement::class,
                                                'reference_id'   => $versement->id,
                                            ]);
                                        }
                                    }
                                    \Log::info('PayDunya épargne: Versement enregistré depuis return_url', [
                                        'versement_id' => $versement->id,
                                        'souscription_id' => $souscription->id,
                                        'echeance_id' => $echeance->id,
                                    ]);
                                }
                            }
                        }
                        $paymentStatus = 'success';
                        $paymentMessage = 'Paiement effectué avec succès.';
                    } elseif ($status === 'cancelled') {
                        $paymentStatus = 'cancelled';
                        $paymentMessage = 'Paiement annulé.';
                    } else {
                        $paymentStatus = 'pending';
                        $paymentMessage = 'Paiement en attente.';
                    }
                } else {
                    $paymentStatus = 'error';
                    $paymentMessage = $verification['message'] ?? 'Erreur lors de la vérification du paiement.';
                }
            } catch (\Exception $e) {
                \Log::error('PayDunya épargne: Erreur return_url', ['error' => $e->getMessage(), 'token' => $invoiceToken ?? null]);
                $paymentStatus = 'error';
                $paymentMessage = 'Erreur lors de la vérification du paiement.';
            }
        }

        $souscription->load(['plan', 'echeances' => fn ($q) => $q->orderBy('date_echeance')], 'versements');

        return view('membres.epargne.show', compact('souscription', 'paymentStatus', 'paymentMessage'));
    }

    /**
     * Initier un paiement Pi-SPI pour une échéance d'épargne
     */
    public function initierPaiementEpargnePiSpi(Request $request, EpargneEcheance $echeance)
    {
        $membre = Auth::guard('membre')->user();
        $souscription = $echeance->souscription;
        if ($souscription->membre_id !== $membre->id) {
            abort(404);
        }
        if (!in_array($echeance->statut, ['en_attente', 'a_venir', 'en_retard'])) {
            return redirect()->back()->with('error', 'Cette échéance est déjà réglée ou en cours de traitement.');
        }

        $request->validate([
            'compte_externe_id' => 'required|exists:membre_comptes_externes,id',
        ]);

        $compteExterne = \App\Models\CompteExterne::findOrFail($request->compte_externe_id);
        if ($compteExterne->membre_id !== $membre->id) abort(403);

        if (!$compteExterne->supportePiSpi()) {
            return redirect()->back()->with('error', 'Ce compte externe (IBAN) ne supporte pas les paiements Pi-SPI. Utilisez un compte de type Alias ou Téléphone.');
        }

        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        if (!$pispiConfig || !$pispiConfig->enabled) {
            return redirect()->back()->with('error', 'Le paiement Pi-SPI n\'est pas activé.');
        }

        try {
            $pispiService = app(\App\Services\PiSpiService::class);
            $payeAlias = \App\Models\PiSpiOperationAlias::getForType('tontine');
            
            $reference = 'T-PISPI-' . time() . '-' . $echeance->id;

            $montant = (float) $echeance->montant;

            // Créer un enregistrement de paiement en attente
            $paiement = \App\Models\Paiement::create([
                'reference'         => $reference,
                'membre_id'         => $membre->id,
                'compte_externe_id' => $compteExterne->id,
                'montant'           => $montant,
                'date_paiement'     => now(),
                'statut'            => 'en_attente',
                'mode_paiement'     => 'pispi',
                'caisse_id'         => $souscription->caisse_id ?? null,
                'metadata'          => [
                    'type'            => 'epargne',
                    'souscription_id' => $souscription->id,
                    'echeance_id'     => $echeance->id
                ],
                'commentaire' => 'Paiement échéance tontine via Pi-SPI',
            ]);

            $result = $pispiService->initiatePayment([
                'txId'        => $reference,
                'payeurAlias' => $compteExterne->getPayeurAliasForPiSpi(),
                'payeAlias'   => $payeAlias,
                'amount'      => $montant,
                'description' => 'Épargne ' . ($souscription->plan->nom ?? '') . ' - Éch ' . $echeance->date_echeance->format('d/m/Y'),
            ]);

            if ($result['success']) {
                $echeance->update(['statut' => 'en_cours']);
                return redirect()->back()->with('success', 'La demande Pi-SPI a été envoyée vers "' . $compteExterne->nom . '". Validez sur votre mobile.');
            }

            $paiement->delete();
            return redirect()->back()->with('error', 'Erreur Pi-SPI : ' . ($result['message'] ?? 'Echec initiation.'));

        } catch (\Exception $e) {
            \Log::error('Pi-SPI Epargne Init Error: ' . $e->getMessage());
            $friendly = app(\App\Services\PiSpiService::class)->getFriendlyErrorMessage($e);
            return redirect()->back()->with('error', $friendly);
        }
    }

    /**
     * Initier un paiement PayDunya pour une échéance d'épargne
     */
    public function initierPaiementEpargnePayDunya(Request $request, EpargneEcheance $echeance)
    {
        $membre = Auth::guard('membre')->user();
        $souscription = $echeance->souscription;
        if ($souscription->membre_id !== $membre->id) {
            abort(404);
        }
        if (!in_array($echeance->statut, ['en_attente', 'a_venir', 'en_retard'])) {
            return redirect()->back()->with('error', 'Cette échéance est déjà réglée ou en cours de traitement.');
        }

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return redirect()->back()->with('error', 'Le paiement en ligne n\'est pas disponible.');
        }

        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            $plan = $souscription->plan;
            $callbackUrl = url('/membre/paydunya/callback');
            $returnUrl = url('/membre/epargne/souscription/' . $souscription->id);
            $cancelUrl = $returnUrl;

            $result = $paydunyaService->createInvoice([
                'type' => 'epargne',
                'membre_id' => $membre->id,
                'souscription_id' => $souscription->id,
                'echeance_id' => $echeance->id,
                'item_name' => 'Épargne - ' . $plan->nom . ' - Échéance ' . $echeance->date_echeance->format('d/m/Y'),
                'amount' => (float) $echeance->montant,
                'description' => 'Versement épargne: ' . $plan->nom,
                'callback_url' => $callbackUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]);

            if ($result['success']) {
                $echeance->update(['statut' => 'en_cours']);
                return redirect($result['invoice_url']);
            }
            return redirect()->back()->with('error', $result['message'] ?? 'Erreur lors de la création du paiement.');
        } catch (\Exception $e) {
            \Log::error('PayDunya épargne: ' . $e->getMessage());
            $friendly = app(\App\Services\PayDunyaService::class)->getFriendlyErrorMessage($e);
            return redirect()->back()->with('error', $friendly);
        }
    }

    /**
     * Soumettre une demande de retrait de tontine
     */
    public function demandeRetrait(Request $request, EpargneSouscription $souscription)
    {
        $membre = Auth::guard('membre')->user();
        if ($souscription->membre_id !== $membre->id) {
            abort(404);
        }

        if ($souscription->statut !== 'active') {
            return redirect()->back()->with('error', 'Vous ne pouvez faire une demande de retrait que sur une tontine active.');
        }

        $request->validate([
            'montant_demande' => 'required|numeric|min:100',
            'mode_retrait'    => 'required|in:virement_interne,pispi',
            'commentaire'     => 'nullable|string|max:500',
        ]);

        $solde = (float) $souscription->solde_courant;
        if ((float) $request->montant_demande > $solde) {
            return redirect()->back()->with('error', 'Le montant demandé (' . number_format($request->montant_demande, 0, ',', ' ') . ') dépasse votre solde disponible (' . number_format($solde, 0, ',', ' ') . ' XOF).');
        }

        \App\Models\EpargneRetraitDemande::create([
            'souscription_id' => $souscription->id,
            'membre_id'       => $membre->id,
            'montant_demande' => $request->montant_demande,
            'statut'          => 'en_attente',
            'mode_retrait'    => $request->mode_retrait,
            'commentaire'     => $request->commentaire,
        ]);

        return redirect()->route('membre.epargne.souscription.show', $souscription)
            ->with('success', 'Votre demande de retrait de ' . number_format($request->montant_demande, 0, ',', ' ') . ' XOF a été soumise avec succès et est en attente de validation.');
    }
}

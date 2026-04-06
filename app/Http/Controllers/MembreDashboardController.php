<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\Annonce;
use App\Models\Paiement;
use App\Models\ParrainageConfig;
use App\Models\ParrainageCommission;
use App\Models\Remboursement;
use App\Models\User;
use App\Notifications\RemboursementPendingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MembreDashboardController extends Controller
{

    /**
     * Afficher le tableau de bord du membre
     */
    public function dashboard()
    {
        $membre = Auth::guard('membre')->user();

        // Cotisations dont le membre a une adhésion acceptée (publiques + privées)
        $cotisationIds = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)
            ->where('statut', 'accepte')
            ->pluck('cotisation_id');

        // Paiements liés au membre (membre_id = membre)
        $paiementsMembre = $membre->paiements()
            ->with(['cotisation', 'caisse'])
            ->orderBy('date_paiement', 'desc')
            ->get();

        // Paiements orphelins (membre_id null) pour les cotisations du membre (ex: paiement privé mal enregistré)
        $orphelins = \App\Models\Paiement::whereNull('membre_id')
            ->whereIn('cotisation_id', $cotisationIds)
            ->with(['cotisation', 'caisse'])
            ->orderBy('date_paiement', 'desc')
            ->get();

        $tousPaiements = $paiementsMembre->merge($orphelins)->unique('id')->sortByDesc('date_paiement')->values();
        $paiementsRecents = $tousPaiements->take(5);

        // Récupérer les engagements en cours et en retard
        $engagementsEnCours = $membre->engagements()
            ->whereIn('statut', ['en_cours', 'en_retard'])
            ->orderBy('periode_fin', 'asc')
            ->get();

        // Vérifier et mettre à jour le statut de chaque engagement
        foreach ($engagementsEnCours as $engagement) {
            $engagement->checkAndUpdateStatut();
        }

        // Récupérer les annonces actives
        $annonces = Annonce::active()
            ->orderBy('ordre', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Statistiques (tous paiements : publics + privés + orphelins rattachés)
        $totalPaiements = $tousPaiements->count();
        $montantTotal = $tousPaiements->sum('montant');
        $engagementsTotal = $membre->engagements()->whereIn('statut', ['en_cours', 'en_retard'])->count();

        // Évolution des paiements (6 derniers mois)
        $paiements6Mois = $tousPaiements->filter(fn ($p) => $p->date_paiement && $p->date_paiement >= now()->subMonths(6));
        $evolutionPaiements = $paiements6Mois->groupBy(fn ($p) => $p->date_paiement?->format('Y-m'))
            ->map(fn ($items) => (object) ['mois' => $items->first()->date_paiement?->format('Y-m'), 'total' => $items->sum('montant')])
            ->values()
            ->sortBy('mois')
            ->values()
            ->map(function ($item) {
                return [
                    'date' => $item->mois . '-01',
                    'total' => (float) $item->total,
                ];
            });

        // Répartition par mode de paiement
        $paiementsParMode = $tousPaiements->groupBy('mode_paiement')
            ->map(fn ($items, $mode) => [
                'mode_paiement' => $mode,
                'total' => (float) $items->sum('montant'),
            ])
            ->values();

        // ── Données de parrainage ──────────────────────────────────────────
        $parrainageConfig   = ParrainageConfig::current();
        $parrainageActif    = $parrainageConfig->actif ?? false;
        $codeParrainage     = $parrainageActif ? $membre->getOrCreateCodeParrainage() : $membre->code_parrainage;
        $nbFilleuls         = $membre->filleuls()->count();
        $commissionsDisponibles = $membre->commissionsParrainage()
            ->where('statut', 'disponible')
            ->sum('montant');
        $commissionsEnAttente = $membre->commissionsParrainage()
            ->where('statut', 'en_attente')
            ->sum('montant');
        $commissionsReclames  = $membre->commissionsParrainage()
            ->where('statut', 'reclame')
            ->sum('montant');
        $commissionsTotales   = $membre->commissionsParrainage()
            ->whereIn('statut', ['disponible', 'reclame', 'paye'])
            ->sum('montant');
        $derniersFilleuls = $membre->filleuls()
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get(['id', 'nom', 'prenom', 'created_at', 'statut']);

        return view('membres.dashboard', compact(
            'membre', 'paiementsRecents', 'engagementsEnCours', 'annonces',
            'totalPaiements', 'montantTotal', 'engagementsTotal', 'evolutionPaiements', 'paiementsParMode',
            'parrainageActif', 'codeParrainage', 'nbFilleuls',
            'commissionsDisponibles', 'commissionsEnAttente', 'commissionsReclames', 'commissionsTotales',
            'derniersFilleuls', 'parrainageConfig'
        ));
    }
    
    /**
     * Afficher la liste des paiements du membre (navigation par onglets : Tous / par année).
     */
    public function paiements()
    {
        $membre = Auth::guard('membre')->user();
        $annee = request('annee');

        $baseQuery = $membre->paiements()
            ->with(['cotisation', 'caisse'])
            ->orderBy('date_paiement', 'desc');

        if ($annee !== null && $annee !== '') {
            $baseQuery->whereYear('date_paiement', (int) $annee);
        }

        $paiements = $baseQuery->paginate(15)->withQueryString();

        $annees = $membre->paiements()
            ->selectRaw('YEAR(date_paiement) as annee')
            ->distinct()
            ->orderByDesc('annee')
            ->pluck('annee');

        return view('membres.paiements', compact('membre', 'paiements', 'annees', 'annee'));
    }
    
    /**
     * Redirection : ancienne URL /membre/cotisations vers Cotisations publiques.
     */
    public function cotisations()
    {
        return redirect()->route('membre.cotisations.publiques');
    }

    /**
     * Cotisations publiques : toutes les cotisations publiques (découvrir et adhérer).
     */
    public function cotisationsPubliques()
    {
        $membre = Auth::guard('membre')->user();
        $baseQuery = \App\Models\Cotisation::where('actif', true)->with(['caisse'])->withCount('paiements')->orderBy('nom');
        $cotisations = (clone $baseQuery)->where(function ($q) {
            $q->where('visibilite', 'publique')->orWhereNull('visibilite');
        })->paginate(15)->withQueryString();
        $adhesions = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        return view('membres.cotisations-publiques', compact('membre', 'cotisations', 'adhesions', 'paydunyaEnabled'));
    }

    /**
     * Cotisations privées : uniquement celles dont l'adhésion du membre a été acceptée.
     */
    public function cotisationsPrivees()
    {
        $membre = Auth::guard('membre')->user();
        $cotisationIdsAcceptees = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)
            ->where('statut', 'accepte')
            ->pluck('cotisation_id');
        $cotisations = \App\Models\Cotisation::where('actif', true)
            ->with(['caisse'])
            ->withCount('paiements')
            ->where('visibilite', 'privee')
            ->whereIn('id', $cotisationIdsAcceptees)
            ->orderBy('nom')
            ->paginate(15)->withQueryString();
        $adhesions = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        return view('membres.cotisations-privees', compact('membre', 'cotisations', 'adhesions', 'paydunyaEnabled'));
    }

    /**
     * Adhérer à une cotisation (publique : direct, privée : demande envoyée à l'admin)
     */
    public function adhererCotisation(Request $request, \App\Models\Cotisation $cotisation)
    {
        $membre = Auth::guard('membre')->user();
        
        if (!$cotisation->actif) {
            return redirect()->route('membre.cotisations')
                ->with('error', 'Cette cotisation n\'est plus active.');
        }
        
        $adhesionExistante = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)
            ->where('cotisation_id', $cotisation->id)
            ->first();
        
        if ($adhesionExistante) {
            if ($adhesionExistante->isAccepte()) {
                return redirect()->route('membre.cotisations.show', $cotisation->id)
                    ->with('info', 'Vous êtes déjà adhérent à cette cotisation.');
            }
            if ($adhesionExistante->isEnAttente()) {
                return redirect()->route('membre.cotisations')
                    ->with('info', 'Votre demande d\'adhésion est déjà en attente.');
            }
        }
        
        if ($cotisation->isPublique()) {
            \App\Models\CotisationAdhesion::create([
                'membre_id' => $membre->id,
                'cotisation_id' => $cotisation->id,
                'statut' => 'accepte',
            ]);
            return redirect()->route('membre.cotisations.show', $cotisation->id)
                ->with('success', 'Vous avez adhéré à la cotisation. Vous pouvez maintenant effectuer vos paiements.');
        }
        
        // Cotisation privée : créer une demande en attente
        \App\Models\CotisationAdhesion::create([
            'membre_id' => $membre->id,
            'cotisation_id' => $cotisation->id,
            'statut' => 'en_attente',
        ]);
        
        // Notifier l'admin de la cotisation : le créateur membre OU les admins app
        $adhesion = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)
            ->where('cotisation_id', $cotisation->id)
            ->first();
        if ($cotisation->created_by_membre_id) {
            $cotisation->createdByMembre->notify(new \App\Notifications\CotisationAdhesionDemandeNotification($adhesion));
        } else {
            $admins = \App\Models\User::role('admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\CotisationAdhesionDemandeNotification($adhesion));
            }
        }
        
        return redirect()->route('membre.cotisations')
            ->with('success', 'Votre demande d\'adhésion a été envoyée. L\'administrateur la traitera prochainement.');
    }
    
    /**
     * Afficher les détails d'une cotisation avec les paiements du membre
     */
    public function showCotisation(Request $request, $id)
    {
        $membre = Auth::guard('membre')->user();
        
        // Récupérer la cotisation
        $cotisation = \App\Models\Cotisation::with(['caisse'])->findOrFail($id);
        
        if (!$cotisation->actif) {
            abort(403, 'Cette cotisation n\'est plus active.');
        }
        
        // Adhésion du membre (pour afficher le bon bouton et autoriser le paiement)
        $adhesion = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)
            ->where('cotisation_id', $cotisation->id)
            ->first();
        
        // Cotisation privée : accès réservé aux membres dont l'adhésion a été acceptée
        if ($cotisation->isPrivee() && (!$adhesion || !$adhesion->isAccepte())) {
            return redirect()->route('membre.cotisations')
                ->with('error', 'Vous n\'avez pas accès à cette cotisation. Recherchez-la par code et faites une demande d\'adhésion.');
        }
        
        $canPay = $adhesion && $adhesion->isAccepte();
        
        // Récupérer les moyens de paiement actifs
        $paymentMethods = \App\Models\PaymentMethod::getActive();
        
        // Vérifier si PayDunya est activé (pour compatibilité)
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        // Si init_payment est demandé, initier le paiement
        if ($request->has('init_payment') && $request->has('payment_method')) {
            $paymentMethodCode = $request->input('payment_method');
            if ($paymentMethodCode === 'paydunya' && $paydunyaEnabled) {
                return $this->initierPaiementPayDunya($request, $cotisation->id);
            }
            // TODO: Ajouter les autres moyens de paiement (PayPal, Stripe)
        }
        
        // Vérifier si c'est un retour après paiement (PayDunya ajoute ?token=...)
        $paymentStatus = null;
        $paymentMessage = null;
        if ($request->has('token') && $paydunyaEnabled) {
            $invoiceToken = $request->input('token');
            try {
                $paydunyaService = new \App\Services\PayDunyaService();
                $verification = $paydunyaService->verifyInvoice($invoiceToken);
                
                if ($verification['success']) {
                    $status = $verification['status'] ?? 'unknown';
                    if ($status === 'completed') {
                        // Verrou pour éviter double enregistrement + double crédit caisse (return_url et callback IPN peuvent tous deux s'exécuter)
                        $lockKey = 'paydunya_payment_' . $invoiceToken;
                        Cache::lock($lockKey, 15)->block(10, function () use ($invoiceToken, $verification, $membre, &$paymentStatus, &$paymentMessage) {
                            $paiementExistant = \App\Models\Paiement::where('numero', 'PAY-' . $invoiceToken)->first();
                            if ($paiementExistant) {
                                return;
                            }
                            $verificationData = $verification['data'] ?? [];
                            $customData = $verificationData['custom_data'] ?? [];
                            $cotisationId = isset($customData['cotisation_id']) ? (int)$customData['cotisation_id'] : null;
                            $membreId = isset($customData['membre_id']) ? (int)$customData['membre_id'] : null;
                            if (!$membreId && $membre) {
                                $membreId = $membre->id;
                            }
                            \Log::info('PayDunya: Tentative d\'enregistrement depuis return_url', [
                                'cotisation_id' => $cotisationId,
                                'membre_id' => $membreId,
                                'custom_data' => $customData,
                            ]);
                            if (!$cotisationId || !$membreId) {
                                return;
                            }
                            $cotisationForPayment = \App\Models\Cotisation::findOrFail($cotisationId);
                            $montantPaiement = isset($verificationData['total_amount'])
                                ? (float)$verificationData['total_amount']
                                : (float)$cotisationForPayment->montant;
                            $paiement = \App\Models\Paiement::create([
                                'numero' => 'PAY-' . $invoiceToken,
                                'membre_id' => $membreId,
                                'cotisation_id' => $cotisationId,
                                'caisse_id' => $cotisationForPayment->caisse_id,
                                'montant' => $montantPaiement,
                                'date_paiement' => now(),
                                'mode_paiement' => 'mobile_money',
                                'notes' => 'Paiement via PayDunya - Token: ' . $invoiceToken . ' (enregistré depuis return_url)',
                            ]);
                            $caisse = \App\Models\Caisse::findOrFail($cotisationForPayment->caisse_id);
                            $soldeAvant = (float)$caisse->solde_initial;
                            $caisse->solde_initial = $soldeAvant + $montantPaiement;
                            $caisse->save();
                                
                                // Recharger la caisse pour vérifier le solde mis à jour
                                $caisse->refresh();
                                
                                \Log::info('PayDunya: Mise à jour du solde de la caisse (return_url)', [
                                    'caisse_id' => $caisse->id,
                                    'caisse_nom' => $caisse->nom,
                                    'solde_avant' => $soldeAvant,
                                    'montant_paiement' => $montantPaiement,
                                    'solde_apres' => $caisse->solde_initial,
                                    'paiement_id' => $paiement->id,
                                ]);
                                
                                // Journaliser le mouvement
                                \App\Models\MouvementCaisse::create([
                                    'caisse_id' => $caisse->id,
                                    'type' => 'paiement',
                                    'sens' => 'entree',
                                    'montant' => $paiement->montant,
                                    'date_operation' => $paiement->date_paiement,
                                    'libelle' => 'Paiement PayDunya: ' . $cotisationForPayment->nom,
                                    'notes' => 'Paiement via PayDunya (return_url)',
                                    'reference_type' => \App\Models\Paiement::class,
                                    'reference_id' => $paiement->id,
                                ]);
                                
                                // Envoyer un email avec PDF au membre
                                try {
                                    $emailService = new \App\Services\EmailService();
                                    $emailService->sendPaymentEmail($paiement);
                                } catch (\Exception $e) {
                                    \Log::error('PayDunya: Erreur lors de l\'envoi de l\'email de paiement', [
                                        'error' => $e->getMessage(),
                                        'paiement_id' => $paiement->id,
                                    ]);
                                }
                                
                                // Envoyer une notification à tous les admins
                                $admins = \App\Models\User::all();
                                foreach ($admins as $admin) {
                                    $admin->notify(new \App\Notifications\PayDunyaPaymentNotification($paiement));
                                }
                                
                                \Log::info('PayDunya: Paiement enregistré depuis return_url', [
                                    'paiement_id' => $paiement->id,
                                    'invoice_token' => $invoiceToken,
                                ]);
                        });
                        
                        $paymentStatus = 'success';
                        $paymentMessage = 'Paiement effectué avec succès !';
                    } elseif ($status === 'cancelled') {
                        $paymentStatus = 'cancelled';
                        $paymentMessage = 'Paiement annulé.';
                    } else {
                        $paymentStatus = 'pending';
                        $paymentMessage = 'Paiement en attente.';
                    }
                } else {
                    $paymentStatus = 'error';
                    $paymentMessage = 'Erreur lors de la vérification du paiement.';
                }
            } catch (\Exception $e) {
                \Log::error('PayDunya: Erreur lors de la vérification du retour', [
                    'error' => $e->getMessage(),
                    'token' => $invoiceToken,
                ]);
                $paymentStatus = 'error';
                $paymentMessage = 'Erreur lors de la vérification du paiement.';
            }
        } elseif ($request->has('payment')) {
            // Gestion des anciens paramètres (pour compatibilité)
            $paymentStatus = $request->input('payment');
            if ($paymentStatus === 'success') {
                $paymentMessage = 'Paiement effectué avec succès !';
            } elseif ($paymentStatus === 'cancelled') {
                $paymentMessage = 'Paiement annulé.';
            }
        }
        
        // Récupérer les paiements du membre pour cette cotisation (APRÈS avoir potentiellement enregistré un nouveau paiement)
        $paiements = \App\Models\Paiement::where('membre_id', $membre->id)
            ->where('cotisation_id', $cotisation->id)
            ->with(['caisse'])
            ->orderBy('date_paiement', 'desc')
            ->get();
        
        // Calculer le total payé
        $totalPaye = $paiements->sum('montant');
        
        return view('membres.cotisation-show', compact('membre', 'cotisation', 'adhesion', 'canPay', 'paiements', 'totalPaye', 'paydunyaEnabled', 'paymentMethods', 'paymentStatus', 'paymentMessage'));
    }
    
    /**
     * Initier un paiement PayDunya pour une cotisation
     */
    public function initierPaiementPayDunya(Request $request, $cotisationId)
    {
        $membre = Auth::guard('membre')->user();
        
        // Récupérer la cotisation
        $cotisation = \App\Models\Cotisation::with(['caisse'])->findOrFail($cotisationId);
        
        // Vérifier que le membre peut payer (adhésion acceptée)
        $adhesion = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)
            ->where('cotisation_id', $cotisation->id)
            ->first();
        
        $hasAccess = $adhesion && $adhesion->isAccepte();
        
        if (!$hasAccess || !$cotisation->actif) {
            return redirect()->route('membre.cotisations')
                ->with('error', 'Vous n\'avez pas accès à cette cotisation.');
        }
        
        // Vérifier si PayDunya est activé
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return redirect()->back()
                ->with('error', 'PayDunya n\'est pas activé.');
        }
        
        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            
            // URLs de callback (utiliser url() pour générer des URLs absolues correctes)
            $callbackUrl = url('/membre/paydunya/callback');
            $returnUrl = url('/membre/cotisations/' . $cotisation->id);
            $cancelUrl = url('/membre/cotisations/' . $cotisation->id);
            
            // Créer la facture PayDunya
            $result = $paydunyaService->createInvoice([
                'cotisation_id' => $cotisation->id,
                'membre_id' => $membre->id,
                'item_name' => $cotisation->nom,
                'amount' => $cotisation->montant,
                'description' => 'Paiement de la cotisation: ' . $cotisation->nom,
                'callback_url' => $callbackUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]);
            
            if ($result['success']) {
                // Rediriger vers la page de paiement PayDunya
                return redirect($result['invoice_url']);
            } else {
                return redirect()->back()
                    ->with('error', 'Erreur lors de la création du paiement: ' . ($result['message'] ?? 'Erreur inconnue'));
            }
        } catch (\Exception $e) {
            \Log::error('PayDunya: Erreur lors de l\'initiation du paiement', [
                'error' => $e->getMessage(),
                'cotisation_id' => $cotisationId,
                'membre_id' => $membre->id,
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage());
        }
    }
    
    /**
     * Callback IPN de PayDunya
     * D'après la documentation PayDunya, les données sont envoyées dans $_POST['data']
     */
    public function paydunyaCallback(Request $request)
    {
        \Log::info('PayDunya: Callback IPN reçu', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'all_data' => $request->all(),
            'raw_input' => $request->getContent(),
        ]);
        
        try {
            // PayDunya envoie les données dans 'data' selon la documentation
            // Mais selon la documentation, c'est un POST avec application/x-www-form-urlencoded
            // Donc les données peuvent être directement dans $request->all() ou dans 'data'
            $data = $request->input('data');
            
            // Si 'data' n'existe pas, essayer de récupérer directement depuis la requête
            if (!$data && $request->has('status')) {
                // Les données sont peut-être directement dans la requête
                $data = $request->all();
            }
            
            if (!$data) {
                \Log::error('PayDunya: Données manquantes dans le callback', [
                    'request_all' => $request->all(),
                    'request_input' => $request->input(),
                ]);
                return response()->json(['error' => 'Données manquantes'], 400);
            }
            
            \Log::info('PayDunya: Structure des données reçues', ['data_structure' => $data]);
            
            // Vérifier le hash pour s'assurer que la requête provient de PayDunya
            $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
            if ($paydunyaConfig && isset($data['hash'])) {
                $expectedHash = hash('sha512', $paydunyaConfig->master_key);
                if ($data['hash'] !== $expectedHash) {
                    \Log::error('PayDunya: Hash invalide dans le callback', [
                        'received_hash' => $data['hash'],
                        'expected_hash' => $expectedHash,
                    ]);
                    return response()->json(['error' => 'Hash invalide'], 400);
                }
                \Log::info('PayDunya: Hash vérifié avec succès');
            } else {
                \Log::warning('PayDunya: Hash non présent dans les données', [
                    'has_hash' => isset($data['hash']),
                    'has_config' => $paydunyaConfig !== null,
                ]);
            }
            
            $status = $data['status'] ?? 'unknown';
            $invoice = $data['invoice'] ?? [];
            $customData = $data['custom_data'] ?? [];
            $invoiceToken = $invoice['token'] ?? null;
            
            \Log::info('PayDunya: Informations extraites', [
                'status' => $status,
                'invoice_token' => $invoiceToken,
                'custom_data' => $customData,
            ]);
            
            // Si le paiement est complété
            if ($status === 'completed') {
                $cotisationId = isset($customData['cotisation_id']) ? (int) $customData['cotisation_id'] : null;
                $membreId = isset($customData['membre_id']) ? (int) $customData['membre_id'] : null;
                $type = isset($customData['type']) ? $customData['type'] : 'cotisation';
                $engagementId = isset($customData['engagement_id']) ? (int) $customData['engagement_id'] : null;
                $souscriptionId = isset($customData['souscription_id']) ? (int) $customData['souscription_id'] : null;
                $echeanceId = isset($customData['echeance_id']) ? (int) $customData['echeance_id'] : null;

                // Paiement épargne
                if ($type === 'epargne' && $membreId && $souscriptionId && $echeanceId && $invoiceToken) {
                    $versementExistant = \App\Models\EpargneVersement::where('reference', 'PAY-' . $invoiceToken)->first();
                    if (!$versementExistant) {
                        $souscription = \App\Models\EpargneSouscription::with('plan')->findOrFail($souscriptionId);
                        $echeance = \App\Models\EpargneEcheance::findOrFail($echeanceId);
                        if ($echeance->souscription_id != $souscription->id) {
                            \Log::warning('PayDunya épargne: echeance ne correspond pas à la souscription');
                        } else {
                            $montant = isset($invoice['total_amount']) ? (float) $invoice['total_amount'] : (float) $echeance->montant;
                            $caisseId = $souscription->plan->caisse_id;

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
                            }
                            \Log::info('PayDunya: Versement épargne enregistré', [
                                'versement_id' => $versement->id,
                                'souscription_id' => $souscription->id,
                                'echeance_id' => $echeance->id,
                                'montant' => $montant,
                            ]);
                        }
                    }
                } elseif ($cotisationId && $membreId && $invoiceToken) {
                    // Verrou pour éviter double enregistrement + double crédit caisse (return_url et callback IPN)
                    $lockKey = 'paydunya_payment_' . $invoiceToken;
                    Cache::lock($lockKey, 15)->block(10, function () use ($invoiceToken, $invoice, $cotisationId, $membreId, $type, $engagementId) {
                        $paiementExistant = \App\Models\Paiement::where('numero', 'PAY-' . $invoiceToken)->first();
                        if ($paiementExistant) {
                            return;
                        }
                        $cotisation = \App\Models\Cotisation::findOrFail($cotisationId);
                        $membre = \App\Models\Membre::findOrFail($membreId);
                        $engagement = null;
                        if ($type === 'engagement' && $engagementId) {
                            $engagement = \App\Models\Engagement::findOrFail($engagementId);
                            $montant = isset($invoice['total_amount']) ? (float)$invoice['total_amount'] : (float)($engagement->montant_engage - ($engagement->montant_paye ?? 0));
                        } else {
                            $montant = isset($invoice['total_amount']) ? (float)$invoice['total_amount'] : (float)$cotisation->montant;
                        }
                        $paiement = \App\Models\Paiement::create([
                            'numero' => 'PAY-' . $invoiceToken,
                            'membre_id' => $membreId,
                            'cotisation_id' => $cotisationId,
                            'caisse_id' => $cotisation->caisse_id,
                            'montant' => $montant,
                            'date_paiement' => now(),
                            'mode_paiement' => 'mobile_money',
                            'notes' => 'Paiement via PayDunya - Token: ' . $invoiceToken . ($type === 'engagement' ? ' (Engagement ID: ' . $engagementId . ')' : ''),
                        ]);
                        \Log::info('PayDunya: Paiement créé', [
                            'paiement_id' => $paiement->id,
                            'paiement_numero' => $paiement->numero,
                            'membre_id' => $membreId,
                            'cotisation_id' => $cotisationId,
                            'engagement_id' => $engagementId,
                            'type' => $type,
                            'montant' => $montant,
                            'date_paiement' => $paiement->date_paiement,
                        ]);
                        $caisse = \App\Models\Caisse::findOrFail($cotisation->caisse_id);
                        $soldeAvant = (float)$caisse->solde_initial;
                        $caisse->solde_initial = $soldeAvant + $montant;
                        $caisse->save();
                        
                        // Recharger la caisse pour vérifier le solde mis à jour
                        $caisse->refresh();
                        
                        \Log::info('PayDunya: Mise à jour du solde de la caisse (callback IPN)', [
                            'caisse_id' => $caisse->id,
                            'caisse_nom' => $caisse->nom,
                            'solde_avant' => $soldeAvant,
                            'montant_paiement' => $montant,
                            'solde_apres' => $caisse->solde_initial,
                            'paiement_id' => $paiement->id,
                        ]);
                        
                        // Journaliser le mouvement
                        \App\Models\MouvementCaisse::create([
                            'caisse_id' => $caisse->id,
                            'type' => 'paiement',
                            'sens' => 'entree',
                            'montant' => $paiement->montant,
                            'date_operation' => $paiement->date_paiement,
                            'libelle' => 'Paiement PayDunya: ' . $cotisation->nom,
                            'notes' => 'Paiement via PayDunya',
                            'reference_type' => \App\Models\Paiement::class,
                            'reference_id' => $paiement->id,
                        ]);
                        
                        // Envoyer un email avec PDF au membre
                        try {
                            $emailService = new \App\Services\EmailService();
                            $emailService->sendPaymentEmail($paiement);
                        } catch (\Exception $e) {
                            \Log::error('PayDunya: Erreur lors de l\'envoi de l\'email de paiement (callback IPN)', [
                                'error' => $e->getMessage(),
                                'paiement_id' => $paiement->id,
                            ]);
                        }
                        
                        // Si c'est un paiement d'engagement, vérifier et mettre à jour le statut
                        if ($type === 'engagement' && $engagement) {
                            // Recalculer le montant payé en récupérant tous les paiements de l'engagement
                            // On récupère tous les paiements depuis le début de la période, même s'ils sont faits après la fin
                            $montantPaye = \App\Models\Paiement::where('membre_id', $membreId)
                                ->where('cotisation_id', $cotisationId)
                                ->where(function ($query) use ($engagement) {
                                    if ($engagement->periode_debut) {
                                        $query->whereDate('date_paiement', '>=', $engagement->periode_debut);
                                    } else {
                                        $query->whereNotNull('date_paiement');
                                    }
                                })
                                ->get()
                                ->sum('montant');
                            
                            $resteAPayer = $engagement->montant_engage - $montantPaye;
                            
                            // Si le reste à payer est 0 ou moins, mettre à jour le statut
                            if ($resteAPayer <= 0 && in_array($engagement->statut, ['en_cours', 'en_retard'])) {
                                $ancienStatut = $engagement->statut;
                                $engagement->statut = 'honore';
                                $engagement->save();
                                
                                \Log::info('PayDunya: Statut de l\'engagement mis à jour', [
                                    'engagement_id' => $engagement->id,
                                    'ancien_statut' => $ancienStatut,
                                    'nouveau_statut' => 'honore',
                                    'montant_paye' => $montantPaye,
                                    'montant_engage' => $engagement->montant_engage,
                                    'reste_a_payer' => $resteAPayer,
                                    'paiement_id' => $paiement->id,
                                ]);
                            } else {
                                \Log::info('PayDunya: Engagement partiellement payé', [
                                    'engagement_id' => $engagement->id,
                                    'montant_paye' => $montantPaye,
                                    'montant_engage' => $engagement->montant_engage,
                                    'reste_a_payer' => $resteAPayer,
                                    'statut' => $engagement->statut,
                                    'paiement_id' => $paiement->id,
                                ]);
                            }
                        }
                        
                        // Envoyer une notification à tous les admins
                        $admins = \App\Models\User::all();
                        foreach ($admins as $admin) {
                            $admin->notify(new \App\Notifications\PayDunyaPaymentNotification($paiement));
                        }
                        
                        \Log::info('PayDunya: Paiement enregistré avec succès', [
                            'paiement_id' => $paiement->id,
                            'paiement_numero' => $paiement->numero,
                            'invoice_token' => $invoiceToken,
                            'montant' => $montant,
                            'cotisation_id' => $cotisationId,
                            'membre_id' => $membreId,
                            'type' => $type,
                            'engagement_id' => $engagementId,
                        ]);
                    });
                } else {
                    \Log::warning('PayDunya: Données incomplètes pour enregistrer le paiement', [
                        'cotisation_id' => $cotisationId,
                        'membre_id' => $membreId,
                        'invoice_token' => $invoiceToken,
                    ]);
                }
            } else {
                \Log::info('PayDunya: Statut de paiement non complété', ['status' => $status]);
            }
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            \Log::error('PayDunya: Erreur dans le callback', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Afficher la liste des engagements du membre
     */
    public function engagements()
    {
        $membre = Auth::guard('membre')->user();
        
        $engagements = $membre->engagements()
            ->with(['cotisation'])
            ->orderBy('periode_fin', 'desc')
            ->get();
        
        // Vérifier et mettre à jour le statut de chaque engagement
        foreach ($engagements as $engagement) {
            $engagement->checkAndUpdateStatut();
        }
        
        // Paginer après la mise à jour
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $engagements->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $engagements = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $engagements->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
        );
        
        return view('membres.engagements', compact('membre', 'engagements'));
    }
    
    /**
     * Afficher les détails d'un engagement avec les paiements du membre
     */
    public function showEngagement(Request $request, $id)
    {
        $membre = Auth::guard('membre')->user();
        
        // Récupérer l'engagement
        $engagement = \App\Models\Engagement::with(['cotisation', 'cotisation.caisse'])->findOrFail($id);
        
        // Vérifier que l'engagement appartient au membre
        if ($engagement->membre_id !== $membre->id) {
            abort(403, 'Vous n\'avez pas accès à cet engagement.');
        }
        
        // Vérifier et mettre à jour le statut selon la date d'échéance
        $engagement->checkAndUpdateStatut();
        
        // Récupérer les moyens de paiement actifs
        $paymentMethods = \App\Models\PaymentMethod::getActive();
        
        // Vérifier si PayDunya est activé (pour compatibilité)
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        // Si init_payment est demandé, initier le paiement
        if ($request->has('init_payment') && $request->has('payment_method')) {
            $paymentMethodCode = $request->input('payment_method');
            if ($paymentMethodCode === 'paydunya' && $paydunyaEnabled) {
                return $this->initierPaiementEngagementPayDunya($request, $engagement->id);
            }
            // TODO: Ajouter les autres moyens de paiement (PayPal, Stripe)
        }
        
        // Vérifier si c'est un retour après paiement (PayDunya ajoute ?token=...)
        $paymentStatus = null;
        $paymentMessage = null;
        if ($request->has('token') && $paydunyaEnabled) {
            $invoiceToken = $request->input('token');
            try {
                $paydunyaService = new \App\Services\PayDunyaService();
                $verification = $paydunyaService->verifyInvoice($invoiceToken);
                
                if ($verification['success']) {
                    $status = $verification['status'] ?? 'unknown';
                    if ($status === 'completed') {
                        $lockKey = 'paydunya_payment_' . $invoiceToken;
                        Cache::lock($lockKey, 15)->block(10, function () use ($invoiceToken, $verification, $membre, $engagement) {
                            $paiementExistant = \App\Models\Paiement::where('numero', 'PAY-' . $invoiceToken)->first();
                            if ($paiementExistant) {
                                return;
                            }
                            $verificationData = $verification['data'] ?? [];
                            $customData = $verificationData['custom_data'] ?? [];
                            $cotisationId = isset($customData['cotisation_id']) ? (int)$customData['cotisation_id'] : null;
                            $membreId = isset($customData['membre_id']) ? (int)$customData['membre_id'] : null;
                            $engagementId = isset($customData['engagement_id']) ? (int)$customData['engagement_id'] : null;
                            if (!$membreId && $membre) {
                                $membreId = $membre->id;
                            }
                            \Log::info('PayDunya: Tentative d\'enregistrement depuis return_url (engagement)', [
                                'cotisation_id' => $cotisationId,
                                'membre_id' => $membreId,
                                'engagement_id' => $engagementId,
                                'custom_data' => $customData,
                            ]);
                            if (!$cotisationId || !$membreId) {
                                return;
                            }
                            $cotisationForPayment = \App\Models\Cotisation::findOrFail($cotisationId);
                            $montantPaiement = isset($verificationData['total_amount'])
                                ? (float)$verificationData['total_amount']
                                : (float)($engagement->montant_engage - ($engagement->montant_paye ?? 0));
                            $paiement = \App\Models\Paiement::create([
                                'numero' => 'PAY-' . $invoiceToken,
                                'membre_id' => $membreId,
                                'cotisation_id' => $cotisationId,
                                'caisse_id' => $cotisationForPayment->caisse_id,
                                'montant' => $montantPaiement,
                                'date_paiement' => now(),
                                'mode_paiement' => 'mobile_money',
                                'notes' => 'Paiement via PayDunya - Token: ' . $invoiceToken . ' (enregistré depuis return_url - Engagement ID: ' . $engagementId . ')',
                            ]);
                            $caisse = \App\Models\Caisse::findOrFail($cotisationForPayment->caisse_id);
                            $soldeAvant = (float)$caisse->solde_initial;
                            $caisse->solde_initial = $soldeAvant + $montantPaiement;
                            $caisse->save();
                            $caisse->refresh();
                            \Log::info('PayDunya: Mise à jour du solde de la caisse (return_url - engagement)', [
                                'caisse_id' => $caisse->id,
                                'caisse_nom' => $caisse->nom,
                                'solde_avant' => $soldeAvant,
                                'montant_paiement' => $montantPaiement,
                                'solde_apres' => $caisse->solde_initial,
                                'paiement_id' => $paiement->id,
                            ]);
                            \App\Models\MouvementCaisse::create([
                                'caisse_id' => $caisse->id,
                                'type' => 'paiement',
                                'sens' => 'entree',
                                'montant' => $paiement->montant,
                                'date_operation' => $paiement->date_paiement,
                                'libelle' => 'Paiement PayDunya: ' . $cotisationForPayment->nom,
                                'notes' => 'Paiement via PayDunya (Engagement)',
                                'reference_type' => \App\Models\Paiement::class,
                                'reference_id' => $paiement->id,
                            ]);
                            try {
                                $emailService = new \App\Services\EmailService();
                                $emailService->sendPaymentEmail($paiement);
                            } catch (\Exception $e) {
                                \Log::error('PayDunya: Erreur lors de l\'envoi de l\'email de paiement (return_url - engagement)', [
                                    'error' => $e->getMessage(),
                                    'paiement_id' => $paiement->id,
                                ]);
                            }
                            $admins = \App\Models\User::all();
                            foreach ($admins as $admin) {
                                $admin->notify(new \App\Notifications\PayDunyaPaymentNotification($paiement));
                            }
                            \Log::info('PayDunya: Paiement enregistré depuis return_url (engagement)', [
                                'paiement_id' => $paiement->id,
                                'invoice_token' => $invoiceToken,
                            ]);
                        });
                        
                        $paymentStatus = 'success';
                        $paymentMessage = 'Votre paiement a été effectué avec succès.';
                    } elseif ($status === 'cancelled') {
                        $paymentStatus = 'cancelled';
                        $paymentMessage = 'Le paiement a été annulé.';
                    } else {
                        $paymentStatus = 'pending';
                        $paymentMessage = 'Votre paiement est en attente de confirmation.';
                    }
                } else {
                    $paymentStatus = 'error';
                    $paymentMessage = 'Erreur lors de la vérification du paiement.';
                }
            } catch (\Exception $e) {
                \Log::error('PayDunya: Erreur lors de la vérification du paiement (engagement)', [
                    'error' => $e->getMessage(),
                ]);
                $paymentStatus = 'error';
                $paymentMessage = 'Erreur lors de la vérification du paiement.';
            }
        }
        
        // Récupérer les paiements liés à cet engagement (APRÈS avoir potentiellement enregistré un nouveau paiement)
        // Pour un engagement, on récupère tous les paiements pour cette cotisation depuis la période de début
        // même s'ils sont faits après la période de fin (paiements en retard)
        $paiements = \App\Models\Paiement::where('membre_id', $membre->id)
            ->where('cotisation_id', $engagement->cotisation_id)
            ->where(function($query) use ($engagement) {
                if ($engagement->periode_debut) {
                    // Récupérer tous les paiements depuis le début de la période
                    $query->whereDate('date_paiement', '>=', $engagement->periode_debut);
                } else {
                    // Si pas de période de début, prendre tous les paiements pour cette cotisation
                    $query->whereNotNull('date_paiement');
                }
            })
            ->with(['caisse'])
            ->orderBy('date_paiement', 'desc')
            ->get();
        
        // Calculer le montant payé à partir des paiements récupérés
        $montantPaye = $paiements->sum('montant');
        $resteAPayer = $engagement->montant_engage - $montantPaye;
        
        // Log pour débogage
        \Log::info('Engagement: Calcul du montant payé', [
            'engagement_id' => $engagement->id,
            'montant_engage' => $engagement->montant_engage,
            'montant_paye' => $montantPaye,
            'reste_a_payer' => $resteAPayer,
            'nombre_paiements' => $paiements->count(),
            'periode_debut' => $engagement->periode_debut,
            'periode_fin' => $engagement->periode_fin,
            'statut' => $engagement->statut,
        ]);
        
        // Si le reste à payer est 0 et que l'engagement est en cours ou en retard, mettre à jour le statut
        if ($resteAPayer <= 0 && in_array($engagement->statut, ['en_cours', 'en_retard'])) {
            $engagement->statut = 'honore';
            $engagement->save();
            // Recharger l'engagement pour avoir le statut mis à jour
            $engagement->refresh();
            
            \Log::info('Engagement: Statut mis à jour à honoré', [
                'engagement_id' => $engagement->id,
                'montant_paye' => $montantPaye,
                'montant_engage' => $engagement->montant_engage,
            ]);
        }
        
        // Si payment=true dans l'URL, on affiche directement la section paiement
        $showPayment = $request->has('payment');
        
        return view('membres.engagement-show', compact(
            'membre', 
            'engagement', 
            'paiements', 
            'montantPaye', 
            'resteAPayer', 
            'paymentMethods', 
            'paydunyaEnabled',
            'paymentStatus',
            'paymentMessage',
            'showPayment'
        ));
    }
    
    /**
     * Initier un paiement PayDunya pour un engagement
     */
    public function initierPaiementEngagementPayDunya(Request $request, $id)
    {
        $membre = Auth::guard('membre')->user();
        
        // Récupérer l'engagement
        $engagement = \App\Models\Engagement::with(['cotisation', 'cotisation.caisse'])->findOrFail($id);
        
        // Vérifier que l'engagement appartient au membre
        if ($engagement->membre_id !== $membre->id) {
            abort(403, 'Vous n\'avez pas accès à cet engagement.');
        }
        
        // Vérifier que l'engagement est en cours ou en retard
        if (!in_array($engagement->statut, ['en_cours', 'en_retard'])) {
            return redirect()->back()->with('error', 'Cet engagement n\'est plus en cours.');
        }
        
        // Calculer le montant à payer (reste à payer)
        $montantPaye = $engagement->montant_paye ?? 0;
        $montantAPayer = $engagement->montant_engage - $montantPaye;
        
        if ($montantAPayer <= 0) {
            return redirect()->back()->with('error', 'Cet engagement est déjà entièrement payé.');
        }
        
        // Vérifier que PayDunya est activé
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return redirect()->back()->with('error', 'Le paiement mobile n\'est pas activé.');
        }
        
        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            
            // URLs de callback (utiliser url() pour générer des URLs absolues correctes)
            $callbackUrl = url('/membre/paydunya/callback');
            $returnUrl = url('/membre/engagements/' . $engagement->id);
            $cancelUrl = url('/membre/engagements/' . $engagement->id);
            
            // Créer la facture PayDunya
            $result = $paydunyaService->createInvoice([
                'engagement_id' => $engagement->id,
                'cotisation_id' => $engagement->cotisation_id,
                'membre_id' => $membre->id,
                'item_name' => 'Paiement engagement - ' . $engagement->cotisation->nom,
                'amount' => $montantAPayer,
                'description' => 'Paiement de l\'engagement: ' . $engagement->cotisation->nom,
                'callback_url' => $callbackUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'type' => 'engagement',
            ]);
            
            if ($result['success']) {
                // Rediriger vers la page de paiement PayDunya
                return redirect($result['invoice_url']);
            } else {
                return redirect()->back()
                    ->with('error', 'Erreur lors de la création du paiement: ' . ($result['message'] ?? 'Erreur inconnue'));
            }
        } catch (\Exception $e) {
            \Log::error('PayDunya: Erreur lors de l\'initiation du paiement (engagement)', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
            return redirect()->back()->with('error', 'Erreur lors de la création du paiement: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher le profil du membre
     */
    public function profil()
    {
        $membre = Auth::guard('membre')->user();

        // Données de parrainage pour la page profil
        $parrainageConfig      = ParrainageConfig::current();
        $parrainageActif       = $parrainageConfig->actif ?? false;
        $codeParrainage        = $parrainageActif ? $membre->getOrCreateCodeParrainage() : $membre->code_parrainage;
        $nbFilleuls            = $membre->filleuls()->count();
        $commissionsDisponibles = $membre->commissionsParrainage()->where('statut', 'disponible')->sum('montant');
        $commissionsTotales    = $membre->commissionsParrainage()->whereIn('statut', ['disponible', 'reclame', 'paye'])->sum('montant');

        return view('membres.profil', compact(
            'membre', 'parrainageActif', 'codeParrainage',
            'nbFilleuls', 'commissionsDisponibles', 'commissionsTotales', 'parrainageConfig'
        ));
    }
    
    /**
     * Mettre à jour le profil du membre
     */
    public function updateProfil(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        
        $validated = $request->validate([
            'email' => 'required|email|unique:membres,email,' . $membre->id,
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        
        // Si le mot de passe est fourni, le hasher
        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Normaliser le téléphone
        if (!empty($validated['telephone'])) {
            $validated['telephone'] = \App\Models\Membre::normalizePhoneNumber($validated['telephone']);
        }
        
        $membre->update($validated);
        
        return redirect()->route('membre.profil')
            ->with('success', 'Vos informations ont été mises à jour avec succès.');
    }

    /**
     * Afficher la liste des remboursements du membre
     */
    public function remboursements()
    {
        $membre = Auth::guard('membre')->user();
        
        $remboursements = $membre->remboursements()
            ->with(['paiement.cotisation', 'caisse', 'traitePar'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('membres.remboursements', compact('membre', 'remboursements'));
    }

    /**
     * Créer une demande de remboursement
     */
    public function creerRemboursement(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        $validated = $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'montant' => 'required|numeric|min:1',
            'raison' => 'required|string|max:1000',
        ]);

        // Vérifier que le paiement appartient au membre
        $paiement = Paiement::findOrFail($validated['paiement_id']);
        if ($paiement->membre_id !== $membre->id) {
            abort(403, 'Vous n\'avez pas accès à ce paiement.');
        }

        // Vérifier que le montant ne dépasse pas le montant du paiement
        if ($validated['montant'] > $paiement->montant) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['montant' => 'Le montant demandé ne peut pas dépasser le montant du paiement.']);
        }

        // Vérifier qu'il n'y a pas déjà un remboursement en attente ou approuvé pour ce paiement
        $remboursementExistant = Remboursement::where('paiement_id', $paiement->id)
            ->whereIn('statut', ['en_attente', 'approuve'])
            ->first();
        
        if ($remboursementExistant) {
            return redirect()->back()
                ->with('error', 'Une demande de remboursement est déjà en cours pour ce paiement.');
        }

        // Générer un numéro de remboursement unique
        do {
            $numero = 'REM-' . strtoupper(Str::random(8));
        } while (Remboursement::where('numero', $numero)->exists());

        // Créer le remboursement
        $remboursement = Remboursement::create([
            'numero' => $numero,
            'paiement_id' => $paiement->id,
            'membre_id' => $membre->id,
            'montant' => $validated['montant'],
            'raison' => $validated['raison'],
            'statut' => 'en_attente',
        ]);

        // Charger les relations nécessaires pour la notification
        $remboursement->load(['paiement', 'membre']);

        // Envoyer une notification à tous les utilisateurs avec le rôle admin
        $admins = User::whereHas('roles', function($query) {
            $query->where('slug', 'admin')->where('actif', true);
        })->get();

        foreach ($admins as $admin) {
            $admin->notify(new RemboursementPendingNotification($remboursement));
        }

        return redirect()->route('membre.remboursements')
            ->with('success', 'Votre demande de remboursement a été enregistrée avec succès. Elle sera traitée par l\'administration.');
    }

    /**
     * Page Nano Crédits (réservée aux membres dont le KYC est validé)
     */
    public function nanoCredits()
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.kyc.index')
                ->with('info', 'Vous devez soumettre votre dossier KYC et qu\'il soit validé par l\'administration avant de pouvoir faire une demande de nano crédit.');
        }

        return view('membres.nano-credits', compact('membre'));
    }
}

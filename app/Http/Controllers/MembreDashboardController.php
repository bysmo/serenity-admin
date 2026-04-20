<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\Segment;
use App\Models\KycVerification;
use App\Models\Cotisation;
use App\Models\CotisationAdhesion;
use App\Models\Engagement;
use App\Models\MouvementCaisse;
use App\Models\Paiement;
use App\Models\Annonce;
use App\Services\PayDunyaCallbackService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MembreDashboardController extends Controller
{
    /**
     * Dashboard ADMIN : Statistiques globales des membres
     */
    public function index(Request $request)
    {
        // Période par défaut : le mois en cours
        $dateDebut = $request->filled('date_debut') 
            ? Carbon::parse($request->date_debut)->startOfDay() 
            : Carbon::now()->startOfMonth();
            
        $dateFin = $request->filled('date_fin') 
            ? Carbon::parse($request->date_fin)->endOfDay() 
            : Carbon::now()->endOfDay();

        // 1. Statistiques générales
        $totalMembres = Membre::count();
        $membresActifs = Membre::where('statut', 'actif')->count();
        
        // Membres inscrits sur la période
        $nouveauxMembres = Membre::whereBetween('created_at', [$dateDebut, $dateFin])->count();

        // 2. Statistiques KYC
        $kycStats = [
            'valide' => KycVerification::where('statut', 'valide')->count(),
            'en_attente' => KycVerification::where('statut', 'en_attente')->count(),
            'rejete' => KycVerification::where('statut', 'rejete')->count(),
        ];
        
        // Membres sans aucun enregistrement KYC
        $membresAvecKycIds = KycVerification::pluck('membre_id')->unique();
        $kycStats['manquant'] = Membre::whereNotIn('id', $membresAvecKycIds)->count();

        // 3. Répartition par segment
        $segments = Segment::withCount('membres')->get();
        
        // 4. Évolution des adhésions
        $diffInDays = $dateDebut->diffInDays($dateFin);
        
        if ($diffInDays <= 62) {
            $evolution = Membre::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as aggregate'))
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->date => $item->aggregate]);
        } else {
            $evolution = Membre::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as date"), DB::raw('count(*) as aggregate'))
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->date => $item->aggregate]);
        }

        // 5. Répartition par genre (Sexe)
        // Note: On utilise ->filter() pour regrouper les null/vides sous "Non précisé" si nécessaire
        $sexeStats = Membre::select('sexe', DB::raw('count(*) as count'))
            ->groupBy('sexe')
            ->get()
            ->mapWithKeys(fn($item) => [($item->sexe ?? 'N/A') => $item->count]);

        return view('membres.dashboard', compact(
            'totalMembres', 'membresActifs', 'nouveauxMembres',
            'kycStats', 'segments', 'evolution', 'sexeStats',
            'dateDebut', 'dateFin'
        ));
    }

    // ─── ESPACE MEMBRE (Portal) ───────────────────────────────────────────────

    /**
     * Dashboard du MEMBRE
     */
    public function dashboard(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        
        $cotisationIds = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        
        $paiementsMembre = $membre->paiements()->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->get();
        $orphelins = Paiement::whereNull('membre_id')->whereIn('cotisation_id', $cotisationIds)->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->get();
        $tousPaiements = $paiementsMembre->merge($orphelins)->unique('id')->sortByDesc('date_paiement')->values();
        
        $paiementsRecents = $tousPaiements->take(5);

        $engagementsEnCours = $membre->engagements()->whereIn('statut', ['en_cours', 'en_retard'])->orderBy('periode_fin', 'asc')->get();
        foreach ($engagementsEnCours as $e) {
            $e->checkAndUpdateStatut();
        }

        $epargnesActives = $membre->epargneSouscriptions()->where('statut', 'active')->count();
        $annonces = Annonce::active()->orderBy('ordre')->orderBy('created_at')->get();

        $cotisationsPubliques = Cotisation::where('actif', true)->where('visibilite', 'publique')->orderBy('nom')->paginate(15, ['*'], 'pub');
        $cotisationsPrivees = Cotisation::where('actif', true)->where('visibilite', 'privee')->whereIn('id', $cotisationIds)->orderBy('nom')->paginate(15, ['*'], 'pri');
        
        $adhesions = CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;

        return view('membres.cotisations', compact(
            'membre', 'paiementsRecents', 'engagementsEnCours', 
            'epargnesActives', 'annonces', 'tousPaiements',
            'cotisationsPubliques', 'cotisationsPrivees', 'adhesions', 
            'paydunyaEnabled', 'pispiEnabled'
        ));
    }

    /**
     * Afficher le profil du membre
     */
    public function profil(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $segments = Segment::where('actif', true)->get();
        
        // Stats parrainage pour la vue profil
        $parrainageActif = (bool) \App\Models\ParrainageConfig::current()?->actif;
        $codeParrainage = $membre->getOrCreateCodeParrainage();
        $nbFilleuls = $membre->filleuls()->count();
        $commissionsDisponibles = $membre->totalCommissionsDisponibles();
        $commissionsTotales = $membre->totalCommissionsPayees();

        return view('membres.profil', compact(
            'membre', 'segments', 'parrainageActif', 
            'codeParrainage', 'nbFilleuls', 
            'commissionsDisponibles', 'commissionsTotales'
        ));
    }

    /**
     * Mettre à jour le profil du membre
     */
    public function updateProfil(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:membres,email,' . $membre->id,
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'pays' => 'nullable|string|max:100',
            'ville' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'secteur' => 'nullable|string|max:100',
            'sexe' => 'nullable|in:M,F',
            'segment_id' => 'nullable|exists:segments,id',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $membre->update($validated);

        return redirect()->route('membre.profil')->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Liste des cotisations du membre
     */
    public function cotisations(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $adhesions = CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        
        $cotisationIds = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        
        $cotisationsPubliques = Cotisation::where('actif', true)->where('visibilite', 'publique')->orderBy('nom')->paginate(15, ['*'], 'pub');
        $cotisationsPrivees = Cotisation::where('actif', true)->where('visibilite', 'privee')->whereIn('id', $cotisationIds)->orderBy('nom')->paginate(15, ['*'], 'pri');
        
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;

        return view('membres.cotisations', compact(
            'membre', 'adhesions', 'cotisationsPubliques', 
            'cotisationsPrivees', 'paydunyaEnabled', 'pispiEnabled'
        ));
    }

    public function cotisationsPubliques(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $cotisations = Cotisation::where('actif', true)->where('visibilite', 'publique')->orderBy('nom')->paginate(15);
        $adhesions = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;

        return view('membres.cotisations-publiques', compact('cotisations', 'adhesions', 'paydunyaEnabled', 'pispiEnabled'));
    }

    public function cotisationsPrivees(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $adhesions = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)->get()->keyBy('cotisation_id');
        $ids = \App\Models\CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        $cotisations = Cotisation::where('actif', true)->where('visibilite', 'privee')->whereIn('id', $ids)->orderBy('nom')->paginate(15);
        
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;

        return view('membres.cotisations-privees', compact('cotisations', 'adhesions', 'paydunyaEnabled', 'pispiEnabled'));
    }

    public function showCotisation(Request $request, $id)
    {
        $cotisation = Cotisation::with('caisse')->findOrFail($id);
        $membre = Auth::guard('membre')->user();
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->first();
        
        // Paiements : l'admin de la cotisation voit tout, le membre simple ne voit que les siens
        $query = Paiement::where('cotisation_id', $cotisation->id);
        
        $isAdmin = ($cotisation->created_by_membre_id === $membre->id) || ($cotisation->admin_membre_id === $membre->id);
        
        if (!$isAdmin) {
            $query->where(function($q) use ($membre) {
                $q->where('membre_id', $membre->id)->orWhereNull('membre_id');
            });
        }

        $paiements = $query->orderBy('date_paiement', 'desc')->get();
            
        $totalPaye = $paiements->where('statut', 'valide')->sum('montant');
        $canPay = $adhesion && $adhesion->statut === 'accepte';

        // Récupérer les statuts des moyens de paiement
        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;
        
        $paymentMethods = \App\Models\PaymentMethod::where('enabled', true)->orderBy('order')->get();

        return view('membres.cotisation-show', compact(
            'cotisation', 'adhesion', 'paiements', 'totalPaye', 'canPay',
            'paydunyaEnabled', 'pispiEnabled', 'paymentMethods'
        ));
    }

    public function paiements(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        
        $query = $membre->paiements()->with(['cotisation', 'caisse']);
        
        // Liste des années pour le filtre
        $annees = $membre->paiements()
            ->selectRaw('YEAR(date_paiement) as annee')
            ->distinct()
            ->orderBy('annee', 'desc')
            ->pluck('annee');
            
        if ($request->filled('annee')) {
            $query->whereYear('date_paiement', $request->annee);
        }
        
        $paiements = $query->orderBy('date_paiement', 'desc')->paginate(15);
        
        return view('membres.paiements', compact('paiements', 'annees'));
    }

    public function engagements(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $engagements = $membre->engagements()->with('cotisation')->orderBy('periode_fin', 'desc')->paginate(15);
        return view('membres.engagements', compact('engagements'));
    }

    public function showEngagement(Request $request, $id)
    {
        $membre = Auth::guard('membre')->user();
        $engagement = $membre->engagements()->with('cotisation.caisse')->findOrFail($id);
        
        $paiements = Paiement::where('cotisation_id', $engagement->cotisation_id)
            ->where(function($q) use ($membre, $engagement) {
                // Pour un engagement, on regarde s'il y a un lien direct via metadata ou juste le membre/cotisation
                $q->where('membre_id', $membre->id);
            })
            ->orderBy('date_paiement', 'desc')
            ->get();
            
        $montantPaye = $paiements->sum('montant');
        $resteAPayer = max(0, $engagement->montant_du - $montantPaye);

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        $paydunyaEnabled = $paydunyaConfig && $paydunyaConfig->enabled;
        
        $pispiConfig = \App\Models\PiSpiConfiguration::getActive();
        $pispiEnabled = $pispiConfig && $pispiConfig->enabled;
        
        $paymentMethods = \App\Models\PaymentMethod::where('enabled', true)->orderBy('order')->get();

        return view('membres.engagement-show', compact(
            'engagement', 'paiements', 'montantPaye', 'resteAPayer',
            'paydunyaEnabled', 'pispiEnabled', 'paymentMethods'
        ));
    }

    public function remboursements(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $remboursements = $membre->remboursements()->orderBy('created_at', 'desc')->paginate(15);
        return view('membres.remboursements', compact('remboursements'));
    }

    // --- Actions spécialisées (Redirection vers services dédiés si nécessaire)

    public function adhererCotisation(Request $request, Cotisation $cotisation)
    {
        // Logique simplifiée pour le web
        $membre = Auth::guard('membre')->user();
        $statut = $cotisation->isPublique() ? 'accepte' : 'en_attente';
        
        CotisationAdhesion::updateOrCreate(
            ['membre_id' => $membre->id, 'cotisation_id' => $cotisation->id],
            ['statut' => $statut]
        );

        return back()->with('success', $statut === 'accepte' ? 'Adhésion réussie.' : 'Demande d\'adhésion envoyée.');
    }

    /**
     * Callback IPN PayDunya — appelé par PayDunya après confirmation de paiement.
     * Dispatcher vers le service centralisé selon le type de l'opération.
     */
    public function paydunyaCallback(Request $request)
    {
        Log::info('PayDunya IPN callback reçu', $request->all());

        try {
            // PayDunya envoie le token via GET ou dans le body selon la version
            $invoiceToken = $request->input('token') ?? $request->input('data.invoice.token') ?? null;

            if (!$invoiceToken) {
                Log::warning('PayDunya IPN: token manquant dans la requête');
                return response()->json(['ok' => false, 'message' => 'Token manquant'], 400);
            }

            // Vérifier la facture via PayDunya
            $paydunyaService = new \App\Services\PayDunyaService();
            $verification    = $paydunyaService->verifyInvoice($invoiceToken);

            if (!$verification['success']) {
                Log::warning('PayDunya IPN: vérification échec', ['token' => $invoiceToken, 'response' => $verification]);
                return response()->json(['ok' => false], 400);
            }

            $status = $verification['status'] ?? 'unknown';

            if ($status !== 'completed') {
                Log::info('PayDunya IPN: statut non complété', ['status' => $status, 'token' => $invoiceToken]);
                return response()->json(['ok' => true, 'status' => $status]);
            }

            $verificationData = $verification['data'] ?? [];
            $customData       = $verificationData['custom_data'] ?? [];
            $amount           = (float) ($verificationData['total_amount'] ?? 0);

            // Dispatch vers le service
            app(PayDunyaCallbackService::class)->handle($customData, $amount, $invoiceToken);

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('PayDunya IPN: exception', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false], 500);
        }
    }
    
    /**
     * Initier un paiement Pi-SPI pour une cotisation
     */
    public function initierPaiementPiSpi(Request $request, $id)
    {
        $cotisation = Cotisation::findOrFail($id);
        $membre = Auth::guard('membre')->user();
        
        // Vérifier si le membre a un numéro de téléphone valide pour Pi-SPI
        if (!$membre->telephone) {
            return back()->with('error', 'Vous devez renseigner votre numéro de téléphone dans votre profil pour utiliser ce mode de paiement.');
        }

        try {
            $pispiService = new \App\Services\PiSpiService();
            $reference = 'P-PISPI-' . time() . '-' . $membre->id;
            
            // Créer le paiement en attente
            $paiement = Paiement::create([
                'reference' => $reference,
                'cotisation_id' => $cotisation->id,
                'membre_id' => $membre->id,
                'montant' => $cotisation->montant,
                'date_paiement' => now(),
                'statut' => 'en_attente',
                'mode_paiement' => 'pispi',
                'caisse_id' => $cotisation->caisse_id,
                'commentaire' => 'Initiation paiement Pi-SPI : Request to Pay',
            ]);

            $result = $pispiService->initiatePayment([
                'txId' => $reference,
                'phone' => $membre->telephone,
                'amount' => $cotisation->montant,
                'description' => 'Cagnotte ' . $cotisation->nom . ' (Serenity)',
            ]);

            if ($result['success']) {
                return back()->with('success', 'Une demande de paiement a été envoyée sur votre téléphone. Veuillez valider la transaction.');
            }

            // Si échec API, on supprime ou annule le paiement
            $paiement->delete();
            return back()->with('error', 'Erreur Pi-SPI : ' . ($result['message'] ?? 'Impossible d\'initier le paiement.'));

        } catch (\Exception $e) {
            Log::error('Pi-SPI Init Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Initier un paiement Pi-SPI pour un engagement
     */
    public function initierPaiementEngagementPiSpi(Request $request, $id)
    {
        $engagement = Engagement::findOrFail($id);
        $membre = Auth::guard('membre')->user();

        if ($engagement->membre_id !== $membre->id) abort(403);
        if ($engagement->estPaye()) return back()->with('error', 'Cet engagement est déjà réglé.');

        if (!$membre->telephone) {
            return back()->with('error', 'Numéro de téléphone requis pour le paiement Pi-SPI.');
        }

        try {
            $pispiService = new \App\Services\PiSpiService();
            $reference = 'E-PISPI-' . time() . '-' . $engagement->id;
            
            $paiement = Paiement::create([
                'reference' => $reference,
                'cotisation_id' => $engagement->cotisation_id,
                'membre_id' => $membre->id,
                'montant' => $engagement->montant_du,
                'date_paiement' => now(),
                'statut' => 'en_attente',
                'mode_paiement' => 'pispi',
                'caisse_id' => $engagement->cotisation->caisse_id ?? null,
                'metadata' => ['engagement_id' => $engagement->id],
                'commentaire' => 'Paiement engagement via Pi-SPI',
            ]);

            $result = $pispiService->initiatePayment([
                'txId' => $reference,
                'phone' => $membre->telephone,
                'amount' => $engagement->montant_du,
                'description' => 'Engagement ' . ($engagement->cotisation->nom ?? '') . ' (Serenity)',
            ]);

            if ($result['success']) {
                return back()->with('success', 'Demande Pi-SPI envoyée. Validez sur votre mobile.');
            }

            $paiement->delete();
            return back()->with('error', 'Erreur Pi-SPI : ' . ($result['message'] ?? 'Echec initiation.'));

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Initier un paiement PayDunya pour une cotisation
     */
    public function initierPaiementPayDunya(Request $request, $id)
    {
        $cotisation = Cotisation::with('caisse')->findOrFail($id);
        $membre     = Auth::guard('membre')->user();

        // Vérifier que le membre est adhérent accepté
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)
            ->where('cotisation_id', $cotisation->id)
            ->where('statut', 'accepte')
            ->first();

        if (!$adhesion) {
            return back()->with('error', 'Vous devez être membre accepté de cette cotisation pour payer.');
        }

        // Montant : si type_montant libre, le prendre du formulaire
        $montant = null;
        if ($cotisation->type_montant === 'fixe') {
            $montant = (float) $cotisation->montant;
        } else {
            $request->validate(['montant' => 'required|numeric|min:100']);
            $montant = (float) $request->input('montant');
        }

        if (!$montant || $montant <= 0) {
            return back()->with('error', 'Montant invalide.');
        }

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return back()->with('error', 'Le paiement PayDunya n\'est pas disponible.');
        }

        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            $callbackUrl     = url('/membre/paydunya/callback');
            $returnUrl       = url('/membre/cotisations/' . $cotisation->id . '?token={token}');
            $cancelUrl       = url('/membre/cotisations/' . $cotisation->id);

            $result = $paydunyaService->createInvoice([
                'type'          => 'cotisation',
                'membre_id'     => $membre->id,
                'cotisation_id' => $cotisation->id,
                'item_name'     => 'Cagnotte - ' . $cotisation->nom,
                'amount'        => $montant,
                'description'   => 'Paiement cotisation: ' . $cotisation->nom,
                'callback_url'  => $callbackUrl,
                'return_url'    => $returnUrl,
                'cancel_url'    => $cancelUrl,
            ]);

            if ($result['success']) {
                return redirect($result['invoice_url']);
            }

            return back()->with('error', $result['message'] ?? 'Erreur lors de la création du paiement.');

        } catch (\Exception $e) {
            Log::error('PayDunya cotisation init: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Initier un paiement PayDunya pour un engagement
     */
    public function initierPaiementEngagementPayDunya(Request $request, $id)
    {
        $engagement = Engagement::with('cotisation')->findOrFail($id);
        $membre     = Auth::guard('membre')->user();

        if ($engagement->membre_id !== $membre->id) abort(403);
        if ($engagement->estPaye()) return back()->with('error', 'Cet engagement est déjà réglé.');

        $paydunyaConfig = \App\Models\PayDunyaConfiguration::getActive();
        if (!$paydunyaConfig || !$paydunyaConfig->enabled) {
            return back()->with('error', 'Le paiement PayDunya n\'est pas disponible.');
        }

        try {
            $paydunyaService = new \App\Services\PayDunyaService();
            $callbackUrl     = url('/membre/paydunya/callback');
            $returnUrl       = url('/membre/engagements/' . $engagement->id);
            $cancelUrl       = $returnUrl;

            $result = $paydunyaService->createInvoice([
                'type'          => 'cotisation',
                'membre_id'     => $membre->id,
                'cotisation_id' => $engagement->cotisation_id,
                'engagement_id' => $engagement->id,
                'item_name'     => 'Engagement - ' . ($engagement->cotisation->nom ?? ''),
                'amount'        => (float) $engagement->montant_du,
                'description'   => 'Paiement engagement: ' . ($engagement->cotisation->nom ?? ''),
                'callback_url'  => $callbackUrl,
                'return_url'    => $returnUrl,
                'cancel_url'    => $cancelUrl,
            ]);

            if ($result['success']) {
                return redirect($result['invoice_url']);
            }

            return back()->with('error', $result['message'] ?? 'Erreur lors de la création du paiement.');

        } catch (\Exception $e) {
            Log::error('PayDunya engagement init: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}

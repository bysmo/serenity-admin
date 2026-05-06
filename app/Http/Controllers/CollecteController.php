<?php

namespace App\Http\Controllers;

use App\Models\Collecte;
use App\Models\CollecteSession;
use App\Models\Membre;
use App\Models\EpargneEcheance;
use App\Models\NanoCreditEcheance;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Services\OtpService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CollecteController extends Controller
{
    protected $otpService;
    protected $emailService;

    public function __construct(OtpService $otpService, EmailService $emailService)
    {
        $this->otpService = $otpService;
        $this->emailService = $emailService;
    }

    /**
     * Dashboard du collecteur
     */
    public function index()
    {
        $user = auth()->user();
        $session = CollecteSession::where('user_id', $user->id)
            ->where('statut', 'ouvert')
            ->first();

        $account = $user->collectorAccount;
        
        $todayCollections = [];
        if ($session) {
            $todayCollections = Collecte::where('collecte_session_id', $session->id)
                ->with('membre')
                ->latest()
                ->get();
        }

        return view('collecte.index', compact('session', 'account', 'todayCollections'));
    }

    /**
     * Ouvrir une session de collecte
     */
    public function openSession(Request $request)
    {
        $user = auth()->user();
        
        // Vérifier si une session est déjà ouverte
        $existing = CollecteSession::where('user_id', $user->id)
            ->where('statut', 'ouvert')
            ->first();
            
        if ($existing) {
            return redirect()->back()->with('error', 'Une session est déjà ouverte.');
        }

        CollecteSession::create([
            'user_id' => $user->id,
            'date_session' => now()->toDateString(),
            'opened_at' => now(),
            'statut' => 'ouvert',
            'montant_ouverture' => $user->collectorAccount?->solde_actuel ?? 0,
        ]);

        return redirect()->route('collecte.index')->with('success', 'Journée de collecte ouverte avec succès.');
    }

    /**
     * Fermer une session de collecte
     */
    public function closeSession()
    {
        $user = auth()->user();
        $session = CollecteSession::where('user_id', $user->id)
            ->where('statut', 'ouvert')
            ->first();

        if (!$session) {
            return redirect()->back()->with('error', 'Aucune session ouverte.');
        }

        $session->update([
            'closed_at' => now(),
            'statut' => 'ferme',
            'montant_fermeture' => $user->collectorAccount?->solde_actuel ?? 0,
        ]);

        return redirect()->route('collecte.index')->with('success', 'Journée de collecte fermée avec succès.');
    }

    /**
     * Rechercher un membre (API)
     */
    public function searchMembre(Request $request)
    {
        $q = $request->q;
        $membre = Membre::where('numero', $q)
            ->orWhere('telephone', 'like', "%{$q}")
            ->first();

        if (!$membre) {
            return response()->json(['error' => 'Membre non trouvé'], 404);
        }

        return response()->json([
            'id' => $membre->id,
            'numero' => $membre->numero,
            'nom_complet' => $membre->nom_complet,
            'telephone' => $membre->telephone,
            'photo' => $membre->photo_url ?? null,
        ]);
    }

    /**
     * Afficher les détails d'un membre pour collecte
     */
    public function showMembre(Membre $membre)
    {
        // Récupérer les échéances impayées (Tontine) - Uniquement celles passées ou du jour
        $tontineEcheances = EpargneEcheance::whereHas('souscription', function($q) use ($membre) {
                $q->where('membre_id', $membre->id);
            })
            ->where('statut', '!=', 'payee')
            ->where('date_echeance', '<=', now()->endOfDay())
            ->orderBy('date_echeance')
            ->get();

        // Récupérer les échéances impayées (Nano-Crédit) - Uniquement celles passées ou du jour
        $creditEcheances = NanoCreditEcheance::whereHas('nanoCredit', function($q) use ($membre) {
                $q->where('membre_id', $membre->id);
            })
            ->where('statut', '!=', 'payee')
            ->where('date_echeance', '<=', now()->endOfDay())
            ->orderBy('date_echeance')
            ->get();

        return view('collecte.membre_show', compact('membre', 'tontineEcheances', 'creditEcheances'));
    }

    /**
     * Initier une collecte et envoyer OTP
     */
    public function store(Request $request)
    {
        $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'type_collecte' => 'required|in:tontine,nano_credit',
            'echeance_id' => 'required',
            'montant' => 'required|numeric|min:1',
        ]);

        $membre = Membre::findOrFail($request->membre_id);
        
        // Vérifier si une session est ouverte
        $session = CollecteSession::where('user_id', auth()->id())
            ->where('statut', 'ouvert')
            ->first();
            
        if (!$session) {
            return redirect()->back()->with('error', 'Vous devez ouvrir une journée de collecte.');
        }

        // Créer l'enregistrement de collecte en attente
        $collecte = Collecte::create([
            'collecte_session_id' => $session->id,
            'membre_id' => $membre->id,
            'type_collecte' => $request->type_collecte,
            'echeance_id' => $request->echeance_id,
            'montant' => $request->montant,
            'is_confirmed' => false,
        ]);

        // Générer et envoyer OTP
        $otp = $this->otpService->generateAndStore($membre->telephone);
        
        // Envoi par SMS (simulé si pas de passerelle)
        $this->otpService->sendOtp($membre->telephone, $otp);
        
        // Envoi par Mail si disponible
        if ($membre->email) {
            $this->emailService->sendOtpEmail($membre, $otp);
        }

        return view('collecte.otp_verify', compact('collecte', 'membre'));
    }

    /**
     * Confirmer la collecte avec OTP
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'collecte_id' => 'required|exists:collectes,id',
            'otp_code' => 'required|string',
        ]);

        $collecte = Collecte::findOrFail($request->collecte_id);
        $membre = $collecte->membre;

        if (!$this->otpService->verify($membre->telephone, $request->otp_code)) {
            return redirect()->back()->with('error', 'Code OTP invalide ou expiré.');
        }

        try {
            DB::beginTransaction();

            $collecte->update([
                'is_confirmed' => true,
                'confirmed_at' => now(),
                'otp_code' => $request->otp_code,
                'reference_transaction' => 'COLL-' . strtoupper(bin2hex(random_bytes(4))),
            ]);

            // Mettre à jour l'échéance et impacter le compte membre
            if ($collecte->type_collecte === 'tontine') {
                $echeance = EpargneEcheance::with('souscription')->findOrFail($collecte->echeance_id);
                $echeance->update([
                    'statut' => 'payee',
                    'paye_le' => now(),
                ]);
                
                // Mouvement comptable : Crédit compte tontine membre
                $compteMembre = Caisse::find($echeance->souscription->caisse_id);
                if ($compteMembre) {
                    MouvementCaisse::create([
                        'caisse_id' => $compteMembre->id,
                        'type' => 'tontine',
                        'sens' => 'entree',
                        'montant' => $collecte->montant,
                        'date_operation' => now(),
                        'libelle' => "Versement Tontine (via Collecteur) - Réf: {$collecte->reference_transaction}",
                        'reference_type' => 'Collecte',
                        'reference_id' => $collecte->id,
                    ]);

                    // Mettre à jour le solde courant de la souscription
                    $echeance->souscription->increment('solde_courant', (float) $collecte->montant);
                }
            } else {
                $echeance = NanoCreditEcheance::with('nanoCredit')->findOrFail($collecte->echeance_id);
                $echeance->update([
                    'statut' => 'payee',
                    'paye_le' => now(),
                ]);

                // Mouvement comptable : Crédit compte crédit membre (réduction de dette)
                $compteMembre = Caisse::find($echeance->nanoCredit->compte_credit_id);
                if ($compteMembre) {
                    MouvementCaisse::create([
                        'caisse_id' => $compteMembre->id,
                        'type' => 'remboursement',
                        'sens' => 'entree', // Entrée sur compte dette = réduction dette
                        'montant' => $collecte->montant,
                        'date_operation' => now(),
                        'libelle' => "Remboursement Nano-Crédit (via Collecteur) - Réf: {$collecte->reference_transaction}",
                        'reference_type' => 'Collecte',
                        'reference_id' => $collecte->id,
                    ]);
                }
            }

            // Impact comptable : Augmenter le solde du collecteur (Entrée de fonds)
            $collectorAccount = auth()->user()->collectorAccount;
            MouvementCaisse::create([
                'caisse_id' => $collectorAccount->id,
                'type' => 'collecte',
                'sens' => 'entree',
                'montant' => $collecte->montant,
                'date_operation' => now(),
                'libelle' => "Collecte {$collecte->type_collecte} - {$membre->nom_complet}",
                'reference_type' => 'Collecte',
                'reference_id' => $collecte->id,
            ]);

            DB::commit();

            return redirect()->route('collecte.index')->with('success', 'Collecte confirmée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la confirmation : ' . $e->getMessage());
        }
    }

    /**
     * Historique des collectes
     */
    public function history()
    {
        $user = auth()->user();
        $sessions = CollecteSession::where('user_id', $user->id)
            ->withCount(['collectes' => function($q) {
                $q->where('is_confirmed', true);
            }])
            ->orderBy('date_session', 'desc')
            ->paginate(10);

        return view('collecte.history', compact('sessions'));
    }

    /**
     * Effectuer un reversement (Settlement)
     */
    public function settle(Request $request)
    {
        $user = auth()->user();
        $account = $user->collectorAccount;
        $amountToSettle = $account->solde_actuel;

        if ($amountToSettle <= 0) {
            return redirect()->back()->with('error', 'Aucun montant à reverser.');
        }

        try {
            DB::beginTransaction();

            // 1. Débiter le compte du collecteur
            MouvementCaisse::create([
                'caisse_id' => $account->id,
                'type' => 'reversement',
                'sens' => 'sortie',
                'montant' => $amountToSettle,
                'date_operation' => now(),
                'libelle' => "Reversement des collectes sur alias {$account->alias}",
            ]);

            // 2. Créditer le compte global (on suppose un dispatching ou un compte d'attente)
            // Pour simplifier, on crédite le compte Tontine global
            $globalAccount = Caisse::getCaisseTontineCli();
            if ($globalAccount) {
                MouvementCaisse::create([
                    'caisse_id' => $globalAccount->id,
                    'type' => 'reversement',
                    'sens' => 'entree',
                    'montant' => $amountToSettle,
                    'date_operation' => now(),
                    'libelle' => "Réception reversement collecteur {$user->name}",
                ]);
            }

            DB::commit();

            return redirect()->route('collecte.index')->with('success', "Reversement de {$amountToSettle} XOF effectué avec succès sur votre alias {$account->alias}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors du reversement : ' . $e->getMessage());
        }
    }
}

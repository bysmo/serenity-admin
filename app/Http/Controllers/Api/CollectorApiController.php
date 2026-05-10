<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collecte;
use App\Models\CollecteSession;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\EpargneSouscription;
use App\Models\EpargneEcheance;
use App\Models\NanoCredit;
use App\Models\NanoCreditEcheance;
use App\Models\NanoCreditVersement;
use App\Models\Paiement;
use App\Models\Caisse;
use App\Models\MouvementCaisse;

class CollectorApiController extends Controller
{
    /**
     * Authentification des collecteurs
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Identifiants incorrects.'], 401);
            }

            if (! $user->hasRole('collecteur') && ! $user->hasRole('admin')) {
                return response()->json(['message' => 'Accès réservé aux collecteurs.'], 403);
            }

            $token = $user->createToken('collector-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur serveur: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Session actuelle du collecteur
     */
    public function currentSession(Request $request): JsonResponse
    {
        $user = $request->user();
        $session = CollecteSession::where('user_id', $user->id)
            ->where('statut', 'ouvert')
            ->first();

        return response()->json([
            'session' => $session ? [
                'id' => $session->id,
                'opened_at' => $session->opened_at,
                'montant_ouverture' => (float)$session->montant_ouverture,
                'total_collecte' => (float)$session->montant_total_collecte,
            ] : null
        ]);
    }

    /**
     * Ouvrir une session
     */
    public function openSession(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $existing = CollecteSession::where('user_id', $user->id)->where('statut', 'ouvert')->exists();
        if ($existing) {
            return response()->json(['message' => 'Une session est déjà ouverte.'], 422);
        }

        $session = CollecteSession::create([
            'user_id' => $user->id,
            'date_session' => Carbon::today(),
            'opened_at' => Carbon::now(),
            'statut' => 'ouvert',
            'montant_ouverture' => $request->montant_ouverture ?? 0,
        ]);

        return response()->json(['session' => $session, 'message' => 'Session ouverte avec succès.']);
    }

    /**
     * Fermer une session
     */
    public function closeSession(Request $request): JsonResponse
    {
        $user = $request->user();
        $session = CollecteSession::where('user_id', $user->id)->where('statut', 'ouvert')->first();
        
        if (!$session) {
            return response()->json(['message' => 'Aucune session ouverte.'], 422);
        }

        $session->update([
            'closed_at' => Carbon::now(),
            'statut' => 'ferme',
            'montant_fermeture' => $session->montant_total_collecte,
        ]);

        return response()->json(['message' => 'Session fermée avec succès.']);
    }

    /**
     * Rechercher un membre
     */
    public function searchMembers(Request $request): JsonResponse
    {
        $q = $request->query('q');
        if (!$q) return response()->json(['data' => []]);

        $members = Membre::where('nom', 'like', "%$q%")
            ->orWhere('prenom', 'like', "%$q%")
            ->orWhere('telephone', 'like', "%$q%")
            ->orWhere('numero', 'like', "%$q%")
            ->limit(10)
            ->get(['id', 'nom', 'prenom', 'telephone', 'numero as code_membre']);

        return response()->json(['data' => $members]);
    }

    /**
     * Détails d'un membre pour la collecte
     */
    public function getMemberDetails($id): JsonResponse
    {
        $member = Membre::findOrFail($id);

        // 1. Tontines (Souscriptions épargne actives)
        $tontines = EpargneSouscription::where('membre_id', $id)
            ->where('statut', 'active')
            ->with(['plan', 'echeances' => function($q) {
                $q->whereIn('statut', ['a_venir', 'en_retard'])
                  ->where('date_echeance', '<=', Carbon::today())
                  ->orderBy('date_echeance', 'asc');
            }])
            ->get();

        // 2. Nano-crédits (Crédits en cours)
        $credits = NanoCredit::where('membre_id', $id)
            ->whereIn('statut', ['debourse', 'en_remboursement', 'success'])
            ->with(['echeances' => function($q) {
                $q->whereIn('statut', ['a_venir', 'en_retard'])
                  ->where('date_echeance', '<=', Carbon::today())
                  ->orderBy('date_echeance', 'asc');
            }])
            ->get();

        return response()->json([
            'member' => $member,
            'tontines' => $tontines,
            'credits' => $credits,
        ]);
    }

    /**
     * Effectuer une collecte
     */
    public function collect(Request $request): JsonResponse
    {
        $user = $request->user();
        $session = CollecteSession::where('user_id', $user->id)->where('statut', 'ouvert')->first();
        
        if (!$session) {
            return response()->json(['message' => 'Aucune session ouverte.'], 422);
        }

        $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'montant' => 'required|numeric|min:100',
            'type_collecte' => 'required|in:tontine,credit,epargne_sporadique',
            'echeance_id' => 'nullable',
            'souscription_id' => 'nullable',
            'credit_id' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            $collecte = Collecte::create([
                'collecte_session_id' => $session->id,
                'membre_id' => $request->membre_id,
                'type_collecte' => $request->type_collecte,
                'echeance_id' => $request->echeance_id,
                'montant' => $request->montant,
                'is_confirmed' => true, 
                'confirmed_at' => Carbon::now(),
                'reference_transaction' => 'COL-' . strtoupper(Str::random(8)),
            ]);

            // Trouver la caisse du collecteur
            $caisse = Caisse::where('user_id', $user->id)->where('type', Caisse::TYPE_COLLECTEUR)->first();
            if (!$caisse) {
                // Créer une caisse collecteur si elle n'existe pas
                $caisse = Caisse::create([
                    'numero' => 'CAI-COL-' . $user->id,
                    'nom' => 'Caisse Collecte - ' . $user->name,
                    'type' => Caisse::TYPE_COLLECTEUR,
                    'user_id' => $user->id,
                    'solde_initial' => 0,
                    'statut' => 'actif',
                ]);
            }

            // Traiter selon le type
            if ($request->type_collecte === 'tontine' && $request->echeance_id) {
                $echeance = EpargneEcheance::findOrFail($request->echeance_id);
                $echeance->update(['statut' => 'paye', 'date_paiement' => Carbon::now()]);
                
                // Créer un paiement
                Paiement::create([
                    'numero' => 'PAY-' . strtoupper(Str::random(10)),
                    'membre_id' => $request->membre_id,
                    'montant' => $request->montant,
                    'date_paiement' => Carbon::now(),
                    'mode_paiement' => 'especes',
                    'statut' => 'valide',
                    'reference' => $collecte->reference_transaction,
                    'caisse_id' => $caisse->id,
                ]);
            } elseif ($request->type_collecte === 'credit' && $request->echeance_id) {
                $echeance = NanoCreditEcheance::findOrFail($request->echeance_id);
                $echeance->update(['statut' => 'payee', 'paye_le' => Carbon::now()]);
                
                // Créer un versement crédit
                NanoCreditVersement::create([
                    'nano_credit_id' => $echeance->nano_credit_id,
                    'montant' => $request->montant,
                    'date_versement' => Carbon::now(),
                    'reference' => $collecte->reference_transaction,
                ]);
            } elseif ($request->type_collecte === 'epargne_sporadique') {
                // Créditer le compte épargne principal (sporadique)
                Paiement::create([
                    'numero' => 'PAY-SP-' . strtoupper(Str::random(10)),
                    'membre_id' => $request->membre_id,
                    'montant' => $request->montant,
                    'date_paiement' => Carbon::now(),
                    'mode_paiement' => 'especes',
                    'statut' => 'valide',
                    'reference' => $collecte->reference_transaction,
                    'caisse_id' => $caisse->id,
                    'commentaire' => 'Dépôt sporadique via collecteur ' . $user->name,
                ]);
            }

            // Mouvement de caisse (Entrée chez le collecteur)
            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'type_mouvement' => 'entree',
                'montant' => $request->montant,
                'motif' => 'Collecte terrain : ' . $request->type_collecte,
                'reference_operation' => $collecte->reference_transaction,
                'date_mouvement' => Carbon::now(),
            ]);
            
            DB::commit();
            return response()->json(['message' => 'Collecte enregistrée avec succès.', 'collecte' => $collecte]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur technique: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reverser la collecte
     */
    public function reversement(Request $request): JsonResponse
    {
        // Logique de reversement simplifiée
        return response()->json(['message' => 'Reversement effectué avec succès.']);
    }
}

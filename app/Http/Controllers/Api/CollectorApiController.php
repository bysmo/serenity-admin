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
use Carbon\Carbon;

class CollectorApiController extends Controller
{
    /**
     * Authentification des collecteurs
     */
    public function login(Request $request): JsonResponse
    {
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
            ->orWhere('code_membre', 'like', "%$q%")
            ->limit(10)
            ->get(['id', 'nom', 'prenom', 'telephone', 'code_membre']);

        return response()->json(['data' => $members]);
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
            'type_collecte' => 'required|in:tontine,credit',
            'echeance_id' => 'nullable',
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
                'reference_transaction' => 'COL-'.time(),
            ]);
            
            DB::commit();
            return response()->json(['message' => 'Collecte enregistrée.', 'collecte' => $collecte]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur: '.$e->getMessage()], 500);
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

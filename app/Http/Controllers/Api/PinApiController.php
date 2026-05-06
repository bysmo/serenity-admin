<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membre;
use App\Services\PinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints de gestion du code PIN de sécurité.
 *
 * Cycle de vie complet :
 *
 *  1. GET  /pin/status         → Statut actuel (PIN défini ? activé ? mode ? session active ?)
 *  2. POST /pin/setup          → Définir le PIN pour la première fois
 *  3. POST /pin/enable         → Activer la protection PIN (choisir mode A "each_time" ou B "session")
 *  4. POST /pin/disable        → Désactiver la protection PIN (PIN requis pour confirmer)
 *  5. POST /pin/mode           → Changer de mode (A ↔ B) sans désactiver (PIN requis)
 *  6. POST /pin/change         → Modifier le code PIN (ancien PIN requis)
 *  7. POST /pin/verify         → Vérifier le PIN manuellement (mode B : ouvre une session 5 min)
 */
class PinApiController extends Controller
{
    public function __construct(protected PinService $pinService) {}

    // ─── Statut ───────────────────────────────────────────────────────────────

    /**
     * Retourne l'état complet du PIN pour le membre connecté.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();

        $sessionActive = $membre->isPinEnabled() && $membre->isPinModeSession()
            ? $this->pinService->hasActiveSession($membre->id)
            : null;

        return response()->json([
            'has_pin'           => $membre->hasPin(),
            'pin_enabled'       => $membre->isPinEnabled(),
            'pin_mode'          => $membre->pin_mode ?? 'each_time',
            'pin_mode_label'    => $membre->isPinModeSession()
                ? 'Session 5 minutes (option B)'
                : 'À chaque opération (option A)',
            'pin_locked'        => $membre->isPinLocked(),
            'pin_locked_until'  => $membre->isPinLocked()
                ? $membre->pin_locked_until?->toIso8601String()
                : null,
            'pin_attempts'      => $membre->pin_attempts ?? 0,
            'max_attempts'      => Membre::PIN_MAX_ATTEMPTS,
            'lock_minutes'      => Membre::PIN_LOCK_MINUTES,
            'session_active'    => $sessionActive,         // null si mode A ou PIN désactivé
            'session_duration'  => PinService::SESSION_DURATION, // minutes (mode B)
        ]);
    }

    // ─── Définir le PIN ───────────────────────────────────────────────────────

    /**
     * Définir le PIN pour la première fois.
     * Ne l'active pas automatiquement : l'utilisateur l'activera via /pin/enable.
     */
    public function setup(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();

        Log::info('Pin setup attempt', [
            'membre_id' => $membre->id,
            'has_pin' => $membre->hasPin(),
            'request_data' => $request->all(),
        ]);

        if ($membre->hasPin()) {
            return response()->json([
                'message' => 'Vous avez déjà un code PIN. Utilisez /pin/change pour le modifier.',
            ], 422);
        }

        $request->validate([
            'pin'              => 'required|digits:' . PinService::PIN_LENGTH,
            'pin_confirmation' => 'nullable|digits:' . PinService::PIN_LENGTH . '|same:pin',
        ], [
            'pin.required'              => 'Le code PIN est obligatoire.',
            'pin.digits'                => 'Le code PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
            'pin_confirmation.same'     => 'Les deux codes PIN ne correspondent pas.',
        ]);

        $membre->setPin($request->input('pin'));

        return response()->json([
            'message'      => 'Code PIN défini avec succès. Activez-le via /pin/enable quand vous le souhaitez.',
            'has_pin'      => true,
            'pin_enabled'  => false,
        ]);
    }

    // ─── Activer la protection PIN ────────────────────────────────────────────

    /**
     * Activer la protection PIN et choisir le mode.
     *
     * Body :
     *   - pin         : string (requis, pour confirmer l'identité)
     *   - mode        : 'each_time' (option A) | 'session' (option B)
     */
    public function enable(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();
        Log::info('Pin enable attempt', ['membre_id' => $membre->id, 'mode' => $request->input('mode')]);

        if (! $membre->hasPin()) {
            return response()->json([
                'message'           => 'Définissez d\'abord votre code PIN via /pin/setup.',
                'require_pin_setup' => true,
            ], 422);
        }

        if ($membre->isPinEnabled()) {
            return response()->json([
                'message'     => 'Le PIN est déjà activé. Utilisez /pin/mode pour changer de mode.',
                'pin_enabled' => true,
                'pin_mode'    => $membre->pin_mode,
            ], 422);
        }

        if ($membre->isPinLocked()) {
            return response()->json([
                'message'    => 'Compte PIN temporairement verrouillé. Réessayez plus tard.',
                'pin_locked' => true,
            ], 403);
        }

        $request->validate([
            'pin'  => 'required|digits:' . PinService::PIN_LENGTH,
            'mode' => 'required|in:each_time,session',
        ], [
            'pin.required'  => 'Votre code PIN est requis pour activer la protection.',
            'pin.digits'    => 'Le code PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
            'mode.required' => 'Choisissez un mode : "each_time" (option A) ou "session" (option B).',
            'mode.in'       => 'Mode invalide. Valeurs acceptées : "each_time" ou "session".',
        ]);

        if (! $membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return response()->json([
                    'message'    => 'Trop de tentatives. Compte PIN verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.',
                    'pin_locked' => true,
                ], 403);
            }
            $remaining = Membre::PIN_MAX_ATTEMPTS - ($membre->pin_attempts ?? 0);
            return response()->json([
                'message'   => "Code PIN incorrect. {$remaining} tentative(s) restante(s).",
                'remaining' => $remaining,
            ], 422);
        }

        $membre->enablePin($request->input('mode'));

        $modeLabel = $request->input('mode') === 'session'
            ? 'session 5 minutes (option B)'
            : 'à chaque opération (option A)';

        return response()->json([
            'message'     => "Protection PIN activée en mode : {$modeLabel}.",
            'pin_enabled' => true,
            'pin_mode'    => $membre->fresh()->pin_mode,
        ]);
    }

    // ─── Désactiver la protection PIN ────────────────────────────────────────

    /**
     * Désactiver la protection PIN (PIN requis pour confirmer).
     * Les opérations critiques seront à nouveau libres.
     */
    public function disable(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();

        if (! $membre->isPinEnabled()) {
            return response()->json([
                'message'     => 'La protection PIN est déjà désactivée.',
                'pin_enabled' => false,
            ], 422);
        }

        if ($membre->isPinLocked()) {
            return response()->json([
                'message'    => 'Compte PIN temporairement verrouillé. Réessayez plus tard.',
                'pin_locked' => true,
            ], 403);
        }

        $request->validate([
            'pin' => 'required|digits:' . PinService::PIN_LENGTH,
        ], [
            'pin.required' => 'Votre code PIN est requis pour désactiver la protection.',
            'pin.digits'   => 'Le code PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
        ]);

        if (! $membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return response()->json([
                    'message'    => 'Trop de tentatives. Compte verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.',
                    'pin_locked' => true,
                ], 403);
            }
            $remaining = Membre::PIN_MAX_ATTEMPTS - ($membre->pin_attempts ?? 0);
            return response()->json([
                'message'   => "Code PIN incorrect. {$remaining} tentative(s) restante(s).",
                'remaining' => $remaining,
            ], 422);
        }

        // Fermer la session PIN si mode B était actif
        $this->pinService->closeSession($membre->id);
        $membre->disablePin();

        return response()->json([
            'message'     => 'Protection PIN désactivée. Les opérations critiques sont à nouveau libres.',
            'pin_enabled' => false,
        ]);
    }

    // ─── Changer de mode ──────────────────────────────────────────────────────

    /**
     * Changer le mode PIN (A ↔ B) sans désactiver.
     *
     * Body :
     *   - pin  : string (confirmation)
     *   - mode : 'each_time' | 'session'
     */
    public function changeMode(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();

        if (! $membre->isPinEnabled()) {
            return response()->json([
                'message' => 'La protection PIN n\'est pas activée. Activez-la d\'abord via /pin/enable.',
            ], 422);
        }

        if ($membre->isPinLocked()) {
            return response()->json([
                'message'    => 'Compte PIN temporairement verrouillé. Réessayez plus tard.',
                'pin_locked' => true,
            ], 403);
        }

        $request->validate([
            'pin'  => 'required|digits:' . PinService::PIN_LENGTH,
            'mode' => 'required|in:each_time,session',
        ], [
            'pin.digits' => 'Le code PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
            'mode.in'    => 'Mode invalide. Valeurs acceptées : "each_time" ou "session".',
        ]);

        if ((($membre->pin_mode ?? 'each_time') === $request->input('mode'))) {
            return response()->json([
                'message'  => 'Vous utilisez déjà ce mode.',
                'pin_mode' => $membre->pin_mode,
            ], 422);
        }

        if (! $membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return response()->json([
                    'message'    => 'Trop de tentatives. Compte verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.',
                    'pin_locked' => true,
                ], 403);
            }
            $remaining = Membre::PIN_MAX_ATTEMPTS - ($membre->pin_attempts ?? 0);
            return response()->json([
                'message'   => "Code PIN incorrect. {$remaining} tentative(s) restante(s).",
                'remaining' => $remaining,
            ], 422);
        }

        // Si on passe du mode B au mode A, fermer la session en cours
        if ($request->input('mode') === 'each_time') {
            $this->pinService->closeSession($membre->id);
        }

        $membre->setPinMode($request->input('mode'));

        $modeLabel = $request->input('mode') === 'session'
            ? 'session 5 minutes (option B)'
            : 'à chaque opération (option A)';

        return response()->json([
            'message'  => "Mode PIN changé : {$modeLabel}.",
            'pin_mode' => $membre->fresh()->pin_mode,
        ]);
    }

    // ─── Modifier le code PIN ─────────────────────────────────────────────────

    /**
     * Modifier le code PIN (l'ancien est requis).
     */
    public function change(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();

        if (! $membre->hasPin()) {
            return response()->json([
                'message'           => 'Définissez d\'abord votre PIN via /pin/setup.',
                'require_pin_setup' => true,
            ], 422);
        }

        if ($membre->isPinLocked()) {
            return response()->json([
                'message'    => 'Compte PIN temporairement verrouillé. Réessayez plus tard.',
                'pin_locked' => true,
            ], 403);
        }

        $request->validate([
            'old_pin'          => 'required|digits:' . PinService::PIN_LENGTH,
            'pin'              => 'required|digits:' . PinService::PIN_LENGTH . '|different:old_pin',
            'pin_confirmation' => 'nullable|digits:' . PinService::PIN_LENGTH . '|same:pin',
        ], [
            'pin.digits'            => 'Le nouveau PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
            'old_pin.digits'        => 'L\'ancien PIN doit comporter exactement ' . PinService::PIN_LENGTH . ' chiffres.',
            'pin.different'         => 'Le nouveau PIN doit être différent de l\'ancien.',
            'pin_confirmation.same' => 'Les deux nouveaux codes PIN ne correspondent pas.',
        ]);

        if (! $membre->verifyPin($request->input('old_pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return response()->json([
                    'message'    => 'Trop de tentatives. Compte verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.',
                    'pin_locked' => true,
                ], 403);
            }
            $remaining = Membre::PIN_MAX_ATTEMPTS - ($membre->pin_attempts ?? 0);
            return response()->json([
                'message'   => "Ancien code PIN incorrect. {$remaining} tentative(s) restante(s).",
                'remaining' => $remaining,
            ], 422);
        }

        // Invalider la session PIN en cours si mode B
        $this->pinService->closeSession($membre->id);
        $membre->setPin($request->input('pin'));

        return response()->json([
            'message' => 'Code PIN modifié avec succès.',
        ]);
    }

    // ─── Vérifier le PIN ──────────────────────────────────────────────────────

    /**
     * Vérifier le PIN.
     *
     * Mode A : simple vérification (réponse OK/KO).
     * Mode B : si correct, ouvre une session de SESSION_DURATION minutes côté serveur.
     *          Les opérations critiques suivantes passent sans re-saisir le PIN.
     */
    public function verify(Request $request): JsonResponse
    {
        /** @var Membre $membre */
        $membre = $request->user();
        Log::info('Pin verify attempt', ['membre_id' => $membre->id]);

        if (! $membre->hasPin()) {
            return response()->json([
                'message'           => 'Vous n\'avez pas encore de code PIN.',
                'require_pin_setup' => true,
            ], 403);
        }

        if ($membre->isPinLocked()) {
            return response()->json([
                'message'    => 'Compte PIN temporairement verrouillé. Réessayez plus tard.',
                'pin_locked' => true,
            ], 403);
        }

        $request->validate([
            'pin' => 'required|digits:' . PinService::PIN_LENGTH,
        ]);

        if (! $membre->verifyPin($request->input('pin'))) {
            $membre->refresh();
            if ($membre->isPinLocked()) {
                return response()->json([
                    'message'    => 'Code PIN incorrect. Compte verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.',
                    'pin_locked' => true,
                    'valid'      => false,
                ], 403);
            }
            $remaining = Membre::PIN_MAX_ATTEMPTS - ($membre->pin_attempts ?? 0);
            return response()->json([
                'message'   => "Code PIN incorrect. {$remaining} tentative(s) restante(s).",
                'valid'     => false,
                'remaining' => $remaining,
            ], 422);
        }

        // PIN correct
        $response = [
            'message'  => 'Code PIN vérifié avec succès.',
            'valid'    => true,
            'pin_mode' => $membre->pin_mode ?? 'each_time',
        ];

        // Mode B : ouvrir une session serveur de 5 minutes
        if ($membre->isPinEnabled() && $membre->isPinModeSession()) {
            $this->pinService->openSession($membre->id);
            $expiresAt = now()->addMinutes(PinService::SESSION_DURATION);
            $response['session_opened']  = true;
            $response['session_expires_at'] = $expiresAt->toIso8601String();
            $response['session_duration_minutes'] = PinService::SESSION_DURATION;
            $response['message'] = 'PIN validé. Session ouverte pour ' . PinService::SESSION_DURATION . ' minutes.';
        }

        return response()->json($response);
    }
}

<?php

namespace App\Services;

use App\Models\Membre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion du code PIN de sécurité.
 *
 * Le PIN est OPTIONNEL. L'utilisateur choisit :
 *   - De ne pas activer le PIN → les opérations critiques passent librement.
 *   - D'activer le PIN en mode A (each_time) → PIN demandé dans chaque requête critique.
 *   - D'activer le PIN en mode B (session) → PIN validé une fois via /pin/verify,
 *     puis une session de 5 minutes est ouverte (cache serveur). Les opérations
 *     critiques suivantes passent sans re-saisir le PIN jusqu'à expiration.
 */
class PinService
{
    public const PIN_LENGTH       = 4;
    public const SESSION_DURATION = 5; // minutes (mode B)

    // ─── Clé de cache pour la session PIN mode B ──────────────────────────────
    
    /**
     * Vérifie si le membre a activé la protection PIN.
     */
    public function isPinActivated(Membre $membre): bool
    {
        return (bool) $membre->pin_enabled;
    }

    public function sessionCacheKey(int $membreId): string
    {
        return "pin_session_membre_{$membreId}";
    }

    // ─── Vérification pour les opérations critiques ───────────────────────────

    /**
     * Vérifie si le membre peut exécuter une opération critique.
     *
     * Logique :
     *  1. PIN désactivé → OK (null)
     *  2. PIN activé + mode A → vérifie "pin" dans le corps de la requête
     *  3. PIN activé + mode B → vérifie si une session PIN active existe dans le cache
     *
     * Retourne null si l'accès est autorisé, ou une JsonResponse 403 sinon.
     */
    public function requirePin(Request $request, Membre $membre): ?JsonResponse
    {
        // 1. PIN non activé → aucune contrainte
        if (! $membre->isPinEnabled()) {
            return null;
        }

        // PIN activé mais pas encore défini (ne devrait pas arriver, mais sécurité)
        if (! $membre->hasPin()) {
            return response()->json([
                'message'           => 'Vous avez activé le PIN mais ne l\'avez pas encore défini.',
                'require_pin_setup' => true,
            ], 403);
        }

        // PIN verrouillé suite aux tentatives échouées
        if ($membre->isPinLocked()) {
            $unlockAt = $membre->pin_locked_until->diffForHumans();
            return response()->json([
                'message'    => "Trop de tentatives incorrectes. Réessayez {$unlockAt}.",
                'pin_locked' => true,
            ], 403);
        }

        // 2. Mode A (each_time) → PIN dans le corps de la requête
        if (! $membre->isPinModeSession()) {
            return $this->verifyPinFromRequest($request, $membre);
        }

        // 3. Mode B (session) → session active dans le cache ?
        if (Cache::has($this->sessionCacheKey($membre->id))) {
            // Rafraîchir la durée de la session à chaque utilisation (sliding window)
            Cache::put($this->sessionCacheKey($membre->id), true, now()->addMinutes(self::SESSION_DURATION));
            return null; // Session valide → accès autorisé
        }

        // Mode B mais pas de session active → demander le PIN
        return response()->json([
            'message'         => 'Votre session PIN a expiré. Veuillez re-valider votre PIN.',
            'require_pin_verify' => true,
            'pin_mode'        => 'session',
        ], 403);
    }

    /**
     * Ouvre une session PIN (mode B) valable SESSION_DURATION minutes.
     * Appelé depuis PinApiController::verify() en mode B.
     */
    public function openSession(int $membreId): void
    {
        Cache::put($this->sessionCacheKey($membreId), true, now()->addMinutes(self::SESSION_DURATION));
    }

    /**
     * Ferme la session PIN du membre (déconnexion, désactivation PIN, etc.).
     */
    public function closeSession(int $membreId): void
    {
        Cache::forget($this->sessionCacheKey($membreId));
    }

    /**
     * Indique si une session PIN est active pour le membre (mode B).
     */
    public function hasActiveSession(int $membreId): bool
    {
        return Cache::has($this->sessionCacheKey($membreId));
    }

    // ─── Helpers internes ─────────────────────────────────────────────────────

    /**
     * Vérifie le champ "pin" dans le corps de la requête (mode A).
     */
    private function verifyPinFromRequest(Request $request, Membre $membre): ?JsonResponse
    {
        $pin = $request->input('pin');

        if ($pin === null || $pin === '') {
            return response()->json([
                'message'     => 'Code PIN requis pour cette opération.',
                'require_pin' => true,
                'pin_mode'    => 'each_time',
            ], 403);
        }

        if (! preg_match('/^\d{' . self::PIN_LENGTH . '}$/', (string) $pin)) {
            return response()->json([
                'message' => 'Le code PIN doit comporter exactement ' . self::PIN_LENGTH . ' chiffres.',
            ], 422);
        }

        if (! $membre->verifyPin((string) $pin)) {
            $membre->refresh();

            if ($membre->isPinLocked()) {
                return response()->json([
                    'message'    => 'Code PIN incorrect. Compte temporairement verrouillé pendant ' . Membre::PIN_LOCK_MINUTES . ' minutes.',
                    'pin_locked' => true,
                ], 403);
            }

            $remaining = Membre::PIN_MAX_ATTEMPTS - ($membre->pin_attempts ?? 0);
            return response()->json([
                'message'   => "Code PIN incorrect. {$remaining} tentative(s) restante(s).",
                'remaining' => $remaining,
            ], 403);
        }

        return null; // PIN correct
    }

    /**
     * Valide le format du PIN (4 chiffres uniquement).
     */
    public function isValidFormat(string $pin): bool
    {
        return (bool) preg_match('/^\d{' . self::PIN_LENGTH . '}$/', $pin);
    }
}

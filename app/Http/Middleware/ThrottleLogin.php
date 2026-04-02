<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLogin
{
    /**
     * Nombre maximum de tentatives avant blocage.
     */
    protected int $maxAttempts = 5;

    /**
     * Durée de blocage en minutes.
     */
    protected int $decayMinutes = 15;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = 'web'): Response
    {
        $key = $this->throttleKey($request, $guard);

        // Vérifier si l'IP est bloquée
        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            // Logger la tentative bloquée
            \Illuminate\Support\Facades\Log::warning('Tentative de connexion bloquée - Rate limit atteint', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'email' => $request->input('email'),
                'guard' => $guard,
                'remaining_seconds' => $seconds,
            ]);

            return back()->withInput($request->only('email', 'telephone'))
                ->withErrors([
                    'email' => "Trop de tentatives de connexion. Veuillez réessayer dans {$minutes} minute(s).",
                ]);
        }

        $response = $next($request);

        // Si la connexion a échoué, incrémenter le compteur
        if ($this->isFailedLogin($response)) {
            RateLimiter::hit($key, $this->decayMinutes * 60);

            $remaining = $this->maxAttempts - RateLimiter::attempts($key);

            if ($remaining > 0) {
                // Logger l'échec
                \Illuminate\Support\Facades\Log::info('Échec de connexion', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'email' => $request->input('email'),
                    'telephone' => $request->input('telephone'),
                    'guard' => $guard,
                    'remaining_attempts' => $remaining,
                ]);
            }
        }

        // Si la connexion a réussi, effacer le compteur
        if ($this->isSuccessfulLogin($response)) {
            RateLimiter::clear($key);

            // Logger la connexion réussie
            \Illuminate\Support\Facades\Log::info('Connexion réussie', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'guard' => $guard,
            ]);
        }

        return $response;
    }

    /**
     * Générer la clé de rate limiting.
     */
    protected function throttleKey(Request $request, string $guard): string
    {
        // Combiner l'IP et l'identifiant (email ou téléphone) pour un blocage plus précis
        $identifier = $request->input('email') ?? $request->input('telephone') ?? '';
        return "login:{$guard}:" . mb_strtolower($identifier) . '|' . $request->ip();
    }

    /**
     * Vérifier si la connexion a échoué.
     */
    protected function isFailedLogin(Response $response): bool
    {
        // La réponse est une redirection avec des erreurs de session
        return $response->isRedirect() && session()->has('error');
    }

    /**
     * Vérifier si la connexion a réussi.
     */
    protected function isSuccessfulLogin(Response $response): bool
    {
        // La réponse est une redirection avec un message de succès
        return $response->isRedirect() && session()->has('success');
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleOtp
{
    /**
     * Nombre maximum de tentatives OTP avant blocage.
     */
    protected int $maxAttempts = 5;

    /**
     * Durée de blocage en minutes.
     */
    protected int $decayMinutes = 30;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->throttleKey($request);

        // Vérifier si l'IP est bloquée
        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            // Logger la tentative bloquée
            \Illuminate\Support\Facades\Log::warning('Tentative OTP bloquée - Rate limit atteint', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'phone' => $request->session()->get('membre_otp_phone'),
                'remaining_seconds' => $seconds,
            ]);

            return back()->withErrors([
                'otp' => "Trop de tentatives. Veuillez demander un nouveau code dans {$minutes} minute(s).",
            ]);
        }

        $response = $next($request);

        // Si la vérification a échoué, incrémenter le compteur
        if ($this->isFailedOtp($response)) {
            RateLimiter::hit($key, $this->decayMinutes * 60);

            $remaining = $this->maxAttempts - RateLimiter::attempts($key);

            // Logger l'échec
            \Illuminate\Support\Facades\Log::warning('Échec de vérification OTP', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'phone' => $request->session()->get('membre_otp_phone'),
                'remaining_attempts' => $remaining,
            ]);
        }

        // Si la vérification a réussi, effacer le compteur
        if ($this->isSuccessfulOtp($response)) {
            RateLimiter::clear($key);

            // Logger la réussite
            \Illuminate\Support\Facades\Log::info('Vérification OTP réussie', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }

    /**
     * Générer la clé de rate limiting.
     */
    protected function throttleKey(Request $request): string
    {
        $phone = $request->session()->get('membre_otp_phone', '');
        return "otp:{$phone}|" . $request->ip();
    }

    /**
     * Vérifier si la vérification a échoué.
     */
    protected function isFailedOtp(Response $response): bool
    {
        return $response->isRedirect() && session()->has('error');
    }

    /**
     * Vérifier si la vérification a réussi.
     */
    protected function isSuccessfulOtp(Response $response): bool
    {
        return $response->isRedirect() && session()->has('success');
    }
}
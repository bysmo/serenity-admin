<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $installed = file_exists(storage_path('installed'));

        // Si l'application n'est pas installée, utiliser le driver 'file' pour les sessions
        // car la table 'sessions' n'existe pas encore en base de données
        if (!$installed) {
            config(['session.driver' => 'file']);
        }

        // Si l'application n'est pas installée, rediriger vers l'installation (sauf pour les routes d'installation elles-mêmes)
        if (!$installed && !$request->is('install*') && !$request->is('admin/login') && !$request->is('membre/login') && !$request->is('/')) {
            return redirect()->route('install.index');
        }

        // BLOQUER l'accès aux routes d'installation si l'application est déjà installée
        // C'est une mesure de sécurité critique pour empêcher la réinstallation
        if ($installed && $request->is('install*')) {
            // Logger la tentative d'accès suspecte
            \Illuminate\Support\Facades\Log::warning('Tentative d\'accès à l\'installation alors que l\'application est déjà installée', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);

            return redirect()->route('admin.login')->with('error', 'L\'application est déjà installée.');
        }

        return $next($request);
    }
}
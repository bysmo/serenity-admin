<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Faire confiance à tous les proxys (nécessaire pour les environnements derrière un Load Balancer ou Reverse Proxy)
        $middleware->trustProxies(at: '*');

        // Vérifier l'installation (doit être en premier, avant le middleware de session)
        $middleware->prependToGroup('web', \App\Http\Middleware\CheckInstallation::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckPasswordExpiration::class);
        
        // Configurer la redirection pour les utilisateurs non authentifiés vers admin.login
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            // Si l'application n'est pas installée, rediriger vers l'installation
            if (!file_exists(storage_path('installed'))) {
                return route('install.index');
            }

            // Détecter si on est sur l'espace membre
            if ($request->is('membre/*') || $request->is('membre')) {
                return route('membre.login');
            }

            return route('admin.login');
        });
        
        // Enregistrer le middleware de permissions
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'password.expiration' => \App\Http\Middleware\CheckPasswordExpiration::class,
        ]);

        // Gérer la redirection des utilisateurs déjà connectés (RedirectIfAuthenticated)
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if (auth()->guard('membre')->check()) {
                return route('membre.dashboard');
            }
            return route('dashboard'); // Admin dashboard (default web guard)
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

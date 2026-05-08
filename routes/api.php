<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MembreAuthApiController;
use App\Http\Controllers\Api\MembreApiController;
use App\Http\Controllers\Api\GarantApiController;
use App\Http\Controllers\Api\PinApiController;

Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Serenity API is reachable',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});

Route::prefix('membre')->group(function () {
    // Auth (sans token)
    Route::post('login', [MembreAuthApiController::class, 'login']);
    Route::post('register', [MembreAuthApiController::class, 'register']);
    Route::post('signup', [MembreAuthApiController::class, 'register']);
    Route::post('verify-otp', [MembreAuthApiController::class, 'verifyOtp']);
    Route::post('resend-otp', [MembreAuthApiController::class, 'resendOtp']);
    Route::get('countries', [MembreAuthApiController::class, 'countryDialCodes']);

    // Routes protégées (token requis)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [MembreAuthApiController::class, 'logout']);
        Route::get('me', [MembreAuthApiController::class, 'me']);

        // ── Code PIN de sécurité ──────────────────────────────────────────────
        Route::get('pin/status', [PinApiController::class, 'status']);
        Route::post('pin/setup', [PinApiController::class, 'setup']);
        Route::post('pin/enable', [PinApiController::class, 'enable']);
        Route::post('pin/disable', [PinApiController::class, 'disable']);
        Route::post('pin/mode', [PinApiController::class, 'changeMode']);
        Route::post('pin/change', [PinApiController::class, 'change']);
        Route::post('pin/verify', [PinApiController::class, 'verify']);


        // Notifications & Tokens
        Route::get('notifications', [MembreApiController::class, 'notifications']);
        Route::post('notifications/read-all', [MembreApiController::class, 'markAllNotificationsRead']);
        Route::post('notifications/{id}/read', [MembreApiController::class, 'markNotificationRead']);
        Route::post('notifications/token', [MembreApiController::class, 'updatePushToken']);

        // Dashboard & Profil
        Route::get('dashboard', [MembreApiController::class, 'dashboard']);
        Route::get('profil', [MembreApiController::class, 'profil']);
        Route::put('profil', [MembreApiController::class, 'updateProfil']);
        Route::get('segments', [MembreApiController::class, 'segments']);
        Route::get('kyc', [MembreApiController::class, 'kyc']);
        Route::post('kyc', [MembreApiController::class, 'kycStore']);

        // Cagnottes (Cotisations)
        Route::get('cotisations/publiques', [MembreApiController::class, 'cotisationsPubliques']);
        Route::get('cotisations/privees', [MembreApiController::class, 'cotisationsPrivees']);
        Route::get('cotisations/rechercher', [MembreApiController::class, 'rechercherCotisation']);
        Route::get('cotisations/rechercher-tags', [MembreApiController::class, 'rechercherCagnottesParTags']);
        Route::post('cotisations/{cotisation}/adherer', [MembreApiController::class, 'adhererCotisation']);
        Route::get('cotisations/{id}', [MembreApiController::class, 'showCotisation']);
        Route::get('cotisations/{cotisation}/chat/messages', [MembreApiController::class, 'cotisationChatMessages']);
        Route::post('cotisations/{cotisation}/chat/send', [MembreApiController::class, 'cotisationChatSend']);
        Route::post('cotisations/{cotisation}/paydunya', [MembreApiController::class, 'cotisationInitierPaiementPaydunya']);
        Route::post('cotisations/{cotisation}/pispi', [MembreApiController::class, 'cotisationInitierPaiementPispi']);

        // Mes cagnottes
        Route::get('mes-cotisations', [MembreApiController::class, 'mesCotisations']);
        Route::post('mes-cotisations', [MembreApiController::class, 'storeMesCotisation']);
        Route::get('mes-cotisations/{cotisation}', [MembreApiController::class, 'showMesCotisation']);
        Route::post('mes-cotisations/{cotisation}/chat-notice', [MembreApiController::class, 'mesCotisationUpdateChatNotice']);

        // Nano Crédits (Emprunteur)
        Route::get('nano-credits', [MembreApiController::class, 'nanoCreditsIndex']);
        Route::get('nano-credits/mes', [MembreApiController::class, 'nanoCreditsMes']);
        Route::post('nano-credits/demander', [MembreApiController::class, 'nanoCreditStoreDemande']);
        Route::get('nano-credits/search-guarantors', [MembreApiController::class, 'nanoCreditSearchGuarantors']);
        Route::get('nano-credits/{id}', [MembreApiController::class, 'nanoCreditShow']);
        Route::post('nano-credits/{id}/update-garants', [MembreApiController::class, 'nanoCreditUpdateGarants']);
        Route::post('nano-credits/{id}/rembourser/paydunya', [MembreApiController::class, 'nanoCreditInitierRemboursementPaydunya']);
        Route::post('nano-credits/{id}/rembourser/pispi', [MembreApiController::class, 'nanoCreditInitierRemboursementPispi']);

        // Espace Garant
        Route::prefix('garant')->group(function () {
            Route::get('stats', [GarantApiController::class, 'stats']);
            Route::get('sollicitations', [GarantApiController::class, 'sollicitations']);
            Route::post('sollicitations/{id}/action', [GarantApiController::class, 'action']);
            Route::post('retirer-gains', [GarantApiController::class, 'retirerGains']);
        });

        // Tontines (Épargne)
        Route::get('epargne/plans', [MembreApiController::class, 'epargnePlans']);
        Route::get('epargne/plans/{planId}', [MembreApiController::class, 'epargnePlanDetail']);
        Route::post('epargne/plans/{planId}/souscrire', [MembreApiController::class, 'epargneStoreSouscription']);
        Route::get('epargne/souscriptions', [MembreApiController::class, 'epargneMesEpargnes']);
        Route::get('epargne/souscriptions/{souscriptionId}', [MembreApiController::class, 'epargneSouscriptionDetail']);
        Route::get('epargne/souscriptions/{id}/echeances', [MembreApiController::class, 'epargneSouscriptionEcheances']);
        Route::post('epargne/souscriptions/{id}/retrait', [MembreApiController::class, 'epargneDemanderRetrait']);
        Route::post('epargne/echeances/{echeanceId}/paydunya', [MembreApiController::class, 'epargneInitierPaiementEcheance']);
        Route::post('epargne/echeances/{echeanceId}/pispi', [MembreApiController::class, 'epargneInitierPaiementPispi']);

        // Autres
        Route::get('paiements', [MembreApiController::class, 'paiements']);
        Route::get('remboursements', [MembreApiController::class, 'remboursements']);
        Route::get('annonces', [MembreApiController::class, 'annonces']);

        // ── Comptes Externes ──────────────────────────────────────────────────
        Route::prefix('comptes-externes')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Api\CompteExterneApiController::class, 'index']);
            Route::post('/',             [\App\Http\Controllers\Api\CompteExterneApiController::class, 'store']);
            Route::get('{id}',           [\App\Http\Controllers\Api\CompteExterneApiController::class, 'show']);
            Route::put('{id}',           [\App\Http\Controllers\Api\CompteExterneApiController::class, 'update']);
            Route::post('{id}/default',  [\App\Http\Controllers\Api\CompteExterneApiController::class, 'setDefault']);
            Route::delete('{id}',        [\App\Http\Controllers\Api\CompteExterneApiController::class, 'destroy']);
        });
    });
});


// ── Espace Collecteur ────────────────────────────────────────────────────
Route::prefix('collector')->group(function () {
    Route::post('login', [\App\Http\Controllers\Api\CollectorApiController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('session/current', [\App\Http\Controllers\Api\CollectorApiController::class, 'currentSession']);
        Route::post('session/open', [\App\Http\Controllers\Api\CollectorApiController::class, 'openSession']);
        Route::post('session/close', [\App\Http\Controllers\Api\CollectorApiController::class, 'closeSession']);
        Route::get('members/search', [\App\Http\Controllers\Api\CollectorApiController::class, 'searchMembers']);
        Route::post('collect', [\App\Http\Controllers\Api\CollectorApiController::class, 'collect']);
        Route::post('reversement', [\App\Http\Controllers\Api\CollectorApiController::class, 'reversement']);
    });
});

// ── Webhooks (Publics) ───────────────────────────────────────────────────
Route::post('pispi/webhook', [\App\Http\Controllers\PiSpiWebhookController::class, 'handle'])->name('api.pispi.webhook');


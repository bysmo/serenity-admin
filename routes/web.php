<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaisseController;

// Routes d'installation (doivent être avant les autres routes et sans middleware d'authentification)
Route::prefix('install')->name('install.')->withoutMiddleware([\App\Http\Middleware\CheckInstallation::class])->group(function () {
    Route::get('/', [\App\Http\Controllers\InstallController::class, 'index'])->name('index');
    Route::get('/check-requirements', [\App\Http\Controllers\InstallController::class, 'checkRequirements'])->name('check-requirements');
    Route::post('/check-database', [\App\Http\Controllers\InstallController::class, 'checkDatabase'])->name('check-database');
    Route::post('/generate-key', [\App\Http\Controllers\InstallController::class, 'generateKey'])->name('generate-key');
    Route::post('/create-storage-link', [\App\Http\Controllers\InstallController::class, 'createStorageLink'])->name('create-storage-link');
    Route::post('/run-migrations', [\App\Http\Controllers\InstallController::class, 'runMigrations'])->name('run-migrations');
    Route::post('/run-seeders', [\App\Http\Controllers\InstallController::class, 'runSeeders'])->name('run-seeders');
    Route::post('/finish', [\App\Http\Controllers\InstallController::class, 'finish'])->name('finish');
});

// Routes d'authentification pour les administrateurs (doivent être avant les routes protégées)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Auth\AdminAuthController::class, 'logout'])->name('logout');
});

Route::get('/', function () {
    // Si l'application n'est pas installée, rediriger vers l'installation
    if (!file_exists(storage_path('installed'))) {
        return redirect()->route('install.index');
    }
    return redirect()->route('admin.login');
});

// Callback PayDunya Déboursement (nano crédit) - sans authentification
Route::post('/paydunya/disburse-callback', [\App\Http\Controllers\PayDunyaDisburseCallbackController::class, '__invoke'])->name('paydunya.disburse.callback');

// Toutes les routes admin nécessitent une authentification
Route::middleware(['auth:web'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Routes spécifiques pour les caisses (doivent être avant la route resource)
// Transferts
Route::get('/caisses/transfert', [CaisseController::class, 'transfert'])->name('caisses.transfert');
Route::get('/caisses/transfert/create', [CaisseController::class, 'createTransfert'])->name('caisses.transfert.create');
Route::post('/caisses/transfert', [CaisseController::class, 'storeTransfert'])->name('caisses.transfert.store');
// Approvisionnements
Route::get('/caisses/approvisionnement', [CaisseController::class, 'approvisionnement'])->name('caisses.approvisionnement');
Route::get('/caisses/approvisionnement/create', [CaisseController::class, 'createApprovisionnement'])->name('caisses.approvisionnement.create');
Route::post('/caisses/approvisionnement', [CaisseController::class, 'storeApprovisionnement'])->name('caisses.approvisionnement.store');
// Sorties de caisses
Route::get('/caisses/sortie', [CaisseController::class, 'sortie'])->name('caisses.sortie');
Route::get('/caisses/sortie/create', [CaisseController::class, 'createSortie'])->name('caisses.sortie.create');
Route::post('/caisses/sortie', [CaisseController::class, 'storeSortie'])->name('caisses.sortie.store');
// Historique des mouvements
Route::get('/caisses/historique', [CaisseController::class, 'historique'])->name('caisses.historique');
// Journal / Balance
Route::get('/caisses/journal', [CaisseController::class, 'journal'])->name('caisses.journal');
Route::get('/caisses/{caisse}/mouvements', [CaisseController::class, 'mouvements'])->name('caisses.mouvements');
// Journal / balance par caisse
Route::get('/caisses/{caisse}/mouvements', [CaisseController::class, 'mouvements'])->name('caisses.mouvements');

    // Route resource pour les caisses (doit être après les routes spécifiques)
    Route::resource('caisses', CaisseController::class, [
        'parameters' => ['caisses' => 'caisse']
    ]);

    // Routes pour les membres (administration)
    Route::resource('membres', \App\Http\Controllers\MembreController::class);
    
    // Routes KYC (admin)
    Route::get('/kyc', [\App\Http\Controllers\KycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{kyc}', [\App\Http\Controllers\KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/{kyc}/validate', [\App\Http\Controllers\KycController::class, 'validateKyc'])->name('kyc.validate');
    Route::post('/kyc/{kyc}/reject', [\App\Http\Controllers\KycController::class, 'reject'])->name('kyc.reject');

    // Routes pour les segments
    Route::get('/segments', [\App\Http\Controllers\SegmentController::class, 'index'])->name('segments.index');
    Route::get('/segments/create', [\App\Http\Controllers\SegmentController::class, 'create'])->name('segments.create');
    Route::post('/segments', [\App\Http\Controllers\SegmentController::class, 'store'])->name('segments.store');
    Route::get('/segments/{segment}', [\App\Http\Controllers\SegmentController::class, 'show'])->name('segments.show');

    // Routes pour les cotisations
    Route::resource('cotisations', \App\Http\Controllers\CotisationController::class);
    
    // Routes pour les tags (cotisations)
    Route::get('/tags', [\App\Http\Controllers\TagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [\App\Http\Controllers\TagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [\App\Http\Controllers\TagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{tag}/edit', [\App\Http\Controllers\TagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [\App\Http\Controllers\TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [\App\Http\Controllers\TagController::class, 'destroy'])->name('tags.destroy');
    Route::get('/tags/{tag}', [\App\Http\Controllers\TagController::class, 'show'])->name('tags.show');

    // Routes spécifiques pour les paiements (doivent être avant la route resource)
    // Paiements d'engagements
    Route::get('/paiements/engagement', [\App\Http\Controllers\PaiementController::class, 'indexEngagement'])->name('paiements.engagement.index');
    Route::get('/paiements/engagement/{engagement}/create', [\App\Http\Controllers\PaiementController::class, 'createEngagement'])->name('paiements.engagement.create');
    Route::post('/paiements/engagement/{engagement}', [\App\Http\Controllers\PaiementController::class, 'storeEngagement'])->name('paiements.engagement.store');
    // Get caisses
    Route::get('/paiements/get-caisses', [\App\Http\Controllers\PaiementController::class, 'getCaisses'])->name('paiements.get-caisses');

    // Route resource pour les paiements (doit être après les routes spécifiques)
    Route::resource('paiements', \App\Http\Controllers\PaiementController::class);
    Route::get('/paiements/{paiement}/pdf', [\App\Http\Controllers\PaiementController::class, 'pdf'])->name('paiements.pdf');

    // Routes pour les engagements
    Route::resource('engagements', \App\Http\Controllers\EngagementController::class);
    
    // Routes pour les tags d'engagements
    Route::get('/engagement-tags', [\App\Http\Controllers\EngagementTagController::class, 'index'])->name('engagement-tags.index');
    Route::get('/engagement-tags/create', [\App\Http\Controllers\EngagementTagController::class, 'create'])->name('engagement-tags.create');
    Route::post('/engagement-tags', [\App\Http\Controllers\EngagementTagController::class, 'store'])->name('engagement-tags.store');
    Route::get('/engagement-tags/{tag}/edit', [\App\Http\Controllers\EngagementTagController::class, 'edit'])->name('engagement-tags.edit');
    Route::put('/engagement-tags/{tag}', [\App\Http\Controllers\EngagementTagController::class, 'update'])->name('engagement-tags.update');
    Route::delete('/engagement-tags/{tag}', [\App\Http\Controllers\EngagementTagController::class, 'destroy'])->name('engagement-tags.destroy');
    Route::get('/engagement-tags/{tag}', [\App\Http\Controllers\EngagementTagController::class, 'show'])->name('engagement-tags.show');
    Route::get('/engagements/{engagement}/pdf', [\App\Http\Controllers\EngagementController::class, 'pdf'])->name('engagements.pdf');

    // Routes pour les plans d'épargne
    Route::resource('epargne-plans', \App\Http\Controllers\EpargnePlanController::class)->except(['show']);

    // Routes pour les annonces
    Route::resource('annonces', \App\Http\Controllers\AnnonceController::class);

    // Routes pour les rapports
    Route::get('/rapports/caisse', [\App\Http\Controllers\RapportController::class, 'parCaisse'])->name('rapports.caisse');
    Route::get('/rapports/cotisation', [\App\Http\Controllers\RapportController::class, 'parCotisation'])->name('rapports.cotisation');
    Route::get('/rapports/membre', [\App\Http\Controllers\RapportController::class, 'parMembre'])->name('rapports.membre');

    // Routes pour les paramètres
    Route::resource('smtp', \App\Http\Controllers\SMTPController::class);
    Route::post('/smtp/{smtp}/test', [\App\Http\Controllers\SMTPController::class, 'test'])->name('smtp.test');
    Route::resource('email-templates', \App\Http\Controllers\EmailTemplateController::class);
    


    // ─── Paliers nano-crédit (CRUD admin) ────────────────────────────────────
    Route::resource('nano-credit-paliers', \App\Http\Controllers\NanoCreditPalierController::class);
    // Actions sur les membres (interdiction / upgrade / downgrade)
    Route::post('/membres/{membre}/interdire-nano-credit', [\App\Http\Controllers\NanoCreditPalierController::class, 'interdireMembre'])->name('membres.interdire-nano-credit');
    Route::post('/membres/{membre}/lever-interdiction-nano-credit', [\App\Http\Controllers\NanoCreditPalierController::class, 'leverInterdiction'])->name('membres.lever-interdiction-nano-credit');
    Route::post('/membres/{membre}/downgrader-palier', [\App\Http\Controllers\NanoCreditPalierController::class, 'downgraderMembre'])->name('membres.downgrader-palier');
    Route::post('/membres/{membre}/upgrader-palier', [\App\Http\Controllers\NanoCreditPalierController::class, 'upgraderMembre'])->name('membres.upgrader-palier');

    // Dashboard, Impayés et Garants Nano-Crédit
    Route::get('/nano-credits/dashboard', [\App\Http\Controllers\NanoCreditDashboardController::class, 'index'])->name('admin.nano-credits.dashboard');
    Route::get('/nano-credits/impayes', [\App\Http\Controllers\NanoCreditController::class, 'impayes'])->name('nano-credits.impayes');
    Route::get('/nano-credits/garants', [\App\Http\Controllers\NanoCreditGarantController::class, 'index'])->name('nano-credits.garants.index');
    Route::get('/nano-credits/garants/{membre}', [\App\Http\Controllers\NanoCreditGarantController::class, 'show'])->name('nano-credits.garants.show');
    
    // Retraits de gains des garants
    Route::get('/nano-credits/garants-retraits', [\App\Http\Controllers\NanoCreditGarantController::class, 'retraitsIndex'])->name('nano-credits.garants.retraits.index');
    Route::post('/nano-credits/garants-retraits/{retrait}/approve', [\App\Http\Controllers\NanoCreditGarantController::class, 'approveRetrait'])->name('nano-credits.garants.retraits.approve');
    Route::post('/nano-credits/garants-retraits/{retrait}/reject', [\App\Http\Controllers\NanoCreditGarantController::class, 'rejectRetrait'])->name('nano-credits.garants.retraits.reject');

    Route::post('/nano-credits/{nanoCredit}/impayes/relancer', [\App\Http\Controllers\NanoCreditController::class, 'relancer'])->name('nano-credits.impayes.relancer');
    Route::post('/nano-credits/{nanoCredit}/impayes/prevenir-garants', [\App\Http\Controllers\NanoCreditController::class, 'prevenirGarants'])->name('nano-credits.impayes.prevenir-garants');
    Route::post('/nano-credits/{nanoCredit}/impayes/recouvrer', [\App\Http\Controllers\NanoCreditController::class, 'recouvrer'])->name('nano-credits.impayes.recouvrer');

    // Demandes de nano crédit (liste + détail + octroyer)
    Route::get('/nano-credits', [\App\Http\Controllers\NanoCreditController::class, 'index'])->name('nano-credits.index');
    Route::get('/nano-credits/{nanoCredit}', [\App\Http\Controllers\NanoCreditController::class, 'show'])->name('nano-credits.show');
    Route::post('/nano-credits/{nanoCredit}/octroyer', [\App\Http\Controllers\NanoCreditController::class, 'octroyer'])->name('nano-credits.octroyer');
    Route::post('/nano-credits/{nanoCredit}/versement', [\App\Http\Controllers\NanoCreditController::class, 'storeVersement'])->name('nano-credits.versement.store');
    Route::post('/nano-credits/{nanoCredit}/refuser', [\App\Http\Controllers\NanoCreditController::class, 'refuser'])->name('nano-credits.refuser');

    // Routes pour PayDunya
    Route::get('/paydunya', [\App\Http\Controllers\PayDunyaController::class, 'index'])->name('paydunya.index');
    Route::put('/paydunya', [\App\Http\Controllers\PayDunyaController::class, 'update'])->name('paydunya.update');
    
    // Routes pour PayPal
    Route::get('/paypal', [\App\Http\Controllers\PayPalController::class, 'index'])->name('paypal.index');
    Route::put('/paypal', [\App\Http\Controllers\PayPalController::class, 'update'])->name('paypal.update');
    
    // Routes pour Stripe
    Route::get('/stripe', [\App\Http\Controllers\StripeController::class, 'index'])->name('stripe.index');
    Route::put('/stripe', [\App\Http\Controllers\StripeController::class, 'update'])->name('stripe.update');
    
    // Routes pour les moyens de paiement
    Route::get('/payment-methods', [\App\Http\Controllers\PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods/initialize', [\App\Http\Controllers\PaymentMethodController::class, 'initialize'])->name('payment-methods.initialize');
    Route::patch('/payment-methods/{paymentMethod}/toggle', [\App\Http\Controllers\PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');

    // Routes pour les passerelles SMS (Paramètres > SMS)
    Route::get('/sms-gateways', [\App\Http\Controllers\SmsGatewayController::class, 'index'])->name('sms-gateways.index');
    Route::get('/sms-gateways/{smsGateway}/edit', [\App\Http\Controllers\SmsGatewayController::class, 'edit'])->name('sms-gateways.edit');
    Route::put('/sms-gateways/{smsGateway}', [\App\Http\Controllers\SmsGatewayController::class, 'update'])->name('sms-gateways.update');
    Route::patch('/sms-gateways/{smsGateway}/toggle', [\App\Http\Controllers\SmsGatewayController::class, 'toggle'])->name('sms-gateways.toggle');

    // Routes pour les rôles et permissions
    Route::resource('roles', \App\Http\Controllers\RoleController::class);
    Route::get('/roles/assign/users', [\App\Http\Controllers\RoleController::class, 'assignUsers'])->name('roles.assign-users');
    Route::post('/users/{user}/assign-role', [\App\Http\Controllers\RoleController::class, 'assignRoleToUser'])->name('users.assign-role');
    Route::delete('/users/{user}/roles/{role}', [\App\Http\Controllers\RoleController::class, 'removeRoleFromUser'])->name('users.remove-role');
    Route::post('/roles/admin/assign-all-permissions', [\App\Http\Controllers\RoleController::class, 'assignAllPermissionsToAdmin'])->name('roles.admin.assign-all-permissions');

    // Routes pour le journal d'audit
    Route::resource('audit-logs', \App\Http\Controllers\AuditLogController::class)->only(['index', 'show']);

    // Tableau de bord de corruption (Scan Checksum)
    Route::get('/logs/security', [\App\Http\Controllers\SecurityLogController::class, 'index'])->name('logs.security');

    // Routes pour les paramètres généraux
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'store'])->name('settings.store');
    Route::put('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

    // Routes pour les backups
    Route::resource('backups', \App\Http\Controllers\BackupController::class)->only(['index', 'destroy']);
    Route::post('/backups/create', [\App\Http\Controllers\BackupController::class, 'create'])->name('backups.create');
    Route::get('/backups/{filename}/download', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{filename}/restore', [\App\Http\Controllers\BackupController::class, 'restore'])->name('backups.restore');

    // Routes pour les utilisateurs
    Route::resource('users', \App\Http\Controllers\UserController::class);

    // Routes pour les notifications
    Route::get('/notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');

    // Routes pour le traitement de fin de mois
    Route::get('/fin-mois', [\App\Http\Controllers\FinMoisController::class, 'index'])->name('fin-mois.index');
    Route::get('/fin-mois/preview', [\App\Http\Controllers\FinMoisController::class, 'preview'])->name('fin-mois.preview');
    Route::post('/fin-mois/process', [\App\Http\Controllers\FinMoisController::class, 'process'])->name('fin-mois.process');
    Route::get('/fin-mois/progress', [\App\Http\Controllers\FinMoisController::class, 'progress'])->name('fin-mois.progress');
    Route::get('/fin-mois/journal', [\App\Http\Controllers\FinMoisController::class, 'journal'])->name('fin-mois.journal');
    Route::post('/fin-mois/{log}/resend', [\App\Http\Controllers\FinMoisController::class, 'resend'])->name('fin-mois.resend');
    
    // Routes pour les remboursements
    Route::resource('remboursements', \App\Http\Controllers\RemboursementController::class)->only(['index', 'show']);
    Route::post('/remboursements/{remboursement}/approve', [\App\Http\Controllers\RemboursementController::class, 'approve'])->name('remboursements.approve');
    Route::post('/remboursements/{remboursement}/reject', [\App\Http\Controllers\RemboursementController::class, 'reject'])->name('remboursements.reject');
    
    // Demandes d'adhésion (cotisations créées par l'admin app uniquement)
    Route::get('/cotisation-adhesions', [\App\Http\Controllers\CotisationAdhesionController::class, 'index'])->name('cotisation-adhesions.index');
    Route::post('/cotisation-adhesions/{adhesion}/accepter', [\App\Http\Controllers\CotisationAdhesionController::class, 'accepter'])->name('cotisation-adhesions.accepter');
    Route::post('/cotisation-adhesions/{adhesion}/refuser', [\App\Http\Controllers\CotisationAdhesionController::class, 'refuser'])->name('cotisation-adhesions.refuser');

    // Demandes de versement des fonds (cotisations créées par les membres)
    Route::get('/cotisation-versement-demandes', [\App\Http\Controllers\CotisationVersementDemandeController::class, 'index'])->name('cotisation-versement-demandes.index');
    
    // Routes pour les campagnes d'emails
    Route::resource('campagnes', \App\Http\Controllers\CampagneController::class);
    Route::post('/campagnes/preview', [\App\Http\Controllers\CampagneController::class, 'preview'])->name('campagnes.preview');
    
    // Routes pour l'historique des emails
    Route::get('/email-logs', [\App\Http\Controllers\EmailLogController::class, 'index'])->name('email-logs.index');
    
    // Route pour servir les logos depuis storage
    Route::get('/storage/logos/{filename}', function ($filename) {
        $path = storage_path('app/public/logos/' . $filename);
        if (\Illuminate\Support\Facades\File::exists($path)) {
            return response()->file($path);
        }
        abort(404);
    })->name('storage.logo');
});

// Routes d'authentification pour les membres
Route::prefix('membre')->name('membre.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\MembreAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\MembreAuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Auth\MembreAuthController::class, 'logout'])->name('logout');
    
    // Routes d'inscription publique (sans authentification)
    Route::get('/register', [\App\Http\Controllers\Auth\MembreAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\MembreAuthController::class, 'register']);

    // Vérification par code OTP (après inscription)
    Route::get('/register/verify-otp', [\App\Http\Controllers\Auth\MembreAuthController::class, 'showVerifyOtpForm'])->name('verify-otp');
    Route::post('/register/verify-otp', [\App\Http\Controllers\Auth\MembreAuthController::class, 'verifyOtp']);
    Route::post('/register/resend-otp', [\App\Http\Controllers\Auth\MembreAuthController::class, 'resendOtp'])->name('resend-otp');

    // Vérification d'email (lien envoyé par mail, legacy)
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\MembreAuthController::class, 'verifyEmail'])->name('verification.verify')->middleware('signed');
    Route::post('/email/verification-resend', [\App\Http\Controllers\Auth\MembreAuthController::class, 'resendVerification'])->name('verification.resend');

    // Route callback PayDunya (sans authentification, appelée par PayDunya)
    Route::post('/membre/paydunya/callback', [\App\Http\Controllers\MembreDashboardController::class, 'paydunyaCallback'])->name('paydunya.callback');
    
    // Routes protégées pour les membres authentifiés
    Route::middleware(['auth:membre'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\MembreDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/cotisations', [\App\Http\Controllers\MembreDashboardController::class, 'cotisations'])->name('cotisations');
        Route::get('/cotisations/publiques', [\App\Http\Controllers\MembreDashboardController::class, 'cotisationsPubliques'])->name('cotisations.publiques');
        Route::get('/cotisations/privees', [\App\Http\Controllers\MembreDashboardController::class, 'cotisationsPrivees'])->name('cotisations.privees');
        Route::get('/cotisations/rechercher', [\App\Http\Controllers\MembreCotisationController::class, 'rechercher'])->name('cotisations.rechercher');
        Route::post('/cotisations/{cotisation}/adherer', [\App\Http\Controllers\MembreDashboardController::class, 'adhererCotisation'])->name('cotisations.adherer')->middleware(\App\Http\Middleware\VerifyMembrePinWeb::class);
        Route::get('/cotisations/{id}', [\App\Http\Controllers\MembreDashboardController::class, 'showCotisation'])->name('cotisations.show');
        Route::get('/mes-cotisations/creer', [\App\Http\Controllers\MembreCotisationController::class, 'create'])->name('mes-cotisations.create');
        Route::post('/mes-cotisations/creer', [\App\Http\Controllers\MembreCotisationController::class, 'store'])->name('mes-cotisations.store');
        Route::get('/mes-cotisations', [\App\Http\Controllers\MembreCotisationController::class, 'mesCotisations'])->name('mes-cotisations');
        Route::get('/mes-cotisations/{cotisation}', [\App\Http\Controllers\MembreCotisationController::class, 'showMesCotisation'])->name('mes-cotisations.show');
        Route::post('/adhesions/{adhesion}/accepter', [\App\Http\Controllers\MembreCotisationController::class, 'accepterAdhesion'])->name('adhesions.accepter');
        Route::post('/adhesions/{adhesion}/refuser', [\App\Http\Controllers\MembreCotisationController::class, 'refuserAdhesion'])->name('adhesions.refuser');
        Route::post('/mes-cotisations/{cotisation}/designer-admin', [\App\Http\Controllers\MembreCotisationController::class, 'designerAdmin'])->name('mes-cotisations.designer-admin');
        Route::post('/mes-cotisations/{cotisation}/cloturer', [\App\Http\Controllers\MembreCotisationController::class, 'cloturer'])->name('mes-cotisations.cloturer');
        Route::post('/mes-cotisations/{cotisation}/demande-versement', [\App\Http\Controllers\MembreCotisationController::class, 'demandeVersement'])->name('mes-cotisations.demande-versement');
        Route::post('/cotisations/{id}/paydunya', [\App\Http\Controllers\MembreDashboardController::class, 'initierPaiementPayDunya'])->name('cotisations.paydunya');
        Route::get('/paiements', [\App\Http\Controllers\MembreDashboardController::class, 'paiements'])->name('paiements');
        Route::get('/paiements/{paiement}/pdf', [\App\Http\Controllers\PaiementController::class, 'pdf'])->name('paiements.pdf');
        Route::get('/engagements', [\App\Http\Controllers\MembreDashboardController::class, 'engagements'])->name('engagements');
        Route::get('/engagements/{id}', [\App\Http\Controllers\MembreDashboardController::class, 'showEngagement'])->name('engagements.show');
        Route::post('/engagements/{id}/paydunya', [\App\Http\Controllers\MembreDashboardController::class, 'initierPaiementEngagementPayDunya'])->name('engagements.paydunya');
        Route::get('/remboursements', [\App\Http\Controllers\MembreDashboardController::class, 'remboursements'])->name('remboursements');
        Route::post('/remboursements/creer', [\App\Http\Controllers\MembreDashboardController::class, 'creerRemboursement'])->name('remboursements.creer');
        Route::get('/kyc', [\App\Http\Controllers\MembreKycController::class, 'index'])->name('kyc.index');
        Route::post('/kyc', [\App\Http\Controllers\MembreKycController::class, 'store'])->name('kyc.store');
        Route::get('/nano-credits', [\App\Http\Controllers\MembreNanoCreditController::class, 'index'])->name('nano-credits');
        Route::get('/nano-credits/mes', [\App\Http\Controllers\MembreNanoCreditController::class, 'mes'])->name('nano-credits.mes');
        Route::get('/nano-credits/demander', [\App\Http\Controllers\MembreNanoCreditController::class, 'demander'])->name('nano-credits.demander');
        Route::post('/nano-credits/demander', [\App\Http\Controllers\MembreNanoCreditController::class, 'storeDemande'])->name('nano-credits.demander.store')->middleware(\App\Http\Middleware\VerifyMembrePinWeb::class);
        Route::get('/nano-credits/search-guarantors', [\App\Http\Controllers\MembreNanoCreditController::class, 'searchGuarantors'])->name('nano-credits.search-guarantors');
        Route::get('/nano-credits/{nanoCredit}/modifier-garants', [\App\Http\Controllers\MembreNanoCreditController::class, 'modifierGarants'])->name('nano-credits.modifier-garants');
        Route::post('/nano-credits/{nanoCredit}/modifier-garants', [\App\Http\Controllers\MembreNanoCreditController::class, 'updateGarants'])->name('nano-credits.update-garants');
        Route::get('/nano-credits/{nanoCredit}', [\App\Http\Controllers\MembreNanoCreditController::class, 'show'])->name('nano-credits.show');
        Route::get('/epargne', [\App\Http\Controllers\MembreEpargneController::class, 'index'])->name('epargne.index');
        Route::get('/epargne/mes-epargnes', [\App\Http\Controllers\MembreEpargneController::class, 'mesEpargnes'])->name('epargne.mes-epargnes');
        Route::get('/epargne/souscrire/{plan}', [\App\Http\Controllers\MembreEpargneController::class, 'souscrire'])->name('epargne.souscrire');
        Route::post('/epargne/souscrire/{plan}', [\App\Http\Controllers\MembreEpargneController::class, 'storeSouscription'])->name('epargne.souscrire.store')->middleware(\App\Http\Middleware\VerifyMembrePinWeb::class);
        Route::get('/epargne/souscription/{souscription}', [\App\Http\Controllers\MembreEpargneController::class, 'showSouscription'])->name('epargne.souscription.show');
        Route::post('/epargne/echeance/{echeance}/paydunya', [\App\Http\Controllers\MembreEpargneController::class, 'initierPaiementEpargnePayDunya'])->name('epargne.echeance.paydunya');
        Route::get('/profil', [\App\Http\Controllers\MembreDashboardController::class, 'profil'])->name('profil');
        Route::put('/profil', [\App\Http\Controllers\MembreDashboardController::class, 'updateProfil'])->name('profil.update');

        // ─── Espace Garant (Nano-Crédit) ─────────────────────────────────────
        Route::prefix('garant')->name('garant.')->group(function () {
            Route::get('/', [\App\Http\Controllers\MembreGarantController::class, 'index'])->name('index');
            Route::get('/sollicitations', [\App\Http\Controllers\MembreGarantController::class, 'sollicitations'])->name('sollicitations');
            Route::post('/sollicitations/{garant}/accepter', [\App\Http\Controllers\MembreGarantController::class, 'accepter'])->name('accepter')->middleware(\App\Http\Middleware\VerifyMembrePinWeb::class);
            Route::post('/sollicitations/{garant}/refuser', [\App\Http\Controllers\MembreGarantController::class, 'refuser'])->name('refuser')->middleware(\App\Http\Middleware\VerifyMembrePinWeb::class);
            Route::get('/engagements', [\App\Http\Controllers\MembreGarantController::class, 'engagements'])->name('engagements');
            Route::post('/retrait', [\App\Http\Controllers\MembreGarantController::class, 'withdraw'])->name('withdraw')->middleware(\App\Http\Middleware\VerifyMembrePinWeb::class);
        });

        // Notifications (cloche top bar)
        Route::get('/notifications/unread', [\App\Http\Controllers\MembreNotificationController::class, 'unread'])->name('notifications.unread');
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\MembreNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\MembreNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::get('/notifications', [\App\Http\Controllers\MembreNotificationController::class, 'index'])->name('notifications.index');
        
        // ─── Sécurité PIN (Web) ──────────────────────────────────────────────
        Route::post('/pin/setup', [\App\Http\Controllers\MembrePinWebController::class, 'setup'])->name('pin.setup');
        Route::post('/pin/enable', [\App\Http\Controllers\MembrePinWebController::class, 'enable'])->name('pin.enable');
        Route::post('/pin/disable', [\App\Http\Controllers\MembrePinWebController::class, 'disable'])->name('pin.disable');
        Route::post('/pin/change', [\App\Http\Controllers\MembrePinWebController::class, 'change'])->name('pin.change');
        
        // Interception PIN
        Route::get('/pin/verify-action', [\App\Http\Controllers\MembrePinWebController::class, 'showVerifyActionForm'])->name('pin.verify-action');
        Route::post('/pin/verify-action', [\App\Http\Controllers\MembrePinWebController::class, 'verifyAction'])->name('pin.verify-action.submit');
    });
});

// ─── Routes Membre - Système de Parrainage ─────────────────────────────────────
Route::prefix('membre')->name('membre.')->middleware(['auth:membre'])->group(function () {
    Route::prefix('parrainage')->name('parrainage.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MembreParrainageController::class, 'index'])->name('index');
        Route::get('/filleuls', [\App\Http\Controllers\MembreParrainageController::class, 'filleuls'])->name('filleuls');
        Route::get('/commissions', [\App\Http\Controllers\MembreParrainageController::class, 'commissions'])->name('commissions');
        Route::post('/reclamer', [\App\Http\Controllers\MembreParrainageController::class, 'reclamer'])->name('reclamer');
        Route::get('/code', [\App\Http\Controllers\MembreParrainageController::class, 'getCode'])->name('code');
        Route::post('/regenerer-code', [\App\Http\Controllers\MembreParrainageController::class, 'regenererCode'])->name('regenerer-code');
    });
});

// ─── Routes Admin - Système de Parrainage ─────────────────────────────────────
Route::middleware(['auth:web'])->prefix('parrainage')->name('parrainage.admin.')->group(function () {
    Route::get('/config', [\App\Http\Controllers\ParrainageAdminController::class, 'config'])->name('config');
    Route::put('/config', [\App\Http\Controllers\ParrainageAdminController::class, 'updateConfig'])->name('config.update');
    Route::get('/commissions', [\App\Http\Controllers\ParrainageAdminController::class, 'commissions'])->name('commissions');
    Route::get('/commissions/{commission}', [\App\Http\Controllers\ParrainageAdminController::class, 'showCommission'])->name('commissions.show');
    Route::post('/commissions/{commission}/approuver', [\App\Http\Controllers\ParrainageAdminController::class, 'approuverCommission'])->name('commissions.approuver');
    Route::post('/commissions/{commission}/rejeter', [\App\Http\Controllers\ParrainageAdminController::class, 'rejeterCommission'])->name('commissions.rejeter');
    Route::post('/commissions/payer-tout', [\App\Http\Controllers\ParrainageAdminController::class, 'payerToutesReclamations'])->name('commissions.payer-tout');
    Route::get('/parrains', [\App\Http\Controllers\ParrainageAdminController::class, 'parrains'])->name('parrains');
});

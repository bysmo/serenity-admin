<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\GeoHelper;
use App\Http\Controllers\Controller;
use App\Models\Membre;
use App\Services\EmailService;
use App\Services\OtpService;
use App\Services\ParrainageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MembreAuthController extends Controller
{
    protected function normalizePhone(string $countryCode, string $number): string
    {
        $digits = preg_replace('/\D/', '', $countryCode . $number);
        return '+' . $digits;
    }

    /**
     * Détermine l'indicatif pays par défaut selon l'emplacement (IP).
     */
    protected function getDefaultCountryAndDial(): array
    {
        // On récupère d'abord le code pays fixé en base de données
        $countryCode = \App\Models\AppSetting::get('default_country_code', 'BF');
        
        // On ne recourt à GeoHelper::getCountryCodeFromIp que si on veut de l'auto-détection dynamique
        // mais ici l'utilisateur demande une "fixation", donc on utilise d'abord le réglage.
        // Si on souhaitait de l'auto-détection on ferait : $countryCode = GeoHelper::getCountryCodeFromIp($countryCode);
        
        $dialCode = GeoHelper::getDialCodeForCountry($countryCode);
        $countries = config('country_dial_codes', []);

        return [
            'country_code' => $countryCode,
            'dial_code' => $dialCode,
            'countries' => $countries,
        ];
    }

    /**
     * Afficher le formulaire de connexion (téléphone + mot de passe, indicatif pays)
     */
    public function showLoginForm()
    {
        $geo = $this->getDefaultCountryAndDial();
        return view('membres.login', [
            'default_country' => $geo['country_code'],
            'default_dial' => $geo['dial_code'],
            'countries' => $geo['countries'],
        ]);
    }

    /**
     * Traiter la connexion (par numéro de téléphone + mot de passe)
     */
    public function login(Request $request)
    {
        $request->validate([
            'country_code' => 'required|string|size:2',
            'telephone' => 'required|string|max:20',
            'password' => 'required',
        ]);

        $phoneNormalized = $this->normalizePhone(
            GeoHelper::getDialCodeForCountry($request->input('country_code')),
            $request->input('telephone')
        );

        if (strlen($phoneNormalized) < 8) {
            throw ValidationException::withMessages([
                'telephone' => ['Le numéro de téléphone est invalide.'],
            ]);
        }

        $membre = Membre::where('telephone', $phoneNormalized)->first();

        if (!$membre) {
            throw ValidationException::withMessages([
                'telephone' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (!$membre->hasVerifiedEmail()) {
            $request->session()->flash('unverified_phone', $membre->telephone);
            throw ValidationException::withMessages([
                'telephone' => ['Vous devez vérifier votre compte avant de vous connecter. Un code OTP vous a été envoyé par SMS et par email.'],
            ]);
        }

        if ($membre->statut !== 'actif') {
            throw ValidationException::withMessages([
                'telephone' => ['Votre compte est inactif. Veuillez contacter l\'administrateur.'],
            ]);
        }

        if (!Hash::check($request->input('password'), $membre->password)) {
            throw ValidationException::withMessages([
                'telephone' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        Auth::guard('membre')->login($membre, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('membre.dashboard'));
    }

    /**
     * Afficher le formulaire d'inscription (avec indicatif pays)
     */
    public function showRegisterForm(Request $request)
    {
        $geo = $this->getDefaultCountryAndDial();
        return view('membres.register', [
            'default_country' => $geo['country_code'],
            'default_dial'    => $geo['dial_code'],
            'countries'       => $geo['countries'],
            'code_parrainage' => $request->query('ref'),   // pré-remplir le code depuis l'URL
        ]);
    }

    /**
     * Traiter l'inscription : créer le membre puis envoyer un code OTP par SMS (au lieu du lien email)
     */
    public function register(Request $request)
    {
        $countryCode = $request->input('country_code', 'BF');
        $dialCode = GeoHelper::getDialCodeForCountry($countryCode);
        $phoneNormalized = $this->normalizePhone($dialCode, $request->input('telephone', ''));

        $validated = $request->validate([
            'nom'              => 'required|string|max:255',
            'prenom'           => 'required|string|max:255',
            'sexe'             => 'required|in:M,F',
            'email'            => 'required|email|max:255|unique:membres,email',
            'country_code'     => 'required|string|size:2',
            'telephone'        => 'required|string|max:20',
            'adresse'          => 'nullable|string',
            'pays'             => 'nullable|string|max:100',
            'ville'            => 'nullable|string|max:100',
            'quartier'         => 'nullable|string|max:100',
            'secteur'          => 'nullable|string|max:100',
            'password'         => 'required|string|min:6|confirmed',
            'code_parrainage'  => 'nullable|string|max:12',
        ]);

        if (strlen($phoneNormalized) < 8) {
            throw ValidationException::withMessages([
                'telephone' => ['Le numéro de téléphone est invalide.'],
            ]);
        }

        $existing = Membre::where('telephone', $phoneNormalized)->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'telephone' => ['Ce numéro de téléphone est déjà utilisé.'],
            ]);
        }

        $validated['numero'] = $this->generateNumeroMembre();
        $validated['date_adhesion'] = now();
        $validated['statut'] = 'actif';
        $validated['password'] = Hash::make($validated['password']);
        $validated['telephone'] = $phoneNormalized;
        $validated['pays'] = $validated['pays'] ?? \App\Models\AppSetting::get('entreprise_pays', 'Burkina Faso');
        unset($validated['country_code']);

        // ─── Résolution du parrain via le code de parrainage ────────────────
        $parrainId = null;
        if (!empty($validated['code_parrainage'])) {
            $parrain = Membre::where('code_parrainage', strtoupper(trim($validated['code_parrainage'])))->first();
            if ($parrain) {
                $parrainId = $parrain->id;
            }
        }
        unset($validated['code_parrainage']);

        $membre = Membre::create($validated);

        // Attribuer le parrain si trouvé
        if ($parrainId) {
            $membre->update(['parrain_id' => $parrainId]);
            // Générer la commission de parrainage (événement : inscription)
            app(ParrainageService::class)->genererCommissions($membre, 'inscription');
        }

        // Générer le code de parrainage pour ce nouveau membre
        $membre->genererCodeParrainage();

        $otpService = app(OtpService::class);
        $code = $otpService->generateAndStore($phoneNormalized);
        
        // 1. Tentative d'envoi par SMS
        $activeGateway = \App\Models\SmsGateway::getActive();
        $smsSent = false;
        if ($activeGateway) {
            try {
                $smsSent = $otpService->sendOtp($phoneNormalized, $code);
            } catch (\Throwable $e) {
                Log::warning('OTP SMS non envoyé : ' . $e->getMessage());
            }
        }

        // 2. Tentative d'envoi par Email
        $emailSent = false;
        if ($membre->email) {
            try {
                $emailSent = app(EmailService::class)->sendOtpEmail($membre, $code);
            } catch (\Throwable $e) {
                Log::warning('OTP email (inscription web) non envoyé : ' . $e->getMessage());
            }
        }

        // Si aucune passerelle n'est fonctionnelle (ni SMS ni Email), on valide automatiquement en mode dégradé (dev/fallback)
        if (!$smsSent && !$emailSent) {
            $membre->markEmailAsVerified();
            return redirect()->route('membre.login')
                ->with('success', 'Votre compte a été créé. Connectez-vous avec vos identifiants.');
        }

        $request->session()->put('membre_otp_phone', $phoneNormalized);
        $request->session()->put('membre_otp_membre_id', $membre->id);
        if ($emailSent) {
            $request->session()->put('email_sent', true);
        }

        $msg = ($smsSent && $emailSent)
            ? "Un code de vérification a été envoyé par SMS au {$phoneNormalized} et par email."
            : ($emailSent ? 'Un code de vérification a été envoyé sur votre adresse email.' : "Un code de vérification a été envoyé par SMS au {$phoneNormalized}.");

        return redirect()->route('membre.verify-otp')->with('success', $msg);
    }

    /**
     * Afficher la page de saisie du code OTP (après inscription)
     */
    public function showVerifyOtpForm(Request $request)
    {
        if (!$request->session()->has('membre_otp_phone')) {
            return redirect()->route('membre.register')
                ->with('error', 'Session expirée. Veuillez vous réinscrire.');
        }
        $phone = $request->session()->get('membre_otp_phone');
        $masked = strlen($phone) > 4 ? '***' . substr($phone, -4) : '***';
        return view('membres.verify-otp', ['phone_masked' => $masked]);
    }

    /**
     * Vérifier le code OTP et activer le compte (marquer email_verified_at)
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $phone = $request->session()->get('membre_otp_phone');
        $membreId = $request->session()->get('membre_otp_membre_id');

        if (!$phone || !$membreId) {
            return redirect()->route('membre.register')
                ->with('error', 'Session expirée. Veuillez vous réinscrire.');
        }

        $otpService = app(OtpService::class);
        if (!$otpService->verify($phone, $request->input('otp'))) {
            throw ValidationException::withMessages([
                'otp' => ['Code OTP invalide ou expiré. Demandez un nouveau code.'],
            ]);
        }

        $membre = Membre::findOrFail($membreId);
        $membre->markEmailAsVerified();

        $request->session()->forget(['membre_otp_phone', 'membre_otp_membre_id']);

        return redirect()->route('membre.login')
            ->with('success', 'Votre compte est activé. Connectez-vous avec votre numéro de téléphone et votre mot de passe.');
    }

    /**
     * Renvoyer un code OTP (depuis la page verify-otp)
     */
    public function resendOtp(Request $request)
    {
        $phone = $request->session()->get('membre_otp_phone');
        if (!$phone) {
            return redirect()->route('membre.register')->with('error', 'Session expirée.');
        }

        $otpService = app(OtpService::class);
        $code = $otpService->generateAndStore($phone);
        $otpService->sendOtp($phone, $code);

        // Renvoyer aussi par email
        $emailSent = false;
        $membreId  = $request->session()->get('membre_otp_membre_id');
        if ($membreId) {
            $membre = \App\Models\Membre::find($membreId);
            if ($membre?->email) {
                try {
                    $emailSent = app(EmailService::class)->sendOtpEmail($membre, $code);
                } catch (\Throwable $e) {
                    Log::warning('OTP email (renvoi web) non envoyé : ' . $e->getMessage());
                }
            }
        }

        if ($emailSent) {
            $request->session()->put('email_sent', true);
        }

        $msg = $emailSent
            ? 'Un nouveau code a été envoyé par SMS et par email.'
            : 'Un nouveau code a été envoyé par SMS.';

        return back()->with('success', $msg);
    }

    /**
     * Vérifier l'email du membre via le lien reçu par mail (legacy, conservé pour compatibilité)
     */
    public function verifyEmail(Request $request)
    {
        $id = $request->route('id');
        $hash = $request->route('hash');

        $validated = validator(['id' => $id, 'hash' => $hash], [
            'id' => 'required|integer|exists:membres,id',
            'hash' => 'required|string',
        ])->validate();

        $membre = Membre::findOrFail($validated['id']);

        if (!hash_equals((string) $validated['hash'], sha1($membre->email))) {
            return redirect()->route('membre.login')
                ->with('error', 'Le lien de vérification est invalide ou a expiré.');
        }

        if ($membre->hasVerifiedEmail()) {
            return redirect()->route('membre.login')
                ->with('success', 'Votre compte est déjà vérifié. Vous pouvez vous connecter.');
        }

        $membre->markEmailAsVerified();

        return redirect()->route('membre.login')
            ->with('success', 'Votre compte a été vérifié. Vous pouvez vous connecter.');
    }

    /**
     * Renvoyer l'email de vérification (legacy)
     */
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:membres,email']);

        $membre = Membre::where('email', $request->email)->first();

        if ($membre->hasVerifiedEmail()) {
            return redirect()->route('membre.login')
                ->with('success', 'Votre compte est déjà vérifié.');
        }

        try {
            app(\App\Services\EmailService::class)->sendVerificationEmail($membre);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Renvoyer email vérification: ' . $e->getMessage());
            return redirect()->route('membre.login')
                ->with('error', 'Impossible d\'envoyer le lien. Vérifiez la configuration SMTP (Paramètres > SMTP).');
        }

        return redirect()->route('membre.login')
            ->with('success', 'Un nouveau lien de vérification a été envoyé à votre adresse email.');
    }

    private function generateNumeroMembre(): string
    {
        do {
            $numero = 'MEM-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (Membre::where('numero', $numero)->exists());
        return $numero;
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::guard('membre')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('membre.login');
    }
}

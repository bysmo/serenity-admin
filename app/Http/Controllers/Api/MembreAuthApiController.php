<?php

namespace App\Http\Controllers\Api;

use App\Helpers\GeoHelper;
use App\Http\Controllers\Controller;
use App\Models\Membre;
use App\Services\EmailService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MembreAuthApiController extends Controller
{
    protected function normalizePhone(string $countryCode, string $number): string
    {
        $digits = preg_replace('/\D/', '', $countryCode . $number);
        // Toujours stocker avec le préfixe '+' pour cohérence avec l'interface web
        return '+' . ltrim($digits, '+');
    }

    /**
     * Connexion : téléphone + mot de passe → token Sanctum
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => 'required|string|size:2',
            'telephone' => 'required|string|max:20',
            'password' => 'required',
        ]);

        $dialCode = GeoHelper::getDialCodeForCountry($request->input('country_code'));
        $phoneNormalized = $this->normalizePhone($dialCode, $request->input('telephone'));

        if (strlen($phoneNormalized) < 8) {
            return response()->json(['message' => 'Numéro de téléphone invalide.'], 422);
        }

        // Chercher avec ou sans le '+' pour compatibilité ascendante
        $membre = Membre::where('telephone', $phoneNormalized)
            ->orWhere('telephone', ltrim($phoneNormalized, '+'))
            ->first();

        if (! $membre || ! Hash::check($request->input('password'), $membre->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if (! $membre->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Vérifiez votre numéro avec le code OTP envoyé par SMS.',
                'require_otp' => true,
                'membre_id' => $membre->id,
            ], 403);
        }

        if ($membre->statut !== 'actif') {
            return response()->json(['message' => 'Compte inactif.'], 403);
        }

        $membre->tokens()->where('name', 'mobile')->delete();
        $token = $membre->createToken('mobile')->plainTextToken;

        Log::info('Login successful', ['membre_id' => $membre->id]);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'membre' => $membre->toApiResource(),
        ]);
    }

    /**
     * Inscription : création du membre puis envoi OTP (pas de session en API → retourner membre_id pour verify-otp)
     */
    public function register(Request $request): JsonResponse
    {
        Log::channel('single')->info('API register/signup called', [
            'email' => $request->input('email'),
            'telephone' => $request->input('telephone'),
        ]);
        try {
            $countryCode = $request->input('country_code', 'SN');
            $dialCode = GeoHelper::getDialCodeForCountry($countryCode);
            $phoneNormalized = $this->normalizePhone($dialCode, $request->input('telephone', ''));

            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:membres,email',
                'country_code' => 'required|string|size:2',
                'telephone' => 'required|string|max:20',
                'adresse' => 'nullable|string',
                'password' => 'required|string|min:6|confirmed',
                'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            ]);

            if (strlen($phoneNormalized) < 8) {
                return response()->json(['message' => 'Numéro de téléphone invalide.'], 422);
            }

            $existing = Membre::where('telephone', $phoneNormalized)->first();
            if ($existing) {
                return response()->json(['message' => 'Ce numéro est déjà utilisé.'], 422);
            }

            $validated['numero'] = $this->generateNumeroMembre();
            $validated['date_adhesion'] = now();
            $validated['statut'] = 'en_attente';
            $validated['password'] = Hash::make($validated['password']);
            $validated['telephone'] = $phoneNormalized;
            unset($validated['country_code'], $validated['photo']);

            $membre = Membre::create($validated);

            if ($request->hasFile('photo')) {
                $dir = 'membres/' . $membre->id;
                $path = $request->file('photo')->store($dir, 'public');
                $membre->update(['photo' => $path]);
            }

            $otpService = app(OtpService::class);
            $code = $otpService->generateAndStore($phoneNormalized);

            // Envoi par SMS (si gateway active)
            $gateway = \App\Models\SmsGateway::getActive();
            if ($gateway) {
                $otpService->sendOtp($phoneNormalized, $code);
            }

            // Envoi par email (si configuré)
            if ($membre->email) {
                app(EmailService::class)->sendOtpEmail($membre, $code);
            }

            return response()->json([
                'message' => 'Un code de vérification a été envoyé par SMS et par email.',
                'require_otp' => true,
                'membre_id' => $membre->id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('single')->error('API register/signup error: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Vérification OTP (API : pas de session → membre_id + otp)
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'membre_id' => 'required|integer|exists:membres,id',
            'otp'       => 'required|string|size:6',
        ]);

        $membre = Membre::findOrFail($request->input('membre_id'));
        $phone  = $membre->telephone;

        $otpService = app(OtpService::class);
        if (! $otpService->verify($phone, $request->input('otp'))) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 422);
        }

        $membre->markEmailAsVerified();
        $membre->update(['statut' => 'actif']);
        $membre->tokens()->where('name', 'mobile')->delete();
        $token = $membre->createToken('mobile')->plainTextToken;

        // Indiquer à l'app si le membre doit encore créer son PIN
        $requirePinSetup = ! $membre->hasPin();

        Log::info('OTP verified', ['membre_id' => $membre->id, 'require_pin_setup' => $requirePinSetup]);

        return response()->json([
            'token'             => $token,
            'token_type'        => 'Bearer',
            'membre'            => $membre->toApiResource(),
            'require_pin_setup' => $requirePinSetup,
        ]);
    }

    /**
     * Renvoyer le code OTP (après inscription, pour l'app mobile)
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'membre_id' => 'required|integer|exists:membres,id',
        ]);

        $membre = Membre::findOrFail($request->input('membre_id'));
        $phone  = $membre->telephone;

        $otpService = app(OtpService::class);
        $code       = $otpService->generateAndStore($phone);

        // Envoi par SMS
        $otpService->sendOtp($phone, $code);

        // Envoi par email (en parallèle, silencieux si SMTP non configuré)
        $emailSent = false;
        if ($membre->email) {
            $emailSent = app(EmailService::class)->sendOtpEmail($membre, $code);
        }

        $message = $emailSent
            ? 'Un nouveau code de vérification a été envoyé par SMS et par email.'
            : 'Un nouveau code de vérification a été envoyé par SMS.';

        return response()->json(['message' => $message]);
    }

    /**
     * Déconnexion : révoquer le token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }

    /**
     * Données du membre connecté (pour vérifier le token et rafraîchir le profil)
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['membre' => $request->user()->toApiResource()]);
    }

    /**
     * Indicatifs pays (pour les formulaires inscription / connexion)
     */
    public function countryDialCodes(): JsonResponse
    {
        $countries = config('country_dial_codes', []);
        $list = [];
        foreach ($countries as $code => $data) {
            $list[] = [
                'code' => $code,
                'dial' => $data['dial'] ?? '',
                'name' => $data['name'] ?? $code,
            ];
        }
        return response()->json(['countries' => $list]);
    }

    private function generateNumeroMembre(): string
    {
        do {
            $numero = 'MEM-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (Membre::where('numero', $numero)->exists());
        return $numero;
    }

    private function membreResource(Membre $membre): array
    {
        return $membre->toApiResource();
    }
}

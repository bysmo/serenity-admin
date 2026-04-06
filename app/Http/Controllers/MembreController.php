<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\ParrainageConfig;
use App\Models\ParrainageCommission;
use App\Helpers\GeoHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MembreController extends Controller
{
    /**
     * Normaliser le numéro de téléphone avec l'indicatif.
     */
    protected function normalizePhone(string $countryCode, string $number): string
    {
        if (empty($number)) {
            return '';
        }
        $digits = preg_replace('/\D/', '', $countryCode . $number);
        return '+' . $digits;
    }

    /**
     * Détermine l'indicatif pays par défaut selon l'emplacement (IP).
     */
    protected function getDefaultCountryAndDial(): array
    {
        $countryCode = GeoHelper::getCountryCodeFromIp('BF');
        $dialCode = GeoHelper::getDialCodeForCountry($countryCode);
        $countries = config('country_dial_codes', []);
        return [
            'country_code' => $countryCode,
            'dial_code' => $dialCode,
            'countries' => $countries,
        ];
    }

    /**
     * Afficher la liste des membres
     */
    public function index(Request $request)
    {
        $query = Membre::query();
        
        // Recherche par nom, prénom, email ou numéro
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('numero', 'like', "%{$search}%");
            });
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $membres = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('membres.index', compact('membres'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $geo = $this->getDefaultCountryAndDial();
        return view('membres.create', [
            'default_country' => $geo['country_code'],
            'default_dial' => $geo['dial_code'],
            'countries' => $geo['countries'],
        ]);
    }

    /**
     * Générer un numéro de membre unique
     */
    private function generateNumeroMembre(): string
    {
        do {
            $numero = 'MEM-' . strtoupper(Str::random(6));
        } while (Membre::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * Enregistrer un nouveau membre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:membres,email',
            'country_code' => 'required|string|size:2',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'date_adhesion' => 'required|date',
            'statut' => 'required|in:actif,inactif,suspendu',
            'password' => 'required|string|min:6',
        ]);

        // Générer un numéro de membre unique
        $validated['numero'] = $this->generateNumeroMembre();

        // Hasher le mot de passe
        $validated['password'] = Hash::make($validated['password']);

        // Normaliser le téléphone
        if (!empty($validated['telephone'])) {
            $dialCode = GeoHelper::getDialCodeForCountry($validated['country_code']);
            $validated['telephone'] = $this->normalizePhone($dialCode, $validated['telephone']);
        } else {
            $validated['telephone'] = null;
        }
        unset($validated['country_code']);

        // Auto-vérifier le numéro de téléphone pour les membres créés par l'admin
        $validated['email_verified_at'] = now();

        Membre::create($validated);

        return redirect()->route('membres.index')
            ->with('success', 'Membre créé avec succès.');
    }

    /**
     * Afficher les détails d'un membre
     */
    public function show(Membre $membre)
    {
        $membre->load(['nanoCreditPalier', 'garants', 'parrain', 'filleuls']);

        // Données de parrainage
        $parrainageConfig     = ParrainageConfig::current();
        $nbFilleuls           = $membre->filleuls->count();
        $commissionsParrainage = $membre->commissionsParrainage()
            ->with('filleul')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $commissionsDisponibles = $membre->commissionsParrainage()->where('statut', 'disponible')->sum('montant');
        $commissionsReclames    = $membre->commissionsParrainage()->where('statut', 'reclame')->sum('montant');
        $commissionsTotales     = $membre->commissionsParrainage()->whereIn('statut', ['disponible', 'reclame', 'paye'])->sum('montant');

        return view('membres.show', compact(
            'membre', 'parrainageConfig', 'nbFilleuls',
            'commissionsParrainage', 'commissionsDisponibles',
            'commissionsReclames', 'commissionsTotales'
        ));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Membre $membre)
    {
        $geo = $this->getDefaultCountryAndDial();
        
        $membreCountry = $geo['country_code'];
        $membreLocalPhone = $membre->telephone;
        
        if ($membre->telephone && str_starts_with($membre->telephone, '+')) {
            foreach ($geo['countries'] as $code => $country) {
                // Ensure the dial code includes the '+' for accurate matching
                $dial = '+' . ltrim($country['dial'], '+');
                if (str_starts_with($membre->telephone, $dial)) {
                    $membreCountry = $code;
                    $membreLocalPhone = substr($membre->telephone, strlen($dial));
                    break;
                }
            }
        }

        return view('membres.edit', [
            'membre' => $membre,
            'membreCountry' => $membreCountry,
            'membreLocalPhone' => $membreLocalPhone,
            'default_country' => $geo['country_code'],
            'default_dial' => $geo['dial_code'],
            'countries' => $geo['countries'],
        ]);
    }

    /**
     * Mettre à jour un membre
     */
    public function update(Request $request, Membre $membre)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('membres')->ignore($membre->id),
            ],
            'country_code' => 'required|string|size:2',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'date_adhesion' => 'required|date',
            'statut' => 'required|in:actif,inactif,suspendu',
            'password' => 'nullable|string|min:6',
        ]);

        // Si le mot de passe est fourni, le hasher
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Normaliser le téléphone
        if (!empty($validated['telephone'])) {
            $dialCode = GeoHelper::getDialCodeForCountry($validated['country_code']);
            $validated['telephone'] = $this->normalizePhone($dialCode, $validated['telephone']);
        } else {
            $validated['telephone'] = null;
        }
        unset($validated['country_code']);

        $membre->update($validated);

        return redirect()->route('membres.index')
            ->with('success', 'Membre mis à jour avec succès.');
    }

    /**
     * Supprimer un membre
     */
    public function destroy(Membre $membre)
    {
        $membre->delete();

        return redirect()->route('membres.index')
            ->with('success', 'Membre supprimé avec succès.');
    }
}

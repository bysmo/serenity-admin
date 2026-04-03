<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\NanoCreditPalier;
use App\Services\NanoCreditPalierService;
use Illuminate\Http\Request;

class NanoCreditPalierController extends Controller
{
    // ─── CRUD Paliers ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $paliers = NanoCreditPalier::orderBy('numero')
            ->withCount('membres')
            ->withCount('nanoCredits')
            ->get();

        return view('nano-credit-paliers.index', compact('paliers'));
    }

    public function create()
    {
        // Numéro suggéré : dernier + 1
        $prochainNumero = (NanoCreditPalier::max('numero') ?? 0) + 1;
        return view('nano-credit-paliers.create', compact('prochainNumero'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero'                        => 'required|integer|min:1|unique:nano_credit_paliers,numero',
            'nom'                           => 'required|string|max:255',
            'description'                   => 'nullable|string',
            // Conditions d'accession
            'min_credits_rembourses'        => 'required|integer|min:0',
            'min_montant_total_rembourse'   => 'required|numeric|min:0',
            'min_epargne_cumulee'           => 'required|numeric|min:0',
            // Paramètres crédit
            'montant_plafond'               => 'required|numeric|min:1',
            'nombre_garants'                => 'required|integer|min:0|max:10',
            'duree_jours'                   => 'required|integer|min:1|max:3650',
            'taux_interet'                  => 'required|numeric|min:0|max:100',
            'frequence_remboursement'       => 'required|in:journalier,hebdomadaire,mensuel,trimestriel',
            'penalite_par_jour'             => 'required|numeric|min:0|max:100',
            'jours_avant_prelevement_garant' => 'required|integer|min:1',
            // Conséquences
            'downgrade_en_cas_impayes'      => 'boolean',
            'jours_impayes_pour_downgrade'  => 'required|integer|min:1',
            'interdiction_en_cas_recidive'  => 'boolean',
            'nb_recidives_pour_interdiction' => 'required|integer|min:1',
            'actif'                         => 'boolean',
        ], $this->messages());

        $validated['downgrade_en_cas_impayes']     = $request->boolean('downgrade_en_cas_impayes', true);
        $validated['interdiction_en_cas_recidive'] = $request->boolean('interdiction_en_cas_recidive', false);
        $validated['actif']                        = $request->boolean('actif', true);

        NanoCreditPalier::create($validated);

        return redirect()->route('nano-credit-paliers.index')
            ->with('success', 'Palier créé avec succès.');
    }

    public function show(NanoCreditPalier $nano_credit_palier)
    {
        $palier = $nano_credit_palier->load(['membres', 'nanoCredits.membre']);

        // Membres dans ce palier avec leurs stats
        $membres = $palier->membres()
            ->where('statut', 'actif')
            ->withCount(['nanoCredits as credits_rembourses' => fn ($q) => $q->where('statut', 'rembourse')])
            ->paginate(15);

        // Stats globales du palier
        $statsCredits = [
            'total'         => $palier->nanoCredits()->count(),
            'en_cours'      => $palier->nanoCredits()->whereIn('statut', ['debourse', 'en_remboursement'])->count(),
            'rembourses'    => $palier->nanoCredits()->where('statut', 'rembourse')->count(),
            'en_retard'     => $palier->nanoCredits()->where('jours_retard', '>', 0)->count(),
        ];

        return view('nano-credit-paliers.show', compact('palier', 'membres', 'statsCredits'));
    }

    public function edit(NanoCreditPalier $nano_credit_palier)
    {
        return view('nano-credit-paliers.edit', ['palier' => $nano_credit_palier]);
    }

    public function update(Request $request, NanoCreditPalier $nano_credit_palier)
    {
        $validated = $request->validate([
            'numero'                        => 'required|integer|min:1|unique:nano_credit_paliers,numero,' . $nano_credit_palier->id,
            'nom'                           => 'required|string|max:255',
            'description'                   => 'nullable|string',
            'min_credits_rembourses'        => 'required|integer|min:0',
            'min_montant_total_rembourse'   => 'required|numeric|min:0',
            'min_epargne_cumulee'           => 'required|numeric|min:0',
            'montant_plafond'               => 'required|numeric|min:1',
            'nombre_garants'               => 'required|integer|min:0|max:10',
            'duree_jours'                   => 'required|integer|min:1|max:3650',
            'taux_interet'                  => 'required|numeric|min:0|max:100',
            'frequence_remboursement'       => 'required|in:journalier,hebdomadaire,mensuel,trimestriel',
            'penalite_par_jour'             => 'required|numeric|min:0|max:100',
            'jours_avant_prelevement_garant' => 'required|integer|min:1',
            'downgrade_en_cas_impayes'      => 'boolean',
            'jours_impayes_pour_downgrade'  => 'required|integer|min:1',
            'interdiction_en_cas_recidive'  => 'boolean',
            'nb_recidives_pour_interdiction' => 'required|integer|min:1',
            'actif'                         => 'boolean',
        ], $this->messages());

        $validated['downgrade_en_cas_impayes']     = $request->boolean('downgrade_en_cas_impayes', true);
        $validated['interdiction_en_cas_recidive'] = $request->boolean('interdiction_en_cas_recidive', false);
        $validated['actif']                        = $request->boolean('actif', true);

        $nano_credit_palier->update($validated);

        return redirect()->route('nano-credit-paliers.index')
            ->with('success', 'Palier mis à jour avec succès.');
    }

    public function destroy(NanoCreditPalier $nano_credit_palier)
    {
        if ($nano_credit_palier->membres()->exists()) {
            return redirect()->route('nano-credit-paliers.index')
                ->with('error', 'Impossible de supprimer ce palier : des membres y sont rattachés.');
        }
        if ($nano_credit_palier->nanoCredits()->exists()) {
            return redirect()->route('nano-credit-paliers.index')
                ->with('error', 'Impossible de supprimer ce palier : des nano-crédits y sont liés.');
        }

        $nano_credit_palier->delete();

        return redirect()->route('nano-credit-paliers.index')
            ->with('success', 'Palier supprimé.');
    }

    // ─── Gestion des Membres ──────────────────────────────────────────────────

    /**
     * Interdit manuellement un membre de prendre des nano-crédits.
     */
    public function interdireMembre(Request $request, Membre $membre, NanoCreditPalierService $service)
    {
        $validated = $request->validate([
            'motif' => 'required|string|max:500',
        ]);

        $service->interdireMembre($membre, $validated['motif']);

        return redirect()->back()
            ->with('success', "Membre {$membre->nom_complet} interdit de nano-crédit.");
    }

    /**
     * Lève l'interdiction de crédit d'un membre.
     */
    public function leverInterdiction(Membre $membre, NanoCreditPalierService $service)
    {
        $service->leverInterdiction($membre);

        return redirect()->back()
            ->with('success', "Interdiction levée pour {$membre->nom_complet}.");
    }

    /**
     * Force le downgrade manuel d'un membre.
     */
    public function downgraderMembre(Request $request, Membre $membre, NanoCreditPalierService $service)
    {
        $validated = $request->validate([
            'motif' => 'required|string|max:500',
        ]);

        $retrogradé = $service->downgraderPalier($membre, $validated['motif']);

        if ($retrogradé) {
            return redirect()->back()->with('success', "Membre {$membre->nom_complet} rétrogradé au palier inférieur.");
        }

        return redirect()->back()->with('error', "Impossible de rétrograder : le membre est déjà au palier le plus bas.");
    }

    /**
     * Force l'upgrade manuel d'un membre.
     */
    public function upgraderMembre(Membre $membre, NanoCreditPalierService $service)
    {
        $upgraded = $service->upgraderPalier($membre);

        if ($upgraded) {
            $membre->refresh();
            return redirect()->back()->with('success', "Membre {$membre->nom_complet} upgradé vers {$membre->nanoCreditPalier?->nom}.");
        }

        return redirect()->back()->with('error', "Le membre n'est pas éligible à un upgrade de palier (conditions non remplies ou déjà au palier max).");
    }

    // ─── Messages de validation ───────────────────────────────────────────────

    private function messages(): array
    {
        return [
            'numero.required'           => 'Le numéro du palier est obligatoire.',
            'numero.unique'             => 'Ce numéro de palier est déjà utilisé.',
            'nom.required'              => 'Le nom du palier est obligatoire.',
            'montant_plafond.required'  => 'Le montant plafond est obligatoire.',
            'duree_jours.required'      => 'La durée du crédit est obligatoire.',
            'taux_interet.required'     => 'Le taux d\'intérêt est obligatoire.',
            'penalite_par_jour.required' => 'Le taux de pénalité journalier est obligatoire.',
        ];
    }
}

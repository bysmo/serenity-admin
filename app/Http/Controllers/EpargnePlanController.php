<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\EpargnePlan;
use Illuminate\Http\Request;

class EpargnePlanController extends Controller
{
    /**
     * Liste des plans d'épargne (style compact)
     */
    public function index(Request $request)
    {
        $query = EpargnePlan::query()->with('caisse');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            if ($request->actif === '1') {
                $query->where('actif', true);
            } elseif ($request->actif === '0') {
                $query->where('actif', false);
            }
        }

        $plans = $query->orderBy('ordre')->orderBy('nom')->get();

        // Nombre de souscriptions actives par plan
        foreach ($plans as $plan) {
            $plan->souscriptions_count = $plan->souscriptions()->where('statut', 'active')->count();
        }

        return view('epargne-plans.index', compact('plans'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $caisses = Caisse::where('statut', 'active')->orderBy('nom')->get();
        return view('epargne-plans.create', compact('caisses'));
    }

    /**
     * Enregistrer un nouveau plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant_min' => 'required|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0|gte:montant_min',
            'frequence' => 'required|in:journalier,hebdomadaire,mensuel,trimestriel',
            'taux_remuneration' => 'required|numeric|min:0|max:100',
            'duree_mois' => 'required|integer|min:1|max:360',
            'caisse_id' => 'nullable|exists:caisses,id',
            'heure_limite_paiement' => 'required|date_format:H:i',
            'delai_rappel_heures' => 'required|integer|min:0',
            'intervalle_rappel_minutes' => 'required|integer|min:1',
            'actif' => 'boolean',
            'ordre' => 'nullable|integer|min:0',
        ], [
            'nom.required' => 'Le nom du plan est obligatoire.',
            'montant_min.required' => 'Le montant minimum est obligatoire.',
            'frequence.required' => 'La fréquence est obligatoire.',
            'taux_remuneration.required' => 'Le taux de rémunération est obligatoire.',
            'duree_mois.required' => 'La durée du plan (en mois) est obligatoire.',
            'montant_max.gte' => 'Le montant maximum doit être supérieur ou égal au montant minimum.',
        ]);

        $validated['actif'] = $request->boolean('actif', true);
        $validated['ordre'] = (int) ($request->ordre ?? 0);

        EpargnePlan::create($validated);

        return redirect()->route('epargne-plans.index')
            ->with('success', 'Plan d\'épargne créé avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(EpargnePlan $epargne_plan)
    {
        $plan = $epargne_plan;
        $caisses = Caisse::where('statut', 'active')->orderBy('nom')->get();
        return view('epargne-plans.edit', compact('plan', 'caisses'));
    }

    /**
     * Mettre à jour un plan
     */
    public function update(Request $request, EpargnePlan $epargne_plan)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant_min' => 'required|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0|gte:montant_min',
            'frequence' => 'required|in:journalier,hebdomadaire,mensuel,trimestriel',
            'taux_remuneration' => 'required|numeric|min:0|max:100',
            'duree_mois' => 'required|integer|min:1|max:360',
            'caisse_id' => 'nullable|exists:caisses,id',
            'actif' => 'boolean',
            'ordre' => 'nullable|integer|min:0',
        ], [
            'nom.required' => 'Le nom du plan est obligatoire.',
            'montant_min.required' => 'Le montant minimum est obligatoire.',
            'frequence.required' => 'La fréquence est obligatoire.',
            'taux_remuneration.required' => 'Le taux de rémunération est obligatoire.',
            'duree_mois.required' => 'La durée du plan (en mois) est obligatoire.',
            'montant_max.gte' => 'Le montant maximum doit être supérieur ou égal au montant minimum.',
        ]);

        $validated['actif'] = $request->boolean('actif', true);
        $validated['ordre'] = (int) ($request->ordre ?? 0);

        $epargne_plan->update($validated);

        return redirect()->route('epargne-plans.index')
            ->with('success', 'Plan d\'épargne mis à jour avec succès.');
    }

    /**
     * Supprimer un plan (si aucune souscription active)
     */
    public function destroy(EpargnePlan $epargne_plan)
    {
        $actives = $epargne_plan->souscriptions()->where('statut', 'active')->count();
        if ($actives > 0) {
            return redirect()->route('epargne-plans.index')
                ->with('error', 'Impossible de supprimer ce plan : il possède des souscriptions actives.');
        }

        $epargne_plan->delete();
        return redirect()->route('epargne-plans.index')
            ->with('success', 'Plan d\'épargne supprimé.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NanoCreditType;
use Illuminate\Http\Request;

class NanoCreditTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = NanoCreditType::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%");
            });
        }
        if ($request->filled('actif')) {
            $query->where('actif', $request->actif === '1');
        }

        $types = $query->orderBy('ordre')->orderBy('nom')->get();
        foreach ($types as $type) {
            $type->demandes_count = $type->nanoCredits()->count();
        }

        return view('nano-credit-types.index', compact('types'));
    }

    public function create()
    {
        return view('nano-credit-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant_min' => 'required|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0',
            'duree_mois' => 'required|integer|min:1|max:120',
            'taux_interet' => 'required|numeric|min:0|max:100',
            'frequence_remboursement' => 'required|in:hebdomadaire,mensuel,trimestriel',
            'actif' => 'boolean',
            'ordre' => 'nullable|integer|min:0',
            'min_epargne_percent' => 'required|numeric|min:0|max:100',
        ], [
            'nom.required' => 'Le nom du type est obligatoire.',
            'montant_min.required' => 'Le montant minimum est obligatoire.',
            'duree_mois.required' => 'La durée (mois) est obligatoire.',
            'taux_interet.required' => 'Le taux d\'intérêt est obligatoire.',
            'frequence_remboursement.required' => 'La fréquence de remboursement est obligatoire.',
        ]);

        $validated['actif'] = $request->boolean('actif', true);
        $validated['ordre'] = (int) ($request->ordre ?? 0);
        if (isset($validated['montant_max']) && $validated['montant_min'] > $validated['montant_max']) {
            return redirect()->back()->withInput()->with('error', 'Le montant maximum doit être supérieur ou égal au montant minimum.');
        }

        NanoCreditType::create($validated);

        return redirect()->route('nano-credit-types.index')
            ->with('success', 'Type de nano crédit créé avec succès.');
    }

    public function edit(NanoCreditType $nano_credit_type)
    {
        $type = $nano_credit_type;
        return view('nano-credit-types.edit', compact('type'));
    }

    public function update(Request $request, NanoCreditType $nano_credit_type)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'montant_min' => 'required|numeric|min:0',
            'montant_max' => 'nullable|numeric|min:0',
            'duree_mois' => 'required|integer|min:1|max:120',
            'taux_interet' => 'required|numeric|min:0|max:100',
            'frequence_remboursement' => 'required|in:hebdomadaire,mensuel,trimestriel',
            'actif' => 'boolean',
            'ordre' => 'nullable|integer|min:0',
            'min_epargne_percent' => 'required|numeric|min:0|max:100',
        ]);
        $validated['actif'] = $request->boolean('actif', true);
        $validated['ordre'] = (int) ($request->ordre ?? 0);
        if (isset($validated['montant_max']) && $validated['montant_min'] > $validated['montant_max']) {
            return redirect()->back()->withInput()->with('error', 'Le montant maximum doit être supérieur ou égal au montant minimum.');
        }

        $nano_credit_type->update($validated);

        return redirect()->route('nano-credit-types.index')
            ->with('success', 'Type de nano crédit mis à jour.');
    }

    public function destroy(NanoCreditType $nano_credit_type)
    {
        if ($nano_credit_type->nanoCredits()->exists()) {
            return redirect()->route('nano-credit-types.index')
                ->with('error', 'Impossible de supprimer ce type : des demandes y sont liées.');
        }
        $nano_credit_type->delete();
        return redirect()->route('nano-credit-types.index')
            ->with('success', 'Type de nano crédit supprimé.');
    }
}

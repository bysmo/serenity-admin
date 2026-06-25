<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Helpers\SecurityHelper;
use Illuminate\Http\Request;

class AnnonceController extends Controller
{
    /**
     * Afficher la liste des annonces
     */
    public function index(Request $request)
    {
        $query = Annonce::query();
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('contenu', 'like', SecurityHelper::likeSearch($search));
            });
        }
        
        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        
        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        $annonces = $query->orderBy('ordre', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('annonces.index', compact('annonces'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        // Récupérer tous les segments uniques existants depuis la table segments
        $segments = \App\Models\Segment::orderBy('nom')
            ->pluck('nom')
            ->toArray();
        
        return view('annonces.create', compact('segments'));
    }

    /**
     * Enregistrer une nouvelle annonce
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'required|in:active,inactive',
            'type' => 'required|in:info,warning,success,danger',
            'ordre' => 'nullable|integer|min:0',
        ]);

        Annonce::create($validated);

        return redirect()->route('annonces.index')
            ->with('success', 'Annonce créée avec succès.');
    }

    /**
     * Afficher les détails d'une annonce
     */
    public function show(Annonce $annonce)
    {
        return view('annonces.show', compact('annonce'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Annonce $annonce)
    {
        // Récupérer tous les segments uniques existants depuis la table segments
        $segments = \App\Models\Segment::orderBy('nom')
            ->pluck('nom')
            ->toArray();
        
        return view('annonces.edit', compact('annonce', 'segments'));
    }

    /**
     * Mettre à jour une annonce
     */
    public function update(Request $request, Annonce $annonce)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'required|in:active,inactive',
            'type' => 'required|in:info,warning,success,danger',
            'ordre' => 'nullable|integer|min:0',
            'segment' => 'nullable|string|max:255',
        ]);

        $annonce->update($validated);

        return redirect()->route('annonces.index')
            ->with('success', 'Annonce mise à jour avec succès.');
    }

    /**
     * Supprimer une annonce
     */
    public function destroy(Annonce $annonce)
    {
        $annonce->delete();

        return redirect()->route('annonces.index')
            ->with('success', 'Annonce supprimée avec succès.');
    }
}

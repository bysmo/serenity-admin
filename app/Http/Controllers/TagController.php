<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Cotisation;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Afficher la liste des tags
     */
    public function index(Request $request)
    {
        // Récupérer tous les tags de type cotisation
        $tags = Tag::where('type', 'cotisation')
            ->orderBy('nom')
            ->get();
        
        // Recherche si fournie
        if ($request->filled('search')) {
            $search = $request->search;
            $tags = $tags->filter(function($tag) use ($search) {
                return stripos($tag->nom, $search) !== false;
            });
        }
        
        // Compter les cotisations par tag
        foreach ($tags as $tag) {
            $tag->nombre_cotisations = Cotisation::where('tag', $tag->nom)->count();
        }
        
        // Total des cotisations sans tag
        $cotisationsSansTag = Cotisation::whereNull('tag')
            ->orWhere('tag', '')
            ->count();
        
        return view('tags.index', compact('tags', 'cotisationsSansTag'));
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('tags.create');
    }
    
    /**
     * Enregistrer un nouveau tag
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:tags,nom',
            'description' => 'nullable|string|max:2000',
        ]);
        
        $validated['type'] = 'cotisation';
        
        Tag::create($validated);
        
        return redirect()->route('tags.index')
            ->with('success', 'Tag créé avec succès.');
    }
    
    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Tag $tag)
    {
        return view('tags.edit', compact('tag'));
    }
    
    /**
     * Mettre à jour un tag
     */
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:tags,nom,' . $tag->id,
            'description' => 'nullable|string|max:2000',
        ]);
        
        $tag->update($validated);
        
        // Mettre à jour toutes les cotisations qui utilisent l'ancien nom
        if ($request->nom !== $tag->getOriginal('nom')) {
            Cotisation::where('tag', $tag->getOriginal('nom'))
                ->update(['tag' => $request->nom]);
        }
        
        return redirect()->route('tags.index')
            ->with('success', 'Tag mis à jour avec succès.');
    }
    
    /**
     * Supprimer un tag
     */
    public function destroy(Tag $tag)
    {
        // Retirer le tag de toutes les cotisations
        Cotisation::where('tag', $tag->nom)->update(['tag' => null]);
        
        $tag->delete();
        
        return redirect()->route('tags.index')
            ->with('success', 'Tag supprimé avec succès.');
    }
    
    /**
     * Afficher les cotisations d'un tag
     */
    public function show(Request $request, $tag)
    {
        // Décoder le tag si nécessaire
        $tag = urldecode($tag);
        
        $tagModel = Tag::where('nom', $tag)->where('type', 'cotisation')->first();
        
        if (!$tagModel) {
            return redirect()->route('tags.index')
                ->with('error', 'Tag introuvable.');
        }
        
        $cotisations = Cotisation::with('caisse')
            ->where('tag', $tag)
            ->orderBy('nom')
            ->paginate(15);
        
        return view('tags.show', compact('tag', 'cotisations'));
    }
}

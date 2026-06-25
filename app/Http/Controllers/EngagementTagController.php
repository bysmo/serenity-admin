<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Engagement;
use Illuminate\Http\Request;

class EngagementTagController extends Controller
{
    /**
     * Afficher la liste des tags d'engagements
     */
    public function index(Request $request)
    {
        // Récupérer tous les tags de type engagement
        $tags = Tag::where('type', 'engagement')
            ->orderBy('nom')
            ->get();
        
        // Recherche si fournie
        if ($request->filled('search')) {
            $search = $request->search;
            $tags = $tags->filter(function($tag) use ($search) {
                return stripos($tag->nom, $search) !== false;
            });
        }
        
        // Compter les engagements par tag
        foreach ($tags as $tag) {
            $tag->nombre_engagements = Engagement::where('tag', $tag->nom)->count();
        }
        
        // Total des engagements sans tag
        $engagementsSansTag = Engagement::whereNull('tag')
            ->orWhere('tag', '')
            ->count();
        
        return view('engagement-tags.index', compact('tags', 'engagementsSansTag'));
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('engagement-tags.create');
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
        
        $validated['type'] = 'engagement';
        
        Tag::create($validated);
        
        return redirect()->route('engagement-tags.index')
            ->with('success', 'Tag créé avec succès.');
    }
    
    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Tag $tag)
    {
        // Vérifier que c'est un tag d'engagement
        if ($tag->type !== 'engagement') {
            return redirect()->route('engagement-tags.index')
                ->with('error', 'Tag introuvable.');
        }
        
        return view('engagement-tags.edit', compact('tag'));
    }
    
    /**
     * Mettre à jour un tag
     */
    public function update(Request $request, Tag $tag)
    {
        // Vérifier que c'est un tag d'engagement
        if ($tag->type !== 'engagement') {
            return redirect()->route('engagement-tags.index')
                ->with('error', 'Tag introuvable.');
        }
        
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:tags,nom,' . $tag->id,
            'description' => 'nullable|string|max:2000',
        ]);
        
        $tag->update($validated);
        
        // Mettre à jour toutes les engagements qui utilisent l'ancien nom
        if ($request->nom !== $tag->getOriginal('nom')) {
            Engagement::where('tag', $tag->getOriginal('nom'))
                ->update(['tag' => $request->nom]);
        }
        
        return redirect()->route('engagement-tags.index')
            ->with('success', 'Tag mis à jour avec succès.');
    }
    
    /**
     * Supprimer un tag
     */
    public function destroy(Tag $tag)
    {
        // Vérifier que c'est un tag d'engagement
        if ($tag->type !== 'engagement') {
            return redirect()->route('engagement-tags.index')
                ->with('error', 'Tag introuvable.');
        }
        
        // Retirer le tag de toutes les engagements
        Engagement::where('tag', $tag->nom)->update(['tag' => null]);
        
        $tag->delete();
        
        return redirect()->route('engagement-tags.index')
            ->with('success', 'Tag supprimé avec succès.');
    }
    
    /**
     * Afficher les engagements d'un tag
     */
    public function show(Request $request, $tag)
    {
        // Décoder le tag si nécessaire
        $tag = urldecode($tag);
        
        $tagModel = Tag::where('nom', $tag)->where('type', 'engagement')->first();
        
        if (!$tagModel) {
            return redirect()->route('engagement-tags.index')
                ->with('error', 'Tag introuvable.');
        }
        
        $engagements = Engagement::with(['membre', 'cotisation'])
            ->where('tag', $tag)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('engagement-tags.show', compact('tag', 'engagements'));
    }
}

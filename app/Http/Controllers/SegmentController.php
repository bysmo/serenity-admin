<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SegmentController extends Controller
{
    /**
     * Afficher la liste des segments
     */
    public function index(Request $request)
    {
        $query = Segment::withCount('membres');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nom', 'like', "%{$search}%");
        }
        
        $segments = $query->orderBy('nom')->get();
        
        return view('segments.index', compact('segments'));
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('segments.create');
    }
    
    /**
     * Enregistrer un nouveau segment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:segments,nom',
            'description' => 'nullable|string',
            'couleur' => 'nullable|string|max:20',
            'icone' => 'nullable|string|max:60',
            'actif' => 'boolean',
        ], [
            'nom.required' => 'Le nom du segment est obligatoire.',
            'nom.unique' => 'Ce segment existe déjà.',
        ]);

        $validated['slug'] = Str::slug($validated['nom']);
        $validated['actif'] = $request->has('actif');

        Segment::create($validated);

        return redirect()->route('segments.index')
            ->with('success', 'Segment créé avec succès.');
    }
    
    /**
     * Afficher les membres d'un segment
     */
    public function show(Segment $segment)
    {
        $membres = $segment->membres()->paginate(15);
        
        return view('segments.show', compact('segment', 'membres'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Segment $segment)
    {
        return view('segments.edit', compact('segment'));
    }

    /**
     * Mettre à jour un segment
     */
    public function update(Request $request, Segment $segment)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:segments,nom,' . $segment->id,
            'description' => 'nullable|string',
            'couleur' => 'nullable|string|max:20',
            'icone' => 'nullable|string|max:60',
            'actif' => 'boolean',
        ], [
            'nom.required' => 'Le nom du segment est obligatoire.',
            'nom.unique' => 'Ce segment existe déjà.',
        ]);

        $validated['slug'] = Str::slug($validated['nom']);
        $validated['actif'] = $request->has('actif');

        $segment->update($validated);

        return redirect()->route('segments.index')
            ->with('success', 'Segment mis à jour avec succès.');
    }

    /**
     * Supprimer un segment
     */
    public function destroy(Segment $segment)
    {
        // Protection : on ne peut pas supprimer un segment par défaut
        if ($segment->is_default) {
            return redirect()->route('segments.index')
                ->with('error', 'Le segment par défaut ne peut pas être supprimé.');
        }

        // Protection : on ne peut pas supprimer un segment qui contient des membres
        if ($segment->membres()->count() > 0) {
            return redirect()->route('segments.index')
                ->with('error', 'Impossible de supprimer ce segment car il contient des membres. Veuillez d\'abord réassigner les membres à un autre segment.');
        }

        $segment->delete();

        return redirect()->route('segments.index')
            ->with('success', 'Segment supprimé avec succès.');
    }
}

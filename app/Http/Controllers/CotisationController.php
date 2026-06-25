<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\Caisse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\SecurityHelper;

class CotisationController extends Controller
{
    /**
     * Afficher la liste des cotisations (templates)
     */
    public function index(Request $request)
    {
        $query = Cotisation::with('caisse');
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('nom', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('type', 'like', SecurityHelper::likeSearch($search))
                  ->orWhereHas('caisse', function($q) use ($search) {
                      $q->where('nom', 'like', SecurityHelper::likeSearch($search));
                  });
            });
        }
        
        // Filtre par statut actif
        if ($request->filled('actif')) {
            $query->where('actif', $request->actif === '1');
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $cotisations = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('cotisations.index', compact('cotisations'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $caisses = Caisse::where('statut', 'active')->orderBy('nom')->get();
        
        // Récupérer tous les tags depuis la table tags
        $tags = \App\Models\Tag::where('type', 'cotisation')
            ->orderBy('nom')
            ->pluck('nom')
            ->toArray();
        
        return view('cotisations.create', compact('caisses', 'tags'));
    }

    /**
     * Générer un numéro de cotisation unique
     */
    private function generateNumeroCotisation(): string
    {
        do {
            $numero = 'COT-' . strtoupper(Str::random(8));
        } while (Cotisation::where('numero', $numero)->exists());

        return $numero;
    }

    private function generateCodeCotisation(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Cotisation::where('code', $code)->exists());

        return $code;
    }

    /**
     * Enregistrer une nouvelle cotisation (template)
     */
    public function store(Request $request)
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'caisse_id' => 'required|exists:caisses,id',
            'type' => 'required|string|in:reguliere,ponctuelle,exceptionnelle',
            'frequence' => 'required|in:mensuelle,trimestrielle,semestrielle,annuelle,unique',
            'type_montant' => 'required|in:libre,fixe',
            'description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
            'nouveau_tag' => 'nullable|string|max:255|required_if:tag,__nouveau__',
            'visibilite' => 'required|in:publique,privee',
            'actif' => 'boolean',
        ];

        // Si le type de montant est fixe, le montant est requis
        if ($request->type_montant === 'fixe') {
            $rules['montant'] = 'required|numeric|min:1';
        } else {
            $rules['montant'] = 'nullable|numeric|min:0';
        }

        $validated = $request->validate($rules);

        // Si un nouveau tag est fourni, l'utiliser
        if ($request->tag === '__nouveau__' && $request->filled('nouveau_tag')) {
            $validated['tag'] = trim($request->nouveau_tag);
        } elseif ($request->tag === '__nouveau__') {
            $validated['tag'] = null;
        }
        
        unset($validated['nouveau_tag']);

        // Générer un numéro et un code de cotisation uniques (code pour recherche/adhésion)
        $validated['numero'] = $this->generateNumeroCotisation();
        $validated['code'] = $this->generateCodeCotisation();
        $validated['actif'] = $request->has('actif');
        
        // Si le montant est libre, mettre null
        if ($validated['type_montant'] === 'libre') {
            $validated['montant'] = null;
        }

        $cotisation = Cotisation::create($validated);

        // Notifier tous les membres des nouvelles cotisations actives
        if ($cotisation->actif) {
            $membres = \App\Models\Membre::all();
            foreach ($membres as $membre) {
                $membre->notify(new \App\Notifications\NewCotisationNotification($cotisation));
            }
            \Log::info('Notifications de nouvelle cotisation envoyées', [
                'cotisation_id' => $cotisation->id,
                'visibilite' => $cotisation->visibilite,
                'nombre_membres' => $membres->count(),
            ]);
        }

        return redirect()->route('cotisations.index')
            ->with('success', 'Cotisation créée avec succès.');
    }

    /**
     * Afficher les détails d'une cotisation
     */
    public function show(Cotisation $cotisation)
    {
        $cotisation->load(['caisse', 'paiements.membre']);
        
        return view('cotisations.show', compact('cotisation'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Cotisation $cotisation)
    {
        $caisses = Caisse::where('statut', 'active')->orderBy('nom')->get();
        
        // Récupérer tous les tags depuis la table tags
        $tags = \App\Models\Tag::where('type', 'cotisation')
            ->orderBy('nom')
            ->pluck('nom')
            ->toArray();
        
        return view('cotisations.edit', compact('cotisation', 'caisses', 'tags'));
    }

    /**
     * Mettre à jour une cotisation
     */
    public function update(Request $request, Cotisation $cotisation)
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'caisse_id' => 'required|exists:caisses,id',
            'type' => 'required|string|in:reguliere,ponctuelle,exceptionnelle',
            'frequence' => 'required|in:mensuelle,trimestrielle,semestrielle,annuelle,unique',
            'type_montant' => 'required|in:libre,fixe',
            'description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
            'nouveau_tag' => 'nullable|string|max:255|required_if:tag,__nouveau__',
            'visibilite' => 'required|in:publique,privee',
            'actif' => 'boolean',
        ];

        // Si le type de montant est fixe, le montant est requis
        if ($request->type_montant === 'fixe') {
            $rules['montant'] = 'required|numeric|min:1';
        } else {
            $rules['montant'] = 'nullable|numeric|min:0';
        }

        $validated = $request->validate($rules);

        // Si un nouveau tag est fourni, l'utiliser
        if ($request->tag === '__nouveau__' && $request->filled('nouveau_tag')) {
            $validated['tag'] = trim($request->nouveau_tag);
        } elseif ($request->tag === '__nouveau__') {
            $validated['tag'] = null;
        }
        unset($validated['nouveau_tag']);

        $validated['actif'] = $request->has('actif');
        
        // Si le montant est libre, mettre null
        if ($validated['type_montant'] === 'libre') {
            $validated['montant'] = null;
        }

        // Générer un code si absent (cotisations créées avant migration)
        if (empty($cotisation->code)) {
            $validated['code'] = $this->generateCodeCotisation();
        }

        $cotisation->update($validated);

        return redirect()->route('cotisations.index')
            ->with('success', 'Cotisation mise à jour avec succès.');
    }

    /**
     * Supprimer une cotisation
     */
    public function destroy(Cotisation $cotisation)
    {
        // Vérifier s'il y a des paiements associés
        if ($cotisation->paiements()->count() > 0) {
            return redirect()->route('cotisations.index')
                ->with('error', 'Impossible de supprimer cette cotisation car elle a des paiements associés.');
        }

        $cotisation->delete();

        return redirect()->route('cotisations.index')
            ->with('success', 'Cotisation supprimée avec succès.');
    }
}

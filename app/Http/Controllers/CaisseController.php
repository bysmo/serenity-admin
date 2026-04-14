<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\Transfert;
use App\Models\Approvisionnement;
use App\Models\SortieCaisse;
use App\Models\MouvementCaisse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CaisseController extends Controller
{
    /**
     * Afficher la liste des caisses
     */
    public function index(Request $request)
    {
        $query = Caisse::query();
        
        // Recherche par nom, numéro ou client
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('numero', 'like', "%{$search}%")
                  ->orWhereHas('membre', function($sq) use ($search) {
                      $sq->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%");
                  });
            });
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $caisses = $query->with('membre')->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('caisses.index', compact('caisses'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $membres = \App\Models\Membre::orderBy('nom')->orderBy('prenom')->get();
        return view('caisses.create', compact('membres'));
    }

    /**
     * Générer un numéro de caisse unique au format XXXX-XXXX (alphanumérique)
     */
    private function generateNumeroCaisse(): string
    {
        do {
            // Générer 4 caractères alphanumériques (majuscules et chiffres)
            $part1 = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
            $part2 = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
            $numero = $part1 . '-' . $part2;
        } while (Caisse::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * Enregistrer une nouvelle caisse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:caisses,nom',
            'description' => 'nullable|string',
            'statut' => 'required|in:active,inactive',
            'type' => 'required|string|in:epargne,courant,tontine,credit,impayes',
            'numero_core_banking' => 'nullable|string|alpha_num|unique:caisses,numero_core_banking',
            'membre_id' => 'required|exists:membres,id',
        ]);

        // Le solde initial est toujours 0 lors de la création
        // Le solde sera alimenté par des mouvements (approvisionnements, transferts, etc.)
        $validated['solde_initial'] = 0;
        
        // Générer un numéro de compte unique
        $validated['numero'] = $this->generateNumeroCaisse();

        Caisse::create($validated);

        return redirect()->route('caisses.index')
            ->with('success', 'Compte créé avec succès.');
    }

    /**
     * Afficher les détails d'une caisse
     */
    public function show(Caisse $caisse)
    {
        return view('caisses.show', compact('caisse'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Caisse $caisse)
    {
        $membres = \App\Models\Membre::orderBy('nom')->orderBy('prenom')->get();
        return view('caisses.edit', compact('caisse', 'membres'));
    }

    /**
     * Mettre à jour une caisse
     */
    public function update(Request $request, Caisse $caisse)
    {
        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('caisses')->ignore($caisse->id),
            ],
            'description' => 'nullable|string',
            'statut' => 'required|in:active,inactive',
            'type' => 'required|string|in:epargne,courant,tontine,credit,impayes',
            'numero_core_banking' => [
                'nullable',
                'string',
                'alpha_num',
                Rule::unique('caisses')->ignore($caisse->id),
            ],
            'membre_id' => 'required|exists:membres,id',
        ]);

        // Empêcher de désactiver une caisse ayant un solde différent de 0
        if ($validated['statut'] === 'inactive' && $caisse->solde_actuel != 0) {
            return redirect()->route('caisses.edit', $caisse)
                ->with('error', 'Impossible de désactiver un compte dont le solde est différent de 0. Veuillez d\'abord vider le compte ou transférer les fonds.')
                ->withInput();
        }

        // Ne pas modifier le solde_initial (il est géré automatiquement par les mouvements)
        $validated['solde_initial'] = $caisse->solde_initial;

        $caisse->update($validated);

        return redirect()->route('caisses.index')
            ->with('success', 'Compte mis à jour avec succès.');
    }

    /**
     * Supprimer une caisse
     */
    public function destroy(Caisse $caisse)
    {
        $caisse->delete();

        return redirect()->route('caisses.index')
            ->with('success', 'Compte supprimé avec succès.');
    }

    /**
     * Afficher la liste des transferts inter caisse
     */
    public function transfert(Request $request)
    {
        $query = Transfert::with(['caisseSource', 'caisseDestination']);
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('caisseSource', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%");
                })
                ->orWhereHas('caisseDestination', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%");
                })
                ->orWhere('motif', 'like', "%{$search}%");
            });
        }
        
        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $transferts = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('caisses.transfert-index', compact('transferts'));
    }

    /**
     * Afficher le formulaire de création de transfert inter caisse
     */
    public function createTransfert()
    {
        $caisses = Caisse::where('statut', 'active')->get();
        
        return view('caisses.transfert', compact('caisses'));
    }

    /**
     * Enregistrer un transfert inter caisse
     */
    public function storeTransfert(Request $request)
    {
        $validated = $request->validate([
            'caisse_source_id' => 'required|exists:caisses,id',
            'caisse_destination_id' => 'required|exists:caisses,id|different:caisse_source_id',
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        // Vérifier que la caisse source a suffisamment de fonds
        $caisseSource = Caisse::findOrFail($validated['caisse_source_id']);
        if ($caisseSource->solde_actuel < $validated['montant']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['montant' => 'Le solde du compte source est insuffisant.']);
        }

        // Enregistrer le transfert
        $transfert = Transfert::create($validated);
        
        // Mettre à jour les soldes des caisses
        $caisseSource->solde_initial = $caisseSource->solde_initial - $validated['montant'];
        $caisseSource->save();
        
        $caisseDestination = Caisse::findOrFail($validated['caisse_destination_id']);
        $caisseDestination->solde_initial = $caisseDestination->solde_initial + $validated['montant'];
        $caisseDestination->save();

        // Journaliser (sortie caisse source)
        MouvementCaisse::create([
            'caisse_id' => $caisseSource->id,
            'type' => 'transfert_out',
            'sens' => 'sortie',
            'montant' => $validated['montant'],
            'date_operation' => now(),
            'libelle' => 'Transfert vers: ' . ($caisseDestination->nom ?? ''),
            'notes' => $validated['motif'] ?? null,
            'reference_type' => Transfert::class,
            'reference_id' => $transfert->id,
        ]);

        // Journaliser (entrée caisse destination)
        MouvementCaisse::create([
            'caisse_id' => $caisseDestination->id,
            'type' => 'transfert_in',
            'sens' => 'entree',
            'montant' => $validated['montant'],
            'date_operation' => now(),
            'libelle' => 'Transfert depuis: ' . ($caisseSource->nom ?? ''),
            'notes' => $validated['motif'] ?? null,
            'reference_type' => Transfert::class,
            'reference_id' => $transfert->id,
        ]);
        
        return redirect()->route('caisses.transfert')
            ->with('success', 'Transfert effectué avec succès.');
    }

    /**
     * Afficher la liste des approvisionnements
     */
    public function approvisionnement(Request $request)
    {
        $query = Approvisionnement::with('caisse');
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('caisse', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('numero', 'like', "%{$search}%");
                })
                ->orWhere('motif', 'like', "%{$search}%");
            });
        }
        
        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $approvisionnements = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('caisses.approvisionnement-index', compact('approvisionnements'));
    }

    /**
     * Afficher le formulaire de création d'approvisionnement
     */
    public function createApprovisionnement()
    {
        $caisses = Caisse::where('statut', 'active')->get();
        
        return view('caisses.approvisionnement', compact('caisses'));
    }

    /**
     * Enregistrer un approvisionnement de caisse
     */
    public function storeApprovisionnement(Request $request)
    {
        $validated = $request->validate([
            'caisse_id' => 'required|exists:caisses,id',
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        // Enregistrer l'approvisionnement
        $appro = Approvisionnement::create($validated);
        
        // Mettre à jour le solde du compte
        $caisse = Caisse::findOrFail($validated['caisse_id']);
        $caisse->solde_initial = $caisse->solde_initial + $validated['montant'];
        $caisse->save();

        // Journaliser le mouvement (entrée)
        MouvementCaisse::create([
            'caisse_id' => $caisse->id,
            'type' => 'approvisionnement',
            'sens' => 'entree',
            'montant' => $validated['montant'],
            'date_operation' => now(),
            'libelle' => 'Approvisionnement',
            'notes' => $validated['motif'] ?? null,
            'reference_type' => Approvisionnement::class,
            'reference_id' => $appro->id,
        ]);
        
        return redirect()->route('caisses.approvisionnement')
            ->with('success', 'Approvisionnement effectué avec succès.');
    }

    /**
     * Afficher la liste des sorties de caisses
     */
    public function sortie(Request $request)
    {
        $query = SortieCaisse::with('caisse');
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('caisse', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('numero', 'like', "%{$search}%");
                })
                ->orWhere('motif', 'like', "%{$search}%");
            });
        }
        
        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('date_sortie', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_sortie', '<=', $request->date_fin);
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $sorties = $query->orderBy('date_sortie', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('caisses.sortie-index', compact('sorties'));
    }

    /**
     * Afficher le formulaire de création de sortie
     */
    public function createSortie()
    {
        $caisses = Caisse::where('statut', 'active')->get();
        
        return view('caisses.sortie', compact('caisses'));
    }

    /**
     * Enregistrer une sortie de caisse
     */
    public function storeSortie(Request $request)
    {
        $validated = $request->validate([
            'caisse_id' => 'required|exists:caisses,id',
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'date_sortie' => 'required|date',
        ]);

        // Vérifier que la caisse a suffisamment de fonds
        $caisse = Caisse::findOrFail($validated['caisse_id']);
        if ($caisse->solde_actuel < $validated['montant']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['montant' => 'Le solde du compte est insuffisant.']);
        }

        // Enregistrer la sortie
        $sortie = SortieCaisse::create($validated);
        
        // Mettre à jour le solde du compte
        $caisse->solde_initial = $caisse->solde_initial - $validated['montant'];
        $caisse->save();

        // Journaliser le mouvement (sortie)
        MouvementCaisse::create([
            'caisse_id' => $caisse->id,
            'type' => 'sortie',
            'sens' => 'sortie',
            'montant' => $validated['montant'],
            'date_operation' => $validated['date_sortie'],
            'libelle' => 'Sortie de caisse' . ($validated['motif'] ? ' - ' . $validated['motif'] : ''),
            'notes' => $validated['notes'] ?? null,
            'reference_type' => SortieCaisse::class,
            'reference_id' => $sortie->id,
        ]);
        
        return redirect()->route('caisses.sortie')
            ->with('success', 'Sortie de caisse enregistrée avec succès.');
    }

    /**
     * Journal / balance des mouvements d'une caisse
     */
    public function mouvements(Request $request, Caisse $caisse)
    {
        $baseQuery = MouvementCaisse::with('reference')
            ->where('caisse_id', $caisse->id);

        $query = clone $baseQuery;

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_operation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_operation', '<=', $request->date_fin);
        }

        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $mouvements = $query->orderBy('date_operation', 'desc')->paginate($perPage);

        // Calculer les totaux avant pagination
        $totalEntrees = (clone $baseQuery)->where('sens', 'entree');
        $totalSorties = (clone $baseQuery)->where('sens', 'sortie');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $totalEntrees->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
            $totalSorties->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('type')) {
            $totalEntrees->where('type', $request->type);
            $totalSorties->where('type', $request->type);
        }
        
        if ($request->filled('date_debut')) {
            $totalEntrees->whereDate('date_operation', '>=', $request->date_debut);
            $totalSorties->whereDate('date_operation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $totalEntrees->whereDate('date_operation', '<=', $request->date_fin);
            $totalSorties->whereDate('date_operation', '<=', $request->date_fin);
        }
        
        $totalEntrees = $totalEntrees->get()->sum('montant');
        $totalSorties = $totalSorties->get()->sum('montant');
        $net = $totalEntrees - $totalSorties;

        $types = (clone $baseQuery)
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('caisses.mouvements', compact('caisse', 'mouvements', 'totalEntrees', 'totalSorties', 'net', 'types'));
    }

    /**
     * Afficher la page de sélection de caisse pour consulter le journal
     */
    public function journal()
    {
        $caisses = Caisse::where('statut', 'active')->orderBy('nom')->get();
        
        return view('caisses.journal-select', compact('caisses'));
    }

    /**
     * Afficher l'historique de tous les mouvements de caisses
     */
    public function historique(Request $request)
    {
        $transferts = collect();
        $approvisionnements = collect();
        
        // Requête pour les transferts
        $queryTransferts = Transfert::with(['caisseSource', 'caisseDestination']);
        
        // Requête pour les approvisionnements
        $queryApprovisionnements = Approvisionnement::with('caisse');
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            
            $queryTransferts->where(function($q) use ($search) {
                $q->whereHas('caisseSource', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%");
                })
                ->orWhereHas('caisseDestination', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%");
                })
                ->orWhere('motif', 'like', "%{$search}%");
            });
            
            $queryApprovisionnements->where(function($q) use ($search) {
                $q->whereHas('caisse', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('numero', 'like', "%{$search}%");
                })
                ->orWhere('motif', 'like', "%{$search}%");
            });
        }
        
        // Filtre par période
        if ($request->filled('date_debut')) {
            $queryTransferts->whereDate('created_at', '>=', $request->date_debut);
            $queryApprovisionnements->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $queryTransferts->whereDate('created_at', '<=', $request->date_fin);
            $queryApprovisionnements->whereDate('created_at', '<=', $request->date_fin);
        }
        
        // Récupérer tous les résultats
        $transferts = $queryTransferts->orderBy('created_at', 'desc')->get();
        $approvisionnements = $queryApprovisionnements->orderBy('created_at', 'desc')->get();
        
        // Fusionner et trier par date
        $mouvements = $transferts->map(function($item) {
            return [
                'type' => 'transfert',
                'date' => $item->created_at,
                'data' => $item
            ];
        })->concat($approvisionnements->map(function($item) {
            return [
                'type' => 'approvisionnement',
                'date' => $item->created_at,
                'data' => $item
            ];
        }))->sortByDesc('date')->values();
        
        // Pagination manuelle
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $currentItems = $mouvements->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $mouvementsPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $mouvements->count(),
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query()
            ]
        );
        
        return view('caisses.historique', compact('mouvementsPaginated', 'transferts', 'approvisionnements'));
    }
}

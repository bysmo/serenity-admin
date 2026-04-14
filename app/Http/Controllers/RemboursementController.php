<?php

namespace App\Http\Controllers;

use App\Models\Remboursement;
use App\Models\MouvementCaisse;
use App\Models\Caisse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RemboursementController extends Controller
{
    /**
     * Générer un numéro de remboursement unique
     */
    private function generateNumeroRemboursement(): string
    {
        do {
            $numero = 'REM-' . strtoupper(Str::random(8));
        } while (Remboursement::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * Afficher la liste des remboursements
     */
    public function index(Request $request)
    {
        $query = Remboursement::with(['paiement', 'membre', 'caisse', 'traitePar']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('membre', function($q) use ($search) {
                      $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('paiement', function($q) use ($search) {
                      $q->where('numero', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $remboursements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Statistiques
        $stats = [
            'total' => Remboursement::count(),
            'en_attente' => Remboursement::where('statut', 'en_attente')->count(),
            'approuves' => Remboursement::where('statut', 'approuve')->count(),
            'refuses' => Remboursement::where('statut', 'refuse')->count(),
        ];

        return view('remboursements.index', compact('remboursements', 'stats'));
    }

    /**
     * Afficher les détails d'un remboursement
     */
    public function show(Remboursement $remboursement)
    {
        $remboursement->load(['paiement.membre', 'paiement.cotisation', 'paiement.caisse', 'membre', 'caisse', 'traitePar']);

        return view('remboursements.show', compact('remboursement'));
    }

    /**
     * Approuver un remboursement
     */
    public function approve(Request $request, Remboursement $remboursement)
    {
        if ($remboursement->statut !== 'en_attente') {
            return redirect()->back()
                ->with('error', 'Ce remboursement a déjà été traité.');
        }

        $validated = $request->validate([
            'commentaire_admin' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Charger les relations nécessaires
            $remboursement->load('paiement.caisse');

            $caisse = $remboursement->paiement->caisse;

            if (!$caisse) {
                throw new \Exception('La caisse associée au paiement n\'existe plus.');
            }

            // Vérifier que la caisse a suffisamment de fonds
            if ($caisse->solde_actuel < $remboursement->montant) {
                return redirect()->back()
                    ->with('error', 'Le solde du compte est insuffisant pour effectuer ce remboursement.');
            }

            // Mettre à jour le remboursement
            $remboursement->statut = 'approuve';
            $remboursement->caisse_id = $caisse->id;
            $remboursement->commentaire_admin = $validated['commentaire_admin'] ?? null;
            $remboursement->traite_par = auth()->id();
            $remboursement->traite_le = now();
            $remboursement->save();

            // Décrémenter le solde du compte
            $caisse->solde_initial = $caisse->solde_initial - $remboursement->montant;
            $caisse->save();

            // Journaliser le mouvement (sortie)
            MouvementCaisse::create([
                'caisse_id' => $caisse->id,
                'type' => 'remboursement',
                'sens' => 'sortie',
                'montant' => $remboursement->montant,
                'date_operation' => now(),
                'libelle' => 'Remboursement - ' . $remboursement->numero,
                'notes' => 'Remboursement du paiement ' . $remboursement->paiement->numero . ($validated['commentaire_admin'] ? ' - ' . $validated['commentaire_admin'] : ''),
                'reference_type' => Remboursement::class,
                'reference_id' => $remboursement->id,
            ]);

            DB::commit();

            return redirect()->route('remboursements.show', $remboursement)
                ->with('success', 'Le remboursement a été approuvé avec succès. Le montant a été débité du compte.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de l\'approbation du remboursement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'approbation du remboursement: ' . $e->getMessage());
        }
    }

    /**
     * Refuser un remboursement
     */
    public function reject(Request $request, Remboursement $remboursement)
    {
        if ($remboursement->statut !== 'en_attente') {
            return redirect()->back()
                ->with('error', 'Ce remboursement a déjà été traité.');
        }

        $validated = $request->validate([
            'commentaire_admin' => 'required|string|max:1000',
        ], [
            'commentaire_admin.required' => 'Un commentaire est obligatoire pour refuser un remboursement.',
        ]);

        $remboursement->statut = 'refuse';
        $remboursement->commentaire_admin = $validated['commentaire_admin'];
        $remboursement->traite_par = auth()->id();
        $remboursement->traite_le = now();
        $remboursement->save();

        return redirect()->route('remboursements.show', $remboursement)
            ->with('success', 'Le remboursement a été refusé.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\NanoCreditGarant;
use Illuminate\Http\Request;

class NanoCreditGarantController extends Controller
{
    /**
     * Liste des garants et statistiques
     */
    public function index(Request $request)
    {
        $query = Membre::where('statut', 'actif');

        // Filtre par nom/prénom/téléphone
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('telephone', 'like', "%{$s}%");
            });
        }

        // Uniquement ceux qui ont déjà été garants ou qui ont de l'épargne
        $query->where(function($q) {
            $q->has('garants')->orHas('epargneSouscriptions');
        });

        $membres = $query->with(['garants', 'epargneSouscriptions'])
            ->withCount(['garants as total_garanties'])
            ->withCount(['garants as garanties_actives' => fn($q) => $q->whereIn('statut', ['accepte', 'preleve'])])
            ->orderBy('garant_qualite', 'desc')
            ->paginate(15);

        return view('nano-credits.garants.index', compact('membres'));
    }

    /**
     * Liste des demandes de retrait des gains des garants
     */
    public function retraitsIndex(Request $request)
    {
        $query = \App\Models\GarantGainRetrait::with(['membre', 'traitePar']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('membre', function($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhere('prenom', 'like', "%{$s}%")
                  ->orWhere('telephone', 'like', "%{$s}%");
            })->orWhere('reference', 'like', "%{$s}%");
        }

        $retraits = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('nano-credits.garants.retraits_index', compact('retraits'));
    }

    /**
     * Approuver une demande de retrait
     */
    public function approveRetrait(Request $request, \App\Models\GarantGainRetrait $retrait)
    {
        if ($retrait->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $membre = $retrait->membre;
        $montant = (float) $retrait->montant;

        if ($membre->garant_solde < $montant) {
            return redirect()->back()->with('error', 'Le solde du membre (' . number_format($membre->garant_solde, 0, ',', ' ') . ' XOF) est insuffisant pour approuver ce retrait.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Déduire du solde du membre
            $membre->decrement('garant_solde', $montant);

            // Mettre à jour la demande
            $retrait->update([
                'statut' => 'approuve',
                'traite_par' => auth()->id(),
                'traite_le' => now(),
                'commentaire_admin' => $request->commentaire_admin,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->back()->with('success', 'La demande de retrait a été approuvée et le solde du membre a été débité.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors du traitement.');
        }
    }

    /**
     * Rejeter une demande de retrait
     */
    public function rejectRetrait(Request $request, \App\Models\GarantGainRetrait $retrait)
    {
        if ($retrait->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $request->validate([
            'commentaire_admin' => 'required|string|max:1000',
        ], [
            'commentaire_admin.required' => 'Un motif de refus est obligatoire.',
        ]);

        $retrait->update([
            'statut' => 'refuse',
            'traite_par' => auth()->id(),
            'traite_le' => now(),
            'commentaire_admin' => $request->commentaire_admin,
        ]);

        return redirect()->back()->with('success', 'La demande de retrait a été rejetée.');
    }
}

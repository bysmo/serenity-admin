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
     * Voir les détails des garanties d'un membre
     */
    public function show(Membre $membre)
    {
        $membre->load(['garants.nanoCredit', 'epargneSouscriptions']);
        return view('nano-credits.garants.show', compact('membre'));
    }
}

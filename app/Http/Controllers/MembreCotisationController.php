<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\CotisationAdhesion;
use App\Models\CotisationVersementDemande;
use App\Models\Caisse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MembreCotisationController extends Controller
{
    /**
     * Formulaire de création d'une cotisation par un membre
     */
    public function create()
    {
        $tags = \App\Models\Tag::where('type', 'cotisation')->orderBy('nom')->pluck('nom')->toArray();
        return view('membres.cotisations.create', compact('tags'));
    }

    /**
     * Enregistrer une cotisation créée par un membre (caisse auto-créée)
     */
    public function store(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        $rules = [
            'nom' => 'required|string|max:255',
            'visibilite' => 'required|in:publique,privee',
            'type' => 'required|string|in:reguliere,ponctuelle,exceptionnelle',
            'frequence' => 'required|in:mensuelle,trimestrielle,semestrielle,annuelle,unique',
            'type_montant' => 'required|in:libre,fixe',
            'description' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
        ];
        if ($request->type_montant === 'fixe') {
            $rules['montant'] = 'required|numeric|min:1';
        } else {
            $rules['montant'] = 'nullable|numeric|min:0';
        }

        $validated = $request->validate($rules);

        // Créer la caisse dédiée à cette cotisation
        $caisse = Caisse::create([
            'numero' => $this->generateNumeroCaisse(),
            'nom' => 'Caisse - ' . $validated['nom'],
            'description' => 'Caisse de collecte pour la cotisation "' . $validated['nom'] . '"',
            'statut' => 'active',
            'solde_initial' => 0,
        ]);

        // Créer la cotisation
        $cotisation = Cotisation::create([
            'numero' => $this->generateNumeroCotisation(),
            'code' => $this->generateCodeCotisation(),
            'nom' => $validated['nom'],
            'caisse_id' => $caisse->id,
            'created_by_membre_id' => $membre->id,
            'type' => $validated['type'],
            'frequence' => $validated['frequence'],
            'type_montant' => $validated['type_montant'],
            'montant' => $validated['type_montant'] === 'libre' ? null : $validated['montant'],
            'description' => $validated['description'] ?? null,
            'tag' => $validated['tag'] ?? null,
            'visibilite' => $validated['visibilite'],
            'actif' => true,
        ]);

        // Le créateur est automatiquement adhérent accepté
        CotisationAdhesion::create([
            'membre_id' => $membre->id,
            'cotisation_id' => $cotisation->id,
            'statut' => 'accepte',
        ]);

        return redirect()->route('membre.mes-cotisations.show', $cotisation)
            ->with('success', 'Tontine créée. Votre code de partage : ' . $cotisation->code . '. Les membres pourront rechercher ce code pour demander à adhérer.');
    }

    /**
     * Liste des cotisations créées par le membre connecté
     */
    public function mesCotisations()
    {
        $membre = Auth::guard('membre')->user();
        $cotisations = Cotisation::where(function ($q) use ($membre) {
            $q->where('created_by_membre_id', $membre->id)
                ->orWhere('admin_membre_id', $membre->id);
        })
            ->with(['caisse', 'adhesions'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return view('membres.cotisations.mes-cotisations', compact('cotisations'));
    }

    /**
     * Détails d'une cotisation créée par le membre (gestion des adhésions)
     */
    public function showMesCotisation(Cotisation $cotisation)
    {
        $membre = Auth::guard('membre')->user();
        $isCreator = $cotisation->created_by_membre_id === $membre->id;
        $isAdmin = $cotisation->getAdminMembreId() === $membre->id;
        if (!$isCreator && !$isAdmin) {
            abort(403, 'Vous n\'êtes pas l\'administrateur de cette cotisation.');
        }

        $cotisation->load(['createdByMembre', 'adminMembre', 'caisse']);
        $adhesionsEnAttente = $cotisation->adhesions()->where('statut', 'en_attente')->with('membre')->get();
        $adhesionsAcceptees = $cotisation->adhesions()->where('statut', 'accepte')->with('membre')->get();
        $adminMembre = $cotisation->admin_membre_id ? $cotisation->adminMembre : $cotisation->createdByMembre;
        $demandeVersementEnCours = $cotisation->versementDemandes()->where('statut', 'en_attente')->exists();
        $soldeCaisse = $cotisation->caisse ? (float) $cotisation->caisse->solde_actuel : 0;
        $paiementsCotisation = $cotisation->paiements()->with('membre')->orderBy('date_paiement', 'desc')->get();
        $totalCollecte = (float) $cotisation->paiements()->get()->sum('montant');

        return view('membres.cotisations.show-mes', compact(
            'cotisation', 'adhesionsEnAttente', 'adhesionsAcceptees',
            'adminMembre', 'demandeVersementEnCours', 'soldeCaisse',
            'paiementsCotisation', 'totalCollecte'
        ));
    }

    /**
     * Rechercher une cotisation par code (GET sans code = formulaire, GET avec code = recherche)
     * Affiche aussi les demandes d'adhésion du membre et leur statut.
     */
    public function rechercher(Request $request)
    {
        if (!$request->filled('code')) {
            $membre = Auth::guard('membre')->user();
            $mesDemandesAdhesion = CotisationAdhesion::where('membre_id', $membre->id)
                ->with('cotisation')
                ->orderBy('created_at', 'desc')
                ->get();
            return view('membres.cotisations.rechercher', compact('mesDemandesAdhesion'));
        }

        $request->validate(['code' => 'required|string|max:20']);
        $code = strtoupper(trim($request->code));

        $cotisation = Cotisation::where('code', $code)->where('actif', true)->with('caisse')->first();

        if (!$cotisation) {
            return redirect()->route('membre.cotisations.rechercher')
                ->with('error', 'Aucune cotisation trouvée pour ce code.')
                ->withInput(['code' => $request->code]);
        }

        $membre = Auth::guard('membre')->user();
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->first();

        return view('membres.cotisations.recherche-resultat', compact('cotisation', 'adhesion'));
    }

    /**
     * Accepter une demande d'adhésion (admin de la cotisation = créateur membre)
     */
    public function accepterAdhesion(CotisationAdhesion $adhesion)
    {
        $membre = Auth::guard('membre')->user();
        if ($adhesion->cotisation->getAdminMembreId() !== $membre->id) {
            abort(403, 'Vous n\'êtes pas l\'administrateur de cette cotisation.');
        }
        if ($adhesion->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $adhesion->update(['statut' => 'accepte', 'traite_par' => null, 'traite_le' => now()]);

        return redirect()->back()->with('success', 'Demande acceptée.');
    }

    /**
     * Refuser une demande d'adhésion
     */
    public function refuserAdhesion(Request $request, CotisationAdhesion $adhesion)
    {
        $membre = Auth::guard('membre')->user();
        if ($adhesion->cotisation->getAdminMembreId() !== $membre->id) {
            abort(403, 'Vous n\'êtes pas l\'administrateur de cette cotisation.');
        }
        if ($adhesion->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $adhesion->update(['statut' => 'refuse', 'traite_le' => now()]);

        return redirect()->back()->with('success', 'Demande refusée.');
    }

    private function generateNumeroCaisse(): string
    {
        do {
            $part1 = strtoupper(Str::random(4));
            $part2 = strtoupper(Str::random(4));
            $numero = $part1 . '-' . $part2;
        } while (Caisse::where('numero', $numero)->exists());
        return $numero;
    }

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
     * Désigner un autre membre comme administrateur de la cotisation (doit être adhérent accepté)
     */
    public function designerAdmin(Request $request, Cotisation $cotisation)
    {
        $membre = Auth::guard('membre')->user();
        $isCreator = $cotisation->created_by_membre_id === $membre->id;
        $isAdmin = $cotisation->getAdminMembreId() === $membre->id;
        if (!$isCreator && !$isAdmin) {
            abort(403, 'Vous n\'êtes pas l\'administrateur de cette cotisation.');
        }

        $request->validate(['admin_membre_id' => 'required|integer|exists:membres,id']);

        $adhesion = CotisationAdhesion::where('cotisation_id', $cotisation->id)
            ->where('membre_id', $request->admin_membre_id)
            ->where('statut', 'accepte')
            ->first();

        if (!$adhesion) {
            return redirect()->back()->with('error', 'Ce membre doit être adhérent accepté.');
        }

        $cotisation->update(['admin_membre_id' => $request->admin_membre_id]);

        return redirect()->back()->with('success', 'Administrateur désigné.');
    }

    /**
     * Clôturer la cotisation (désactiver)
     */
    public function cloturer(Cotisation $cotisation)
    {
        $membre = Auth::guard('membre')->user();
        $isCreator = $cotisation->created_by_membre_id === $membre->id;
        $isAdmin = $cotisation->getAdminMembreId() === $membre->id;
        if (!$isCreator && !$isAdmin) {
            abort(403, 'Vous n\'êtes pas l\'administrateur de cette cotisation.');
        }

        $cotisation->update(['actif' => false]);

        return redirect()->back()->with('success', 'Tontine clôturée.');
    }

    /**
     * Demander le versement des fonds à l'administrateur de l'application
     */
    public function demandeVersement(Cotisation $cotisation)
    {
        $membre = Auth::guard('membre')->user();
        $isCreator = $cotisation->created_by_membre_id === $membre->id;
        $isAdmin = $cotisation->getAdminMembreId() === $membre->id;
        if (!$isCreator && !$isAdmin) {
            abort(403, 'Vous n\'êtes pas l\'administrateur de cette cotisation.');
        }

        $enCours = $cotisation->versementDemandes()->where('statut', 'en_attente')->exists();
        if ($enCours) {
            return redirect()->back()->with('error', 'Une demande de versement est déjà en cours.');
        }

        $caisse = $cotisation->caisse;
        if (!$caisse) {
            return redirect()->back()->with('error', 'Aucune caisse associée.');
        }

        $montant = $caisse->solde_actuel ?? 0;
        if ($montant <= 0) {
            return redirect()->back()->with('error', 'Le solde du compte est nul.');
        }

        CotisationVersementDemande::create([
            'cotisation_id' => $cotisation->id,
            'caisse_id' => $caisse->id,
            'demande_par_membre_id' => $membre->id,
            'montant_demande' => $montant,
            'statut' => 'en_attente',
        ]);

        // Notifier les admins app (User avec rôle admin)
        $admins = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['admin', 'super-admin']);
        })->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\CotisationVersementDemandeNotification($cotisation, $montant, $membre));
        }

        return redirect()->back()->with('success', 'Demande de versement envoyée à l\'administration.');
    }
}

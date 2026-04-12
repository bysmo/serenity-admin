<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\Segment;
use App\Models\KycVerification;
use App\Models\Cotisation;
use App\Models\CotisationAdhesion;
use App\Models\Paiement;
use App\Models\Annonce;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class MembreDashboardController extends Controller
{
    /**
     * Dashboard ADMIN : Statistiques globales des membres
     */
    public function index(Request $request)
    {
        // Période par défaut : le mois en cours
        $dateDebut = $request->filled('date_debut') 
            ? Carbon::parse($request->date_debut)->startOfDay() 
            : Carbon::now()->startOfMonth();
            
        $dateFin = $request->filled('date_fin') 
            ? Carbon::parse($request->date_fin)->endOfDay() 
            : Carbon::now()->endOfDay();

        // 1. Statistiques générales
        $totalMembres = Membre::count();
        $membresActifs = Membre::where('statut', 'actif')->count();
        
        // Membres inscrits sur la période
        $nouveauxMembres = Membre::whereBetween('created_at', [$dateDebut, $dateFin])->count();

        // 2. Statistiques KYC
        $kycStats = [
            'valide' => KycVerification::where('statut', 'valide')->count(),
            'en_attente' => KycVerification::where('statut', 'en_attente')->count(),
            'rejete' => KycVerification::where('statut', 'rejete')->count(),
        ];
        
        // Membres sans aucun enregistrement KYC
        $membresAvecKycIds = KycVerification::pluck('membre_id')->unique();
        $kycStats['manquant'] = Membre::whereNotIn('id', $membresAvecKycIds)->count();

        // 3. Répartition par segment
        $segments = Segment::withCount('membres')->get();
        
        // 4. Évolution des adhésions
        $diffInDays = $dateDebut->diffInDays($dateFin);
        
        if ($diffInDays <= 62) {
            $evolution = Membre::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as aggregate'))
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->date => $item->aggregate]);
        } else {
            $evolution = Membre::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as date"), DB::raw('count(*) as aggregate'))
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->date => $item->aggregate]);
        }

        // 5. Répartition par genre (Sexe)
        // Note: On utilise ->filter() pour regrouper les null/vides sous "Non précisé" si nécessaire
        $sexeStats = Membre::select('sexe', DB::raw('count(*) as count'))
            ->groupBy('sexe')
            ->get()
            ->mapWithKeys(fn($item) => [($item->sexe ?? 'N/A') => $item->count]);

        return view('membres.dashboard', compact(
            'totalMembres', 'membresActifs', 'nouveauxMembres',
            'kycStats', 'segments', 'evolution', 'sexeStats',
            'dateDebut', 'dateFin'
        ));
    }

    // ─── ESPACE MEMBRE (Portal) ───────────────────────────────────────────────

    /**
     * Dashboard du MEMBRE
     */
    public function dashboard(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        
        $cotisationIds = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        
        $paiementsMembre = $membre->paiements()->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->get();
        $orphelins = Paiement::whereNull('membre_id')->whereIn('cotisation_id', $cotisationIds)->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->get();
        $tousPaiements = $paiementsMembre->merge($orphelins)->unique('id')->sortByDesc('date_paiement')->values();
        
        $paiementsRecents = $tousPaiements->take(5);

        $engagementsEnCours = $membre->engagements()->whereIn('statut', ['en_cours', 'en_retard'])->orderBy('periode_fin', 'asc')->get();
        foreach ($engagementsEnCours as $e) {
            $e->checkAndUpdateStatut();
        }

        $epargnesActives = $membre->epargneSouscriptions()->where('statut', 'active')->count();
        $annonces = Annonce::active()->orderBy('ordre')->orderBy('created_at')->get();

        return view('membres.cotisations', compact(
            'membre', 'paiementsRecents', 'engagementsEnCours', 
            'epargnesActives', 'annonces', 'tousPaiements'
        ));
    }

    /**
     * Afficher le profil du membre
     */
    public function profil(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $segments = Segment::where('actif', true)->get();
        
        // Stats parrainage pour la vue profil
        $parrainageActif = (bool) \App\Models\ParrainageConfig::current()?->actif;
        $codeParrainage = $membre->getOrCreateCodeParrainage();
        $nbFilleuls = $membre->filleuls()->count();
        $commissionsDisponibles = $membre->totalCommissionsDisponibles();
        $commissionsTotales = $membre->totalCommissionsPayees();

        return view('membres.profil', compact(
            'membre', 'segments', 'parrainageActif', 
            'codeParrainage', 'nbFilleuls', 
            'commissionsDisponibles', 'commissionsTotales'
        ));
    }

    /**
     * Mettre à jour le profil du membre
     */
    public function updateProfil(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:membres,email,' . $membre->id,
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'pays' => 'nullable|string|max:100',
            'ville' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'secteur' => 'nullable|string|max:100',
            'sexe' => 'nullable|in:M,F',
            'segment_id' => 'nullable|exists:segments,id',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $membre->update($validated);

        return redirect()->route('membre.profil')->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Liste des cotisations du membre
     */
    public function cotisations(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $adhesions = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->with('cotisation')->get();
        return view('membres.cotisations', compact('membre', 'adhesions'));
    }

    public function cotisationsPubliques(Request $request)
    {
        $cotisations = Cotisation::where('actif', true)->where('visibilite', 'publique')->orderBy('nom')->paginate(15);
        return view('membres.cotisations-publiques', compact('cotisations'));
    }

    public function cotisationsPrivees(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $ids = CotisationAdhesion::where('membre_id', $membre->id)->where('statut', 'accepte')->pluck('cotisation_id');
        $cotisations = Cotisation::where('actif', true)->where('visibilite', 'privee')->whereIn('id', $ids)->orderBy('nom')->paginate(15);
        return view('membres.cotisations-privees', compact('cotisations'));
    }

    public function showCotisation(Request $request, $id)
    {
        $cotisation = Cotisation::with('caisse')->findOrFail($id);
        $membre = Auth::guard('membre')->user();
        $adhesion = CotisationAdhesion::where('membre_id', $membre->id)->where('cotisation_id', $cotisation->id)->first();
        
        // Paiements
        $paiements = Paiement::where('cotisation_id', $cotisation->id)
            ->where(function($q) use ($membre) {
                $q->where('membre_id', $membre->id)->orWhereNull('membre_id');
            })
            ->orderBy('date_paiement', 'desc')
            ->get();

        return view('membres.cotisation-show', compact('cotisation', 'adhesion', 'paiements'));
    }

    public function paiements(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $paiements = $membre->paiements()->with(['cotisation', 'caisse'])->orderBy('date_paiement', 'desc')->paginate(15);
        return view('membres.paiements', compact('paiements'));
    }

    public function engagements(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $engagements = $membre->engagements()->with('cotisation')->orderBy('periode_fin', 'desc')->paginate(15);
        return view('membres.engagements', compact('engagements'));
    }

    public function showEngagement(Request $request, $id)
    {
        $engagement = Auth::guard('membre')->user()->engagements()->with('cotisation')->findOrFail($id);
        return view('membres.engagement-show', compact('engagement'));
    }

    public function remboursements(Request $request)
    {
        $membre = Auth::guard('membre')->user();
        $remboursements = $membre->remboursements()->orderBy('created_at', 'desc')->paginate(15);
        return view('membres.remboursements', compact('remboursements'));
    }

    // --- Actions spécialisées (Redirection vers services dédiés si nécessaire)

    public function adhererCotisation(Request $request, Cotisation $cotisation)
    {
        // Logique simplifiée pour le web
        $membre = Auth::guard('membre')->user();
        $statut = $cotisation->isPublique() ? 'accepte' : 'en_attente';
        
        CotisationAdhesion::updateOrCreate(
            ['membre_id' => $membre->id, 'cotisation_id' => $cotisation->id],
            ['statut' => $statut]
        );

        return back()->with('success', $statut === 'accepte' ? 'Adhésion réussie.' : 'Demande d\'adhésion envoyée.');
    }

    public function paydunyaCallback(Request $request) { /* Implementation invisible ici, gérée en arrière plan */ }
    public function initierPaiementPayDunya(Request $request, $id) { /* Redirection vers service de paiement */ }
    public function initierPaiementEngagementPayDunya(Request $request, $id) { /* Redirection vers service de paiement */ }
}

<?php

namespace App\Http\Controllers;

use App\Models\CotisationVersementDemande;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CotisationVersementDemandeController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Liste des demandes de versement des fonds
     */
    public function index(Request $request)
    {
        $query = CotisationVersementDemande::with(['cotisation', 'demandeParMembre'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $demandes = $query->paginate(15);

        return view('cotisation-versement-demandes.index', compact('demandes'));
    }

    /**
     * Détails d'une demande
     */
    public function show(CotisationVersementDemande $demande)
    {
        $demande->load(['cotisation.caisse', 'demandeParMembre.compteCourant', 'traiteParUser']);
        return view('cotisation-versement-demandes.show', compact('demande'));
    }

    /**
     * Approuver une demande de versement
     */
    public function approve(CotisationVersementDemande $demande)
    {
        if ($demande->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        try {
            $result = $this->financeService->traiterDemandeVersementCotisation($demande, Auth::user());
            
            return redirect()->route('cotisation-versement-demandes.index')
                ->with('success', 'La demande de versement de ' . number_format($result['montant'], 0, ',', ' ') . ' XOF a été approuvée et traitée.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors du traitement : ' . $e->getMessage());
        }
    }

    /**
     * Rejeter une demande de versement
     */
    public function reject(Request $request, CotisationVersementDemande $demande)
    {
        if ($demande->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $request->validate([
            'commentaire' => 'required|string|max:500',
        ]);

        $demande->update([
            'statut' => 'rejete',
            'traite_par_user_id' => Auth::id(),
            'traite_le' => now(),
            'commentaire' => $request->commentaire,
        ]);

        return redirect()->route('cotisation-versement-demandes.index')
            ->with('success', 'La demande de versement a été rejetée.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\EpargneRetraitDemande;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpargneRetraitDemandeController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Liste des demandes de retrait de tontines
     */
    public function index(Request $request)
    {
        // Validation des filtres
        $request->validate([
            'statut' => 'nullable|string|in:en_attente,approuve,rejete',
        ]);

        $query = EpargneRetraitDemande::with(['souscription.plan', 'membre'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $demandes = $query->paginate(15);

        return view('epargne-retrait-demandes.index', compact('demandes'));
    }

    /**
     * Approuver une demande de retrait
     */
    public function approve(Request $request, EpargneRetraitDemande $demande)
    {
        if ($demande->statut !== 'en_attente') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        try {
            $viaPiSpi = $request->boolean('via_pispi');
            $result = $this->financeService->traiterDemandeRetraitEpargne($demande, Auth::user(), $viaPiSpi);
            
            $msg = 'La demande de retrait de ' . number_format($result['montant'], 0, ',', ' ') . ' XOF a été approuvée.';
            if ($viaPiSpi) $msg .= ' Le virement Pi-SPI a été effectué.';

            return redirect()->route('epargne-retrait-demandes.index')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors du traitement : ' . $e->getMessage());
        }
    }

    /**
     * Rejeter une demande de retrait
     */
    public function reject(Request $request, EpargneRetraitDemande $demande)
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

        return redirect()->route('epargne-retrait-demandes.index')
            ->with('success', 'La demande de retrait a été rejetée.');
    }
}

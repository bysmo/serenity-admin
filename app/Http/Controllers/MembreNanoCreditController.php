<?php

namespace App\Http\Controllers;

use App\Models\NanoCredit;
use App\Models\NanoCreditPalier;
use App\Models\User;
use App\Notifications\NanoCreditDemandeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembreNanoCreditController extends Controller
{
    /**
     * Liste des types de nano crédit disponibles + lien vers Mes nano crédits
     */
    public function index()
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.kyc.index')
                ->with('info', 'Vous devez soumettre votre dossier KYC et qu\'il soit validé avant de pouvoir faire une demande de nano crédit.');
        }

        $palier = $membre->nanoCreditPalier;
        
        // Si le membre n'a pas de palier (ne devrait pas arriver si KYC validé), on lui assigne le 1
        if (!$palier) {
            app(\App\Services\NanoCreditPalierService::class)->assignerPalierInitial($membre);
            $membre->refresh();
            $palier = $membre->nanoCreditPalier;
        }

        return view('membres.nano-credits.index', compact('membre', 'palier'));
    }

    /**
     * Mes nano crédits (demandes et crédits octroyés)
     */
    public function mes()
    {
        $membre = Auth::guard('membre')->user();
        $nanoCredits = $membre->nanoCredits()
            ->with('palier')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('membres.nano-credits.mes', compact('membre', 'nanoCredits'));
    }

    /**
     * Formulaire de demande (souscription) pour un type donné — le membre ne choisit que le montant.
     * Le contact et le canal sont récupérés du profil du membre lors de l'octroi par l'admin.
     */
    public function demander()
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.nano-credits')->with('error', 'KYC requis.');
        }

        $palier = $membre->nanoCreditPalier;
        if (!$palier) {
            return redirect()->route('membre.nano-credits')->with('error', 'Aucun palier de crédit assigné.');
        }

        if ($membre->hasCreditEnCours()) {
            return redirect()->route('membre.nano-credits')->with('error', 'Vous avez déjà un crédit en cours non soldé. Veuillez le rembourser avant d\'en prendre un nouveau.');
        }

        return view('membres.nano-credits.demander', compact('membre', 'palier'));
    }

    /**
     * Enregistrer la demande de nano crédit
     */
    public function storeDemande(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.nano-credits')->with('error', 'KYC requis.');
        }

        $palier = $membre->nanoCreditPalier;
        if (!$palier) {
            return redirect()->route('membre.nano-credits')->with('error', 'Aucun palier assigné.');
        }

        if ($membre->hasCreditEnCours()) {
            return redirect()->route('membre.nano-credits')->with('error', 'Vous avez déjà un crédit en cours non soldé. Veuillez le rembourser avant d\'en prendre un nouveau.');
        }

        $montantMax = (float) $palier->montant_plafond;

        $validated = $request->validate([
            'montant' => 'required|numeric|min:1000|max:' . $montantMax,
        ], [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant minimum est 1 000 XOF.',
            'montant.max' => 'Le montant maximum pour votre palier actuel est ' . number_format($montantMax, 0, ',', ' ') . ' XOF.',
        ]);

        $nanoCredit = NanoCredit::create([
            'palier_id' => $palier->id,
            'membre_id' => $membre->id,
            'montant' => (int) round((float) $validated['montant'], 0),
            'telephone' => null,
            'withdraw_mode' => null,
            'statut' => 'demande_en_attente',
        ]);

        $nanoCredit->load(['membre', 'palier']);
        $admins = User::whereHas('roles', function ($q) {
            $q->where('slug', 'admin')->where('actif', true);
        })->get();
        foreach ($admins as $admin) {
            $admin->notify(new NanoCreditDemandeNotification($nanoCredit));
        }

        return redirect()->route('membre.nano-credits.mes')
            ->with('success', 'Votre demande de nano crédit a été enregistrée. L\'administration l\'étudiera et vous octroiera le crédit si accordé.');
    }

    /**
     * Détail d'un nano crédit : tableau d'amortissement + historique des remboursements
     */
    public function show(NanoCredit $nanoCredit)
    {
        $membre = Auth::guard('membre')->user();

        if ($nanoCredit->membre_id !== $membre->id) {
            abort(403);
        }

        $nanoCredit->load(['palier', 'echeances', 'versements']);
        return view('membres.nano-credits.show', compact('membre', 'nanoCredit'));
    }

    private function normalizePhone(string $telephone): string
    {
        $digits = preg_replace('/\D/', '', $telephone);
        $indicatifs = ['221', '229', '225', '228', '223', '226'];
        foreach ($indicatifs as $code) {
            if (str_starts_with($digits, $code) && strlen($digits) > strlen($code)) {
                return substr($digits, strlen($code));
            }
        }
        return $digits;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NanoCredit;
use App\Models\NanoCreditType;
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

        $types = NanoCreditType::where('actif', true)
            ->orderBy('ordre')
            ->orderBy('nom')
            ->get();

        return view('membres.nano-credits.index', compact('membre', 'types'));
    }

    /**
     * Mes nano crédits (demandes et crédits octroyés)
     */
    public function mes()
    {
        $membre = Auth::guard('membre')->user();
        $nanoCredits = $membre->nanoCredits()
            ->with('nanoCreditType')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('membres.nano-credits.mes', compact('membre', 'nanoCredits'));
    }

    /**
     * Formulaire de demande (souscription) pour un type donné — le membre ne choisit que le montant.
     * Le contact et le canal sont récupérés du profil du membre lors de l'octroi par l'admin.
     */
    public function demander(NanoCreditType $nano_credit_type)
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.nano-credits')->with('error', 'KYC requis.');
        }

        if (!$nano_credit_type->actif) {
            return redirect()->route('membre.nano-credits')->with('error', 'Ce type n\'est plus disponible.');
        }

        if ($membre->hasCreditEnCours()) {
            return redirect()->route('membre.nano-credits')->with('error', 'Vous avez déjà un crédit en cours non soldé. Veuillez le rembourser avant d\'en prendre un nouveau.');
        }

        $type = $nano_credit_type;
        return view('membres.nano-credits.demander', compact('membre', 'type'));
    }

    /**
     * Enregistrer la demande de nano crédit
     */
    public function storeDemande(Request $request, NanoCreditType $nano_credit_type)
    {
        $membre = Auth::guard('membre')->user();

        if (!$membre->hasKycValide()) {
            return redirect()->route('membre.nano-credits')->with('error', 'KYC requis.');
        }

        if (!$nano_credit_type->actif) {
            return redirect()->route('membre.nano-credits')->with('error', 'Ce type n\'est plus disponible.');
        }

        if ($membre->hasCreditEnCours()) {
            return redirect()->route('membre.nano-credits')->with('error', 'Vous avez déjà un crédit en cours non soldé. Veuillez le rembourser avant d\'en prendre un nouveau.');
        }

        $montantMin = (float) $nano_credit_type->montant_min;
        $montantMax = $nano_credit_type->montant_max ? (float) $nano_credit_type->montant_max : null;

        $validated = $request->validate([
            'montant' => 'required|numeric|min:' . $montantMin,
        ], [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant minimum pour ce type est ' . number_format($montantMin, 0, ',', ' ') . ' XOF.',
        ]);

        if ($montantMax && (float) $validated['montant'] > $montantMax) {
            return redirect()->back()->withInput()->with('error', 'Le montant maximum pour ce type est ' . number_format($montantMax, 0, ',', ' ') . ' XOF.');
        }

        $nanoCredit = NanoCredit::create([
            'nano_credit_type_id' => $nano_credit_type->id,
            'membre_id' => $membre->id,
            'montant' => (int) round((float) $validated['montant'], 0),
            'telephone' => null,
            'withdraw_mode' => null,
            'statut' => 'demande_en_attente',
        ]);

        $nanoCredit->load(['membre', 'nanoCreditType']);
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

        $nanoCredit->load(['nanoCreditType', 'echeances', 'versements']);
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

<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use App\Notifications\KycValidatedNotification;
use App\Services\NanoCreditPalierService;
use Illuminate\Http\Request;

class KycController extends Controller
{
    /**
     * Liste des KYC (filtre par statut, recherche)
     */
    public function index(Request $request)
    {
        $query = KycVerification::with(['membre', 'validatedByUser', 'rejectedByUser']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('membre', function ($q) use ($term) {
                $q->where('nom', 'like', "%{$term}%")
                    ->orWhere('prenom', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('numero', 'like', "%{$term}%");
            });
        }

        $kycs = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('kyc.index', compact('kycs'));
    }

    /**
     * Détail d'un KYC
     */
    public function show(KycVerification $kyc)
    {
        $kyc->load(['membre', 'documents', 'validatedByUser', 'rejectedByUser']);
        return view('kyc.show', compact('kyc'));
    }

    /**
     * Valider un KYC
     */
    public function validateKyc(KycVerification $kyc)
    {
        if (!$kyc->isEnAttente()) {
            return redirect()->route('kyc.index')
                ->with('error', 'Ce KYC n\'est pas en attente de validation.');
        }

        $kyc->update([
            'statut' => KycVerification::STATUT_VALIDE,
            'validated_at' => now(),
            'validated_by' => auth()->id(),
            'motif_rejet' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        $kyc->load('membre');
        $kyc->membre->notify(new KycValidatedNotification($kyc));

        // ── Assigner automatiquement le Palier 1 au membre ──────────────────
        app(NanoCreditPalierService::class)->assignerPalierInitial($kyc->membre);

        return redirect()->route('kyc.index')
            ->with('success', 'Le KYC du membre ' . $kyc->membre->nom_complet . ' a été validé. Un email et une notification ont été envoyés au membre.');
    }

    /**
     * Rejeter un KYC (avec motif obligatoire)
     */
    public function reject(Request $request, KycVerification $kyc)
    {
        $request->validate([
            'motif_rejet' => 'required|string|max:1000',
        ], [
            'motif_rejet.required' => 'Le motif du rejet est obligatoire.',
        ]);

        if (!$kyc->isEnAttente()) {
            return redirect()->route('kyc.index')
                ->with('error', 'Ce KYC n\'est pas en attente de validation.');
        }

        $kyc->update([
            'statut' => KycVerification::STATUT_REJETE,
            'motif_rejet' => $request->motif_rejet,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'validated_at' => null,
            'validated_by' => null,
        ]);

        return redirect()->route('kyc.index')
            ->with('success', 'Le KYC du membre ' . $kyc->membre->nom_complet . ' a été rejeté. Le membre pourra soumettre à nouveau son dossier.');
    }
}

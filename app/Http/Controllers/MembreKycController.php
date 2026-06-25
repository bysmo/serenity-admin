<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MembreKycController extends Controller
{
    /**
     * Afficher la page KYC du membre (formulaire ou statut)
     */
    public function index()
    {
        $membre = Auth::guard('membre')->user();
        $kyc = $membre->kycVerification;

        return view('membres.kyc.index', compact('membre', 'kyc'));
    }

    /**
     * Soumettre ou mettre à jour le KYC
     */
    public function store(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        $validated = $request->validate([
            'type_piece' => 'required|string|in:cni,passeport,permis',
            'numero_piece' => 'required|string|max:100',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'adresse_kyc' => 'required|string|max:500',
            'metier' => 'nullable|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'contact_1' => 'nullable|string|max:50',
            'contact_2' => 'nullable|string|max:50',
            'piece_identite_recto' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'piece_identite_verso' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo_identite' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'justificatif_domicile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'type_piece.required' => 'Le type de pièce est requis.',
            'piece_identite_recto.required' => 'Le recto de la pièce d\'identité est requis.',
            'piece_identite_verso.required' => 'Le verso de la pièce d\'identité est requis.',
            'photo_identite.required' => 'La photo d\'identité est requise.',
            'justificatif_domicile.required' => 'Le justificatif de domicile est requis.',
        ]);

        $kyc = $membre->kycVerification;

        if ($kyc && $kyc->isRejete()) {
            // Réinitialiser pour une nouvelle soumission
            foreach ($kyc->documents as $doc) {
                if (Storage::disk('public')->exists($doc->path)) {
                    Storage::disk('public')->delete($doc->path);
                }
            }
            $kyc->documents()->delete();
            $kyc->update([
                'statut' => KycVerification::STATUT_EN_ATTENTE,
                'motif_rejet' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'type_piece' => $validated['type_piece'],
                'numero_piece' => $validated['numero_piece'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'adresse_kyc' => $validated['adresse_kyc'],
                'metier' => $validated['metier'] ?? null,
                'localisation' => $validated['localisation'] ?? null,
                'contact_1' => $validated['contact_1'] ?? null,
                'contact_2' => $validated['contact_2'] ?? null,
            ]);
        } else {
            if ($kyc && $kyc->isEnAttente()) {
                return redirect()->route('membre.kyc.index')
                    ->with('info', 'Votre KYC est déjà en cours d\'examen.');
            }
            if ($kyc && $kyc->isValide()) {
                return redirect()->route('membre.kyc.index')
                    ->with('info', 'Votre KYC est déjà validé.');
            }

            $kyc = KycVerification::create([
                'membre_id' => $membre->id,
                'statut' => KycVerification::STATUT_EN_ATTENTE,
                'type_piece' => $validated['type_piece'],
                'numero_piece' => $validated['numero_piece'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'adresse_kyc' => $validated['adresse_kyc'],
                'metier' => $validated['metier'] ?? null,
                'localisation' => $validated['localisation'] ?? null,
                'contact_1' => $validated['contact_1'] ?? null,
                'contact_2' => $validated['contact_2'] ?? null,
            ]);
        }

        $basePath = 'kyc_documents/' . $kyc->id;

        $documentInputs = [
            'piece_identite_recto' => KycDocument::TYPE_PIECE_IDENTITE_RECTO,
            'piece_identite_verso' => KycDocument::TYPE_PIECE_IDENTITE_VERSO,
            'photo_identite' => KycDocument::TYPE_PHOTO_IDENTITE,
            'justificatif_domicile' => KycDocument::TYPE_JUSTIFICATIF_DOMICILE,
        ];
        foreach ($documentInputs as $inputKey => $type) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $path = $file->store($basePath, 'public');
                KycDocument::create([
                    'kyc_verification_id' => $kyc->id,
                    'type' => $type,
                    'path' => $path,
                    'nom_original' => $file->getClientOriginalName(),
                ]);
            }
        }

        return redirect()->route('membre.kyc.index')
            ->with('success', 'Votre KYC a été soumis avec succès. Vous serez notifié après examen par l\'administration.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompteExterne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Gestion des Comptes Externes du membre connecté.
 *
 * Un compte externe identifie un compte tiers pour les paiements :
 *   - alias   : UUID Pi-SPI
 *   - telephone: numéro E.164 (ex: +22670000000)
 *   - iban    : IBAN bancaire
 */
class CompteExterneApiController extends Controller
{
    // ─── Liste ────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $membre = $request->user();

        $comptes = CompteExterne::where('membre_id', $membre->id)
            ->orderByDesc('is_default')
            ->orderBy('nom')
            ->get()
            ->map(fn ($c) => $this->formatCompte($c));

        return response()->json(['data' => $comptes]);
    }

    // ─── Créer ────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $membre = $request->user();

        // Première validation : champs généraux
        $request->validate([
            'nom'              => 'required|string|max:100',
            'description'      => 'nullable|string|max:500',
            'pays'             => 'nullable|string|size:2',
            'type_identifiant' => ['required', Rule::in(['alias', 'telephone', 'iban'])],
            'identifiant'      => 'required|string|max:255',
        ], [
            'nom.required'              => 'Le nom du compte est obligatoire.',
            'type_identifiant.required' => 'Le type d\'identifiant est obligatoire.',
            'type_identifiant.in'       => 'Type invalide. Valeurs : alias, telephone, iban.',
            'identifiant.required'      => 'L\'identifiant est obligatoire.',
        ]);

        // Validation spécifique selon le type
        $this->validateIdentifiant($request);

        // Unicité : un même identifiant ne peut pas être enregistré deux fois par le même membre
        $alreadyExists = CompteExterne::where('membre_id', $membre->id)
            ->where('type_identifiant', $request->type_identifiant)
            ->where('identifiant', $request->identifiant)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'Ce compte externe est déjà enregistré.',
                'errors'  => ['identifiant' => ['Identifiant déjà utilisé pour ce type.']],
            ], 422);
        }

        $isFirst = CompteExterne::where('membre_id', $membre->id)->count() === 0;

        $compte = CompteExterne::create([
            'membre_id'        => $membre->id,
            'nom'              => $request->nom,
            'description'      => $request->description,
            'pays'             => $request->pays ? strtoupper($request->pays) : null,
            'type_identifiant' => $request->type_identifiant,
            'identifiant'      => $request->identifiant,
            'is_default'       => $isFirst,
        ]);

        Log::info('Compte externe créé', [
            'membre_id'        => $membre->id,
            'compte_id'        => $compte->id,
            'type_identifiant' => $compte->type_identifiant,
        ]);

        return response()->json([
            'message' => 'Compte externe créé avec succès.',
            'compte'  => $this->formatCompte($compte),
        ], 201);
    }

    // ─── Afficher ─────────────────────────────────────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $compte = $this->findOwnedCompte($request, $id);
        return response()->json(['compte' => $this->formatCompte($compte)]);
    }

    // ─── Modifier ─────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $membre = $request->user();
        $compte = $this->findOwnedCompte($request, $id);

        $request->validate([
            'nom'         => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'pays'        => 'nullable|string|size:2',
        ]);

        // On ne permet pas de changer le type/identifiant après création (sécurité)
        $compte->update([
            'nom'         => $request->input('nom', $compte->nom),
            'description' => $request->input('description', $compte->description),
            'pays'        => $request->filled('pays') ? strtoupper($request->pays) : $compte->pays,
        ]);

        Log::info('Compte externe modifié', ['membre_id' => $membre->id, 'compte_id' => $compte->id]);

        return response()->json([
            'message' => 'Compte externe mis à jour.',
            'compte'  => $this->formatCompte($compte->fresh()),
        ]);
    }

    // ─── Définir par défaut ───────────────────────────────────────────────────

    public function setDefault(Request $request, int $id): JsonResponse
    {
        $membre = $request->user();
        $compte = $this->findOwnedCompte($request, $id);

        // Réinitialiser tous les comptes du membre
        CompteExterne::where('membre_id', $membre->id)->update(['is_default' => false]);
        $compte->update(['is_default' => true]);

        Log::info('Compte externe défini par défaut', ['membre_id' => $membre->id, 'compte_id' => $compte->id]);

        return response()->json([
            'message' => 'Compte par défaut mis à jour.',
            'compte'  => $this->formatCompte($compte->fresh()),
        ]);
    }

    // ─── Supprimer ────────────────────────────────────────────────────────────

    public function destroy(Request $request, int $id): JsonResponse
    {
        $membre = $request->user();
        $compte = $this->findOwnedCompte($request, $id);

        // Si c'était le compte par défaut, on assigne le suivant
        $wasDefault = $compte->is_default;
        $compte->delete();

        if ($wasDefault) {
            $next = CompteExterne::where('membre_id', $membre->id)->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        Log::info('Compte externe supprimé', ['membre_id' => $membre->id, 'compte_id' => $id]);

        return response()->json(['message' => 'Compte externe supprimé.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function findOwnedCompte(Request $request, int $id): CompteExterne
    {
        $compte = CompteExterne::findOrFail($id);

        if ((int) $compte->membre_id !== (int) $request->user()->id) {
            abort(403, 'Accès refusé.');
        }

        return $compte;
    }

    private function formatCompte(CompteExterne $compte): array
    {
        return [
            'id'                  => $compte->id,
            'nom'                 => $compte->nom,
            'description'         => $compte->description,
            'pays'                => $compte->pays,
            'type_identifiant'    => $compte->type_identifiant,
            'type_label'          => $compte->type_label,
            'identifiant'         => $compte->identifiant,
            'identifiant_masque'  => $compte->identifiant_masque,
            'is_default'          => $compte->is_default,
            'supporte_pispi'      => $compte->supportePiSpi(),
            'created_at'          => $compte->created_at?->toIso8601String(),
        ];
    }

    /**
     * Valide le format de l'identifiant selon le type_identifiant soumis.
     */
    private function validateIdentifiant(Request $request): void
    {
        $type       = $request->input('type_identifiant');
        $identifiant = $request->input('identifiant');

        $rules = [
            'alias'     => 'uuid',
            'telephone' => 'regex:/^\+[1-9]\d{7,14}$/',
            'iban'      => 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/',
        ];

        $messages = [
            'alias'     => 'L\'alias doit être un UUID valide (ex: 550e8400-e29b-41d4-a716-446655440000).',
            'telephone' => 'Le numéro doit être au format international E.164 (ex: +22670000000).',
            'iban'      => 'L\'IBAN doit être valide (ex: FR7630006000011234567890189).',
        ];

        $request->validate(
            ['identifiant' => $rules[$type] ?? 'string'],
            ['identifiant.' . explode(':', $rules[$type] ?? 'string')[0] => $messages[$type] ?? 'Identifiant invalide.']
        );

        // Validation IBAN checksum avancée
        if ($type === 'iban' && !CompteExterne::validateIdentifiant('iban', strtoupper(str_replace(' ', '', $identifiant)))) {
            abort(response()->json([
                'message' => 'Validation échouée.',
                'errors'  => ['identifiant' => ['L\'IBAN fourni n\'est pas valide (checksum incorrect).']],
            ], 422));
        }
    }
}

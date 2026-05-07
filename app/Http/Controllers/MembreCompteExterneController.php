<?php

namespace App\Http\Controllers;

use App\Models\CompteExterne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Gestion des Comptes Externes du membre (portail web).
 */
class MembreCompteExterneController extends Controller
{
    public function index()
    {
        $membre  = Auth::guard('membre')->user();
        $comptesExternes = $membre->comptesExternes()->orderByDesc('is_default')->orderBy('nom')->get();

        return view('membres.comptes-externes.index', compact('comptesExternes'));
    }

    public function store(Request $request)
    {
        $membre = Auth::guard('membre')->user();

        $request->validate([
            'nom'               => 'required|string|max:100',
            'description'       => 'nullable|string|max:500',
            'pays'              => 'nullable|string|max:5',
            'type_identifiant'  => ['required', Rule::in(['alias', 'telephone', 'iban'])],
            'valeur_identifiant'=> ['required', 'string', 'max:255', $this->identifiantRule($request->type_identifiant)],
        ], $this->validationMessages());

        $alreadyExists = CompteExterne::where('membre_id', $membre->id)
            ->where('type_identifiant', $request->type_identifiant)
            ->where('identifiant', $request->valeur_identifiant)
            ->exists();

        if ($alreadyExists) {
            return back()->withErrors(['valeur_identifiant' => 'Ce compte est déjà enregistré.'])->withInput();
        }

        $isFirst = $membre->comptesExternes()->count() === 0;

        $membre->comptesExternes()->create([
            'nom'              => $request->nom,
            'description'      => $request->description,
            'pays'             => $request->pays ? strtoupper($request->pays) : null,
            'type_identifiant' => $request->type_identifiant,
            'identifiant'      => $request->valeur_identifiant,
            'is_default'       => $isFirst || $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Compte externe ajouté avec succès.');
    }

    public function update(Request $request, CompteExterne $compteExterne)
    {
        $membre = Auth::guard('membre')->user();
        if ($compteExterne->membre_id !== $membre->id) abort(403);

        $request->validate([
            'nom'         => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'pays'        => 'nullable|string|size:2',
        ]);

        $compteExterne->update([
            'nom'         => $request->nom,
            'description' => $request->description,
            'pays'        => $request->pays ? strtoupper($request->pays) : $compteExterne->pays,
        ]);

        return back()->with('success', 'Compte externe mis à jour.');
    }

    public function setDefault(CompteExterne $compteExterne)
    {
        $membre = Auth::guard('membre')->user();
        if ($compteExterne->membre_id !== $membre->id) abort(403);

        $membre->comptesExternes()->update(['is_default' => false]);
        $compteExterne->update(['is_default' => true]);

        return back()->with('success', 'Compte par défaut mis à jour.');
    }

    public function destroy(CompteExterne $compteExterne)
    {
        $membre = Auth::guard('membre')->user();
        if ($compteExterne->membre_id !== $membre->id) abort(403);

        $wasDefault = $compteExterne->is_default;
        $compteExterne->delete();

        if ($wasDefault) {
            $next = $membre->comptesExternes()->first();
            if ($next) $next->update(['is_default' => true]);
        }

        return back()->with('success', 'Compte externe supprimé.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function identifiantRule(?string $type): string
    {
        return match ($type) {
            'alias'     => 'uuid',
            'telephone' => 'regex:/^\+[1-9]\d{7,14}$/',
            'iban'      => 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/i',
            default     => 'string',
        };
    }

    private function validationMessages(): array
    {
        return [
            'nom.required'                   => 'Le nom du compte est obligatoire.',
            'type_identifiant.required'      => "Le type d'identifiant est obligatoire.",
            'type_identifiant.in'            => 'Type invalide.',
            'valeur_identifiant.required'    => "L'identifiant est obligatoire.",
            'valeur_identifiant.uuid'        => "L'alias doit être un UUID valide (format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).",
            'valeur_identifiant.regex'       => 'Format invalide pour ce type d\'identifiant.',
        ];
    }
}

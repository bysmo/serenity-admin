<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('users.index', compact('users'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $roles = Role::where('actif', true)->orderBy('nom')->get();
        return view('users.create', compact('roles'));
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'alias' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Attribuer le rôle
        if (!empty($validated['role_id'])) {
            $user->roles()->attach($validated['role_id']);
            
            // Si c'est un collecteur, créer son compte
            $role = Role::find($validated['role_id']);
            if ($role && $role->slug === 'collecteur') {
                \App\Models\Caisse::create([
                    'user_id' => $user->id,
                    'type' => 'collecteur',
                    'nom' => "Compte Collecteur - {$user->name}",
                    'numero' => \App\Models\Caisse::generateNumeroCompte(),
                    'alias' => $validated['alias'] ?? null,
                    'solde_initial' => 0,
                    'statut' => 'active',
                ]);
            }
        }

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'model' => 'User',
            'model_id' => $user->id,
            'new_values' => $user->toArray(),
            'description' => "Utilisateur '{$user->name}' créé",
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec succès');
    }

    /**
     * Afficher les détails d'un utilisateur
     */
    public function show(User $user)
    {
        $user->load('roles');
        return view('users.show', compact('user'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        $roles = Role::where('actif', true)->orderBy('nom')->get();
        $user->load('roles');
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'alias' => 'nullable|string|max:255',
        ]);

        $oldValues = $user->toArray();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Mettre à jour le mot de passe si fourni
        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Synchroniser le rôle (un seul rôle)
        if (!empty($validated['role_id'])) {
            // Retirer tous les rôles existants et ajouter le nouveau
            $user->roles()->sync([$validated['role_id']]);
            
            // Gérer le compte collecteur
            $role = Role::find($validated['role_id']);
            if ($role && $role->slug === 'collecteur') {
                $account = $user->collectorAccount;
                if ($account) {
                    $account->update(['alias' => $validated['alias'] ?? $account->alias]);
                } else {
                    \App\Models\Caisse::create([
                        'user_id' => $user->id,
                        'type' => 'collecteur',
                        'nom' => "Compte Collecteur - {$user->name}",
                        'numero' => \App\Models\Caisse::generateNumeroCompte(),
                        'alias' => $validated['alias'] ?? null,
                        'solde_initial' => 0,
                        'statut' => 'active',
                    ]);
                }
            }
        } else {
            // Si aucun rôle n'est sélectionné, retirer tous les rôles
            $user->roles()->sync([]);
        }

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'model' => 'User',
            'model_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $user->fresh()->toArray(),
            'description' => "Utilisateur '{$user->name}' modifié",
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur modifié avec succès');
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        $userName = $user->name;
        $oldValues = $user->toArray();

        $user->delete();

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'model' => 'User',
            'model_id' => $user->id,
            'old_values' => $oldValues,
            'description' => "Utilisateur '{$userName}' supprimé",
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès');
    }
}

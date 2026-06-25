<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Afficher la liste des rôles
     */
    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->orderBy('nom')->get();
        return view('roles.index', compact('roles'));
    }

    /**
     * Afficher le formulaire de création d'un rôle
     */
    public function create()
    {
        // Grouper les permissions par catégorie
        $permissions = Permission::orderBy('categorie')->orderBy('nom')->get();
        $permissionsByCategory = $permissions->groupBy('categorie');
        
        return view('roles.create', compact('permissionsByCategory', 'permissions'));
    }

    /**
     * Enregistrer un nouveau rôle
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom',
            'description' => 'nullable|string|max:2000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'actif' => 'boolean',
        ]);

        $role = Role::create([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'] ?? null,
            'actif' => $request->has('actif') ? true : false,
        ]);

        // Attacher les permissions
        if (!empty($validated['permissions'])) {
            $role->permissions()->attach($validated['permissions']);
        }

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'model' => 'Role',
            'model_id' => $role->id,
            'new_values' => $role->toArray(),
            'description' => "Rôle '{$role->nom}' créé",
        ]);

        return redirect()->route('roles.index')
            ->with('success', 'Rôle créé avec succès');
    }

    /**
     * Afficher les détails d'un rôle
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);
        return view('roles.show', compact('role'));
    }

    /**
     * Afficher le formulaire d'édition d'un rôle
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('categorie')->orderBy('nom')->get();
        $permissionsByCategory = $permissions->groupBy('categorie');
        $role->load('permissions');
        
        return view('roles.edit', compact('role', 'permissionsByCategory', 'permissions'));
    }

    /**
     * Mettre à jour un rôle
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom,' . $role->id,
            'description' => 'nullable|string|max:2000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'actif' => 'boolean',
        ]);

        $oldValues = $role->toArray();

        $role->update([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'] ?? null,
            'actif' => $request->has('actif') ? true : false,
        ]);

        // Synchroniser les permissions
        $role->permissions()->sync($validated['permissions'] ?? []);

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'model' => 'Role',
            'model_id' => $role->id,
            'old_values' => $oldValues,
            'new_values' => $role->fresh()->toArray(),
            'description' => "Rôle '{$role->nom}' modifié",
        ]);

        return redirect()->route('roles.index')
            ->with('success', 'Rôle modifié avec succès');
    }

    /**
     * Supprimer un rôle
     */
    public function destroy(Role $role)
    {
        $roleName = $role->nom;
        $oldValues = $role->toArray();

        $role->delete();

        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'model' => 'Role',
            'model_id' => $role->id,
            'old_values' => $oldValues,
            'description' => "Rôle '{$roleName}' supprimé",
        ]);

        return redirect()->route('roles.index')
            ->with('success', 'Rôle supprimé avec succès');
    }

    /**
     * Afficher la page d'affectation de rôles aux utilisateurs
     */
    public function assignUsers()
    {
        $users = User::with('roles')->get();
        $roles = Role::where('actif', true)->orderBy('nom')->get();
        
        return view('roles.assign-users', compact('users', 'roles'));
    }

    /**
     * Affecter un rôle à un utilisateur
     */
    public function assignRoleToUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (!$user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->attach($role->id);

            // Enregistrer dans le journal d'audit
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'assign_role',
                'model' => 'User',
                'model_id' => $user->id,
                'new_values' => ['role_id' => $role->id, 'role_nom' => $role->nom],
                'description' => "Rôle '{$role->nom}' affecté à l'utilisateur {$user->name}",
            ]);

            return redirect()->back()
                ->with('success', "Rôle '{$role->nom}' affecté avec succès");
        }

        return redirect()->back()
            ->with('error', "L'utilisateur possède déjà ce rôle");
    }

    /**
     * Retirer un rôle d'un utilisateur
     */
    public function removeRoleFromUser(Request $request, User $user, Role $role)
    {
        if ($user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->detach($role->id);

            // Enregistrer dans le journal d'audit
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'remove_role',
                'model' => 'User',
                'model_id' => $user->id,
                'old_values' => ['role_id' => $role->id, 'role_nom' => $role->nom],
                'description' => "Rôle '{$role->nom}' retiré de l'utilisateur {$user->name}",
            ]);

            return redirect()->back()
                ->with('success', "Rôle '{$role->nom}' retiré avec succès");
        }

        return redirect()->back()
            ->with('error', "L'utilisateur ne possède pas ce rôle");
    }

    /**
     * Attribuer toutes les permissions au rôle administrateur
     */
    public function assignAllPermissionsToAdmin()
    {
        // Créer ou récupérer le rôle admin
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'nom' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'actif' => true,
            ]
        );
        
        // Récupérer toutes les permissions
        $allPermissions = Permission::all();
        
        if ($allPermissions->isEmpty()) {
            return redirect()->route('roles.index')
                ->with('error', 'Aucune permission trouvée dans la base de données ! Exécutez d\'abord: php artisan db:seed --class=PermissionSeeder');
        }
        
        // Supprimer toutes les permissions existantes du rôle admin
        $adminRole->permissions()->detach();
        
        // Attribuer toutes les permissions au rôle admin
        $adminRole->permissions()->sync($allPermissions->pluck('id'));
        
        // S'assurer que l'utilisateur admin@ecotisations.com a le rôle admin
        $defaultAdmin = User::where('email', 'admin@ecotisations.com')->first();
        if ($defaultAdmin && !$defaultAdmin->roles()->where('slug', 'admin')->exists()) {
            $defaultAdmin->roles()->attach($adminRole->id);
        }
        
        // Enregistrer dans le journal d'audit
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'model' => 'Role',
            'model_id' => $adminRole->id,
            'new_values' => ['permissions_count' => $allPermissions->count()],
            'description' => "Toutes les permissions ({$allPermissions->count()}) attribuées au rôle Administrateur",
        ]);
        
        return redirect()->route('roles.index')
            ->with('success', "✓ Toutes les permissions ({$allPermissions->count()}) ont été attribuées au rôle Administrateur. Le rôle administrateur a maintenant {$adminRole->permissions()->count()} permission(s).");
    }
}

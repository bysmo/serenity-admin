<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation avec les rôles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * Vérifier si l'utilisateur a un rôle
     */
    public function hasRole($roleSlug)
    {
        return $this->roles()->where('slug', $roleSlug)->where('actif', true)->exists();
    }

    /**
     * Vérifier si l'utilisateur a une permission
     */
    public function hasPermission($permissionSlug)
    {
        // Les administrateurs ont toutes les permissions par défaut
        if ($this->hasRole('admin')) {
            return true;
        }
        
        return $this->roles()
            ->where('actif', true)
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Obtenir toutes les permissions de l'utilisateur
     */
    public function getPermissions()
    {
        return \App\Models\Permission::whereHas('roles', function ($query) {
            $query->whereIn('id', $this->roles()->where('actif', true)->pluck('id'));
        })->get();
    }

    /**
     * Relation avec les logs d'audit
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Relation avec le compte collecteur (Caisse)
     */
    public function collectorAccount()
    {
        return $this->hasOne(Caisse::class, 'user_id')->where('type', 'collecteur');
    }

    /**
     * Relation avec les sessions de collecte
     */
    public function collecteSessions()
    {
        return $this->hasMany(CollecteSession::class);
    }
}

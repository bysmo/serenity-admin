<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'actor_type',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Relation polymorphique avec l'acteur (User, Membre, etc.)
     */
    public function actor()
    {
        return $this->morphTo();
    }

    /**
     * Accessor pour obtenir le nom de l'acteur (Admin ou Membre)
     */
    public function getActorNameAttribute()
    {
        if (!$this->actor) {
            return 'Système';
        }

        if ($this->actor_type === \App\Models\User::class) {
            return $this->actor->name;
        }

        if ($this->actor_type === \App\Models\Membre::class) {
            return $this->actor->nom . ' ' . $this->actor->prenom;
        }

        return 'Inconnu';
    }

    /**
     * Scope pour filtrer par action
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour filtrer par modèle
     */
    public function scopeModel($query, $model)
    {
        return $query->where('model', $model);
    }

    /**
     * Scope pour filtrer par acteur
     */
    public function scopeActor($query, $actorId, $actorType)
    {
        return $query->where('actor_id', $actorId)->where('actor_type', $actorType);
    }
}

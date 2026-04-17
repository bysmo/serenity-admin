<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMTPConfiguration extends Model
{
    use HasFactory;

    protected $table = 'smtp_configurations';

    protected $fillable = [
        'nom',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'actif',
    ];

    protected $casts = [
        'port' => 'integer',
        'actif' => 'boolean',
        'password' => 'encrypted',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Vérifier si la configuration est active
     */
    public function isActive()
    {
        return $this->actif;
    }
}

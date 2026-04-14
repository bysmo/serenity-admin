<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiSpiConfiguration extends Model
{
    protected $table = 'pispi_configurations';

    protected $fillable = [
        'client_id',
        'client_secret',
        'api_key',
        'paye_alias',
        'mode',
        'enabled',
        'webhook_secret',
        'token_cache_key',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Récupère la configuration active (Singleton)
     */
    public static function getActive()
    {
        return self::first();
    }
}

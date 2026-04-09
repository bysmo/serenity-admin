<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, \App\Traits\HasChecksum;

    protected $fillable = [
        'name',
        'code',
        'icon',
        'description',
        'enabled',
        'config',
        'order',
        'checksum',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Récupérer tous les moyens de paiement actifs
     */
    public static function getActive()
    {
        return self::where('enabled', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Récupérer un moyen de paiement par son code
     */
    public static function getByCode($code)
    {
        return self::where('code', $code)->first();
    }

    /**
     * Vérifier si le moyen de paiement est activé
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Récupérer une valeur de configuration
     */
    public function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Définir une valeur de configuration
     */
    public function setConfig($key, $value)
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
    }
}

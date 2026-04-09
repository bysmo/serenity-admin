<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory, \App\Traits\HasChecksum;

    protected $table = 'app_settings';

    protected $fillable = [
        'cle',
        'valeur',
        'type',
        'description',
        'groupe',
        'checksum',
    ];

    /**
     * Récupérer la valeur d'un paramètre
     */
    public static function get($cle, $default = null)
    {
        $setting = self::where('cle', $cle)->first();
        
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->valeur,
            'boolean' => filter_var($setting->valeur, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->valeur, true),
            default => $setting->valeur,
        };
    }

    /**
     * Définir la valeur d'un paramètre
     */
    public static function set($cle, $valeur, $type = 'string', $description = null, $groupe = 'general')
    {
        $formattedValue = match ($type) {
            'integer', 'boolean' => (string) $valeur,
            'json' => json_encode($valeur),
            default => $valeur,
        };

        return self::updateOrCreate(
            ['cle' => $cle],
            [
                'valeur' => $formattedValue,
                'type' => $type,
                'description' => $description,
                'groupe' => $groupe,
            ]
        );
    }
}

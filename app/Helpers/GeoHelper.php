<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class GeoHelper
{
    /**
     * Détermine le code pays (ISO 3166-1 alpha-2) à partir de l'IP du visiteur.
     * Utilise ip-api.com (gratuit, sans clé). En cas d'échec, retourne le pays par défaut.
     */
    public static function getCountryCodeFromIp(?string $default = null): string
    {
        $default = $default ?? \App\Models\AppSetting::get('default_country_code', 'BF');
        try {
            $response = Http::timeout(2)->get('http://ip-api.com/json/?fields=countryCode');
            if ($response->successful()) {
                $code = $response->json('countryCode');
                if (is_string($code) && strlen($code) === 2) {
                    return strtoupper($code);
                }
            }
        } catch (\Throwable $e) {
            // Ignorer (timeout, réseau, etc.)
        }
        return $default;
    }

    /**
     * Retourne l'indicatif téléphonique pour un code pays (ex: BF => 226).
     */
    public static function getDialCodeForCountry(string $countryCode): string
    {
        $countries = config('country_dial_codes', []);
        $countryCode = strtoupper($countryCode);
        return $countries[$countryCode]['dial'] ?? \App\Models\AppSetting::get('default_dial_code', '226');
    }
}

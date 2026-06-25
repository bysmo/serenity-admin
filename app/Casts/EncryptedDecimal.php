<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Cast pour chiffrer les montants en base et toujours retourner un float à la lecture.
 * Évite les erreurs "Unsupported operand types: int + string" dans Collection::sum()
 * et "number_format(): Argument #1 ($num) must be of type float, string given".
 */
class EncryptedDecimal implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        return self::decryptRaw($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        $numeric = is_numeric($value) ? (float) $value : $value;
        return Crypt::encrypt($numeric);
    }

    public static function decryptRaw(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        try {
            $decrypted = Crypt::decrypt($value);
            return (float) $decrypted;
        } catch (\Throwable $e) {
            return is_numeric($value) ? (float) $value : 0.0;
        }
    }
}

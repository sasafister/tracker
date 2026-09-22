<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * A money amount stored encrypted, read back as a two-decimal string
 * ("35.00") just like the decimal:2 cast it replaces.
 *
 * @implements CastsAttributes<string|null, mixed>
 */
class EncryptedDecimal implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::format(Crypt::decryptString($value));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(self::format($value));
    }

    public static function format(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}

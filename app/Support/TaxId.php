<?php

namespace App\Support;

/**
 * A company's tax number: a Croatian OIB (eleven digits), or a VAT ID that
 * starts with the country code, such as DE355361438 or HR12345678903.
 */
class TaxId
{
    /**
     * Spaces, dots and dashes are how people write these, not part of them.
     */
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $compacted = strtoupper(preg_replace('/[\s.\-]+/', '', $value));

        return $compacted === '' ? null : $compacted;
    }

    public static function isOib(string $value): bool
    {
        return preg_match('/^\d{11}$/', $value) === 1;
    }

    /**
     * How the number is introduced on a report.
     */
    public static function label(string $value): string
    {
        return self::isOib($value) ? 'OIB' : 'VAT ID';
    }

    /**
     * ISO 7064, MOD 11,10 — the check digit of an OIB.
     */
    public static function hasValidOibCheckDigit(string $digits): bool
    {
        $remainder = 10;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder + (int) $digits[$i]) % 10;
            $remainder = ($remainder === 0 ? 10 : $remainder) * 2 % 11;
        }

        return (11 - $remainder) % 10 === (int) $digits[10];
    }
}

<?php

namespace App\Support;

use NumberFormatter;

/**
 * The currencies a user can bill in, and money formatted the way the
 * browser formats it in the current language: "4.650,00 €", "€4,650.00".
 *
 * Keep the list in step with resources/js/currencies.js.
 */
class Currency
{
    public const CODES = [
        'EUR',
        'USD',
        'GBP',
        'CHF',
        'BAM',
        'RSD',
        'HUF',
        'CZK',
        'PLN',
        'SEK',
        'NOK',
        'DKK',
        'CAD',
        'AUD',
    ];

    public static function format(float $amount, string $code, ?string $intlLocale = null): string
    {
        $formatter = new NumberFormatter($intlLocale ?? Locale::intl(), NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $code);
    }
}

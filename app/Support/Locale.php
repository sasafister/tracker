<?php

namespace App\Support;

/**
 * The languages the app speaks. Keep in step with resources/js/i18n.js.
 */
class Locale
{
    public const DEFAULT = 'hr';

    /**
     * [code => ICU locale used for dates and money]
     */
    public const SUPPORTED = [
        'hr' => 'hr_HR',
        'en' => 'en_GB',
        'de' => 'de_DE',
        'sl' => 'sl_SI',
    ];

    public static function codes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    public static function intl(?string $code = null): string
    {
        return self::SUPPORTED[$code ?? app()->getLocale()] ?? self::SUPPORTED[self::DEFAULT];
    }
}

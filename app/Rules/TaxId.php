<?php

namespace App\Rules;

use App\Support\TaxId as TaxIdNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An OIB, or a VAT ID with a two-letter country prefix. An OIB — on its own
 * or behind "HR" — must have a valid check digit; other countries' VAT IDs
 * are checked for shape only.
 */
class TaxId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = TaxIdNumber::normalize($value) ?? '';

        if (preg_match('/^(HR)?(\d{11})$/', $value, $oib) === 1) {
            if (! TaxIdNumber::hasValidOibCheckDigit($oib[2])) {
                $fail(__('app.errors.oib_check'));
            }

            return;
        }

        if (str_starts_with($value, 'HR')) {
            $fail(__('app.errors.hr_vat'));

            return;
        }

        // EU VAT IDs run 8 to 12 characters after the country code, with digits.
        if (preg_match('/^[A-Z]{2}(?=[A-Z0-9]*\d)[A-Z0-9]{8,12}$/', $value) !== 1) {
            $fail(__('app.errors.tax_id'));
        }
    }
}

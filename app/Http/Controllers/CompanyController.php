<?php

namespace App\Http\Controllers;

use App\Rules\TaxId;
use App\Support\TaxId as TaxIdNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in user's company details. Every field is optional; whatever is
 * filled in appears on the PDF report.
 */
class CompanyController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        // Spaces are how people write IBANs and tax numbers, not part of them.
        $request->merge([
            'company_tax_id' => TaxIdNumber::normalize($request->input('company_tax_id')),
            'company_iban' => $this->compact($request->input('company_iban')),
        ]);

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_tax_id' => ['nullable', new TaxId],
            'company_iban' => ['nullable', 'string', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/'],
        ], [
            'company_iban.regex' => __('app.errors.iban'),
        ]);

        $request->user()->update($data);

        return response()->json(self::present($request->user()));
    }

    public static function present(object $user): array
    {
        return [
            'company_name' => $user->company_name,
            'company_address' => $user->company_address,
            'company_tax_id' => $user->company_tax_id,
            'company_iban' => $user->company_iban,
        ];
    }

    private function compact(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $compacted = strtoupper(preg_replace('/\s+/', '', $value));

        return $compacted === '' ? null : $compacted;
    }
}

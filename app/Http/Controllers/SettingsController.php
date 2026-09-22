<?php

namespace App\Http\Controllers;

use App\Support\Currency;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The signed-in user's profile, default rate and currency.
 */
class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($request));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', 'string', Rule::in(Currency::CODES)],
            'locale' => ['sometimes', 'string', Rule::in(Locale::codes())],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->hourly_rate = $data['hourly_rate'];
        $user->currency = $data['currency'];

        if (isset($data['locale'])) {
            $user->locale = $data['locale'];
            $request->session()->put('locale', $data['locale']);
        }
        $user->save();

        return response()->json($this->payload($request));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => $data['password'],
        ]);

        return response()->json(null, 204);
    }

    private function payload(Request $request): array
    {
        $user = $request->user();

        return [
            'name' => $user->name,
            'email' => $user->email,
            'hourly_rate' => $user->hourly_rate,
            'currency' => $user->currency,
            'locale' => $user->locale,
            'company' => CompanyController::present($user),
        ];
    }
}

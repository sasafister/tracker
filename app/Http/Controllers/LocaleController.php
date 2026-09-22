<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The language switcher on the landing and sign-in pages.
 */
class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, Locale::codes(), true), 404);

        $request->session()->put('locale', $locale);

        $request->user()?->update(['locale' => $locale]);

        return redirect()->back();
    }
}

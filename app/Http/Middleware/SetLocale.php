<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * A signed-in user gets their own language; a guest the one they picked on
 * the landing or login page, else the browser's, else Croatian.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $request->getPreferredLanguage(Locale::codes())
            ?? Locale::DEFAULT;

        if (! in_array($locale, Locale::codes(), true)) {
            $locale = Locale::DEFAULT;
        }

        App::setLocale($locale);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dili URL'den (/en, /ru) ya da form alanından (locale) belirler.
 * Kök adres varsayılan dildir (tr).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('site.locales'));
        $locale = $request->route('locale') ?? $request->input('locale') ?? config('site.default_locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('site.default_locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}

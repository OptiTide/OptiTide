<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale for translatable content (CmsPage): a `?lang=`
 * query (persisted to the session), else the authenticated user's locale, else
 * the app default. Only allow-listed locales are honoured — an arbitrary value
 * can't be forced, and spatie falls back to the default locale for any missing
 * translation.
 */
class SetLocale
{
    /** @var array<int, string> */
    protected array $supported = ['en', 'fr', 'es', 'de'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('lang') && in_array($request->query('lang'), $this->supported, true)) {
            $request->session()->put('locale', $request->query('lang'));
        }

        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? config('app.locale');

        if (in_array($locale, $this->supported, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}

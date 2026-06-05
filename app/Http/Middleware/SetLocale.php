<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the user's chosen language for the request.
 *
 * Source of the choice: the 'locale' value stored in the session by the
 * locale-switch route. If it's missing or not in config/locales.php, we fall
 * back to the default (config('app.locale') — APP_LOCALE in .env).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('locales.supported', []));
        $locale = $request->session()->get('locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}

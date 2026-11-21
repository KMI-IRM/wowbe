<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Locale;
use Symfony\Component\HttpFoundation\Response;

class Localization
{
    public const LOCALES = ['en', 'fr', 'nl'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set the locale based on the Accept-Language header
        $locale = Locale::acceptFromHttp($request->header('Accept-Language'));
        if ($locale !== false && in_array($locale, self::LOCALES, true)) {
            app()->setLocale($locale);
        }

        // Set the locale based on the authenticated user's preference
        if (Auth::check() === true && Auth::user() instanceof HasLocalePreference) {
            $userLocale = Auth::user()->preferredLocale();
            if (! is_null($userLocale) && in_array($userLocale, self::LOCALES, true)) {
                app()->setLocale($userLocale);
            }
        }

        // Set the locale based on the 'lang' query parameter
        if ($request->has('lang') === true && in_array($request->query('lang'), self::LOCALES, true)) {
            app()->setLocale($request->query('lang'));

            if (Auth::check() === true && Auth::user() instanceof HasLocalePreference) {
                $user = $request->user();
                $user->locale = $request->query('lang');
                $user->save();
            }
        }

        return $next($request);
    }

    /**
     * Get available locales for the application.
     *
     * @return array<string,string>
     */
    public static function getAvailableLocales(): array
    {
        $locales = [];

        foreach (self::LOCALES as $locale) {
            $displayLanguage = Locale::getDisplayLanguage($locale, $locale);
            if ($displayLanguage !== false) {
                $locales[$locale] = $displayLanguage;
            }
        }

        return $locales;
    }
}

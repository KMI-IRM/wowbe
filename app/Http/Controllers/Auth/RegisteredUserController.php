<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Localization;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Locale;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        $locale = app()->getLocale();

        $language = Locale::acceptFromHttp($request->header('Accept-Language'));
        if ($language !== false && in_array($language, Localization::LOCALES, true)) {
            $locale = $language;
        }

        if ($request->has('lang') === true && in_array($request->query('lang'), Localization::LOCALES, true)) {
            $locale = $request->query('lang');
        }

        return Inertia::render('auth/Register', [
            'availableLocales' => Localization::getAvailableLocales(),
            'locale' => $locale,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'locale' => $validated['locale'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}

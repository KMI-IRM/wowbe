<?php

namespace Tests\Unit;

use App\Http\Middleware\Localization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_locale_to_en_when_accept_language_is_en(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'en',
        ]);

        (new Localization)->handle($request, fn () => new Response);

        $this->assertTrue(App::isLocale('en'));
    }

    public function test_sets_locale_to_fr_when_accept_language_contains_fr(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'fr,fr-BE;q=0.8,en-US;q=0.5,en;q=0.3',
        ]);

        (new Localization)->handle($request, fn () => new Response);

        $this->assertTrue(App::isLocale('fr'));
    }

    public function test_sets_locale_to_nl_when_accept_language_is_nl(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'nl',
        ]);

        (new Localization)->handle($request, fn () => new Response);

        $this->assertTrue(App::isLocale('nl'));
    }

    public function test_does_not_set_locale_when_accept_language_is_not_supported(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'es',
        ]);

        (new Localization)->handle($request, fn () => new Response);

        $this->assertTrue(App::isLocale(config('app.locale')));
    }

    public function test_sets_locale_from_query_parameter(): void
    {
        $request = Request::create('/?lang=fr', 'GET');

        (new Localization)->handle($request, fn () => new Response);

        $this->assertTrue(App::isLocale('fr'));
    }

    public function test_sets_locale_from_authenticated_user_with_null_preference(): void
    {
        // Create a mock user that implements HasLocalePreference but returns null
        $user = $this->createMock(User::class);
        $user->method('preferredLocale')->willReturn(null);

        $this->actingAs($user);

        $request = Request::create('/', 'GET');

        (new Localization)->handle($request, fn () => new Response);

        // Should keep the default locale since user preference is null
        $this->assertTrue(App::isLocale(config('app.locale')));
    }

    public function test_sets_locale_from_authenticated_user_with_valid_preference(): void
    {
        // Create a mock user that implements HasLocalePreference and returns a valid locale
        $user = $this->createMock(User::class);
        $user->method('preferredLocale')->willReturn('fr');

        $this->actingAs($user);

        $request = Request::create('/', 'GET');

        (new Localization)->handle($request, fn () => new Response);

        // Should set locale to user's preference
        $this->assertTrue(App::isLocale('fr'));
    }

    public function test_saves_locale_to_authenticated_user_when_using_lang_parameter(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user);

        $request = Request::create('/?lang=fr', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        (new Localization)->handle($request, fn () => new Response);

        // Should set locale to the lang parameter
        $this->assertTrue(App::isLocale('fr'));

        // Should save the locale to the user
        $user->refresh();
        $this->assertEquals('fr', $user->locale);
    }
}

<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/web/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/web/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'locale' => 'en',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_screen_uses_accept_language_header(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'fr,en;q=0.5',
        ])->get('/web/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_uses_lang_query_parameter(): void
    {
        $response = $this->get('/web/register?lang=nl');

        $response->assertStatus(200);
    }

    public function test_registration_github(): void
    {
        $name = fake()->name();
        $email = fake()->safeEmail();

        Socialite::fake('github', User::fake([
            'id' => 'github-123',
            'name' => $name,
            'email' => $email,
        ]));

        $response = $this->get('/auth/github/callback');

        $response->assertRedirect('/web/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'oauth_id' => 'github-123',
            'oauth_provider' => 'github',
        ]);
    }

    public function test_registration_google(): void
    {
        $name = fake()->name();
        $email = fake()->safeEmail();

        Socialite::fake('google', User::fake([
            'id' => 'google-123',
            'name' => $name,
            'email' => $email,
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/web/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'oauth_id' => 'google-123',
            'oauth_provider' => 'google',
        ]);
    }

    public function test_registration_google_long_avatar(): void
    {
        $name = fake()->name();
        $email = fake()->safeEmail();
        $avatar = fake()->text(500);

        Socialite::fake('google', User::fake([
            'id' => 'google-123',
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/web/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'oauth_id' => 'google-123',
            'oauth_provider' => 'google',
            'avatar' => $avatar,
        ]);
    }
}

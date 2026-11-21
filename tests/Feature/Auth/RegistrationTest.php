<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/web/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/web/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/web/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/web/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // The login route allows five submissions per minute.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/web/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // The 6th attempt should be rejected by the route throttle.
        $response = $this->post('/web/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_submission_rate_limit_is_separate_for_each_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/web/login')->assertRedirect();
        }

        $this->post('/web/login')->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->post('/web/login')->assertRedirect();
    }
}

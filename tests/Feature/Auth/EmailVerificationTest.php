<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/web/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_notification_can_be_sent(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        Notification::fake();

        $response = $this->actingAs($user)->post('/web/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_email_verification_notification_redirects_to_dashboard_if_already_verified(): void
    {
        /** @var User $user */
        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)->post('/web/email/verification-notification');

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_email_verification_prompt_renders_for_unverified_user(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/web/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_verification_prompt_redirects_for_verified_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)->get('/web/verify-email');

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_email_can_be_verified_when_already_verified(): void
    {
        /** @var User $user */
        $user = User::factory()->create(); // Already verified

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // Should still redirect to dashboard, but Verified event should not be dispatched
        Event::assertNotDispatched(Verified::class);
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }
}

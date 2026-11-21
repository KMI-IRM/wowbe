<?php

namespace Tests\Feature\Settings;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/web/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/web/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'locale' => 'en',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/web/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/web/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'locale' => 'en',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/web/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/web/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/web/settings/profile')
            ->delete('/web/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/web/settings/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_oauth_user_can_delete_their_account_without_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/web/settings/profile');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_user_can_delete_account_and_force_delete_sites(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Site $site */
        $site = Site::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->delete('/web/settings/profile', [
                'password' => 'password',
                'delete_data' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());

        // Site should be force deleted (not just soft deleted)
        $this->assertNull(Site::withTrashed()->find($site->id));
    }

    public function test_user_can_delete_account_and_soft_delete_sites(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Site $site */
        $site = Site::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->delete('/web/settings/profile', [
                'password' => 'password',
                'delete_data' => false,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());

        // Site should be soft deleted
        $this->assertNotNull(Site::withTrashed()->find($site->id));
        $this->assertTrue(Site::withTrashed()->find($site->id)->trashed());
    }

    public function test_oauth_user_can_delete_account_with_force_delete_data(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        /** @var Site $site */
        $site = Site::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->delete('/web/settings/profile', [
                'delete_data' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());

        // Site should be force deleted (not just soft deleted)
        $this->assertNull(Site::withTrashed()->find($site->id));
    }
}

<?php

namespace Tests\Feature;

use App\Mail\AccountCreated;
use App\Mail\AccountDeleted;
use App\Mail\SiteCreated;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_random_observations_command(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $this->artisan('app:random-observations')
            ->expectsOutput('Processing site: '.$site->name)
            ->assertSuccessful();

        $this->assertDatabaseCount('observations', 1);
    }

    public function test_test_email_command(): void
    {
        $user = User::factory()->createOne();
        Site::factory()->createOne(['user_id' => $user->id]);

        Mail::fake();

        $this->artisan('app:test-email')
            ->expectsQuestion('Please enter the recipient email address for the test email(s):', 'test@example.com')
            ->expectsQuestion('Which email do you want to send?', [
                AccountCreated::class,
                AccountDeleted::class,
                SiteCreated::class,
            ])
            ->expectsQuestion('Which locale do you want to use?', 'en')
            ->expectsOutput('Test email(s) sent successfully.')
            ->assertSuccessful();

        Mail::assertSent(AccountCreated::class);
        Mail::assertSent(AccountDeleted::class);
        Mail::assertSent(SiteCreated::class);

        Mail::assertSentCount(3);
    }
}

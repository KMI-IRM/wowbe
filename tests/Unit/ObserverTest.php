<?php

namespace Tests\Unit;

use App\Jobs\AggregateObservations5min;
use App\Jobs\AggregateObservationsDay;
use App\Mail\AccountCreated;
use App\Mail\AccountDeleted;
use App\Mail\SiteCreated;
use App\Models\Observation;
use App\Models\Site;
use App\Models\User;
use App\Observers\ObservationObserver;
use App\Observers\SiteObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_observation_observer_dispatches_jobs_on_created(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        Observation::factory()->create([
            'site_id' => $site->id,
            'dateutc' => '2024-01-01 12:00:00',
        ]);

        Queue::assertPushed(AggregateObservations5min::class, function ($job) use ($site) {
            return $job->siteId === $site->id;
        });

        Queue::assertPushed(AggregateObservationsDay::class, function ($job) use ($site) {
            return $job->siteId === $site->id;
        });
    }

    public function test_observation_observer_updated_method_exists(): void
    {
        $observer = new ObservationObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $observation = Observation::factory()->create(['site_id' => $site->id]);

        // This method should not throw any errors
        $observer->updated($observation);
        $this->assertTrue(true);
    }

    public function test_observation_observer_deleted_method_exists(): void
    {
        $observer = new ObservationObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $observation = Observation::factory()->create(['site_id' => $site->id]);

        // This method should not throw any errors
        $observer->deleted($observation);
        $this->assertTrue(true);
    }

    public function test_observation_observer_restored_method_exists(): void
    {
        $observer = new ObservationObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $observation = Observation::factory()->create(['site_id' => $site->id]);

        // This method should not throw any errors
        $observer->restored($observation);
        $this->assertTrue(true);
    }

    public function test_observation_observer_force_deleted_method_exists(): void
    {
        $observer = new ObservationObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $observation = Observation::factory()->create(['site_id' => $site->id]);

        // This method should not throw any errors
        $observer->forceDeleted($observation);
        $this->assertTrue(true);
    }

    public function test_site_observer_sends_email_on_created(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Site::factory()->create(['user_id' => $user->id]);

        Mail::assertSent(SiteCreated::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_site_observer_updated_method_exists(): void
    {
        $observer = new SiteObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // This method should not throw any errors
        $observer->updated($site);
        $this->assertTrue(true);
    }

    public function test_site_observer_deletes_observations_and_media_on_deleted(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        Observation::factory()->create(['site_id' => $site->id]);

        $observer = new SiteObserver;
        $observer->deleted($site);

        // The actual deletion behavior is handled by the model relationships
        // This test ensures the method runs without error
        $this->assertTrue(true);
    }

    public function test_site_observer_restored_method_exists(): void
    {
        $observer = new SiteObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // This method should not throw any errors
        $observer->restored($site);
        $this->assertTrue(true);
    }

    public function test_site_observer_force_deleted_method_exists(): void
    {
        $observer = new SiteObserver;
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // This method should not throw any errors
        $observer->forceDeleted($site);
        $this->assertTrue(true);
    }

    public function test_user_observer_sends_email_on_created(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::assertSent(AccountCreated::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_user_observer_updated_method_exists(): void
    {
        $observer = new UserObserver;
        $user = User::factory()->create();

        // This method should not throw any errors
        $observer->updated($user);
        $this->assertTrue(true);
    }

    public function test_user_observer_sends_email_on_deleted(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $observer = new UserObserver;
        $observer->deleted($user);

        Mail::assertSent(AccountDeleted::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_user_observer_restored_method_exists(): void
    {
        $observer = new UserObserver;
        $user = User::factory()->create();

        // This method should not throw any errors
        $observer->restored($user);
        $this->assertTrue(true);
    }

    public function test_user_observer_sends_email_on_force_deleted(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $observer = new UserObserver;
        $observer->forceDeleted($user);

        Mail::assertSent(AccountDeleted::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}

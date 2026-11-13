<?php

namespace Tests\Unit;

use App\Models\Observation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_observation(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id, 'timezone' => 'Europe/Brussels']);

        $datetime = now()->utc();

        // Test that an observation with only -9999 values is discarded
        $observation = new Observation(['dateutc' => $datetime, 'tempf' => -9999]);
        $observation->site()->associate($site);
        $observation->save();

        $this->assertDatabaseEmpty('observations');

        // Test that an observation with at least one valid measurement is saved
        $observation = new Observation(['dateutc' => $datetime, 'tempf' => -9999, 'absbaromin' => 29.0]);
        $observation->site()->associate($site);
        $observation->save();

        $this->assertDatabaseHas('observations', ['site_id' => $site->id, 'dateutc' => $datetime, 'tempf' => null, 'absbaromin' => 29.0]);
    }
}

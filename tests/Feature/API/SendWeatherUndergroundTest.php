<?php

namespace Tests\Feature\API;

use App\Helpers\ObservationHelper;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SendWeatherUndergroundTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_send_observation_via_get_global(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $hash = $this->faker->sha256();
        $datetime = now()->utc();

        $absbaromin = $this->faker->randomFloat(2, 28, 31);
        $tempf = $this->faker->randomFloat(2, -40, 212);

        $query = http_build_query([
            'ID' => $site->id,
            'PASSWORD' => $site->auth_key,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'softwaretype' => $hash,
            'baromin' => ObservationHelper::absbaromin2baromin($absbaromin, $tempf, $site->altitude),
            'absbaromin' => $absbaromin,
            'dailyrainin' => $this->faker->randomFloat(2, 0, 10),
            'dewptf' => $this->faker->randomFloat(2, -40, 212),
            'humidity' => $this->faker->numberBetween(0, 100),
            'rainin' => $this->faker->randomFloat(2, 0, 10),
            'soilmoisture' => $this->faker->randomFloat(2, 0, 100),
            'soiltempf' => $this->faker->randomFloat(2, -40, 212),
            'tempf' => $tempf,
            'visibility' => $this->faker->randomFloat(2, 0, 10),
            'winddir' => $this->faker->numberBetween(0, 360),
            'windspeedmph' => $this->faker->randomFloat(2, 0, 100),
            'windgustmph' => $this->faker->randomFloat(2, 0, 100),
            'solarradiation' => $this->faker->randomFloat(2, 0, 1000),
        ]);

        $this
            ->get('/api/v2/send?'.$query)
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('type', 'Feature')
                    ->has('id')
                    ->where('geometry.type', 'Point')
                    ->has('geometry.coordinates.0')
                    ->has('geometry.coordinates.1')
                    ->has('geometry.coordinates.2')
                    ->has('properties.baromin')
                    ->has('properties.absbaromin')
                    ->has('properties.dailyrainin')
                    ->has('properties.dewptf')
                    ->has('properties.humidity')
                    ->has('properties.rainin')
                    ->has('properties.soilmoisture')
                    ->has('properties.soiltempf')
                    ->has('properties.tempf')
                    ->has('properties.visibility')
                    ->has('properties.winddir')
                    ->has('properties.windspeedmph')
                    ->has('properties.windgustmph')
                    ->has('properties.solarradiation')
                    ->has('metadata.created_at')
                    ->has('metadata.updated_at')
                    ->where('properties.dateutc', $datetime->format(DATE_ATOM))
                    ->where('properties.softwaretype', $hash)
                    ->where('properties.site.id', $site->id)
            );

        $this->assertDatabaseHas('observations', [
            'site_id' => $site->id,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'softwaretype' => $hash,
        ]);
    }

    public function test_send_observation_via_get(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $hash = $this->faker->sha256();
        $datetime = now()->utc();

        $absbaromin = $this->faker->randomFloat(2, 28, 31);
        $tempf = $this->faker->randomFloat(2, -40, 212);

        $query = http_build_query([
            'ID' => $site->id,
            'PASSWORD' => $site->auth_key,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'softwaretype' => $hash,
            'baromin' => ObservationHelper::absbaromin2baromin($absbaromin, $tempf, $site->altitude),
            'absbaromin' => $absbaromin,
            'dailyrainin' => $this->faker->randomFloat(2, 0, 10),
            'dewptf' => $this->faker->randomFloat(2, -40, 212),
            'humidity' => $this->faker->numberBetween(0, 100),
            'rainin' => $this->faker->randomFloat(2, 0, 10),
            'soilmoisture' => $this->faker->randomFloat(2, 0, 100),
            'soiltempf' => $this->faker->randomFloat(2, -40, 212),
            'tempf' => $tempf,
            'visibility' => $this->faker->randomFloat(2, 0, 10),
            'winddir' => $this->faker->numberBetween(0, 360),
            'windspeedmph' => $this->faker->randomFloat(2, 0, 100),
            'windgustmph' => $this->faker->randomFloat(2, 0, 100),
            'solarradiation' => $this->faker->randomFloat(2, 0, 1000),
        ]);

        $this
            ->get('/api/v2/send/weatherunderground?'.$query)
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('type', 'Feature')
                    ->has('id')
                    ->where('geometry.type', 'Point')
                    ->has('geometry.coordinates.0')
                    ->has('geometry.coordinates.1')
                    ->has('geometry.coordinates.2')
                    ->has('properties.baromin')
                    ->has('properties.absbaromin')
                    ->has('properties.dailyrainin')
                    ->has('properties.dewptf')
                    ->has('properties.humidity')
                    ->has('properties.rainin')
                    ->has('properties.soilmoisture')
                    ->has('properties.soiltempf')
                    ->has('properties.tempf')
                    ->has('properties.visibility')
                    ->has('properties.winddir')
                    ->has('properties.windspeedmph')
                    ->has('properties.windgustmph')
                    ->has('properties.solarradiation')
                    ->has('metadata.created_at')
                    ->has('metadata.updated_at')
                    ->where('properties.dateutc', $datetime->format(DATE_ATOM))
                    ->where('properties.softwaretype', $hash)
                    ->where('properties.site.id', $site->id)
            );

        $this->assertDatabaseHas('observations', [
            'site_id' => $site->id,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'softwaretype' => $hash,
        ]);
    }

    public function test_authentication_failure_invalid_id(): void
    {
        $query = http_build_query([
            'ID' => 'non-existent-site-id',
            'PASSWORD' => 'some_password',
            'dateutc' => now()->utc()->format('Y-m-d H:i:s'),
            'softwaretype' => $this->faker->word(),
        ]);

        $this
            ->get('/api/v2/send?'.$query)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ID']);

        $this->assertDatabaseMissing('observations');
    }

    public function test_authentication_failure_invalid_password(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $query = http_build_query([
            'ID' => $site->id,
            'PASSWORD' => 'invalid_password',
            'dateutc' => now()->utc()->format('Y-m-d H:i:s'),
            'softwaretype' => $this->faker->word(),
        ]);

        $this
            ->get('/api/v2/send?'.$query)
            ->assertStatus(403);

        $this->assertDatabaseMissing('observations', [
            'site_id' => $site->id,
        ]);
    }

    public function test_send_observation_with_short_id(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $hash = $this->faker->sha256();
        $datetime = now()->utc();

        $query = http_build_query([
            'ID' => $site->short_id, // Use short_id instead of UUID
            'PASSWORD' => $site->auth_key,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'softwaretype' => $hash,
            'tempf' => $this->faker->randomFloat(2, -40, 212),
            'humidity' => $this->faker->numberBetween(0, 100),
        ]);

        $this
            ->get('/api/v2/send?'.$query)
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('id')
                    ->where('properties.dateutc', $datetime->format(DATE_ATOM))
                    ->where('properties.softwaretype', $hash)
                    ->where('properties.site.id', $site->id)
                    ->etc()
            );

        $this->assertDatabaseHas('observations', [
            'site_id' => $site->id,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'softwaretype' => $hash,
        ]);
    }

    public function test_empty_observation(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $datetime = now()->utc();

        $absbaromin = $this->faker->randomFloat(2, 28, 31);

        // Test that an observation with only -9999 values is discarded but returned as response
        $query = http_build_query([
            'ID' => $site->id,
            'PASSWORD' => $site->auth_key,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'tempf' => -9999,
        ]);

        $this
            ->get('/api/v2/send/weatherunderground?'.$query)
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('type', 'Feature')
                    ->has('id')
                    ->has('geometry')
                    ->has('metadata')
                    ->has('properties.tempf')
                    ->where('properties.dateutc', $datetime->format(DATE_ATOM))
                    ->where('properties.site.id', $site->id)
                    ->where('properties.tempf', null)
            );

        $this->assertDatabaseEmpty('observations');

        // Test that an observation with at least one valid measurement is saved
        $query = http_build_query([
            'ID' => $site->id,
            'PASSWORD' => $site->auth_key,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'absbaromin' => $absbaromin,
            'tempf' => -9999,
        ]);

        $this
            ->get('/api/v2/send/weatherunderground?'.$query)
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('type', 'Feature')
                    ->has('id')
                    ->has('geometry')
                    ->has('metadata')
                    ->has('properties.absbaromin')
                    ->has('properties.tempf')
                    ->where('properties.dateutc', $datetime->format(DATE_ATOM))
                    ->where('properties.site.id', $site->id)
                    ->where('properties.absbaromin', $absbaromin)
                    ->where('properties.tempf', null)
            );

        $this->assertDatabaseHas('observations', [
            'site_id' => $site->id,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'tempf' => null,
            'absbaromin' => $absbaromin,
        ]);
    }
}

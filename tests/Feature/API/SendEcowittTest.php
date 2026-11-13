<?php

namespace Tests\Feature\API;

use App\Helpers\ObservationHelper;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SendEcowittTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_send_observation_via_post_global(): void
    {
        $macAddress = $this->faker->macAddress();

        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id, 'mac_address' => $macAddress]);

        $hash = $this->faker->sha256();
        $datetime = now()->utc();

        $absbaromin = $this->faker->randomFloat(2, 28, 31);
        $tempf = $this->faker->randomFloat(2, -40, 212);

        $data = [
            'PASSKEY' => strtoupper(md5($macAddress)),
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'stationtype' => $hash,
            'baromrelin' => ObservationHelper::absbaromin2baromin($absbaromin, $tempf, $site->altitude),
            'baromabsin' => $absbaromin,
            'tempf' => $tempf,
            'humidity' => $this->faker->numberBetween(0, 100),
            'winddir' => $this->faker->numberBetween(0, 360),
            'windspeedmph' => $this->faker->randomFloat(2, 0, 100),
            'windgustmph' => $this->faker->randomFloat(2, 0, 100),
            'rainratein' => $this->faker->randomFloat(2, 0, 10),
            'dailyrainin' => $this->faker->randomFloat(2, 0, 10),
            'solarradiation' => $this->faker->randomFloat(2, 0, 1000),
            'model' => $this->faker->word(),
        ];

        $this
            ->post('/api/v2/send', $data)
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
                    ->has('properties.humidity')
                    ->has('properties.rainin')
                    ->has('properties.tempf')
                    ->has('properties.winddir')
                    ->has('properties.windspeedmph')
                    ->has('properties.windgustmph')
                    ->has('properties.solarradiation')
                    ->has('properties.model')
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

    public function test_send_observation_via_post(): void
    {
        $macAddress = $this->faker->macAddress();

        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id, 'mac_address' => $macAddress]);

        $hash = $this->faker->sha256();
        $datetime = now()->utc();

        $absbaromin = $this->faker->randomFloat(2, 28, 31);
        $tempf = $this->faker->randomFloat(2, -40, 212);

        $data = [
            'PASSKEY' => strtoupper(md5($macAddress)),
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'stationtype' => $hash,
            'baromrelin' => ObservationHelper::absbaromin2baromin($absbaromin, $tempf, $site->altitude),
            'baromabsin' => $absbaromin,
            'tempf' => $tempf,
            'humidity' => $this->faker->numberBetween(0, 100),
            'winddir' => $this->faker->numberBetween(0, 360),
            'windspeedmph' => $this->faker->randomFloat(2, 0, 100),
            'windgustmph' => $this->faker->randomFloat(2, 0, 100),
            'rainratein' => $this->faker->randomFloat(2, 0, 10),
            'dailyrainin' => $this->faker->randomFloat(2, 0, 10),
            'solarradiation' => $this->faker->randomFloat(2, 0, 1000),
            'model' => $this->faker->word(),
        ];

        $this
            ->post('/api/v2/send/ecowitt', $data)
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
                    ->has('properties.humidity')
                    ->has('properties.rainin')
                    ->has('properties.tempf')
                    ->has('properties.winddir')
                    ->has('properties.windspeedmph')
                    ->has('properties.windgustmph')
                    ->has('properties.solarradiation')
                    ->has('properties.model')
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

    public function test_rate_limit_is_scoped_per_site(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $firstMacAddress = $this->faker->macAddress();
        Site::factory()->createOne(['user_id' => $user->id, 'mac_address' => $firstMacAddress]);

        $secondMacAddress = $this->faker->macAddress();
        $secondSite = Site::factory()->createOne(['user_id' => $user->id, 'mac_address' => $secondMacAddress]);

        $datetime = now()->utc();

        // Exhaust the 20 requests per minute allowance of the first site.
        for ($i = 0; $i < 20; $i++) {
            $this
                ->post('/api/v2/send/ecowitt', $this->ecowittPayload($firstMacAddress, $datetime->copy()->subMinutes($i)))
                ->assertOk();
        }

        $this
            ->post('/api/v2/send/ecowitt', $this->ecowittPayload($firstMacAddress, $datetime))
            ->assertStatus(429);

        // The second site sends from the same IP address and must be unaffected.
        $this
            ->post('/api/v2/send/ecowitt', $this->ecowittPayload($secondMacAddress, $datetime))
            ->assertOk();

        $this->assertDatabaseHas('observations', [
            'site_id' => $secondSite->id,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_authentication_failure_invalid_passkey(): void
    {
        $data = [
            'PASSKEY' => 'invalid_passkey_hash',
            'dateutc' => now()->utc()->format('Y-m-d H:i:s'),
            'stationtype' => $this->faker->sha256(),
            'tempf' => $this->faker->randomFloat(2, -40, 212),
        ];

        $this
            ->post('/api/v2/send', $data)
            ->assertStatus(404);

        $this->assertDatabaseMissing('observations', []);
    }

    /**
     * @return array<string, mixed>
     */
    private function ecowittPayload(string $macAddress, Carbon $datetime): array
    {
        return [
            'PASSKEY' => strtoupper(md5($macAddress)),
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'stationtype' => $this->faker->sha256(),
            'tempf' => $this->faker->randomFloat(2, -40, 212),
        ];
    }

    public function test_empty_observation(): void
    {
        $macAddress = $this->faker->macAddress();

        /** @var User $user */
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id, 'mac_address' => $macAddress]);

        $hash = $this->faker->sha256();
        $datetime = now()->utc();

        // Test that an observation with only -9999 values is discarded but returned as response
        $data = [
            'PASSKEY' => strtoupper(md5($macAddress)),
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'stationtype' => $hash,
            'tempf' => -9999,
        ];

        $this
            ->post('/api/v2/send/ecowitt', $data)
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
        $absbaromin = $this->faker->randomFloat(2, 28, 31);

        $data = [
            'PASSKEY' => strtoupper(md5($macAddress)),
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'stationtype' => $hash,
            'baromabsin' => $absbaromin,
            'tempf' => -9999,
        ];

        $this
            ->post('/api/v2/send/ecowitt', $data)
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
                    ->where('properties.tempf', null)
                    ->where('properties.absbaromin', $absbaromin)
            );

        $this->assertDatabaseHas('observations', [
            'site_id' => $site->id,
            'dateutc' => $datetime->format('Y-m-d H:i:s'),
            'tempf' => null,
            'absbaromin' => $absbaromin,
        ]);
    }
}

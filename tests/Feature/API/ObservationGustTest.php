<?php

namespace Tests\Feature\API;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ObservationGustTest extends TestCase
{
    use RefreshDatabase;

    public static function gustCases(): iterable
    {
        foreach (['v1', 'v2'] as $version) {
            foreach ([false, true] as $historical) {
                foreach ([
                    'reported' => [32.5, 247.5],
                    'zero' => [0.0, 0.0],
                    'speed only' => [32.5, null],
                    'direction only' => [null, 247.5],
                    'missing' => [null, null],
                ] as $label => [$speed, $direction]) {
                    $window = $historical ? 'historical' : 'live';
                    yield "$version $window $label" => [$version, $historical, $speed, $direction];
                }
            }
        }
    }

    #[DataProvider('gustCases')]
    public function test_observation_api_returns_latest_gust_values(
        string $version,
        bool $historical,
        ?float $speed,
        ?float $direction,
    ): void {
        $this->travelTo(Carbon::parse('2026-01-15T12:00:00Z'));

        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);
        $end = $historical ? now()->subDay() : now();

        // Seed aggregates directly: this tests API serialization, not aggregation.
        // Model writes are intentionally prohibited by ReadOnlyObserver.
        DB::table('observations_agg_5min')->insert([
            [
                'site_id' => $site->id,
                'dateutc' => $end->copy()->subMinutes(10)->utc()->format('Y-m-d H:i:s'),
                'windspeed' => 8.0,
                'winddir' => 90.0,
                'windgustspeed' => 65.0,
                'windgustdir' => 180.0,
                'count' => 1,
            ],
            [
                'site_id' => $site->id,
                'dateutc' => $end->copy()->subMinutes(5)->utc()->format('Y-m-d H:i:s'),
                'windspeed' => 12.5,
                'winddir' => 135.0,
                'windgustspeed' => $speed,
                'windgustdir' => $direction,
                'count' => 1,
            ],
        ]);

        $url = "/api/$version/observation";
        if ($historical) {
            $url .= '?'.http_build_query(['date' => $end->copy()->utc()->toIso8601String()]);
        }

        $response = $this->getJson($url);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.'.($version === 'v1' ? 'siteId' : 'site_id'), $site->id)
            ->assertJsonStructure([
                'features' => [
                    '*' => [
                        'properties' => [
                            'primary' => ['dws', 'dwd', 'dwg', 'dwgd'],
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('features.0.properties.primary.dws', 12.5)
            ->assertJsonPath('features.0.properties.primary.dwd', 135);

        $primary = $response->json('features.0.properties.primary');

        // JSON may encode whole-valued floats as integers; null must remain null.
        foreach (['dwg' => $speed, 'dwgd' => $direction] as $key => $expected) {
            if ($expected === null) {
                $this->assertNull($primary[$key]);
            } else {
                $this->assertIsNumeric($primary[$key]);
                $this->assertEquals($expected, $primary[$key]);
            }
        }
    }
}

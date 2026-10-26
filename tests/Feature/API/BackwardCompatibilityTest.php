<?php

namespace Tests\Feature\API;

use App\Models\Observation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class BackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_live_observations(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        Observation::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->get('/api/v1/observation');

        $response
            ->assertOk()
            ->assertJsonPath('crs.type', 'name')
            ->assertJsonPath('crs.properties.name', 'urn:ogc:def:crs:OGC:1.3:CRS84')
            ->assertJsonPath('features.0.properties.siteId', $site->id)
            ->assertJsonCount(1, 'features')
            ->assertJsonStructure([
                'crs' => [
                    'properties' => [
                        'name',
                    ],
                    'type',
                ],
                'features' => [
                    '*' => [
                        'geometry' => [
                            'type',
                            'coordinates',
                        ],
                        'properties' => [
                            'siteId',
                            'siteName',
                            'isOfficial',
                            'timestamp',
                            'primary' => [
                                'dt',
                                'dpt',
                                'dws',
                                'drr',
                                'dra',
                                'dm',
                                'dh',
                                'dwd',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_get_past_observations(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        Observation::factory()->create(['site_id' => $site->id, 'dateutc' => '2000-01-01 12:00:00']);
        Observation::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->get('/api/v1/observation?date=2000-01-01T12:00:00Z');

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('crs.type', 'name')
                ->where('crs.properties.name', 'urn:ogc:def:crs:OGC:1.3:CRS84')
                ->where('features.0.properties.siteId', $site->id)
                ->has('features', 1)
                ->where('features.0.properties.timestamp', '2000-01-01T12:00:00+01:00')
                ->etc()
            );
    }

    public function test_get_station_metadata(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        $response = $this->get("/api/v1/site/{$site->id}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $site->id)
            ->assertJsonStructure([
                'id',
                'siteName',
                'groupId',
                'groupName',
                'groupTypeName',
                'groupImageUrl',
                'belongsToBrand',
                'hasGroup',
                'isSchool',
                'isOfficial',
                'metOfficeId',
                'location' => [
                    'geography' => [
                        'coordinateSystemId',
                        'wellKnownText',
                    ],
                ],
                'siteLogoImagePath',
                'northSideImagePath',
                'eastSideImagePath',
                'southSideImagePath',
                'westSideImagePath',
                'siteRating',
                'description',
                'isActive',
                'allowDownload',
                'reason',
                'timeZone',
                'locationExposureAttrib',
                'temperatureAttrib',
                'rainfallAttrib',
                'windAttrib',
                'urbanClimateZoneAttrib',
                'reportingHoursAttrib',
                'website',
                'otherReason',
                'equipmentType',
                'defaultGraphFields',
                'defaultTableFields',
                'isDcnn',
                'hasEditPermission',
                'hasViewHolidaysPermission',
                'hasManageUsersPermission',
            ]);
    }

    public function test_get_latest_observation(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        Observation::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->get("/api/v1/site/{$site->id}/latest");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'geometry' => [
                    'type',
                    'coordinates',
                ],
                'timestamp',
                'primary' => [
                    'dt',
                    'dws',
                    'dwd',
                    'drr',
                    'dm',
                    'dh',
                ],
            ]);
    }

    public function test_get_graph_data(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        Observation::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->get("/api/v1/site/{$site->id}/graph");

        $response
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'timestamp',
                    'primary' => [
                        'dt',
                        'dws',
                        'dwd',
                        'drr',
                        'dm',
                        'dh',
                    ],
                ],
            ]);
    }

    public function test_get_daily_observations(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        Observation::factory()->count(3)->create(['site_id' => $site->id]);

        $response = $this->get("/api/v1/site/{$site->id}/daily?".http_build_query([
            'day1' => now()->subDays(1)->format('Y-m-d\\TH:i:s.vp'),
            'day2' => now()->format('Y-m-d\\TH:i:s.vp'),
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'timestamp',
                    'data' => [
                        'temperature' => [
                            'min',
                            'max',
                            'mean',
                        ],
                        'dewpoint' => [
                            'mean',
                        ],
                        'humidity' => [
                            'mean',
                        ],
                        'rainfall' => [
                            'max_intensity',
                            'precipitation_quantity',
                            'precipitation_duration',
                        ],
                        'wind' => [
                            'max',
                            'gust',
                        ],
                        'pressure' => [
                            'mean',
                        ],
                    ],
                ],
            ]);
    }

    public function test_search(): void
    {
        $user = User::factory()->createOne();

        Site::factory()->createOne(['user_id' => $user->id, 'name' => 'Example Site']);

        $response = $this->get('/api/v1/site?search=example');

        $response
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'description',
                    'longitude',
                    'latitude',
                ],
            ]);
    }
}

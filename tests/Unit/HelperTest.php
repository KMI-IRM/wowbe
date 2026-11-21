<?php

namespace Tests\Unit;

use App\Helpers\ObservationHelper;
use App\Helpers\SiteHelper;
use App\Models\Observation;
use App\Models\Site;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function test_serialize_geometry_for_observation(): void
    {
        $observation = new Observation;
        $observation->longitude = 4.3415232;
        $observation->latitude = 50.8949242;
        $observation->altitude = 93.0;

        $geometry = ObservationHelper::serializeGeometry2D($observation);

        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [4.3415232, 50.8949242],
        ], $geometry);

        $geometryZ = ObservationHelper::serializeGeometry3D($observation);

        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [4.3415232, 50.8949242, 93.0],
        ], $geometryZ);
    }

    public function test_serialize_geometry_for_site(): void
    {
        $site = new Site;
        $site->longitude = 4.3415232;
        $site->latitude = 50.8949242;
        $site->altitude = 93.0;

        $geometry = SiteHelper::serializeGeometry2D($site);

        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [4.3415232, 50.8949242],
        ], $geometry);

        $geometryZ = SiteHelper::serializeGeometry3D($site);

        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [4.3415232, 50.8949242, 93.0],
        ], $geometryZ);
    }
}

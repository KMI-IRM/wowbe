<?php

namespace App\Helpers;

use App\Models\Site;

class SiteHelper
{
    /**
     * Serialize the geometry of a site for GeoJSON in 2D.
     *
     * @return array{type:string,coordinates:float[]}
     */
    public static function serializeGeometry2D(Site $site): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$site->longitude, $site->latitude],
        ];
    }

    /**
     * Serialize the geometry of a site for GeoJSON in 3D.
     *
     * @return array{type:string,coordinates:float[]}
     */
    public static function serializeGeometry3D(Site $site): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$site->longitude, $site->latitude, $site->altitude],
        ];
    }

    /**
     * Get the site short ID.
     */
    public static function getShortId(Site $site): string
    {
        return hash('xxh32', (string) $site->id);
    }
}

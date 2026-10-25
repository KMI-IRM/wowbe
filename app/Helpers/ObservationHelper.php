<?php

namespace App\Helpers;

use App\Models\Observation;

class ObservationHelper
{
    /**
     * Serialize the geometry of a site for GeoJSON in 2D.
     *
     * @return array{type:string,coordinates:float[]}
     */
    public static function serializeGeometry2D(Observation $observation): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$observation->longitude, $observation->latitude],
        ];
    }

    /**
     * Serialize the geometry of a site for GeoJSON in 3D.
     *
     * @return array{type:string,coordinates:float[]}
     */
    public static function serializeGeometry3D(Observation $observation): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$observation->longitude, $observation->latitude, $observation->altitude],
        ];
    }

    /**
     * Calculate the Relative Pressure from the Absolute Pressure, temperature, and altitude.
     *
     * @param  float  $absbaromin  Absolute barometric pressure at the site location (in inches of mercury).
     * @param  float  $tempf  Air temperature (in Fahrenheit).
     * @param  float  $altitude  Altitude (in meters).
     * @return float Relative Pressure in inches of mercury.
     */
    public static function absbaromin2baromin(float $absbaromin, float $tempf, float $altitude): float
    {
        // Convert Fahrenheit to Kelvin
        $tempk = ($tempf - 32) * 5 / 9 + 273.15;

        // Calculate relative pressure using the barometric formula
        return round($absbaromin * pow((1 - (0.0065 * $altitude) / ($tempk + 0.0065 * $altitude)), -5.257), 2);
    }
}

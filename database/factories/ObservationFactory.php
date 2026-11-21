<?php

namespace Database\Factories;

use App\Models\Observation;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dateutc' => $this->faker->dateTimeBetween('-5 minute', 'now', 'UTC'),
            'softwaretype' => $this->faker->word(),
            'baromin' => $this->faker->randomFloat(2, 28, 32), // Barometric pressure in inches of mercury
            'dailyrainin' => $this->faker->randomFloat(2, 0, 10), // Daily rainfall in inches
            'dewptf' => $this->faker->randomFloat(2, 0, 100), // Dew point temperature in Fahrenheit
            'humidity' => $this->faker->randomFloat(2, 0, 100), // Relative humidity percentage
            'rainin' => $this->faker->randomFloat(2, 0, 10), // Rainfall in inches
            'soilmoisture' => $this->faker->randomFloat(2, 0, 100), // Soil moisture percentage
            'soiltempf' => $this->faker->randomFloat(2, 0, 35), // Soil temperature in Fahrenheit
            'tempf' => $this->faker->randomFloat(2, 0, 35), // Air temperature in Fahrenheit
            'visibility' => $this->faker->randomFloat(2, 0, 100), // Visibility in kilometers
            'winddir' => $this->faker->randomFloat(2, 0, 360), // Wind direction in degrees
            'windspeedmph' => $this->faker->randomFloat(2, 0, 100), // Wind speed in miles per hour
            'windgustdir' => $this->faker->randomFloat(2, 0, 360),  // Wind gust direction in degrees
            'windgustmph' => $this->faker->randomFloat(2, 0, 100), // Wind gust speed in miles per hour
            'solarradiation' => $this->faker->randomFloat(2, 0, 1100), // Solar radiation in W/m²
            'site_id' => Site::inRandomOrder()->first(),
        ];
    }
}

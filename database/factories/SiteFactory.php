<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'longitude' => $this->faker->longitude(2.5, 6.4),
            'latitude' => $this->faker->latitude(49.5, 51.51),
            'altitude' => $this->faker->numberBetween(1, 100),
            'timezone' => $this->faker->timezone('BE'),
            'auth_key' => str_split($this->faker->numerify('######')),
            'user_id' => User::inRandomOrder()->first(),
        ];
    }
}

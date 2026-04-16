<?php

namespace Database\Factories;

use App\Models\Gare;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gare>
 */
class GareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agence_id' => fake()->numberBetween(2, 4),
            'nom' => fake()->company(),
            'ville' => fake()->city(),
            'adresse' => fake()->address(),
            'telephone' => fake()->phoneNumber(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

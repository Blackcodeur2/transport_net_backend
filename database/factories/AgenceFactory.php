<?php

namespace Database\Factories;

use App\Models\Agence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agence>
 */
class AgenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prorietaire_id' => fake()->numberBetween(5, 7),
            'nom' => fake()->company(),
            'email' => fake()->companyEmail(),
            'telephone' => fake()->phoneNumber(),
            'adresse' => fake()->address()
        ];
    }
}

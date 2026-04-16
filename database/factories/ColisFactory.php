<?php

namespace Database\Factories;

use App\Models\Colis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Colis>
 */
class ColisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(4,7),
            'nom_colis' => fake()->name(),
            'chemin_image' => fake()->filePath(),
            'tel_destinataire' => fake()->phoneNumber(),
            'nom_destinataire' => fake()->userName(),
            'provenance' => fake()->numberBetween(1,5),
            'destination' => fake()->numberBetween(3,6), 
        ];
    }
}

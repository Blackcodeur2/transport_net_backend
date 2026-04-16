<?php

namespace Database\Factories;

use App\Models\KWCDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KWCDocument>
 */
class KWCDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'type' => 'cni',
            'chemin_fichier' => fake()->filePath(),
            'statut' => 'approuve'
        ];
    }
}

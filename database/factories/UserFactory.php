<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->firstName(),
            'prenom' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'matricule' => strtoupper(Str::random(8)),
            'date_naissance' => fake()->date(),
            'region' => fake()->country(),
            'ville' => fake()->city(),
            'pos_lat' => fake()->latitude(),
            'pos_lng' => fake()->longitude(),
            'telephone' => fake()->phoneNumber(),
            'role_user' => fake()->randomElement([
                'AGENT','CHAUFFEUR','ADMIN','PROPRIETAIRE','CHEF_AGENCE'
            ]),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'gare_id' => 1,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

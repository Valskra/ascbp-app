<?php

// database/factories/UserFactory.php - Fix définitif du problème de hash

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'account_status' => 'active',
            // ✅ Solution: Utiliser bcrypt directement qui est compatible avec Laravel 11
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'firstname' => 'Admin',
            'lastname' => 'ASCBP',
            'email' => 'admin@gmail.com',
        ]);
    }

    public function animator(): static
    {
        return $this->state(fn(array $attributes) => [
            'firstname' => 'Animateur',
            'lastname' => 'ASCBP',
        ]);
    }

    /**
     * Indicate that the user's password should be the given value.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn(array $attributes) => [
            'password' => bcrypt($password),
        ]);
    }

    /**
     * Indicate that the user should have an unverified email.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

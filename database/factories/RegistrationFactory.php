<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'registration_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'amount' => fake()->randomFloat(2, 0, 50),
            'metadata' => fake()->optional()->passthrough([
                'status' => fake()->randomElement(['confirmed', 'pending', 'cancelled']),
                'payment_method' => fake()->randomElement(['online', 'cash', 'transfer']),
                'notes' => fake()->optional()->sentence(),
                'confirmation_sent' => fake()->boolean(),
            ]),
        ];
    }

    /**
     * Inscription confirmée
     */
    public function confirmed(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['status'] = 'confirmed';
            return ['metadata' => $metadata];
        });
    }

    /**
     * Inscription en attente
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['status'] = 'pending';
            return ['metadata' => $metadata];
        });
    }

    /**
     * Inscription annulée
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['status'] = 'cancelled';
            return ['metadata' => $metadata];
        });
    }
}

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
            'status' => fake()->randomElement(['confirmed', 'pending', 'cancelled']),
            'payment_method' => fake()->randomElement(['online', 'cash', 'transfer']),
        ];
    }
}

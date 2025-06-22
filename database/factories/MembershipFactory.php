<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contribution_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'amount' => fake()->randomFloat(2, 15, 50),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'online']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

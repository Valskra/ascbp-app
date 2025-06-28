<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipFactory extends Factory
{
    public function definition(): array
    {
        $contributionDate = $this->faker->dateTimeBetween('-2 years', 'now');

        return [
            'user_id' => User::factory(),
            'year' => $contributionDate->format('Y'),
            'contribution_date' => $contributionDate,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'metadata' => $this->faker->optional()->passthrough([
                'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'check', 'card']),
                'notes' => $this->faker->optional()->sentence(),
                'receipt_number' => $this->faker->optional()->numerify('REC-####'),
            ]),
        ];
    }

    /**
     * Adhésion active (cotisation de moins d'un an)
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $contributionDate = $this->faker->dateTimeBetween('-11 months', 'now');
            return [
                'year' => $contributionDate->format('Y'),
                'contribution_date' => $contributionDate,
            ];
        });
    }

    /**
     * Adhésion expirée (cotisation entre 1 et 2 ans)
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $contributionDate = $this->faker->dateTimeBetween('-2 years', '-12 months');
            return [
                'year' => $contributionDate->format('Y'),
                'contribution_date' => $contributionDate,
            ];
        });
    }

    /**
     * Adhésion inactive (cotisation de plus de 2 ans)
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            $contributionDate = $this->faker->dateTimeBetween('-5 years', '-2 years');
            return [
                'year' => $contributionDate->format('Y'),
                'contribution_date' => $contributionDate,
            ];
        });
    }
    /**
     * Adhésion récente (cotisation de moins d'un mois)
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            $contributionDate = $this->faker->dateTimeBetween('-1 month', 'now');
            return [
                'year' => $contributionDate->format('Y'),
                'contribution_date' => $contributionDate,
            ];
        });
    }

    /**
     * Adhésion très ancienne (plus de 2 ans)
     */
    public function veryOld(): static
    {
        return $this->state(function (array $attributes) {
            $contributionDate = $this->faker->dateTimeBetween('-5 years', '-2 years');
            return [
                'year' => $contributionDate->format('Y'),
                'contribution_date' => $contributionDate,
            ];
        });
    }
}

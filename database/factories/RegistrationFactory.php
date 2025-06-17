<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{User, Event};
use Carbon\Carbon;

class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        // Montant par défaut
        $amount = $this->faker->randomElement([0, 10, 15, 20, 25, 30, 50]);

        $metadata = null;
        if ($this->faker->boolean(40)) { // 40% de chance d'avoir des metadata
            $metadataArray = $this->faker->randomElement([
                ['payment_status' => 'paid', 'payment_method' => 'cash'],
                ['payment_status' => 'paid', 'payment_method' => 'check', 'check_number' => $this->faker->numberBetween(1000000, 9999999)],
                ['payment_status' => 'paid', 'payment_method' => 'transfer'],
                ['payment_status' => 'pending', 'reminder_sent' => false],
                ['payment_status' => 'partial', 'amount_paid' => $amount * 0.5],
            ]);
            $metadata = json_encode($metadataArray);
        }

        return [
            'registration_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'amount' => $amount,
            'metadata' => $metadata,
            'event_id' => 1, // Sera défini dans le seeder
            'user_id' => User::factory(),
        ];
    }

    /**
     * Inscription avec paiement effectué
     */
    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'metadata' => ['payment_status' => 'paid', 'payment_method' => 'transfer'],
        ]);
    }

    /**
     * Inscription en attente de paiement
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'metadata' => ['payment_status' => 'pending', 'reminder_sent' => false],
        ]);
    }
}

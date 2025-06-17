<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Carbon\Carbon;

class MembershipFactory extends Factory
{
    public function definition(): array
    {
        // Générer une adhésion pour l'année en cours ou précédente
        $year = $this->faker->randomElement([
            now()->year,           // Année courante (adhésion active)
            now()->year - 1,       // Année précédente
            now()->year - 2,       // Il y a 2 ans
        ]);

        // Date de cotisation cohérente avec l'année
        $contributionDate = Carbon::create($year, $this->faker->numberBetween(1, 12), $this->faker->numberBetween(1, 28));

        // Montants réalistes pour une association sportive
        $amounts = [30.00, 35.00, 40.00, 45.00, 50.00, 55.00, 60.00];

        $metadata = null;
        if ($this->faker->boolean(30)) { // 30% de chance d'avoir des metadata
            $metadataArray = $this->faker->randomElement([
                ['payment_method' => 'cash', 'receipt_number' => $this->faker->numberBetween(1000, 9999)],
                ['payment_method' => 'check', 'check_number' => $this->faker->numberBetween(1000000, 9999999)],
                ['payment_method' => 'transfer', 'reference' => 'ADH' . $year . $this->faker->numberBetween(100, 999)],
                ['payment_method' => 'online', 'transaction_id' => 'TXN' . $this->faker->randomNumber(8)],
            ]);
            $metadata = json_encode($metadataArray);
        }

        return [
            'year' => $year,
            'contribution_date' => $contributionDate,
            'amount' => $this->faker->randomElement($amounts),
            'metadata' => $metadata,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Adhésion active pour l'année en cours
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'year' => now()->year,
            'contribution_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Ancienne adhésion expirée
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'year' => now()->year - $this->faker->numberBetween(1, 3),
            'contribution_date' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+3 months');
        
        return [
            'title' => fake()->randomElement([
                'Sortie VTT en forêt',
                'Randonnée pédestre',
                'Trail découverte'
            ]) . ' - ' . fake()->city(),
            'category' => fake()->randomElement(['vtt', 'randonnee', 'trail']),
            'description' => fake()->paragraphs(2, true),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +6 hours'),
            'location' => fake()->city() . ', ' . fake()->address(),
            'organizer_id' => User::factory(),
        ];
    }

    public function vtt(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'vtt',
            'requires_medical_certificate' => true,
        ]);
    }
}
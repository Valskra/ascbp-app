<?php

namespace Database\Factories;

use App\Models\EventAddress;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventAddressFactory extends Factory
{
    protected $model = EventAddress::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'label' => $this->faker->optional()->randomElement(['Salle principale', 'Auditorium', 'Salle de conférence', 'Local associatif']),
            'house_number' => $this->faker->buildingNumber(),
            'street_name' => $this->faker->streetName(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country' => 'France',
            'additional_info' => $this->faker->optional()->sentence(),
        ];
    }
}

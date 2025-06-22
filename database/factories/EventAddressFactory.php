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
            'street' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => 'France',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}

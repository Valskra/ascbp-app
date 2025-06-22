<?php

// database/factories/RoleFactory.php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['admin', 'animator', 'member']),
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'permissions' => (object) [
                'admin_access' => fake()->boolean() ? 1 : 0,
                'create_events' => fake()->boolean() ? 1 : 0,
                'create_articles' => fake()->boolean() ? 1 : 0,
            ],
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'admin',
            'display_name' => 'Administrateur',
            'permissions' => (object) [
                'admin_access' => 1,
                'create_events' => 1,
                'create_articles' => 1,
            ],
        ]);
    }
}

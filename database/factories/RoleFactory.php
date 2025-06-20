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
            'name' => $this->faker->randomElement(['admin', 'animator', 'member', 'guest']),
            'permissions' => [
                'admin_access' => $this->faker->boolean(20), // 20% chance d'être admin
                'manage_events' => $this->faker->boolean(40),
                'create_articles' => $this->faker->boolean(60),
                'moderate_content' => $this->faker->boolean(30),
            ],
        ];
    }

    /**
     * État admin
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'admin',
            'permissions' => [
                'admin_access' => 1,
                'manage_events' => 1,
                'create_articles' => 1,
                'moderate_content' => 1,
            ],
        ]);
    }

    /**
     * État animateur
     */
    public function animator(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'animator',
            'permissions' => [
                'admin_access' => 0,
                'manage_events' => 1,
                'create_articles' => 1,
                'moderate_content' => 0,
            ],
        ]);
    }

    /**
     * État membre simple
     */
    public function member(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'member',
            'permissions' => [
                'admin_access' => 0,
                'manage_events' => 0,
                'create_articles' => 1,
                'moderate_content' => 0,
            ],
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'publish_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'user_id' => User::factory(),
            'status' => 'published',
            'is_post' => fake()->boolean(30),
        ];
    }
}
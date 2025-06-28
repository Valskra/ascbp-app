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

    /**
     * Créer un post court
     */
    public function post(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_post' => true,
            'title' => fake()->sentence(3), // Titre plus court pour un post
            'content' => fake()->paragraph(1), // Contenu plus court
        ]);
    }

    /**
     * Créer un article normal (pas un post)
     */
    public function article(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_post' => false,
            'content' => fake()->paragraphs(5, true), // Contenu plus long
        ]);
    }

    /**
     * Créer un brouillon
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
            'publish_date' => null,
        ]);
    }

    /**
     * Créer un article épinglé
     */
    public function pinned(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_pinned' => true, // Si vous avez cette colonne
        ]);
    }
}

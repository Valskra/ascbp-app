<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{User, Article, ArticleComment};

class ArticleCommentFactory extends Factory
{
    public function definition(): array
    {
        $comments = [
            'Très intéressant, merci pour le partage !',
            'J\'ai hâte de participer à cet événement.',
            'Excellente initiative de la part de l\'association.',
            'Merci pour ces informations utiles.',
            'Bravo pour cette belle performance !',
            'Quand aura lieu le prochain événement ?',
            'Est-ce que c\'est ouvert aux débutants ?',
            'Superbes résultats, félicitations à tous !',
            'Où peut-on s\'inscrire exactement ?',
            'J\'espère pouvoir venir cette fois-ci.',
            'Très bonne organisation comme toujours.',
            'Y a-t-il encore des places disponibles ?',
            'Merci pour ce guide très détaillé.',
            'Parfait, exactement ce que je cherchais.',
            'Belle équipe, continuez comme ça !',
            'J\'aimerais avoir plus d\'informations.',
            'Excellent travail de communication.',
            'Vivement la prochaine édition !',
            'Merci pour ces conseils pratiques.',
            'Très bon article, très instructif.',
        ];

        return [
            'content' => $this->faker->randomElement($comments),
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'is_approved' => $this->faker->boolean(95), // 95% des commentaires approuvés
        ];
    }

    public function reply(): static
    {
        return $this->state(fn(array $attributes) => [
            'parent_id' => ArticleComment::factory(),
            'content' => $this->faker->randomElement([
                'Merci pour votre retour !',
                'Je suis d\'accord avec vous.',
                'Effectivement, c\'est un point important.',
                'Vous avez tout à fait raison.',
                'Merci de votre intérêt !',
                'N\'hésitez pas si vous avez des questions.',
                'Content que cela vous plaise !',
                'Nous restons à votre disposition.',
            ]),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_approved' => false,
        ]);
    }
}

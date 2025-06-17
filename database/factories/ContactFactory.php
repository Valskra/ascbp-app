<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        $frenchFirstNames = [
            'Alexandre',
            'Antoine',
            'Arthur',
            'Baptiste',
            'Benjamin',
            'Clément',
            'David',
            'Florian',
            'Guillaume',
            'Hugo',
            'Julien',
            'Lucas',
            'Mathieu',
            'Maxime',
            'Nicolas',
            'Olivier',
            'Pierre',
            'Romain',
            'Thomas',
            'Vincent',
            'Amélie',
            'Anne',
            'Camille',
            'Caroline',
            'Charlotte',
            'Chloé',
            'Claire',
            'Émilie',
            'Emma',
            'Julie',
            'Laura',
            'Léa',
            'Lucie',
            'Manon',
            'Marie',
            'Marine',
            'Mélanie',
            'Pauline',
            'Sarah',
            'Sophie'
        ];

        $frenchLastNames = [
            'Bernard',
            'Dubois',
            'Durand',
            'Moreau',
            'Petit',
            'Robert',
            'Richard',
            'Michel',
            'Martin',
            'Roux',
            'David',
            'Bertrand',
            'Leroy',
            'Garnier',
            'Chevalier',
            'François',
            'Legrand',
            'Mercier',
            'Boyer',
            'Blanc'
        ];

        $relations = ['Parent', 'Conjoint(e)', 'Frère/Sœur', 'Ami(e)', 'Collègue', 'Voisin(e)', 'Autre'];

        $firstname = $this->faker->randomElement($frenchFirstNames);
        $lastname = $this->faker->randomElement($frenchLastNames);

        return [
            'label' => $this->faker->optional(0.7)->randomElement(['Personnel', 'Professionnel', 'Urgence', 'Famille']),
            'firstname' => $firstname,
            'lastname' => $this->faker->optional(0.8)->randomElement($frenchLastNames),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->optional(0.6)->email(),
            'relation' => $this->faker->randomElement($relations),
            'priority' => $this->faker->numberBetween(1, 5),
            'user_id' => User::factory(),
        ];
    }
}

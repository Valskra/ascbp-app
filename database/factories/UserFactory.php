<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frenchFirstNames = [
            'male' => ['Alexandre', 'Antoine', 'Arthur', 'Aurélien', 'Baptiste', 'Benjamin', 'Clément', 'Damien', 'David', 'Emmanuel', 'Fabien', 'Florian', 'Guillaume', 'Hugo', 'Jérémy', 'Julien', 'Kevin', 'Louis', 'Lucas', 'Mathieu', 'Maxime', 'Nicolas', 'Olivier', 'Pierre', 'Quentin', 'Raphaël', 'Romain', 'Sébastien', 'Thomas', 'Vincent'],
            'female' => ['Amélie', 'Anne', 'Aurélie', 'Camille', 'Caroline', 'Catherine', 'Céline', 'Charlotte', 'Chloé', 'Claire', 'Émilie', 'Emma', 'Estelle', 'Éva', 'Fanny', 'Florence', 'Isabelle', 'Julie', 'Laura', 'Léa', 'Lucie', 'Manon', 'Marie', 'Marine', 'Mélanie', 'Nathalie', 'Pauline', 'Sarah', 'Sophie', 'Virginie']
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
            'Blanc',
            'Barbier',
            'Arnaud',
            'Martinez',
            'Girard',
            'Pierre',
            'Schmitt',
            'Perrin',
            'Morel',
            'Mathieu',
            'Clement',
            'Gauthier',
            'Dupont',
            'Lopez',
            'Fontaine',
            'Rousseau',
            'Dufour',
            'Morvan',
            'Guillot',
            'Caron',
            'Brunet'
        ];

        $gender = $this->faker->randomElement(['male', 'female']);
        $firstname = $this->faker->randomElement($frenchFirstNames[$gender]);
        $lastname = $this->faker->randomElement($frenchLastNames);

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'birth_date' => $this->faker->dateTimeBetween('-70 years', '-16 years')->format('Y-m-d'),
            'phone' => $this->faker->optional(0.8)->phoneNumber(),
            'phone_secondary' => $this->faker->optional(0.3)->phoneNumber(),
            'email' => strtolower($firstname . '.' . $lastname . $this->faker->numberBetween(1, 999) . '@example.com'),
            'email_pro' => $this->faker->optional(0.4)->safeEmail(),
            'email_verified_at' => now(),
            'iban' => $this->faker->optional(0.6)->iban('FR'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

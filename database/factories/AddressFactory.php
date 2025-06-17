<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        // Villes de Seine-et-Marne
        $cities77 = [
            ['name' => 'Meaux', 'postal_code' => '77100'],
            ['name' => 'Melun', 'postal_code' => '77000'],
            ['name' => 'Chelles', 'postal_code' => '77500'],
            ['name' => 'Pontault-Combault', 'postal_code' => '77340'],
            ['name' => 'Savigny-le-Temple', 'postal_code' => '77176'],
            ['name' => 'Champs-sur-Marne', 'postal_code' => '77420'],
            ['name' => 'Villeparisis', 'postal_code' => '77270'],
            ['name' => 'Torcy', 'postal_code' => '77200'],
            ['name' => 'Roissy-en-Brie', 'postal_code' => '77680'],
            ['name' => 'Combs-la-Ville', 'postal_code' => '77380'],
            ['name' => 'Dammarie-lès-Lys', 'postal_code' => '77190'],
            ['name' => 'Lagny-sur-Marne', 'postal_code' => '77400'],
            ['name' => 'Montereau-Fault-Yonne', 'postal_code' => '77130'],
            ['name' => 'Coulommiers', 'postal_code' => '77120'],
            ['name' => 'Mitry-Mory', 'postal_code' => '77290'],
            ['name' => 'Nemours', 'postal_code' => '77140'],
            ['name' => 'Ozoir-la-Ferrière', 'postal_code' => '77330'],
            ['name' => 'Fontainebleau', 'postal_code' => '77300'],
            ['name' => 'Avon', 'postal_code' => '77210'],
            ['name' => 'Provins', 'postal_code' => '77160'],
            ['name' => 'Brie-Comte-Robert', 'postal_code' => '77170'],
            ['name' => 'Crécy-la-Chapelle', 'postal_code' => '77580'],
            ['name' => 'Noisiel', 'postal_code' => '77186'],
            ['name' => 'Lognes', 'postal_code' => '77185'],
            ['name' => 'Vaires-sur-Marne', 'postal_code' => '77360']
        ];

        $streetNames = [
            'rue de la Paix',
            'avenue de la République',
            'rue du Général de Gaulle',
            'impasse des Roses',
            'boulevard Victor Hugo',
            'rue Jean Jaurès',
            'avenue du Maréchal Foch',
            'place de la Liberté',
            'rue de la Mairie',
            'chemin des Écoliers',
            'rue des Tilleuls',
            'avenue de Verdun',
            'rue du 8 Mai 1945',
            'boulevard de la Gare',
            'rue de l\'Église',
            'avenue Jean Moulin',
            'rue des Champs',
            'place du Marché',
            'rue de la Forêt',
            'avenue de Paris'
        ];

        $selectedCity = $this->faker->randomElement($cities77);

        return [
            'label' => $this->faker->randomElement(['home', 'birth']),
            'house_number' => $this->faker->optional(0.9)->numberBetween(1, 999),
            'street_name' => $this->faker->randomElement($streetNames),
            'postal_code' => $selectedCity['postal_code'],
            'city' => $selectedCity['name'],
            'country' => 'France',
            'additional_info' => $this->faker->optional(0.2)->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function home(): static
    {
        return $this->state(fn(array $attributes) => [
            'label' => 'home',
        ]);
    }

    public function birth(): static
    {
        return $this->state(fn(array $attributes) => [
            'label' => 'birth',
            'house_number' => null,
            'street_name' => null,
            'additional_info' => null,
        ]);
    }
}

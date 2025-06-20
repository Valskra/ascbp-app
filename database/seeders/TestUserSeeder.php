<?php

// database/seeders/TestUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Role};

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un utilisateur par défaut pour les tests (id = 1)
        User::create([
            'id' => 1,
            'firstname' => 'Test',
            'lastname' => 'Organizer',
            'email' => 'organizer@test.com',
            'password' => bcrypt('password'),
            'birth_date' => '1990-01-01',
            'phone' => '0123456789',
        ]);

        // Créer des rôles de base pour les tests
        Role::create([
            'id' => 1,
            'name' => 'admin',
            'permissions' => [
                'admin_access' => 1,
                'manage_events' => 1,
                'create_articles' => 1,
                'moderate_content' => 1,
            ],
        ]);

        Role::create([
            'id' => 2,
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

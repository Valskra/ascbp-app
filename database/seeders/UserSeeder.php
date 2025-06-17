<?php
// UserSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Role, Address, Contact, Membership};

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisé uniquement si on veut seeder les utilisateurs séparément
        $roles = Role::all()->keyBy('name');

        // Créer quelques utilisateurs de test
        User::factory(20)->create()->each(function ($user) use ($roles) {
            $user->roles()->attach($roles['Membre']);

            // Adresses et données
            if (rand(1, 10) <= 7) {
                Address::factory()->home()->create(['user_id' => $user->id]);
            }

            if (rand(1, 10) <= 6) {
                Contact::factory(rand(1, 2))->create(['user_id' => $user->id]);
            }

            if (rand(1, 10) <= 2) {
                Membership::factory()->active()->create(['user_id' => $user->id]);
            }
        });
    }
}

// EventSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Event, EventAddress, User};

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des événements avec leurs adresses
        Event::factory(10)->create()->each(function ($event) {
            EventAddress::factory()->create(['event_id' => $event->id]);
        });
    }
}

// ArticleSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Article, User, Event};

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Admin', 'Animateur', 'Contributeur']);
        })->get();

        if ($authors->isEmpty()) {
            $this->command->warn('Aucun auteur trouvé. Exécutez d\'abord RolePermissionSeeder et UserSeeder.');
            return;
        }

        // Articles normaux
        Article::factory(15)->create([
            'user_id' => $authors->random()->id,
        ]);

        // Posts courts
        Article::factory(5)->post()->create([
            'user_id' => $authors->random()->id,
        ]);
    }
}

<?php

namespace Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use App\Models\Article;
use App\Models\File;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateurs ASCBP
        User::factory()->admin()->create();
        User::factory()->animator()->create();
        User::factory()->count(10)->create();
        
        // Événements variés
        Event::factory()->vtt()->count(3)->create();
        Event::factory()->count(5)->create();
        
        // Articles et posts
        Article::factory()->count(8)->create();
        
        // Fichiers
        File::factory()->count(10)->create();
        
        $this->command->info('✨ Données de test ASCBP créées avec succès !');
    }
}
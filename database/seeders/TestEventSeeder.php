<?php

// ==============================================================================
// TestEventSeeder.php - Seeder pour les événements de test
// ==============================================================================

namespace Tests\Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestEventSeeder extends Seeder
{
    public function run(): void
    {
        $organizers = User::where('role', 'admin')
            ->orWhere('role', 'moderator')
            ->orWhere('is_premium', true)
            ->get();

        if ($organizers->isEmpty()) {
            $organizers = User::factory()->count(3)->create();
        }

        // Événements à venir (futurs)
        Event::factory()
            ->upcoming()
            ->count(8)
            ->create([
                'organizer_id' => $organizers->random()->id,
            ]);

        // Événements passés
        Event::factory()
            ->past()
            ->count(12)
            ->create([
                'organizer_id' => $organizers->random()->id,
            ]);

        // Événement aujourd'hui
        Event::factory()
            ->today()
            ->count(2)
            ->create([
                'organizer_id' => $organizers->first()->id,
                'title' => 'Événement du jour',
            ]);

        // Événements gratuits
        Event::factory()
            ->free()
            ->upcoming()
            ->count(4)
            ->create([
                'organizer_id' => $organizers->random()->id,
            ]);

        // Événements premium
        Event::factory()
            ->premium()
            ->upcoming()
            ->count(3)
            ->create([
                'organizer_id' => $organizers->random()->id,
            ]);

        // Événements complets
        Event::factory()
            ->full()
            ->upcoming()
            ->count(2)
            ->create([
                'organizer_id' => $organizers->random()->id,
                'title' => 'Événement complet',
            ]);

        // Brouillons
        Event::factory()
            ->draft()
            ->count(5)
            ->create([
                'organizer_id' => $organizers->random()->id,
            ]);

        // Événements annulés
        Event::factory()
            ->cancelled()
            ->count(3)
            ->create([
                'organizer_id' => $organizers->random()->id,
            ]);

        // Événement spécifique pour tests
        Event::factory()->create([
            'title' => 'Événement de Test Spécifique',
            'slug' => 'evenement-test-specifique',
            'description' => 'Cet événement est créé spécifiquement pour les tests automatisés.',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(30)->addHours(4),
            'location' => 'Centre de Test, 123 Rue de Test, 75001 Paris',
            'max_participants' => 50,
            'price' => 25.00,
            'organizer_id' => $organizers->first()->id,
            'status' => 'published',
        ]);
    }
}

// ==============================================================================
// TestArticleSeeder.php - Seeder pour les articles de test
// ==============================================================================

namespace Tests\Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authors = User::where('role', 'admin')
            ->orWhere('role', 'moderator')
            ->get();

        if ($authors->isEmpty()) {
            $authors = User::factory()->count(3)->create();
        }

        // Articles publiés récents
        Article::factory()
            ->recent()
            ->withCategory()
            ->withTags()
            ->count(10)
            ->create([
                'author_id' => $authors->random()->id,
            ]);

        // Articles populaires
        Article::factory()
            ->popular()
            ->featured()
            ->withCategory()
            ->withTags()
            ->count(5)
            ->create([
                'author_id' => $authors->random()->id,
            ]);

        // Articles en brouillon
        Article::factory()
            ->draft()
            ->withCategory()
            ->count(8)
            ->create([
                'author_id' => $authors->random()->id,
            ]);

        // Articles avec catégories spécifiques
        $categories = [
            'Actualités' => 'actualites',
            'Tutoriels' => 'tutoriels',
            'Événements' => 'evenements',
            'Communauté' => 'communaute',
            'Technologie' => 'technologie'
        ];

        foreach ($categories as $categoryName => $categorySlug) {
            Article::factory()
                ->count(3)
                ->create([
                    'author_id' => $authors->random()->id,
                    'category_name' => $categoryName,
                    'category_slug' => $categorySlug,
                    'title' => "Article {$categoryName} - " . fake()->sentence(3),
                ]);
        }

        // Article spécifique pour tests
        Article::factory()->create([
            'title' => 'Article de Test Complet',
            'slug' => 'article-test-complet',
            'excerpt' => 'Cet article est créé spécifiquement pour les tests automatisés et contient toutes les propriétés nécessaires.',
            'content' => $this->getTestArticleContent(),
            'author_id' => $authors->first()->id,
            'status' => 'published',
            'is_featured' => true,
            'category_name' => 'Tests',
            'category_slug' => 'tests',
            'tags' => json_encode(['test', 'automation', 'laravel', 'phpunit']),
            'meta_description' => 'Article complet pour tester toutes les fonctionnalités.',
            'reading_time' => 5,
            'published_at' => now()->subDays(7),
        ]);
    }

    private function getTestArticleContent(): string
    {
        return <<<'MARKDOWN'
# Article de Test Complet

Cet article a été créé spécifiquement pour les tests automatisés de l'application Laravel.

## Contenu de Test

### Section 1 : Introduction
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.

### Section 2 : Développement
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

### Section 3 : Conclusion
Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.

## Code Example

```php
<?php
// Exemple de code pour les tests
public function testExample()
{
    $this->assertTrue(true);
}
```

## Liste de Tests
- Test unitaire
- Test d'intégration
- Test fonctionnel
- Test de performance

**Note importante** : Cet article contient des données de test uniquement.
MARKDOWN;
    }
}

// ==============================================================================
// TestPaymentSeeder.php - Seeder pour les paiements de test
// ==============================================================================

namespace Tests\Database\Seeders;

use App\Models\Payment;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Seeder;

class TestPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $events = Event::where('price', '>', 0)->get();

        if ($users->isEmpty() || $events->isEmpty()) {
            $this->command->warn('Aucun utilisateur ou événement disponible pour créer des paiements');
            return;
        }

        // Paiements réussis récents
        Payment::factory()
            ->count(25)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiements en attente
        Payment::factory()
            ->pending()
            ->count(5)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiements échoués
        Payment::factory()
            ->failed()
            ->count(8)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiements remboursés
        Payment::factory()
            ->refunded()
            ->count(4)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Remboursements partiels
        Payment::factory()
            ->partialRefund()
            ->count(2)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiements de montant élevé
        Payment::factory()
            ->highAmount()
            ->count(3)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiements récurrents
        Payment::factory()
            ->recurring()
            ->count(6)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiements internationaux
        Payment::factory()
            ->international()
            ->count(4)
            ->create([
                'user_id' => $users->random()->id,
                'event_id' => $events->random()->id,
            ]);

        // Paiement spécifique pour tests
        Payment::factory()->create([
            'user_id' => User::where('email', 'user@test.local')->first()->id,
            'event_id' => $events->first()->id,
            'amount' => 50.00,
            'currency' => 'EUR',
            'status' => 'completed',
            'stripe_payment_id' => 'pi_test_specific_payment_id',
            'description' => 'Paiement de test spécifique',
            'processed_at' => now()->subDays(5),
        ]);
    }
}

// ==============================================================================
// TestFileSeeder.php - Seeder pour les fichiers de test
// ==============================================================================

namespace Tests\Database\Seeders;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestFileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur disponible pour créer des fichiers');
            return;
        }

        // Images de profil
        File::factory()
            ->avatar()
            ->count(10)
            ->create([
                'uploaded_by' => $users->random()->id,
            ]);

        // Images générales
        File::factory()
            ->image()
            ->count(15)
            ->create([
                'uploaded_by' => $users->random()->id,
            ]);

        // Documents
        File::factory()
            ->document()
            ->count(12)
            ->create([
                'uploaded_by' => $users->random()->id,
            ]);

        // Certificats valides
        File::factory()
            ->certificate()
            ->count(8)
            ->create([
                'uploaded_by' => $users->random()->id,
                'is_validated' => true,
            ]);

        // Certificats expirés
        File::factory()
            ->certificate()
            ->expired()
            ->count(3)
            ->create([
                'uploaded_by' => $users->random()->id,
                'is_validated' => false,
            ]);

        // Fichiers volumineux
        File::factory()
            ->large()
            ->count(4)
            ->create([
                'uploaded_by' => $users->random()->id,
            ]);

        // Fichiers spécifiques pour tests
        File::factory()->create([
            'name' => 'test-image.jpg',
            'original_name' => 'test-image.jpg',
            'path' => 'test/test-image.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 156789,
            'uploaded_by' => User::where('email', 'admin@test.local')->first()->id,
            'is_public' => true,
            'metadata' => json_encode([
                'width' => 800,
                'height' => 600,
                'format' => 'JPEG',
                'test_file' => true,
            ]),
        ]);

        File::factory()->create([
            'name' => 'test-document.pdf',
            'original_name' => 'test-document.pdf',
            'path' => 'test/test-document.pdf',
            'type' => 'document',
            'mime_type' => 'application/pdf',
            'size' => 245678,
            'uploaded_by' => User::where('email', 'user@test.local')->first()->id,
            'is_public' => false,
            'metadata' => json_encode([
                'pages' => 5,
                'searchable' => true,
                'test_file' => true,
            ]),
        ]);
    }
}

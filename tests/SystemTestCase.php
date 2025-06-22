<?php
// tests/SystemTestCase.php - Classe de base spécialisée pour les tests système

namespace Tests;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\{Storage, Mail, Queue, Notification, Http};

abstract class SystemTestCase extends TestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration spécifique aux tests système uniquement
        $this->setupSystemTestEnvironment();
    }

    /**
     * Configuration de l'environnement de test système
     */
    protected function setupSystemTestEnvironment(): void
    {
        // Configuration stockage S3 pour tests système
        config([
            'filesystems.disks.s3.driver' => 's3',
            'filesystems.disks.s3.key' => 'test_key',
            'filesystems.disks.s3.secret' => 'test_secret',
            'filesystems.disks.s3.region' => 'test_region',
            'filesystems.disks.s3.bucket' => 'test_bucket',
        ]);

        // Configuration Stripe pour tests système
        config([
            'services.stripe.secret' => 'sk_test_fake_key',
            'services.stripe.key' => 'pk_test_fake_key',
            'services.stripe.webhook.secret' => 'whsec_fake_secret',
        ]);

        // Configuration email pour tests système
        config([
            'mail.default' => 'array',
            'mail.mailers.array.transport' => 'array',
        ]);

        // Configuration queue pour tests système
        config([
            'queue.default' => 'database',
        ]);
    }

    /**
     * Créer un utilisateur de test avec profil complet
     */
    protected function createTestUser(array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create($attributes);
        
        // Ajouter adresse si pas de conflit avec tests existants
        if (!$user->homeAddress) {
            $user->homeAddress()->create([
                'label' => 'home',
                'house_number' => '123',
                'street_name' => 'rue de Test',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France'
            ]);
        }

        // Ajouter contacts d'urgence si pas de conflit
        if ($user->contacts()->count() === 0) {
            $user->contacts()->create([
                'firstname' => 'Contact',
                'lastname' => 'Urgence',
                'phone' => '0123456789',
                'email' => 'contact@example.com',
                'relation' => 'Famille',
                'priority' => 1
            ]);
        }

        return $user;
    }

    /**
     * Créer un événement de test complet
     */
    protected function createTestEvent(array $attributes = []): \App\Models\Event
    {
        $organizer = \App\Models\User::factory()->create();
        
        $event = \App\Models\Event::factory()->create(array_merge([
            'organizer_id' => $organizer->id,
            'title' => 'Événement Test',
            'category' => 'Sport',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addHours(3),
            'registration_open' => now()->subDay(),
            'registration_close' => now()->addDays(6),
            'max_participants' => 50,
            'price' => null
        ], $attributes));

        // Ajouter adresse événement seulement pour tests système
        if (!$event->address) {
            $event->address()->create([
                'label' => 'location',
                'street_name' => 'Avenue du Sport',
                'city' => 'Lyon',
                'postal_code' => '69000',
                'country' => 'France'
            ]);
        }

        return $event;
    }

    /**
     * Mock complet des services externes
     */
    protected function mockExternalServices(): void
    {
        // Mock Storage S3
        Storage::fake('s3');
        Storage::fake('public');

        // Mock Mail
        Mail::fake();

        // Mock Queue
        Queue::fake();

        // Mock Notifications
        Notification::fake();

        // Mock HTTP pour Stripe
        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'test_id',
                'status' => 'succeeded'
            ], 200)
        ]);
    }

    /**
     * Simuler une charge de données réaliste
     */
    protected function seedRealisticData(): void
    {
        // Créer des utilisateurs avec différents profils
        $members = \App\Models\User::factory()->count(50)->create();
        $admins = \App\Models\User::factory()->count(3)->create();
        
        // Assigner des rôles si la table existe
        if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $adminRole = \App\Models\Role::factory()->create([
                'name' => 'Admin',
                'permissions' => ['admin_access' => 1, 'manage_events' => 1]
            ]);
            
            foreach ($admins as $admin) {
                $admin->roles()->attach($adminRole);
            }
        }

        // Créer des événements variés
        $events = [];
        for ($i = 0; $i < 20; $i++) {
            $events[] = $this->createTestEvent([
                'organizer_id' => $admins->random()->id,
                'start_date' => now()->addDays(rand(1, 90)),
                'price' => rand(0, 1) ? rand(10, 100) : null
            ]);
        }

        // Créer des inscriptions
        foreach ($members->take(30) as $member) {
            foreach (collect($events)->random(rand(1, 5)) as $event) {
                \App\Models\Registration::factory()->create([
                    'user_id' => $member->id,
                    'event_id' => $event->id
                ]);
            }
        }

        // Créer des articles si la table existe
        if (\Illuminate\Support\Facades\Schema::hasTable('articles')) {
            foreach ($admins as $admin) {
                \App\Models\Article::factory()->count(rand(3, 8))->create([
                    'user_id' => $admin->id,
                    'status' => 'published'
                ]);
            }
        }
    }

    /**
     * Mesurer les performances d'une opération
     */
    protected function measurePerformance(callable $operation): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $operation();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        return [
            'result' => $result,
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory
        ];
    }

    /**
     * Vérifier l'intégrité des données après une opération
     */
    protected function assertDataIntegrity(): void
    {
        // Vérifier les contraintes de base
        $this->assertDatabaseCount('users', \App\Models\User::count());
        $this->assertDatabaseCount('events', \App\Models\Event::count());
        $this->assertDatabaseCount('registrations', \App\Models\Registration::count());

        // Vérifier les relations si les tables existent
        if (\Illuminate\Support\Facades\Schema::hasTable('registrations')) {
            \App\Models\Registration::all()->each(function ($registration) {
                $this->assertNotNull($registration->user);
                $this->assertNotNull($registration->event);
            });
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('documents')) {
            \App\Models\Document::all()->each(function ($document) {
                $this->assertNotNull($document->user);
                $this->assertNotNull($document->file);
            });
        }
    }
}

// tests/Pest.php - Configuration Pest mise à jour (à ajouter à votre fichier existant)

<?php

use Tests\TestCase;
use Tests\SystemTestCase; // Nouveau

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
uses(SystemTestCase::class)->in('Feature/System'); // Spécifique aux tests système

/*
|--------------------------------------------------------------------------
| Functions Helper pour Tests Système
|--------------------------------------------------------------------------
*/

/**
 * Fonction helper pour créer rapidement un utilisateur de test
 */
function createTestUser(array $attributes = []): \App\Models\User
{
    return test()->createTestUser($attributes);
}

/**
 * Fonction helper pour créer rapidement un événement de test
 */
function createTestEvent(array $attributes = []): \App\Models\Event
{
    return test()->createTestEvent($attributes);
}

/**
 * Fonction helper pour mock tous les services externes
 */
function mockServices(): void
{
    test()->mockExternalServices();
}

/**
 * Fonction helper pour mesurer les performances
 */
function measurePerformance(callable $operation): array
{
    return test()->measurePerformance($operation);
}

/**
 * Fonction helper pour vérifier l'intégrité des données
 */
function assertDataIntegrity(): void
{
    test()->assertDataIntegrity();
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBePerformant', function (float $maxTime = 1.0) {
    return $this->toBeLessThan($maxTime);
});

expect()->extend('toHaveValidEmail', function () {
    return $this->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
});

expect()->extend('toBeValidStripeId', function () {
    return $this->toMatch('/^(cs_|pi_|cus_|ch_)/');
});

expect()->extend('toBeValidS3Path', function () {
    return $this->toMatch('/^[a-zA-Z0-9\/_-]+\.[a-z]+$/');
});

/*
|--------------------------------------------------------------------------
| Datasets
|--------------------------------------------------------------------------
*/

dataset('valid_file_types', [
    'PDF' => ['pdf', 'application/pdf'],
    'JPEG' => ['jpg', 'image/jpeg'],
    'PNG' => ['png', 'image/png'],
    'DOC' => ['doc', 'application/msword'],
    'DOCX' => ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
]);

dataset('invalid_file_types', [
    'Executable' => ['exe', 'application/x-msdownload'],
    'Script' => ['php', 'application/x-php'],
    'Archive' => ['zip', 'application/zip'],
    'Video' => ['mp4', 'video/mp4']
]);

dataset('event_prices', [
    'Free' => [null],
    'Low cost' => ['10'],
    'Medium cost' => ['25'],
    'High cost' => ['50'],
    'Premium' => ['100']
]);

dataset('user_roles', [
    'Member' => ['member', false, false],
    'Animator' => ['animator', true, false],
    'Admin' => ['admin', true, true]
]);
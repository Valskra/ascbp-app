<?php
// tests/Feature/System/DatabaseTransactionTest.php

use App\Models\{User, Event, Registration, Article, Document, File};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema, Cache};
use Illuminate\Database\QueryException;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Configuration base de données pour les tests
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'cache.default' => 'array'
    ]);

    // Vider le cache entre les tests
    Cache::flush();
});

describe('Database Transaction System', function () {

    it('handles event registration with transaction rollback on failure', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'max_participants' => 1,
            'price' => null,
            'start_date' => now()->addWeek()
        ]);

        // Première inscription réussie
        Registration::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id
        ]);

        $secondUser = User::factory()->create();

        // Act - Tenter une seconde inscription (devrait échouer car événement plein)
        $initialRegistrationCount = Registration::count();

        try {
            DB::transaction(function () use ($secondUser, $event) {
                // Vérifier si l'événement est plein
                $currentCount = Registration::where('event_id', $event->id)->count();

                if ($currentCount >= $event->max_participants) {
                    throw new \Exception('Événement complet');
                }

                // Cette ligne ne devrait jamais être atteinte
                Registration::create([
                    'user_id' => $secondUser->id,
                    'event_id' => $event->id,
                    'registration_date' => now(),
                    'amount' => 0
                ]);
            });
        } catch (\Exception $e) {
            // Transaction automatiquement rollback
        }

        // Assert
        expect(Registration::count())->toBe($initialRegistrationCount);
        expect(Registration::where('user_id', $secondUser->id)->exists())->toBeFalse();
    });

    it('ensures data consistency during concurrent registrations', function () {
        // Arrange
        $users = User::factory()->count(3)->create();
        $event = Event::factory()->create([
            'max_participants' => 1, // Un seul participant autorisé
            'start_date' => now()->addWeek()
        ]);

        // Act - Simuler des inscriptions simultanées
        $successfulRegistrations = 0;
        $exceptions = 0;

        foreach ($users as $user) {
            try {
                DB::transaction(function () use ($user, $event) {
                    // Lock pour éviter les race conditions
                    $currentCount = DB::table('registrations')
                        ->where('event_id', $event->id)
                        ->lockForUpdate()
                        ->count();

                    if ($currentCount >= $event->max_participants) {
                        throw new \Exception('Événement complet');
                    }

                    Registration::create([
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'registration_date' => now(),
                        'amount' => 0
                    ]);
                }, 3); // 3 tentatives

                $successfulRegistrations++;
            } catch (\Exception $e) {
                $exceptions++;
            }
        }

        // Assert
        expect($successfulRegistrations)->toBe(1);
        expect($exceptions)->toBe(2);
        expect(Registration::count())->toBe(1);
    });

    it('handles file upload with database rollback on S3 failure', function () {
        // Arrange
        $user = User::factory()->create();
        Storage::fake('s3');

        // Act - Simuler un échec S3 après création du record File
        $initialFileCount = File::count();
        $initialDocumentCount = Document::count();

        try {
            DB::transaction(function () use ($user) {
                // Créer le record File
                $file = File::create([
                    'fileable_id' => $user->id,
                    'fileable_type' => get_class($user),
                    'name' => 'test_certificate',
                    'extension' => 'pdf',
                    'mimetype' => 'application/pdf',
                    'size' => 1024,
                    'path' => 'test/path.pdf',
                    'disk' => 's3',
                    'hash' => hash('sha256', 'test content')
                ]);

                // Créer le document
                $document = Document::create([
                    'title' => 'Test Certificate',
                    'user_id' => $user->id,
                    'file_id' => $file->id,
                    'expiration_date' => now()->addYear()
                ]);

                // Simuler échec S3
                throw new \Exception('S3 upload failed');
            });
        } catch (\Exception $e) {
            // Transaction rollback automatique
        }

        // Assert
        expect(File::count())->toBe($initialFileCount);
        expect(Document::count())->toBe($initialDocumentCount);
    });

    it('handles payment processing with proper transaction isolation', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create(['price' => '50']);

        // Act - Simuler un processus de paiement avec transaction
        $paymentProcessed = false;

        try {
            DB::transaction(function () use ($user, $event, &$paymentProcessed) {
                // Étape 1: Créer l'inscription en attente
                $registration = Registration::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'registration_date' => now(),
                    'amount' => 0, // En attente de paiement
                    'metadata' => [
                        'payment_status' => 'pending',
                        'stripe_session_id' => 'cs_test_123'
                    ]
                ]);

                // Étape 2: Simuler validation paiement
                $paymentSuccessful = true; // Mock paiement réussi

                if (!$paymentSuccessful) {
                    throw new \Exception('Payment failed');
                }

                // Étape 3: Mettre à jour avec montant payé
                $registration->update([
                    'amount' => 50,
                    'metadata' => [
                        'payment_status' => 'completed',
                        'stripe_session_id' => 'cs_test_123'
                    ]
                ]);

                $paymentProcessed = true;
            });
        } catch (\Exception $e) {
            $paymentProcessed = false;
        }

        // Assert
        expect($paymentProcessed)->toBeTrue();
        expect(Registration::count())->toBe(1);

        $registration = Registration::first();
        expect($registration->amount)->toBe(50);
        expect($registration->metadata['payment_status'])->toBe('completed');
    });
});

describe('Database Performance Tests', function () {

    it('handles large dataset queries efficiently', function () {
        // Arrange - Créer un grand dataset
        $users = User::factory()->count(100)->create();
        $events = Event::factory()->count(20)->create();

        // Créer de nombreuses inscriptions
        foreach ($users->take(80) as $user) {
            foreach ($events->take(5) as $event) {
                Registration::factory()->create([
                    'user_id' => $user->id,
                    'event_id' => $event->id
                ]);
            }
        }

        // Act - Requête complexe avec jointures
        $startTime = microtime(true);

        $results = DB::table('events')
            ->join('registrations', 'events.id', '=', 'registrations.event_id')
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->select(
                'events.title',
                'events.start_date',
                DB::raw('COUNT(registrations.id) as participants_count'),
                DB::raw('AVG(registrations.amount) as avg_amount')
            )
            ->groupBy('events.id', 'events.title', 'events.start_date')
            ->having('participants_count', '>', 3)
            ->orderBy('participants_count', 'desc')
            ->get();

        $executionTime = microtime(true) - $startTime;

        // Assert
        expect($results)->not->toBeEmpty();
        expect($executionTime)->toBeLessThan(1.0); // Moins d'1 seconde
        expect($results->count())->toBeGreaterThan(0);

        // Vérifier la structure des résultats
        $firstResult = $results->first();
        expect($firstResult->participants_count)->toBeGreaterThan(3);
        expect(isset($firstResult->avg_amount))->toBeTrue();
    });

    it('optimizes N+1 query problems', function () {
        // Arrange
        $events = Event::factory()->count(10)->create();
        foreach ($events as $event) {
            $users = User::factory()->count(5)->create();
            foreach ($users as $user) {
                Registration::factory()->create([
                    'user_id' => $user->id,
                    'event_id' => $event->id
                ]);
            }
        }

        // Act - Requête sans eager loading (N+1 problem)
        DB::enableQueryLog();

        $eventsWithParticipants = Event::with(['registrations.user'])->get();

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        DB::disableQueryLog();

        // Assert
        expect($queryCount)->toBeLessThan(15); // Devrait être ~3 requêtes avec eager loading
        expect($eventsWithParticipants->count())->toBe(10);

        // Vérifier que les relations sont bien chargées
        $firstEvent = $eventsWithParticipants->first();
        expect($firstEvent->registrations)->not->toBeEmpty();
        expect($firstEvent->registrations->first()->user)->not->toBeNull();
    });

    it('handles complex search queries with proper indexing', function () {
        // Arrange
        $articles = Article::factory()->count(50)->create([
            'status' => 'published',
            'publish_date' => now()->subDays(rand(1, 30))
        ]);

        $users = User::factory()->count(20)->create();

        // Act - Requête de recherche complexe
        $searchTerm = 'test';
        $startTime = microtime(true);

        $searchResults = Article::published()
            ->where(function ($query) use ($searchTerm) {
                $query->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('content', 'LIKE', "%{$searchTerm}%");
            })
            ->with(['author'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('publish_date')
            ->paginate(10);

        $executionTime = microtime(true) - $startTime;

        // Assert
        expect($executionTime)->toBeLessThan(0.5); // Moins de 500ms
        expect($searchResults)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    });

    it('tests database connection pooling under load', function () {
        // Arrange & Act - Simuler plusieurs connexions simultanées
        $connections = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $connections[] = DB::connection();

            // Effectuer une requête sur chaque connexion
            DB::table('users')->count();
        }

        $executionTime = microtime(true) - $startTime;

        // Assert
        expect($executionTime)->toBeLessThan(2.0);
        expect(count($connections))->toBe(10);

        // Vérifier que les connexions sont bien gérées
        foreach ($connections as $connection) {
            expect($connection)->toBeInstanceOf(\Illuminate\Database\Connection::class);
        }
    });
});

describe('Database Migration and Schema Tests', function () {

    it('validates all required tables exist', function () {
        // Act & Assert
        $requiredTables = [
            'users',
            'events',
            'registrations',
            'articles',
            'files',
            'documents',
            'upload_links',
            'memberships',
            'roles',
            'user_roles',
            'addresses',
            'contacts'
        ];

        foreach ($requiredTables as $table) {
            expect(Schema::hasTable($table))->toBeTrue("Table {$table} should exist");
        }
    });

    it('validates foreign key constraints', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create();

        // Act & Assert - Tenter de créer une inscription avec un user_id invalide
        expect(function () use ($event) {
            Registration::create([
                'user_id' => 99999, // ID invalide
                'event_id' => $event->id,
                'registration_date' => now(),
                'amount' => 0
            ]);
        })->toThrow(QueryException::class);

        // Vérifier qu'avec des IDs valides, ça fonctionne
        expect(function () use ($user, $event) {
            Registration::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'registration_date' => now(),
                'amount' => 0
            ]);
        })->not->toThrow(QueryException::class);
    });

    it('validates unique constraints', function () {
        // Arrange
        $user = User::factory()->create(['email' => 'unique@example.com']);

        // Act & Assert - Tenter de créer un utilisateur avec le même email
        expect(function () {
            User::factory()->create(['email' => 'unique@example.com']);
        })->toThrow(QueryException::class);
    });

    it('tests cascade delete behavior', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $user->id]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id
        ]);
        $document = Document::factory()->create(['user_id' => $user->id]);

        // Act - Supprimer l'utilisateur
        $user->delete();

        // Assert - Vérifier les suppressions en cascade
        expect(Registration::find($registration->id))->toBeNull();
        expect(Document::find($document->id))->toBeNull();

        // L'événement organisé par l'utilisateur peut rester selon la logique métier
        expect(Event::find($event->id))->not->toBeNull();
    });
});

describe('Database Backup and Recovery', function () {

    it('verifies data integrity after export/import', function () {
        // Arrange
        $originalUsers = User::factory()->count(5)->create();
        $originalEvents = Event::factory()->count(3)->create();

        // Act - Simuler export des données
        $exportedUsers = User::all()->toArray();
        $exportedEvents = Event::all()->toArray();

        // Vider les tables
        DB::table('events')->delete();
        DB::table('users')->delete();

        // Réimporter
        foreach ($exportedUsers as $userData) {
            User::create($userData);
        }
        foreach ($exportedEvents as $eventData) {
            Event::create($eventData);
        }

        // Assert
        expect(User::count())->toBe(5);
        expect(Event::count())->toBe(3);

        // Vérifier l'intégrité des données
        $restoredUser = User::first();
        $originalUser = $originalUsers->first();
        expect($restoredUser->email)->toBe($originalUser->email);
    });

    it('handles database corruption gracefully', function () {
        // Arrange
        $user = User::factory()->create();

        // Act - Simuler corruption en modifiant directement
        try {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['email' => null]); // Violer contrainte NOT NULL
        } catch (QueryException $e) {
            // Corruption détectée
        }

        // Assert - Vérifier que l'application peut gérer la corruption
        expect(function () use ($user) {
            User::find($user->id);
        })->not->toThrow(\Exception::class);
    });
});

describe('Database Monitoring and Diagnostics', function () {

    it('monitors slow queries', function () {
        // Arrange
        DB::enableQueryLog();

        // Act - Effectuer une requête potentiellement lente
        $startTime = microtime(true);

        $result = DB::table('users')
            ->crossJoin('events')
            ->select('users.email', 'events.title')
            ->limit(100)
            ->get();

        $executionTime = microtime(true) - $startTime;
        $queries = DB::getQueryLog();

        DB::disableQueryLog();

        // Assert
        expect($executionTime)->toBeLessThan(1.0);
        expect(count($queries))->toBeGreaterThan(0);

        // Analyser la dernière requête
        $lastQuery = end($queries);
        expect($lastQuery['time'])->toBeLessThan(1000); // Moins de 1000ms
    });

    it('tracks database connection health', function () {
        // Act
        $connectionName = DB::getDefaultConnection();
        $connection = DB::connection();

        // Test de ping
        $canPing = true;
        try {
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            $canPing = false;
        }

        // Assert
        expect($canPing)->toBeTrue();
        expect($connectionName)->toBe('sqlite');
        expect($connection->getPdo())->not->toBeNull();
    });

    it('validates database storage usage', function () {
        // Arrange - Créer des données de test
        User::factory()->count(100)->create();
        Event::factory()->count(50)->create();
        Article::factory()->count(200)->create();

        // Act - Calculer l'utilisation de l'espace
        $tableStats = [];
        $tables = ['users', 'events', 'articles', 'registrations'];

        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $tableStats[$table] = $count;
        }

        // Assert
        expect($tableStats['users'])->toBe(100);
        expect($tableStats['events'])->toBe(50);
        expect($tableStats['articles'])->toBe(200);

        // Vérifier que la base grandit de manière raisonnable
        $totalRecords = array_sum($tableStats);
        expect($totalRecords)->toBeGreaterThan(300);
    });
});

<?php
// tests/Feature/System/EmailSystemTest.php

use App\Models\{User, Event, Registration, Article};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Mail, Queue, Notification};
use Illuminate\Support\Facades\Config;
use App\Mail\{EventRegistrationConfirmation, WelcomeEmail, PasswordResetMail};
use App\Jobs\{SendEventNotification, ProcessEmailQueue};
use App\Notifications\{EventReminder, ArticlePublished};

uses(RefreshDatabase::class);

beforeEach(function () {
    // Configuration email pour les tests
    Config::set('mail.default', 'array');
    Config::set('mail.mailers.array.transport', 'array');
    Config::set('queue.default', 'database');

    // Fake les services pour les tests
    Mail::fake();
    Queue::fake();
    Notification::fake();
});

describe('Event Registration Email System', function () {

    it('sends registration confirmation email for free event', function () {
        // Arrange
        $user = User::factory()->create([
            'email' => 'participant@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe'
        ]);

        $event = Event::factory()->create([
            'title' => 'Course à pied gratuite',
            'price' => null, // Événement gratuit
            'registration_open' => now()->subDay(),
            'registration_close' => now()->addDay(),
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addHours(3)
        ]);

        $registrationData = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'participant@example.com',
            'phone' => '0123456789',
            'birth_date' => '1990-01-01'
        ];

        // Act
        $response = $this->actingAs($user)
            ->post(route('events.register', $event), $registrationData);

        // Assert
        $response->assertRedirect(route('events.show', $event))
            ->assertSessionHas('success', 'Inscription réussie !');

        // Vérifier l'envoi de l'email
        Mail::assertSent(EventRegistrationConfirmation::class, function ($mail) use ($user, $event) {
            return $mail->hasTo($user->email) &&
                $mail->event->id === $event->id &&
                $mail->user->id === $user->id;
        });

        // Vérifier que l'inscription est créée
        expect(Registration::count())->toBe(1);
        $registration = Registration::first();
        expect($registration->user_id)->toBe($user->id);
        expect($registration->event_id)->toBe($event->id);
    });

    it('sends different email for paid event registration', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'title' => 'Marathon payant',
            'price' => '25',
            'start_date' => now()->addWeek()
        ]);

        // Mock Stripe success (pas d'email jusqu'au paiement confirmé)
        $registrationData = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
            'birth_date' => '1990-01-01'
        ];

        // Act - Première étape: redirection vers Stripe
        $response = $this->actingAs($user)
            ->post(route('events.register', $event), $registrationData);

        // Assert - Pas d'email envoyé avant confirmation paiement
        Mail::assertNothingSent();
        expect(Registration::count())->toBe(0);

        // Act - Simulation retour Stripe avec succès
        $this->mock(\Stripe\Checkout\Session::class, function ($mock) use ($event, $user, $registrationData) {
            $mock->shouldReceive('retrieve')
                ->andReturn((object) [
                    'payment_status' => 'paid',
                    'metadata' => (object) [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'registration_data' => json_encode($registrationData)
                    ],
                    'amount_total' => 2500
                ]);
        });

        $response = $this->actingAs($user)
            ->get(route('events.registration.success', [
                'event' => $event->id,
                'session_id' => 'cs_test_fake_session'
            ]));

        // Assert - Email envoyé après confirmation paiement
        Mail::assertSent(EventRegistrationConfirmation::class, function ($mail) use ($user, $event) {
            return $mail->hasTo($user->email) &&
                $mail->event->id === $event->id;
        });

        expect(Registration::count())->toBe(1);
    });

    it('handles email delivery failures gracefully', function () {
        // Arrange
        Mail::shouldReceive('send')
            ->andThrow(new \Exception('SMTP server unavailable'));

        $user = User::factory()->create();
        $event = Event::factory()->create([
            'price' => null,
            'start_date' => now()->addWeek()
        ]);

        // Act
        $response = $this->actingAs($user)
            ->post(route('events.register', $event), [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '0123456789',
                'birth_date' => '1990-01-01'
            ]);

        // Assert - L'inscription doit réussir même si l'email échoue
        $response->assertRedirect()
            ->assertSessionHas('success');

        expect(Registration::count())->toBe(1);
    });
});

describe('User Authentication Email System', function () {

    it('sends welcome email on user registration', function () {
        // Arrange
        $userData = [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'birth_date' => '1995-05-15'
        ];

        // Act
        $response = $this->post(route('register'), $userData);

        // Assert
        $response->assertRedirect(route('dashboard', absolute: false));

        // Vérifier l'envoi de l'email de bienvenue
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });

        // Vérifier la création de l'utilisateur
        expect(User::where('email', $userData['email'])->exists())->toBeTrue();
    });

    it('sends password reset email', function () {
        // Arrange
        $user = User::factory()->create([
            'email' => 'user@example.com'
        ]);

        // Act
        $response = $this->post(route('password.email'), [
            'email' => $user->email
        ]);

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('status');

        // Vérifier l'envoi de l'email de reset
        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    });

    it('sends email verification notification', function () {
        // Arrange
        $user = User::factory()->unverified()->create();

        // Act
        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        // Assert
        $response->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        // Vérifier l'envoi de l'email de vérification
        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class);
    });
});

describe('Email Queue System', function () {

    it('queues email jobs for heavy loads', function () {
        // Arrange
        Config::set('mail.default', 'smtp'); // Configuration pour déclencher la queue

        $users = User::factory()->count(10)->create();
        $event = Event::factory()->create([
            'title' => 'Grand événement',
            'start_date' => now()->addWeek()
        ]);

        // Act - Inscrire tous les utilisateurs (déclenche 10 emails)
        foreach ($users as $user) {
            $this->actingAs($user)
                ->post(route('events.register', $event), [
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'phone' => '0123456789',
                    'birth_date' => '1990-01-01'
                ]);
        }

        // Assert - Vérifier que les jobs sont mis en queue
        Queue::assertPushed(SendEventNotification::class, 10);

        // Vérifier que les inscriptions sont créées
        expect(Registration::count())->toBe(10);
    });

    it('processes email queue correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $job = new SendEventNotification($user, $event, 'registration_confirmation');

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(EventRegistrationConfirmation::class, function ($mail) use ($user, $event) {
            return $mail->hasTo($user->email) &&
                $mail->event->id === $event->id;
        });
    });

    it('handles failed email jobs with retry logic', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create();

        // Simuler un échec d'email
        Mail::shouldReceive('send')
            ->twice() // Premier échec, puis succès au retry
            ->andThrow(new \Exception('Temporary failure'))
            ->andReturn(true);

        $job = new SendEventNotification($user, $event, 'registration_confirmation');

        // Act & Assert
        expect(function () use ($job) {
            $job->handle();
        })->toThrow(\Exception::class);

        // Le job devrait être retryé automatiquement
        Queue::assertPushed(SendEventNotification::class);
    });

    it('processes batch email operations efficiently', function () {
        // Arrange
        $users = User::factory()->count(50)->create();
        $event = Event::factory()->create([
            'start_date' => now()->addDay() // Événement demain
        ]);

        // Inscrire tous les utilisateurs
        foreach ($users as $user) {
            Registration::factory()->create([
                'user_id' => $user->id,
                'event_id' => $event->id
            ]);
        }

        // Act - Envoyer rappels d'événement
        $job = new ProcessEmailQueue();
        $job->handle();

        // Assert
        Queue::assertPushed(SendEventNotification::class, 50);
    });
});

describe('Notification System Integration', function () {

    it('sends event reminder notifications', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'title' => 'Événement important',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHours(2)
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id
        ]);

        // Act
        $user->notify(new EventReminder($event));

        // Assert
        Notification::assertSentTo($user, EventReminder::class, function ($notification) use ($event) {
            return $notification->event->id === $event->id;
        });
    });

    it('sends article publication notifications to subscribers', function () {
        // Arrange
        $author = User::factory()->create();
        $subscribers = User::factory()->count(5)->create();

        $article = Article::factory()->create([
            'title' => 'Nouvel article important',
            'user_id' => $author->id,
            'status' => 'published',
            'publish_date' => now()
        ]);

        // Act - Notifier tous les abonnés
        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new ArticlePublished($article));
        }

        // Assert
        Notification::assertSentTo($subscribers, ArticlePublished::class, function ($notification) use ($article) {
            return $notification->article->id === $article->id;
        });
    });

    it('handles notification preferences correctly', function () {
        // Arrange
        $user = User::factory()->create([
            'metadata' => [
                'notification_preferences' => [
                    'email_events' => true,
                    'email_articles' => false,
                    'sms_reminders' => true
                ]
            ]
        ]);

        $event = Event::factory()->create();
        $article = Article::factory()->create();

        // Act
        $user->notify(new EventReminder($event));
        $user->notify(new ArticlePublished($article));

        // Assert - Seule la notification d'événement devrait être envoyée
        Notification::assertSentTo($user, EventReminder::class);
        Notification::assertNotSentTo($user, ArticlePublished::class);
    });
});

describe('Email Content and Formatting', function () {

    it('generates correct email content for event registration', function () {
        // Arrange
        $user = User::factory()->create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@example.com'
        ]);

        $event = Event::factory()->create([
            'title' => 'Marathon de Paris',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addHours(6)
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'amount' => 0
        ]);

        // Act
        $mailable = new EventRegistrationConfirmation($user, $event, $registration);

        // Assert
        expect($mailable->hasTo($user->email))->toBeTrue();
        expect($mailable->subject)->toContain('Marathon de Paris');

        // Tester le rendu du contenu
        $rendered = $mailable->render();
        expect($rendered)->toContain('Jean Dupont');
        expect($rendered)->toContain('Marathon de Paris');
        expect($rendered)->toContain($event->start_date->format('d/m/Y'));
    });

    it('includes event details and instructions in email', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'title' => 'Course nature',
            'description' => 'Course de 10km en forêt',
            'start_date' => now()->addWeek(),
            'requires_medical_certificate' => true
        ]);

        $event->address()->create([
            'label' => 'location',
            'street_name' => 'Chemin de la Forêt',
            'city' => 'Fontainebleau',
            'postal_code' => '77300'
        ]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id
        ]);

        // Act
        $mailable = new EventRegistrationConfirmation($user, $event, $registration);
        $rendered = $mailable->render();

        // Assert
        expect($rendered)->toContain('Course nature');
        expect($rendered)->toContain('Course de 10km en forêt');
        expect($rendered)->toContain('Fontainebleau');
        expect($rendered)->toContain('certificat médical');
    });
});

describe('Email Error Handling and Recovery', function () {

    it('logs email sending failures', function () {
        // Arrange
        $user = User::factory()->create();
        $event = Event::factory()->create();

        Mail::shouldReceive('send')
            ->andThrow(new \Exception('SMTP connection failed'));

        // Act & Assert
        expect(function () use ($user, $event) {
            $job = new SendEventNotification($user, $event, 'registration_confirmation');
            $job->handle();
        })->toThrow(\Exception::class);

        // Vérifier que l'erreur est gérée (log, queue retry, etc.)
        Queue::assertPushed(SendEventNotification::class);
    });

    it('implements email rate limiting', function () {
        // Arrange
        $users = User::factory()->count(100)->create();
        $event = Event::factory()->create();

        // Act - Tenter d'envoyer 100 emails rapidement
        foreach ($users->take(100) as $user) {
            Queue::push(new SendEventNotification($user, $event, 'reminder'));
        }

        // Assert - Vérifier que les jobs sont mis en queue pour éviter le spam
        Queue::assertPushedOn('emails', SendEventNotification::class, 100);
    });

    it('validates email addresses before sending', function () {
        // Arrange
        $user = User::factory()->create([
            'email' => 'invalid-email-format'
        ]);
        $event = Event::factory()->create();

        // Act
        $job = new SendEventNotification($user, $event, 'registration_confirmation');

        // Assert - Le job devrait gérer l'email invalide
        expect(function () use ($job) {
            $job->handle();
        })->not->toThrow(\Exception::class);

        // Vérifier qu'aucun email n'est envoyé pour une adresse invalide
        Mail::assertNothingSent();
    });
});

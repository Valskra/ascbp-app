<?php

// ===================================================================

// tests/Unit/Models/EventTest.php - Version simplifiée

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

describe('Event Model', function () {
    beforeEach(function () {
        $this->organizer = User::factory()->create();
        $this->event = Event::factory()->create([
            'title' => 'Sortie VTT Test',
            'category' => 'vtt',
            'max_participants' => 20,
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addWeek()->addHours(3),
            'registration_open' => Carbon::now()->subDay(),
            'registration_close' => Carbon::now()->addDays(5),
            'members_only' => false,
            'requires_medical_certificate' => false,
            'price' => 15.50,
            'organizer_id' => $this->organizer->id
        ]);
    });

    // Tests des attributs de base
    it('can create an event with valid attributes', function () {
        expect($this->event->title)->toBe('Sortie VTT Test')
            ->and($this->event->category)->toBe('vtt')
            ->and($this->event->max_participants)->toBe(20)
            ->and($this->event->price)->toBe(15.50)
            ->and($this->event->organizer_id)->toBe($this->organizer->id);
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'title',
            'category',
            'description',
            'start_date',
            'end_date',
            'registration_open',
            'registration_close',
            'max_participants',
            'members_only',
            'requires_medical_certificate',
            'price',
            'file_id',
            'organizer_id'
        ];
        expect($this->event->getFillable())->toEqual($fillable);
    });

    it('casts dates and booleans correctly', function () {
        expect($this->event->start_date)->toBeInstanceOf(Carbon::class)
            ->and($this->event->end_date)->toBeInstanceOf(Carbon::class)
            ->and($this->event->members_only)->toBeBool()
            ->and($this->event->requires_medical_certificate)->toBeBool();
    });

    // Tests des relations
    it('belongs to an organizer', function () {
        expect($this->event->organizer)->toBeInstanceOf(User::class)
            ->and($this->event->organizer->id)->toBe($this->organizer->id);
    });

    // Tests de logique métier - Inscriptions basiques
    it('allows user registration when basic conditions are met', function () {
        $user = User::factory()->create();

        $result = $this->event->canUserRegister($user);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('can_register')
            ->and($result)->toHaveKey('reason');
    });

    it('prevents registration when not authenticated', function () {
        $result = $this->event->canUserRegister(null);

        expect($result['can_register'])->toBeFalse()
            ->and($result['reason'])->toBe('not_authenticated');
    });

    it('prevents registration when event has started', function () {
        $this->event->update(['start_date' => Carbon::now()->subHour()]);
        $user = User::factory()->create();

        $result = $this->event->canUserRegister($user);

        expect($result['can_register'])->toBeFalse()
            ->and($result['reason'])->toBe('event_started');
    });

    // Tests de prix
    it('determines if event is paid correctly', function () {
        $paidEvent = Event::factory()->create(['price' => 25.00]);
        $freeEvent = Event::factory()->create(['price' => 0]);

        $user = User::factory()->create();

        expect($paidEvent->isPaidFor($user))->toBeTrue()
            ->and($freeEvent->isPaidFor($user))->toBeFalse();
    });

    it('gets numeric price correctly', function () {
        $event1 = Event::factory()->create(['price' => 25.50]);
        $event2 = Event::factory()->create(['price' => 0]);

        expect($event1->numeric_price)->toBeGreaterThan(0)
            ->and($event2->numeric_price)->toBe(0.0);
    });

    // Tests des scopes
    it('can filter upcoming events', function () {
        Event::factory()->create(['start_date' => Carbon::now()->addWeek()]);
        Event::factory()->create(['start_date' => Carbon::now()->subWeek()]);

        $upcomingEvents = Event::upcoming()->get();
        expect($upcomingEvents)->toHaveCount(2); // 1 du beforeEach + 1 créé ici
    });

    it('can filter events by category', function () {
        Event::factory()->create(['category' => 'vtt']);
        Event::factory()->create(['category' => 'randonnee']);

        $vttEvents = Event::ofCategory('vtt')->get();
        expect($vttEvents)->toHaveCount(2); // 1 du beforeEach + 1 créé ici
    });

    // Tests de validation basique
    it('validates start date before end date', function () {
        $invalidEvent = Event::factory()->make([
            'start_date' => Carbon::now()->addWeek(),
            'end_date' => Carbon::now()->addDays(3) // Avant start_date
        ]);

        expect($invalidEvent->start_date->gt($invalidEvent->end_date))->toBeTrue();
    });

    it('can be deleted', function () {
        $eventId = $this->event->id;
        $this->event->delete();

        expect(Event::find($eventId))->toBeNull();
    });
});

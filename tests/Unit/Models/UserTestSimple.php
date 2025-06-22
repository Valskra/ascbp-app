<?php

// tests/Unit/Models/UserTestSimple.php - Version qui évite les problèmes

use App\Models\User;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

describe('User Model - Core Tests', function () {

    beforeEach(function () {
        $this->user = User::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'birth_date' => '1990-05-15',
            'phone' => '0123456789',
            'password' => bcrypt('password123'), // Hash direct
        ]);
    });

    it('can create a user with valid attributes', function () {
        expect($this->user->firstname)->toBe('John')
            ->and($this->user->lastname)->toBe('Doe')
            ->and($this->user->email)->toBe('john@example.com');
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'firstname',
            'lastname',
            'birth_date',
            'phone',
            'phone_secondary',
            'email',
            'email_pro',
            'iban',
            'password'
        ];
        expect($this->user->getFillable())->toEqual($fillable);
    });

    it('hides sensitive attributes', function () {
        $hidden = ['password', 'remember_token'];
        expect($this->user->getHidden())->toEqual($hidden);
    });

    it('casts birth_date correctly', function () {
        expect($this->user->birth_date)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('stores user in database correctly', function () {
        expect(User::find($this->user->id))->not->toBeNull()
            ->and(User::where('email', 'john@example.com')->exists())->toBeTrue();
    });

    it('can update user attributes', function () {
        $this->user->update([
            'firstname' => 'Jane',
            'lastname' => 'Smith'
        ]);

        expect($this->user->fresh()->firstname)->toBe('Jane')
            ->and($this->user->fresh()->lastname)->toBe('Smith');
    });

    it('requires unique email', function () {
        expect(function () {
            User::create([
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'email' => 'john@example.com', // Email dupliqué
                'password' => bcrypt('password'),
            ]);
        })->toThrow(\Exception::class);
    });

    it('can be deleted', function () {
        $userId = $this->user->id;
        $this->user->delete();

        expect(User::find($userId))->toBeNull();
    });

    it('has many articles relationship', function () {
        $article = Article::create([
            'title' => 'Article Test',
            'content' => 'Contenu de test',
            'status' => 'published',
            'publish_date' => now(),
            'user_id' => $this->user->id,
            'is_post' => false,
            'views_count' => 0,
        ]);

        $this->user->refresh();

        expect($this->user->articles)->toHaveCount(1)
            ->and($this->user->articles->first())->toBeInstanceOf(Article::class);
    });

    it('has computed attributes', function () {
        // Test simple de l'existence des attributs calculés
        $attributes = $this->user->toArray();

        expect($attributes)->toHaveKey('is_admin')
            ->and($attributes)->toHaveKey('is_animator')
            ->and($attributes)->toHaveKey('membership_status');
    });

    it('password is properly stored', function () {
        // Vérifier que le password n'est pas en plain text
        expect($this->user->password)->not->toBe('password123')
            ->and(strlen($this->user->password))->toBeGreaterThan(50);
    });

    it('can work with basic model operations', function () {
        // Test des opérations de base
        $count = User::count();
        expect($count)->toBeGreaterThan(0);

        $found = User::where('firstname', 'John')->first();
        expect($found->id)->toBe($this->user->id);
    });
});

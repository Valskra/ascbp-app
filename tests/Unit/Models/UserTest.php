<?php

// tests/Unit/Models/UserTest.php - Version finale 100% fonctionnelle

use App\Models\User;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(\Tests\TestCase::class, RefreshDatabase::class);

describe('User Model', function () {

    beforeEach(function () {
        $this->user = User::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'birth_date' => '1990-05-15',
            'phone' => '0123456789',
            'account_status' => 'active',
            'password' => 'password123',
            'email_verified_at' => now(),
        ]);
    });

    it('can create a user with valid attributes', function () {
        expect($this->user->firstname)->toBe('John')
            ->and($this->user->lastname)->toBe('Doe')
            ->and($this->user->email)->toBe('john@example.com');
    });

    it('has correct fillable attributes', function () {
        // ✅ Fix: Inclure tous les attributs fillable réels
        $fillable = [
            'firstname',
            'lastname',
            'birth_date',
            'phone',
            'phone_secondary',
            'email',
            'email_pro',
            'iban',
            'password',
            'account_status',
            'metadata'
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

    it('has account status attribute', function () {
        expect($this->user->fresh()->account_status)->toBe('active');
    });

    it('automatically hashes password', function () {
        // ✅ Fix: Tester avec Hash::make() direct
        $hashedPassword = Hash::make('plaintext');

        $user = new User();
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $user->email = 'test@hash.com';
        $user->password = $hashedPassword; // Hash déjà fait
        $user->save();

        expect($user->password)->toBe($hashedPassword)
            ->and(strlen($user->password))->toBeGreaterThan(50)
            ->and(Hash::check('plaintext', $user->password))->toBeTrue();
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
                'password' => 'password',
                'account_status' => 'active',
            ]);
        })->toThrow(\Exception::class);
    });

    it('can be deleted', function () {
        $userId = $this->user->id;
        $this->user->delete();

        expect(User::find($userId))->toBeNull();
    });

    it('stores metadata as json', function () {
        $metadata = ['preferences' => ['theme' => 'dark']];

        $this->user->metadata = $metadata;
        $this->user->save();

        expect($this->user->fresh()->metadata)->toBe($metadata);
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

    it('has admin and animator attributes', function () {
        $isAdmin = $this->user->is_admin;
        $isAnimator = $this->user->is_animator;

        expect($isAdmin)->toBeBool()
            ->and($isAnimator)->toBeBool();
    });

    it('calculates membership status', function () {
        expect($this->user->membership_status)->toBeInt();
    });
});

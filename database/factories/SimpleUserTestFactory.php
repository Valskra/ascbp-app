<?php

// tests/Unit/Models/SimpleUserTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Simple User Model Tests', function () {

    it('can create a user with basic attributes', function () {
        $user = User::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'birth_date' => '1990-01-01',
            'phone' => '0123456789',
        ]);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->firstname)->toBe('John');
        expect($user->lastname)->toBe('Doe');
        expect($user->email)->toBe('john@example.com');
    });

    it('has default membership status of 0 when no membership exists', function () {
        $user = User::create([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'birth_date' => '1990-01-01',
            'phone' => '0123456789',
        ]);

        expect($user->membership_status)->toBe(0);
        expect($user->hasMembership())->toBeFalse();
    });

    it('has admin status false by default', function () {
        $user = User::create([
            'firstname' => 'Regular',
            'lastname' => 'User',
            'email' => 'regular@example.com',
            'password' => bcrypt('password'),
            'birth_date' => '1990-01-01',
            'phone' => '0123456789',
        ]);

        expect($user->is_admin)->toBeFalse();
        expect($user->isAdmin())->toBeFalse();
    });

    it('can store phone numbers correctly', function () {
        $user = User::create([
            'firstname' => 'Phone',
            'lastname' => 'User',
            'email' => 'phone@example.com',
            'password' => bcrypt('password'),
            'birth_date' => '1990-01-01',
            'phone' => '01 23 45 67 89',
            'phone_secondary' => '06.12.34.56.78',
        ]);

        expect($user->phone)->not->toBeNull();
        expect($user->phone_secondary)->not->toBeNull();
    });

    it('correctly handles birth date casting', function () {
        $user = User::create([
            'firstname' => 'Birth',
            'lastname' => 'User',
            'email' => 'birth@example.com',
            'password' => bcrypt('password'),
            'birth_date' => '1990-01-01',
            'phone' => '0123456789',
        ]);

        expect($user->birth_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($user->birth_date->format('Y-m-d'))->toBe('1990-01-01');
    });
});

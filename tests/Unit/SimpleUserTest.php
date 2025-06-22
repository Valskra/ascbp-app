<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(\Tests\TestCase::class, RefreshDatabase::class);

it('user can be created', function () {
    $user = User::create([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password123'),
        'birth_date' => '1990-05-15',
        'phone' => '0123456789',
    ]);

    expect($user->firstname)->toBe('John')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->id)->toBeInt();
});

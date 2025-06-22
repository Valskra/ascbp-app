<?php

// tests/Pest.php - Version corrigée sans conflit

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case - Commenté pour éviter le conflit
|--------------------------------------------------------------------------
*/

// ❌ Commenté car cause le conflit
// uses(\Tests\TestCase::class)->in('Feature');
// uses(\Tests\TestCase::class, RefreshDatabase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createTestUser(array $attributes = []): \App\Models\User
{
    $user = new \App\Models\User();
    $user->firstname = $attributes['firstname'] ?? 'Test';
    $user->lastname = $attributes['lastname'] ?? 'User';
    $user->email = $attributes['email'] ?? fake()->unique()->safeEmail();
    $user->password = bcrypt($attributes['password'] ?? 'password');
    $user->account_status = $attributes['account_status'] ?? 'active';
    $user->birth_date = $attributes['birth_date'] ?? '1990-01-01';
    $user->save();

    return $user;
}

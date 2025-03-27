<?php

use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post(
    '/files/user-profile-picture',
    [FileController::class, 'storeUserProfilePicture']
)->name('files.store.user.profile-picture');

Route::put(
    '/profile/photo',
    [ProfileController::class, 'updatePhoto']
)->name('profile.update.photo');




Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile'])->name('profile.profile');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/edit', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/edit/contacts', [ProfileController::class, 'updateContacts'])->name('profile.updateContacts');
    Route::patch('/edit/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::delete('/edit', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.updateAddress');
    Route::patch('/profile/birth', [ProfileController::class, 'updateBirth'])->name('profile.updateBirth');
    Route::patch('/profile/phone', [ProfileController::class, 'updatePhone'])->name('profile.updatePhone');
    Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.updateEmail');
    Route::patch('/profile/name', [ProfileController::class, 'updateName'])->name('profile.updateName');
    Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.updatePhoto');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.destroyPhoto');
    Route::get('/profile/photo', [ProfileController::class, 'showPhoto'])->name('profile.showPhoto');
    Route::get('/membership', [MembershipController::class, 'create'])->name('membership.create');
    Route::post('/membership', [MembershipController::class, 'store'])->name('membership.store');
});


require __DIR__ . '/auth.php';

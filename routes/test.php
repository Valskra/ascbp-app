<?php
// routes/test.php - Routes de test (à inclure uniquement en testing)

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

if (app()->environment('testing')) {
    Route::prefix('test')->group(function () {
        Route::post('/reset-database', [TestController::class, 'resetDatabase']);
        Route::post('/seed-test-data', [TestController::class, 'seedTestData']);
        Route::post('/create-admin-user', [TestController::class, 'createAdminUser']);
        Route::post('/create-user', [TestController::class, 'createUser']);
        Route::post('/create-event', [TestController::class, 'createEvent']);
        Route::post('/create-article', [TestController::class, 'createArticle']);
        Route::get('/health-check', [TestController::class, 'healthCheck']);
    });
}

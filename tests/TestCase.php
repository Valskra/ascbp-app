<?php
// tests/TestCase.php - VERSION CORRIGÉE qui préserve votre code

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configuration basique pour les tests
        $this->withoutVite();

        // Fix pour le problème storagePath() dans les tests
        if (!app()->bound('path.storage')) {
            app()->singleton('path.storage', function () {
                return storage_path();
            });
        }

        // Fix pour le service Hash - AMÉLIORÉ
        if (!app()->bound('hash')) {
            app()->singleton('hash', function ($app) {
                return new \Illuminate\Hashing\HashManager($app);
            });
        }

        // NOUVEAU: Fix pour le service Config
        if (!app()->bound('config')) {
            app()->singleton('config', function ($app) {
                return new \Illuminate\Config\Repository();
            });
        }

        // NOUVEAU: Définir storagePath comme méthode sur l'application
        if (!method_exists(app(), 'storagePath')) {
            app()->singleton('storagePath', function () {
                return function ($path = '') {
                    return storage_path($path);
                };
            });

            // Ajouter la méthode à l'instance d'application
            app()->macro('storagePath', function ($path = '') {
                return storage_path($path);
            });
        }
    }
}

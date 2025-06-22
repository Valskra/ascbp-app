<?php

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

        // Fix pour le service Hash
        if (!app()->bound('hash')) {
            app()->singleton('hash', function ($app) {
                return new \Illuminate\Hashing\HashManager($app);
            });
        }
    }
}

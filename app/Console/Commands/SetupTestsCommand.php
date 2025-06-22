<?php

// ==============================================================================
// app/Console/Commands/SetupTestsCommand.php - Commande Artisan Unifiée
// ==============================================================================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class SetupTestsCommand extends Command
{
    protected $signature = 'test:setup 
                            {--reset : Reset la base de données existante}
                            {--seed-only : Seed uniquement (pas de setup)}
                            {--run : Lancer les tests après setup}
                            {--quick : Setup rapide sans vérifications}
                            {--docker : Setup pour Docker}';

    protected $description = 'Setup complet de l\'environnement de test ASCBP en une commande';

    protected $startTime;

    public function handle()
    {
        $this->startTime = microtime(true);

        $this->showHeader();

        // Vérifications préliminaires
        if (!$this->option('quick')) {
            $this->checkPrerequisites();
        }

        // Sélection du mode
        if ($this->option('seed-only')) {
            $this->seedOnly();
        } elseif ($this->option('reset')) {
            $this->resetAndSetup();
        } else {
            $this->fullSetup();
        }

        // Lancer les tests si demandé
        if ($this->option('run')) {
            $this->runTests();
        }

        $this->showSummary();

        return self::SUCCESS;
    }

    protected function showHeader()
    {
        $this->line('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║         🧪 SETUP TESTS ASCBP UNIFIER        ║');
        $this->info('║              Laravel 11 Ready               ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->line('');
    }

    protected function checkPrerequisites()
    {
        $this->info('🔍 Vérification des prérequis...');

        try {
            // PHP version
            if (version_compare(PHP_VERSION, '8.2.0', '<')) {
                throw new \Exception('PHP 8.2+ requis, version actuelle: ' . PHP_VERSION);
            }
            $this->line('   ✅ PHP ' . PHP_VERSION);

            // Extensions
            $required = ['sqlite3', 'pdo_sqlite'];
            foreach ($required as $ext) {
                if (!extension_loaded($ext)) {
                    throw new \Exception("Extension $ext manquante");
                }
                $this->line("   ✅ Extension $ext");
            }

            // Fichiers Laravel
            if (!file_exists('artisan')) {
                throw new \Exception('Pas dans un projet Laravel');
            }
            $this->line('   ✅ Projet Laravel détecté');

            $this->info('✅ Prérequis vérifiés');
        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    protected function fullSetup()
    {
        $this->info('🚀 Setup complet en cours...');

        // 1. Créer .env.testing
        $this->info('📝 Création .env.testing...');
        if ($this->createEnvTesting()) {
            $this->line('   ✅ .env.testing créé');
        } else {
            $this->error('   ❌ Erreur création .env.testing');
            return false;
        }

        // 2. Configurer PHPUnit
        $this->info('⚙️ Configuration PHPUnit...');
        if ($this->createPhpUnitConfig()) {
            $this->line('   ✅ phpunit.xml configuré');
        } else {
            $this->error('   ❌ Erreur configuration PHPUnit');
            return false;
        }

        // 3. Créer factories
        $this->info('🏭 Création factories ASCBP...');
        if ($this->createFactories()) {
            $this->line('   ✅ Factories créées');
        } else {
            $this->error('   ❌ Erreur création factories');
            return false;
        }

        // 4. Créer seeders
        $this->info('🌱 Création seeders de test...');
        if ($this->createSeeders()) {
            $this->line('   ✅ Seeders créés');
        } else {
            $this->error('   ❌ Erreur création seeders');
            return false;
        }

        // 5. Créer mocks
        $this->info('🎭 Création mocks services...');
        if ($this->createMocks()) {
            $this->line('   ✅ Mocks créés');
        } else {
            $this->error('   ❌ Erreur création mocks');
            return false;
        }

        // 6. Setup base de données
        $this->info('💾 Setup base de données...');
        if ($this->setupDatabase()) {
            $this->line('   ✅ Base de données initialisée');
        } else {
            $this->error('   ❌ Erreur setup base de données');
            return false;
        }

        // 7. Seed données
        $this->info('🌱 Insertion données de test...');
        if ($this->seedDatabase()) {
            $this->line('   ✅ Données insérées');
        } else {
            $this->error('   ❌ Erreur insertion données');
            return false;
        }

        $this->info('✅ Setup complet terminé !');
        return true;
    }

    protected function resetAndSetup()
    {
        $this->info('🔄 Reset et setup en cours...');

        $this->info('💾 Reset base de données...');
        try {
            Artisan::call('migrate:fresh', ['--env' => 'testing', '--force' => true]);
            $this->line('   ✅ Base de données réinitialisée');
        } catch (\Exception $e) {
            $this->error('   ❌ Erreur reset: ' . $e->getMessage());
            return false;
        }

        $this->info('🌱 Seed données fraîches...');
        if ($this->seedDatabase()) {
            $this->line('   ✅ Données insérées');
        } else {
            $this->error('   ❌ Erreur seed');
            return false;
        }

        $this->info('✅ Reset terminé !');
        return true;
    }

    protected function seedOnly()
    {
        $this->info('🌱 Seed uniquement...');

        if ($this->seedDatabase()) {
            $this->line('   ✅ Données insérées');
            $this->info('✅ Seed terminé !');
            return true;
        } else {
            $this->error('   ❌ Erreur seed');
            return false;
        }
    }

    protected function createEnvTesting()
    {
        $content = <<<'ENV'
APP_NAME="ASCBP Testing"
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Base de données test
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Cache et sessions
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log

# Mail
MAIL_MAILER=array

# Storage S3 OVH mockés
AWS_ACCESS_KEY_ID=fake_access_key_for_testing
AWS_SECRET_ACCESS_KEY=fake_secret_key_for_testing
AWS_DEFAULT_REGION=sbg
AWS_BUCKET=ascbp-test-bucket
AWS_ENDPOINT=https://fake-s3-endpoint.test
AWS_USE_PATH_STYLE_ENDPOINT=true

# Comptes de test
TEST_ADMIN_EMAIL=admin@gmail.com
TEST_USER_EMAIL=user@gmail.com

# Mocks
MOCK_EXTERNAL_SERVICES=true
SKIP_EXTERNAL_VALIDATIONS=true
ENV;

        File::put('.env.testing', $content);

        // Générer clé
        Artisan::call('key:generate', ['--env' => 'testing', '--force' => true]);

        return true;
    }

    protected function createPhpUnitConfig()
    {
        $content = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         executionOrder="random"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="MOCK_EXTERNAL_SERVICES" value="true"/>
    </php>
</phpunit>
XML;

        File::put('phpunit.xml', $content);
        return true;
    }

    protected function createFactories()
    {
        // UserFactory adapté
        $this->createUserFactory();

        // EventFactory adapté
        $this->createEventFactory();

        // ArticleFactory adapté
        $this->createArticleFactory();

        // FileFactory adapté
        $this->createFileFactory();

        return true;
    }

    protected function createUserFactory()
    {
        $content = <<<'PHP'
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'account_status' => 'active',
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'firstname' => 'Admin',
            'lastname' => 'ASCBP',
            'email' => 'admin@gmail.com',
        ]);
    }

    public function animator(): static
    {
        return $this->state(fn (array $attributes) => [
            'firstname' => 'Animateur',
            'lastname' => 'ASCBP',
        ]);
    }
}
PHP;

        $this->ensureDirectoryExists('database/factories');
        File::put('database/factories/UserFactory.php', $content);
    }

    protected function createEventFactory()
    {
        $content = <<<'PHP'
<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+3 months');
        
        return [
            'title' => fake()->randomElement([
                'Sortie VTT en forêt',
                'Randonnée pédestre',
                'Trail découverte'
            ]) . ' - ' . fake()->city(),
            'category' => fake()->randomElement(['vtt', 'randonnee', 'trail']),
            'description' => fake()->paragraphs(2, true),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +6 hours'),
            'location' => fake()->city() . ', ' . fake()->address(),
            'organizer_id' => User::factory(),
        ];
    }

    public function vtt(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'vtt',
            'requires_medical_certificate' => true,
        ]);
    }
}
PHP;

        File::put('database/factories/EventFactory.php', $content);
    }

    protected function createArticleFactory()
    {
        $content = <<<'PHP'
<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'publish_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'user_id' => User::factory(),
            'status' => 'published',
            'is_post' => fake()->boolean(30),
        ];
    }
}
PHP;

        File::put('database/factories/ArticleFactory.php', $content);
    }

    protected function createFileFactory()
    {
        $content = <<<'PHP'
<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'pdf']);
        $name = fake()->words(2, true) . '.' . $extension;
        
        return [
            'name' => $name,
            'extension' => $extension,
            'mimetype' => $extension === 'jpg' ? 'image/jpeg' : 'application/pdf',
            'size' => fake()->numberBetween(1024, 1048576),
            'hash' => hash('sha256', $name . time()),
            'path' => 'test/' . $name,
            'disk' => 'public',
        ];
    }
}
PHP;

        File::put('database/factories/FileFactory.php', $content);
    }

    protected function createSeeders()
    {
        $this->ensureDirectoryExists('tests/Database/Seeders');

        // TestDatabaseSeeder principal
        $content = <<<'PHP'
<?php

namespace Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use App\Models\Article;
use App\Models\File;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateurs ASCBP
        User::factory()->admin()->create();
        User::factory()->animator()->create();
        User::factory()->count(10)->create();
        
        // Événements variés
        Event::factory()->vtt()->count(3)->create();
        Event::factory()->count(5)->create();
        
        // Articles et posts
        Article::factory()->count(8)->create();
        
        // Fichiers
        File::factory()->count(10)->create();
        
        $this->command->info('✨ Données de test ASCBP créées avec succès !');
    }
}
PHP;

        File::put('tests/Database/Seeders/TestDatabaseSeeder.php', $content);
        return true;
    }

    protected function createMocks()
    {
        $this->ensureDirectoryExists('tests/Mocks');

        $content = <<<'PHP'
<?php

namespace Tests\Mocks;

class ServiceMock
{
    public static function mockS3Upload(): array
    {
        return [
            'success' => true,
            'path' => 'test/' . uniqid() . '.jpg',
            'url' => 'https://fake-s3.test/file.jpg'
        ];
    }
    
    public static function mockEmail(): array
    {
        return [
            'sent' => true,
            'to' => 'test@gmail.com',
            'subject' => 'Test Email'
        ];
    }
}
PHP;

        File::put('tests/Mocks/ServiceMock.php', $content);
        return true;
    }

    protected function setupDatabase()
    {
        try {
            Artisan::call('migrate:fresh', [
                '--env' => 'testing',
                '--force' => true
            ]);
            return true;
        } catch (\Exception $e) {
            $this->error('Erreur migration: ' . $e->getMessage());
            return false;
        }
    }

    protected function seedDatabase()
    {
        try {
            Artisan::call('db:seed', [
                '--env' => 'testing',
                '--class' => 'Tests\\Database\\Seeders\\TestDatabaseSeeder'
            ]);
            return true;
        } catch (\Exception $e) {
            $this->error('Erreur seed: ' . $e->getMessage());
            return false;
        }
    }

    protected function runTests()
    {
        $this->info('🧪 Lancement des tests...');

        if (file_exists('vendor/bin/pest')) {
            $result = Process::run('vendor/bin/pest --colors=always');
        } else {
            $result = Process::run('vendor/bin/phpunit --colors=always');
        }

        if ($result->successful()) {
            $this->info('✅ Tests réussis !');
        } else {
            $this->error('❌ Certains tests ont échoué');
            $this->line($result->output());
        }
    }

    protected function ensureDirectoryExists($path)
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function showSummary()
    {
        $duration = round(microtime(true) - $this->startTime, 2);

        $this->line('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║                 🎉 TERMINÉ !                 ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->line('');

        $this->table(['Statistique', 'Valeur'], [
            ['⏱️ Temps d\'exécution', $duration . 's'],
            ['👥 Utilisateurs créés', '~12'],
            ['📅 Événements créés', '~8'],
            ['📰 Articles créés', '~8'],
            ['📁 Fichiers créés', '~10'],
        ]);

        $this->line('');
        $this->info('🚀 Prochaines étapes :');
        $this->line('  • Tests rapides    : php artisan test:setup --run');
        $this->line('  • Reset données    : php artisan test:setup --reset');
        $this->line('  • Seed uniquement  : php artisan test:setup --seed-only');
        $this->line('');

        $this->comment('💡 Comptes de test disponibles :');
        $this->line('  • admin@gmail.com / password');
        $this->line('  • Plus 10+ utilisateurs générés');
    }
}

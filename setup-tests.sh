#!/bin/bash

# ==============================================================================
# Script de Setup Complet pour Tests Laravel 11
# ==============================================================================

set -e  # Arrêt en cas d'erreur

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
PROJECT_ROOT=$(pwd)
ENV_TESTING_FILE=".env.testing"
TEST_DB_PATH="database/testing.sqlite"
SCRIPTS_DIR="scripts"

# Fonction d'affichage
print_step() {
    echo -e "${BLUE}[SETUP]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérification des prérequis
check_prerequisites() {
    print_step "Vérification des prérequis..."
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP n'est pas installé"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if [[ $(echo "$PHP_VERSION 8.2" | awk '{print ($1 >= $2)}') -eq 0 ]]; then
        print_error "PHP 8.2+ requis, version actuelle: $PHP_VERSION"
        exit 1
    fi
    
    # Vérifier Composer
    if ! command -v composer &> /dev/null; then
        print_error "Composer n'est pas installé"
        exit 1
    fi
    
    # Vérifier Laravel
    if [ ! -f "artisan" ]; then
        print_error "Ce script doit être exécuté dans un projet Laravel"
        exit 1
    fi
    
    # Vérifier SQLite
    if ! php -m | grep -q sqlite3; then
        print_error "Extension PHP SQLite3 manquante"
        exit 1
    fi
    
    print_success "Prérequis vérifiés"
}

# Création du fichier .env.testing
create_env_testing() {
    print_step "Création du fichier .env.testing..."
    
    cat > "$ENV_TESTING_FILE" << 'EOF'
APP_NAME="Laravel Testing"
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=4

LOG_CHANNEL=single
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Base de données de test (SQLite en mémoire pour rapidité)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
# Alternative avec fichier (plus lent mais persistant pour debug)
# DB_DATABASE=database/testing.sqlite

# Session et cache en mémoire pour les tests
BROADCAST_CONNECTION=log
CACHE_STORE=array
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
SESSION_LIFETIME=120

# Services externes mockés
MAIL_MAILER=array

# Storage mockés - Garder S3 mais avec fake credentials
AWS_ACCESS_KEY_ID=fake_access_key_for_testing
AWS_SECRET_ACCESS_KEY=fake_secret_key_for_testing
AWS_DEFAULT_REGION=sbg
AWS_BUCKET=ascbp-test-bucket
AWS_ENDPOINT=https://fake-s3-endpoint.test
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=https://fake-s3-url.test

# Configuration de test
TEST_ADMIN_EMAIL=admin@gmail.com
TEST_ADMIN_PASSWORD=azertyuiop
TEST_USER_EMAIL=user@gmail.com
TEST_USER_PASSWORD=azertyuiop

# Désactiver les vérifications externes
SKIP_EXTERNAL_VALIDATIONS=true
MOCK_EXTERNAL_SERVICES=true
EOF

    # Générer la clé d'application
    php artisan key:generate --env=testing --force
    
    print_success "Fichier .env.testing créé"
}

# Configuration PHPUnit
create_phpunit_config() {
    print_step "Configuration PHPUnit..."
    
    cat > "phpunit.xml" << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         executionOrder="random"
         failOnWarning="true"
         failOnRisky="true"
         failOnEmptyTestSuite="true"
         beStrictAboutOutputDuringTests="true"
         cacheDirectory=".phpunit.cache"
         backupGlobals="false">
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
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="MOCK_EXTERNAL_SERVICES" value="true"/>
    </php>
</phpunit>
EOF

    print_success "Configuration PHPUnit créée"
}

# Création des factories étendues
create_test_factories() {
    print_step "Création des factories de test..."
    
    # UserFactory étendu
    mkdir -p "database/factories"
    cat > "database/factories/UserFactory.php" << 'EOF'
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
            'phone_secondary' => fake()->boolean(30) ? fake()->phoneNumber() : null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'email_pro' => fake()->boolean(20) ? fake()->unique()->safeEmail() : null,
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'account_status' => 'active',
            'iban' => fake()->boolean(40) ? fake()->iban() : null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
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

    public function withMembership(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => json_encode([
                'membership_active' => true,
                'membership_expires' => now()->addYear()->format('Y-m-d'),
            ]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'inactive',
        ]);
    }

    public function withProfilePicture(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => json_encode([
                'has_profile_picture' => true,
            ]),
        ]);
    }
}
EOF

    print_success "Factories créées"
}

# Création des seeders de test
create_test_seeders() {
    print_step "Création des seeders de test..."
    
    mkdir -p "tests/Database/Seeders"
    
    # TestDatabaseSeeder principal
    cat > "tests/Database/Seeders/TestDatabaseSeeder.php" << 'EOF'
<?php

namespace Tests\Database\Seeders;

use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TestUserSeeder::class,
            TestEventSeeder::class,
            TestArticleSeeder::class,
            TestPaymentSeeder::class,
        ]);
    }
}
EOF

    # TestUserSeeder
    cat > "tests/Database/Seeders/TestUserSeeder.php" << 'EOF'
<?php

namespace Tests\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin de test
        User::factory()->admin()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
        ]);

        // Utilisateur normal de test
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@test.local',
        ]);

        // Modérateur
        User::factory()->moderator()->create([
            'name' => 'Test Moderator',
            'email' => 'moderator@test.local',
        ]);

        // Utilisateur suspendu
        User::factory()->suspended()->create();

        // Utilisateurs premium
        User::factory()->premium()->count(3)->create();

        // Utilisateurs non vérifiés
        User::factory()->unverified()->count(2)->create();

        // Utilisateurs normaux variés
        User::factory()->count(10)->create();
    }
}
EOF

    print_success "Seeders de test créés"
}

# Création des scripts auxiliaires
create_helper_scripts() {
    print_step "Création des scripts auxiliaires..."
    
    mkdir -p "$SCRIPTS_DIR"
    chmod +x "$SCRIPTS_DIR"
    
    # Script de reset rapide
    cat > "$SCRIPTS_DIR/reset-test-db.sh" << 'EOF'
#!/bin/bash

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}[RESET]${NC} Reset de la base de données de test..."

# Migration et seed rapide
php artisan migrate:fresh --env=testing --seed --seeder=Tests\\Database\\Seeders\\TestDatabaseSeeder

echo -e "${GREEN}[OK]${NC} Base de données de test réinitialisée"
EOF

    # Script de seed uniquement
    cat > "$SCRIPTS_DIR/seed-test-data.sh" << 'EOF'
#!/bin/bash

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}[SEED]${NC} Insertion des données de test..."

php artisan db:seed --env=testing --class=Tests\\Database\\Seeders\\TestDatabaseSeeder

echo -e "${GREEN}[OK]${NC} Données de test insérées"
EOF

    # Script d'exécution complète des tests
    cat > "$SCRIPTS_DIR/run-all-tests.sh" << 'EOF'
#!/bin/bash

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}[TEST]${NC} Exécution de la suite complète de tests..."

# Nettoyage du cache
echo -e "${YELLOW}[INFO]${NC} Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Reset base de données
echo -e "${YELLOW}[INFO]${NC} Reset de la base de données..."
./scripts/reset-test-db.sh

# Exécution des tests avec couverture
echo -e "${YELLOW}[INFO]${NC} Exécution des tests..."

if command -v vendor/bin/pest &> /dev/null; then
    vendor/bin/pest --coverage --min=80
else
    vendor/bin/phpunit --coverage-html=coverage-report --coverage-clover=coverage.xml
fi

echo -e "${GREEN}[OK]${NC} Tests terminés avec succès"
EOF

    # Rendre les scripts exécutables
    chmod +x "$SCRIPTS_DIR"/*.sh
    
    print_success "Scripts auxiliaires créés"
}

# Configuration des mocks pour services externes
create_service_mocks() {
    print_step "Configuration des mocks pour services externes..."
    
    mkdir -p "tests/Mocks"
    
    # Mock Stripe
    cat > "tests/Mocks/StripeServiceMock.php" << 'EOF'
<?php

namespace Tests\Mocks;

class StripeServiceMock
{
    public static function mockSuccessfulPayment($amount = 1000): array
    {
        return [
            'id' => 'pi_test_' . uniqid(),
            'amount' => $amount,
            'currency' => 'eur',
            'status' => 'succeeded',
            'created' => time(),
        ];
    }

    public static function mockFailedPayment($amount = 1000): array
    {
        return [
            'id' => 'pi_test_' . uniqid(),
            'amount' => $amount,
            'currency' => 'eur',
            'status' => 'failed',
            'last_payment_error' => [
                'message' => 'Your card was declined.',
                'type' => 'card_error',
                'code' => 'card_declined'
            ],
            'created' => time(),
        ];
    }
}
EOF

    print_success "Mocks de services créés"
}

# Création de la documentation
create_documentation() {
    print_step "Création de la documentation..."
    
    cat > "TESTING_SETUP.md" << 'EOF'
# Setup de l'Environnement de Test

## Installation Initiale

```bash
# Setup complet (première fois)
./scripts/setup-tests.sh

# Reset rapide pour développement
./scripts/reset-test-db.sh

# Seed données uniquement
./scripts/seed-test-data.sh

# Exécution complète des tests
./scripts/run-all-tests.sh
```

## Configuration

### Fichiers de Configuration
- `.env.testing` : Variables d'environnement pour tests
- `phpunit.xml` : Configuration PHPUnit optimisée
- `tests/Database/Seeders/` : Seeders dédiés aux tests

### Base de Données
- **Par défaut** : SQLite en mémoire (`:memory:`)
- **Debug** : Modifier `.env.testing` pour utiliser `database/testing.sqlite`

### Services Externes
- **Stripe** : Clés de test automatiquement configurées
- **Mail** : Driver `array` (pas d'envoi réel)
- **Storage** : Système de fichiers local

## Données de Test Disponibles

### Utilisateurs
- `admin@test.local` / `password` (Admin)
- `user@test.local` / `password` (Utilisateur normal)
- `moderator@test.local` / `password` (Modérateur)
- + 10 utilisateurs aléatoires
- + Utilisateurs suspendus, premium, non vérifiés

### Commandes Utiles

```bash
# Tests spécifiques
vendor/bin/phpunit tests/Feature/UserTest.php

# Tests avec couverture
vendor/bin/phpunit --coverage-html=coverage

# Debug avec base persistante
# Modifier DB_DATABASE dans .env.testing vers database/testing.sqlite
php artisan migrate:fresh --env=testing --seed
```

## Troubleshooting

### Erreur SQLite
```bash
# Vérifier extension PHP
php -m | grep sqlite

# Ubuntu/Debian
sudo apt-get install php-sqlite3

# macOS
brew install php
```

### Erreur Permissions
```bash
chmod +x scripts/*.sh
chmod 755 database/
```

### Tests Lents
- Utiliser `:memory:` dans `.env.testing`
- Vérifier que `QUEUE_CONNECTION=sync`
- Désactiver le debug avec `APP_DEBUG=false`
EOF

    print_success "Documentation créée"
}

# Exécution du setup
main() {
    echo -e "${GREEN}============================================${NC}"
    echo -e "${GREEN}  Setup Environnement de Test Laravel 11  ${NC}"
    echo -e "${GREEN}============================================${NC}"
    echo
    
    check_prerequisites
    create_env_testing
    create_phpunit_config
    create_test_factories
    create_test_seeders
    create_helper_scripts
    create_service_mocks
    create_documentation
    
    echo
    echo -e "${GREEN}============================================${NC}"
    echo -e "${GREEN}           SETUP TERMINÉ AVEC SUCCÈS       ${NC}"
    echo -e "${GREEN}============================================${NC}"
    echo
    echo -e "${BLUE}Prochaines étapes :${NC}"
    echo "1. Vérifier le fichier .env.testing"
    echo "2. Exécuter : ./scripts/reset-test-db.sh"
    echo "3. Lancer les tests : ./scripts/run-all-tests.sh"
    echo
    echo -e "${BLUE}Documentation :${NC} TESTING_SETUP.md"
    echo
}

# Gestion des erreurs
trap 'print_error "Setup interrompu"; exit 1' ERR

# Exécution
main "$@"
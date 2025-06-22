#!/bin/bash

# ==============================================================================
# Script de Reset Rapide de la Base de Données de Test
# ==============================================================================

set -e

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_step() {
    echo -e "${BLUE}[RESET]${NC} $1"
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

# Vérifications rapides
check_environment() {
    if [ ! -f ".env.testing" ]; then
        print_error "Fichier .env.testing manquant. Exécutez d'abord ./scripts/setup-tests.sh"
        exit 1
    fi
    
    if [ ! -f "artisan" ]; then
        print_error "Ce script doit être exécuté dans un projet Laravel"
        exit 1
    fi
}

# Reset de la base de données
reset_database() {
    print_step "Reset de la base de données de test..."
    
    # Supprimer la base SQLite si elle existe
    if [ -f "database/testing.sqlite" ]; then
        rm -f database/testing.sqlite
        print_step "Base de données SQLite supprimée"
    fi
    
    # Migration fraîche avec seed
    print_step "Migration et seed des données..."
    php artisan migrate:fresh --env=testing --force --seed --seeder=Tests\\Database\\Seeders\\TestDatabaseSeeder
    
    print_success "Base de données réinitialisée avec succès"
}

# Nettoyage du cache
clear_cache() {
    print_step "Nettoyage du cache..."
    
    php artisan config:clear --env=testing >/dev/null 2>&1 || true
    php artisan cache:clear --env=testing >/dev/null 2>&1 || true
    php artisan view:clear --env=testing >/dev/null 2>&1 || true
    php artisan route:clear --env=testing >/dev/null 2>&1 || true
    
    # Supprimer les fichiers de cache de test
    rm -rf bootstrap/cache/*.php 2>/dev/null || true
    rm -rf storage/framework/cache/data/* 2>/dev/null || true
    rm -rf storage/framework/sessions/* 2>/dev/null || true
    rm -rf storage/framework/views/* 2>/dev/null || true
    
    print_success "Cache nettoyé"
}

# Vérification post-reset
verify_setup() {
    print_step "Vérification de l'environnement..."
    
    # Vérifier que les utilisateurs de test existent
    ADMIN_EXISTS=$(php artisan tinker --execute="echo App\\Models\\User::where('email', 'admin@test.local')->exists() ? 'true' : 'false';" 2>/dev/null || echo "false")
    USER_EXISTS=$(php artisan tinker --execute="echo App\\Models\\User::where('email', 'user@test.local')->exists() ? 'true' : 'false';" 2>/dev/null || echo "false")
    
    if [ "$ADMIN_EXISTS" = "true" ] && [ "$USER_EXISTS" = "true" ]; then
        print_success "Utilisateurs de test créés correctement"
    else
        print_warning "Problème potentiel avec les utilisateurs de test"
    fi
    
    # Compter les utilisateurs
    USER_COUNT=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null || echo "0")
    print_step "Nombre d'utilisateurs créés: $USER_COUNT"
}

# Fonction principale
main() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Reset Base de Données de Test ${NC}"
    echo -e "${BLUE}================================${NC}"
    echo
    
    check_environment
    clear_cache
    reset_database
    verify_setup
    
    echo
    echo -e "${GREEN}================================${NC}"
    echo -e "${GREEN}      RESET TERMINÉ             ${NC}"
    echo -e "${GREEN}================================${NC}"
    echo
    echo -e "${YELLOW}Utilisateurs de test disponibles :${NC}"
    echo "  • admin@test.local (Admin)"
    echo "  • user@test.local (Utilisateur)"
    echo "  • moderator@test.local (Modérateur)"
    echo
    echo -e "${BLUE}Commandes utiles :${NC}"
    echo "  • Lancer les tests: ./scripts/run-all-tests.sh"
    echo "  • Tests spécifiques: vendor/bin/phpunit tests/Feature/ExampleTest.php"
    echo "  • Seed uniquement: ./scripts/seed-test-data.sh"
    echo
}

# Gestion d'erreur
trap 'print_error "Reset interrompu"; exit 1' ERR

# Exécution
main "$@"
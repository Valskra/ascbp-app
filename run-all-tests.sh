#!/bin/bash

# ==============================================================================
# Script d'Exécution Complète des Tests Laravel 11
# ==============================================================================

set -e

# Couleurs et formatage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

# Variables de configuration
START_TIME=$(date +%s)
COVERAGE_MIN=80
COVERAGE_DIR="coverage-report"
LOG_FILE="test-results.log"
PARALLEL_JOBS=4

# Fonctions d'affichage
print_header() {
    echo -e "${BOLD}${BLUE}$1${NC}"
    echo -e "${BLUE}$(printf '=%.0s' {1..60})${NC}"
}

print_step() {
    echo -e "${CYAN}[TEST]${NC} $1"
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

print_info() {
    echo -e "${PURPLE}[INFO]${NC} $1"
}

# Fonction pour afficher le temps écoulé
show_elapsed_time() {
    local end_time=$(date +%s)
    local elapsed=$((end_time - START_TIME))
    local minutes=$((elapsed / 60))
    local seconds=$((elapsed % 60))
    
    if [ $minutes -gt 0 ]; then
        echo -e "${BLUE}Temps écoulé: ${minutes}m ${seconds}s${NC}"
    else
        echo -e "${BLUE}Temps écoulé: ${seconds}s${NC}"
    fi
}

# Vérification de l'environnement
check_environment() {
    print_step "Vérification de l'environnement de test..."
    
    # Vérifier les fichiers requis
    local required_files=(".env.testing" "phpunit.xml" "artisan")
    for file in "${required_files[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Fichier manquant: $file"
            print_info "Exécutez d'abord: ./scripts/setup-tests.sh"
            exit 1
        fi
    done
    
    # Vérifier PHP et extensions
    if ! php -m | grep -q sqlite3; then
        print_error "Extension SQLite3 manquante"
        exit 1
    fi
    
    # Vérifier Composer
    if [ ! -d "vendor" ]; then
        print_warning "Dossier vendor manquant, installation des dépendances..."
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
    
    print_success "Environnement vérifié"
}

# Nettoyage pré-test
cleanup_before_tests() {
    print_step "Nettoyage pré-test..."
    
    # Nettoyer les caches
    php artisan config:clear --env=testing >/dev/null 2>&1 || true
    php artisan cache:clear --env=testing >/dev/null 2>&1 || true
    php artisan view:clear --env=testing >/dev/null 2>&1 || true
    php artisan route:clear --env=testing >/dev/null 2>&1 || true
    
    # Nettoyer les anciens rapports
    rm -rf "$COVERAGE_DIR" 2>/dev/null || true
    rm -f coverage.xml coverage.clover "$LOG_FILE" 2>/dev/null || true
    
    # Nettoyer le cache PHPUnit
    rm -rf .phpunit.cache 2>/dev/null || true
    
    # Nettoyer les fichiers de test temporaires
    rm -rf storage/framework/testing 2>/dev/null || true
    rm -rf storage/app/testing 2>/dev/null || true
    
    print_success "Nettoyage terminé"
}

# Reset de la base de données
reset_test_database() {
    print_step "Initialisation de la base de données de test..."
    
    if [ -f "./scripts/reset-test-db.sh" ]; then
        ./scripts/reset-test-db.sh
    else
        # Fallback si le script n'existe pas
        php artisan migrate:fresh --env=testing --force --seed --seeder=Tests\\Database\\Seeders\\TestDatabaseSeeder
    fi
    
    print_success "Base de données initialisée"
}

# Optimisation Composer
optimize_composer() {
    print_step "Optimisation Composer pour les tests..."
    
    composer dump-autoload --optimize --quiet 2>/dev/null || composer dump-autoload --optimize
    
    print_success "Optimisation terminée"
}

# Détection du framework de test
detect_test_framework() {
    if [ -f "vendor/bin/pest" ] && command -v vendor/bin/pest &> /dev/null; then
        echo "pest"
    elif [ -f "vendor/bin/phpunit" ] && command -v vendor/bin/phpunit &> /dev/null; then
        echo "phpunit"
    else
        print_error "Aucun framework de test détecté (PHPUnit ou Pest)"
        exit 1
    fi
}

# Exécution des tests avec Pest
run_pest_tests() {
    print_step "Exécution des tests avec Pest..."
    
    local pest_args="--colors=always"
    
    # Ajouter la couverture si disponible
    if php -m | grep -q xdebug; then
        pest_args="$pest_args --coverage --coverage-html=$COVERAGE_DIR --coverage-clover=coverage.xml --min=$COVERAGE_MIN"
        print_info "Couverture de code activée (minimum: $COVERAGE_MIN%)"
    else
        print_warning "Xdebug non disponible, couverture désactivée"
    fi
    
    # Exécution avec parallélisation si disponible
    if vendor/bin/pest --help | grep -q parallel; then
        pest_args="$pest_args --parallel --processes=$PARALLEL_JOBS"
        print_info "Exécution en parallèle avec $PARALLEL_JOBS processus"
    fi
    
    # Lancer les tests
    echo -e "${YELLOW}Commande:${NC} vendor/bin/pest $pest_args"
    echo
    
    if vendor/bin/pest $pest_args 2>&1 | tee "$LOG_FILE"; then
        print_success "Tests Pest réussis"
        return 0
    else
        print_error "Échec des tests Pest"
        return 1
    fi
}

# Exécution des tests avec PHPUnit
run_phpunit_tests() {
    print_step "Exécution des tests avec PHPUnit..."
    
    local phpunit_args="--colors=always --testdox"
    
    # Ajouter la couverture si disponible
    if php -m | grep -q xdebug; then
        phpunit_args="$phpunit_args --coverage-html=$COVERAGE_DIR --coverage-clover=coverage.xml"
        print_info "Couverture de code activée"
    else
        print_warning "Xdebug non disponible, couverture désactivée"
    fi
    
    # Lancer les tests
    echo -e "${YELLOW}Commande:${NC} vendor/bin/phpunit $phpunit_args"
    echo
    
    if vendor/bin/phpunit $phpunit_args 2>&1 | tee "$LOG_FILE"; then
        print_success "Tests PHPUnit réussis"
        return 0
    else
        print_error "Échec des tests PHPUnit"
        return 1
    fi
}

# Tests spécifiques par type
run_unit_tests() {
    print_step "Exécution des tests unitaires..."
    
    local framework=$(detect_test_framework)
    
    if [ "$framework" = "pest" ]; then
        vendor/bin/pest tests/Unit --colors=always
    else
        vendor/bin/phpunit tests/Unit --colors=always --testdox
    fi
}

run_feature_tests() {
    print_step "Exécution des tests fonctionnels..."
    
    local framework=$(detect_test_framework)
    
    if [ "$framework" = "pest" ]; then
        vendor/bin/pest tests/Feature --colors=always
    else
        vendor/bin/phpunit tests/Feature --colors=always --testdox
    fi
}

# Analyse des résultats
analyze_test_results() {
    print_step "Analyse des résultats..."
    
    if [ ! -f "$LOG_FILE" ]; then
        print_warning "Fichier de log des tests introuvable"
        return
    fi
    
    # Extraire les statistiques
    local total_tests=$(grep -o "Tests: [0-9]*" "$LOG_FILE" | head -1 | grep -o "[0-9]*" || echo "N/A")
    local assertions=$(grep -o "Assertions: [0-9]*" "$LOG_FILE" | head -1 | grep -o "[0-9]*" || echo "N/A")
    local failures=$(grep -o "Failures: [0-9]*" "$LOG_FILE" | head -1 | grep -o "[0-9]*" || echo "0")
    local errors=$(grep -o "Errors: [0-9]*" "$LOG_FILE" | head -1 | grep -o "[0-9]*" || echo "0")
    
    echo
    print_header "RÉSULTATS DES TESTS"
    echo -e "${BLUE}Tests exécutés:${NC} $total_tests"
    echo -e "${BLUE}Assertions:${NC} $assertions"
    echo -e "${BLUE}Échecs:${NC} $failures"
    echo -e "${BLUE}Erreurs:${NC} $errors"
    
    # Analyse de la couverture
    if [ -f "coverage.xml" ]; then
        local coverage=$(grep -o 'line-rate="[0-9.]*"' coverage.xml | head -1 | grep -o '[0-9.]*' || echo "0")
        local coverage_percent=$(echo "$coverage * 100" | bc 2>/dev/null || echo "N/A")
        echo -e "${BLUE}Couverture:${NC} ${coverage_percent}%"
        
        if [ "$coverage_percent" != "N/A" ] && [ $(echo "$coverage_percent >= $COVERAGE_MIN" | bc 2>/dev/null || echo 0) -eq 1 ]; then
            print_success "Couverture suffisante (>= $COVERAGE_MIN%)"
        elif [ "$coverage_percent" != "N/A" ]; then
            print_warning "Couverture insuffisante (< $COVERAGE_MIN%)"
        fi
    fi
    
    show_elapsed_time
}

# Génération du rapport final
generate_report() {
    print_step "Génération du rapport final..."
    
    local report_file="test-report-$(date +%Y%m%d-%H%M%S).md"
    
    cat > "$report_file" << EOF
# Rapport de Tests - $(date '+%Y-%m-%d %H:%M:%S')

## Configuration
- **Framework**: $(detect_test_framework)
- **PHP Version**: $(php -r "echo PHP_VERSION;")
- **Laravel Version**: $(php artisan --version | grep -o "Laravel Framework [0-9.]*")
- **Date d'exécution**: $(date '+%Y-%m-%d %H:%M:%S')

## Résultats
EOF

    if [ -f "$LOG_FILE" ]; then
        echo "### Statistiques" >> "$report_file"
        grep -E "(Tests:|Assertions:|Failures:|Errors:)" "$LOG_FILE" | head -4 >> "$report_file"
        echo >> "$report_file"
    fi
    
    if [ -f "coverage.xml" ]; then
        echo "### Couverture de Code" >> "$report_file"
        local coverage=$(grep -o 'line-rate="[0-9.]*"' coverage.xml | head -1 | grep -o '[0-9.]*' || echo "0")
        local coverage_percent=$(echo "$coverage * 100" | bc 2>/dev/null || echo "N/A")
        echo "- **Couverture totale**: ${coverage_percent}%" >> "$report_file"
        echo >> "$report_file"
    fi
    
    echo "### Fichiers générés" >> "$report_file"
    [ -d "$COVERAGE_DIR" ] && echo "- Rapport de couverture HTML: \`$COVERAGE_DIR/index.html\`" >> "$report_file"
    [ -f "coverage.xml" ] && echo "- Rapport de couverture XML: \`coverage.xml\`" >> "$report_file"
    echo "- Log complet: \`$LOG_FILE\`" >> "$report_file"
    
    print_success "Rapport généré: $report_file"
}

# Nettoyage post-test
cleanup_after_tests() {
    print_step "Nettoyage post-test..."
    
    # Garder les rapports importants, nettoyer le reste
    rm -rf storage/framework/testing 2>/dev/null || true
    rm -rf storage/app/testing 2>/dev/null || true
    
    # Optimiser à nouveau
    composer dump-autoload --optimize --quiet 2>/dev/null || true
    
    print_success "Nettoyage terminé"
}

# Affichage de l'aide
show_help() {
    echo -e "${BOLD}Script d'Exécution des Tests Laravel${NC}"
    echo
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo
    echo -e "${BLUE}Options:${NC}"
    echo "  -h, --help              Afficher cette aide"
    echo "  -u, --unit              Exécuter uniquement les tests unitaires"
    echo "  -f, --feature           Exécuter uniquement les tests fonctionnels"
    echo "  -c, --coverage-only     Générer uniquement la couverture"
    echo "  -q, --quick             Exécution rapide (sans couverture)"
    echo "  -p, --parallel          Forcer l'exécution en parallèle"
    echo "  --min-coverage=NUM      Couverture minimale requise (défaut: $COVERAGE_MIN)"
    echo "  --no-cleanup            Ne pas nettoyer avant/après les tests"
    echo
    echo -e "${BLUE}Exemples:${NC}"
    echo "  $0                      # Exécution complète"
    echo "  $0 --unit              # Tests unitaires uniquement"
    echo "  $0 --quick             # Exécution rapide"
    echo "  $0 --min-coverage=90   # Couverture minimale 90%"
}

# Fonction principale
main() {
    # Variables par défaut
    local unit_only=false
    local feature_only=false
    local coverage_only=false
    local quick_mode=false
    local force_parallel=false
    local no_cleanup=false
    
    # Analyse des arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -u|--unit)
                unit_only=true
                shift
                ;;
            -f|--feature)
                feature_only=true
                shift
                ;;
            -c|--coverage-only)
                coverage_only=true
                shift
                ;;
            -q|--quick)
                quick_mode=true
                shift
                ;;
            -p|--parallel)
                force_parallel=true
                shift
                ;;
            --min-coverage=*)
                COVERAGE_MIN="${1#*=}"
                shift
                ;;
            --no-cleanup)
                no_cleanup=true
                shift
                ;;
            *)
                print_error "Option inconnue: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # En-tête
    echo
    print_header "EXÉCUTION DES TESTS LARAVEL 11"
    echo -e "${BLUE}Démarrage à:${NC} $(date '+%Y-%m-%d %H:%M:%S')"
    echo
    
    # Étapes d'exécution
    check_environment
    
    if [ "$no_cleanup" = false ]; then
        cleanup_before_tests
    fi
    
    reset_test_database
    optimize_composer
    
    # Exécution selon le mode
    local test_success=true
    
    if [ "$unit_only" = true ]; then
        run_unit_tests || test_success=false
    elif [ "$feature_only" = true ]; then
        run_feature_tests || test_success=false
    elif [ "$coverage_only" = true ]; then
        print_step "Mode couverture uniquement..."
        local framework=$(detect_test_framework)
        if [ "$framework" = "pest" ]; then
            vendor/bin/pest --coverage --coverage-html="$COVERAGE_DIR" --coverage-clover=coverage.xml >/dev/null 2>&1 || test_success=false
        else
            vendor/bin/phpunit --coverage-html="$COVERAGE_DIR" --coverage-clover=coverage.xml >/dev/null 2>&1 || test_success=false
        fi
    else
        # Mode complet
        local framework=$(detect_test_framework)
        if [ "$framework" = "pest" ]; then
            run_pest_tests || test_success=false
        else
            run_phpunit_tests || test_success=false
        fi
    fi
    
    # Analyse et rapports
    analyze_test_results
    generate_report
    
    if [ "$no_cleanup" = false ]; then
        cleanup_after_tests
    fi
    
    # Résultat final
    echo
    if [ "$test_success" = true ]; then
        print_header "TESTS RÉUSSIS ✅"
        echo -e "${GREEN}Tous les tests sont passés avec succès !${NC}"
        
        if [ -d "$COVERAGE_DIR" ]; then
            echo -e "${BLUE}Rapport de couverture:${NC} $COVERAGE_DIR/index.html"
        fi
        
        show_elapsed_time
        exit 0
    else
        print_header "TESTS ÉCHOUÉS ❌"
        echo -e "${RED}Certains tests ont échoué. Consultez le log pour plus de détails.${NC}"
        echo -e "${BLUE}Log des tests:${NC} $LOG_FILE"
        
        show_elapsed_time
        exit 1
    fi
}

# Gestion des erreurs
trap 'print_error "Exécution interrompue"; show_elapsed_time; exit 1' ERR INT TERM

# Point d'entrée
main "$@"
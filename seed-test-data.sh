#!/bin/bash

# ==============================================================================
# Script de Seed des Données de Test Laravel 11
# ==============================================================================

set -e

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
NC='\033[0m'

# Variables
SEEDER_CLASS="Tests\\Database\\Seeders\\TestDatabaseSeeder"
ENV_FILE=".env.testing"

print_step() {
    echo -e "${BLUE}[SEED]${NC} $1"
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

# Vérification de l'environnement
check_environment() {
    print_step "Vérification de l'environnement..."
    
    if [ ! -f "$ENV_FILE" ]; then
        print_error "Fichier $ENV_FILE manquant"
        print_info "Exécutez d'abord: ./scripts/setup-tests.sh"
        exit 1
    fi
    
    if [ ! -f "artisan" ]; then
        print_error "Ce script doit être exécuté dans un projet Laravel"
        exit 1
    fi
    
    print_success "Environnement vérifié"
}

# Vérification de la base de données
check_database() {
    print_step "Vérification de la base de données..."
    
    # Tester la connexion à la base de données
    if ! php artisan migrate:status --env=testing >/dev/null 2>&1; then
        print_warning "Base de données non initialisée, création..."
        php artisan migrate --env=testing --force
    fi
    
    print_success "Base de données vérifiée"
}

# Nettoyage des données existantes
clean_existing_data() {
    print_step "Nettoyage des données existantes..."
    
    # Utiliser truncate si disponible, sinon delete
    php artisan tinker --execute="
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            \$tables = ['users', 'events', 'articles', 'payments', 'files'];
            foreach (\$tables as \$table) {
                if (Schema::hasTable(\$table)) {
                    DB::table(\$table)->truncate();
                    echo \"Table \$table nettoyée\\n\";
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            echo \"Nettoyage terminé\\n\";
        } catch (Exception \$e) {
            echo \"Erreur: \" . \$e->getMessage() . \"\\n\";
        }
    " --env=testing 2>/dev/null || true
    
    print_success "Données existantes nettoyées"
}

# Seed des utilisateurs
seed_users() {
    print_step "Création des utilisateurs de test..."
    
    php artisan db:seed --env=testing --class=Tests\\Database\\Seeders\\TestUserSeeder
    
    # Vérifier la création
    local admin_count=$(php artisan tinker --execute="echo App\\Models\\User::where('email', 'like', '%gmail.com')->count();" --env=testing 2>/dev/null || echo "0")
    local user_count=$(php artisan tinker --execute="echo App\\Models\\User::count();" --env=testing 2>/dev/null || echo "0")
    print_success "$user_count utilisateurs créés ($admin_count ASCBP)"
}

# Seed des événements
seed_events() {
    print_step "Création des événements de test..."
    
    php artisan db:seed --env=testing --class=Tests\\Database\\Seeders\\TestEventSeeder
    
    # Vérifier la création
    local vtt_events=$(php artisan tinker --execute="echo App\\Models\\Event::where('category', 'vtt')->count();" --env=testing 2>/dev/null || echo "0")
    local total_events=$(php artisan tinker --execute="echo App\\Models\\Event::count();" --env=testing 2>/dev/null || echo "0")
    print_success "$total_events événements créés ($vtt_events VTT)"
}

# Seed des articles
seed_articles() {
    print_step "Création des articles de test..."
    
    php artisan db:seed --env=testing --class=Tests\\Database\\Seeders\\TestArticleSeeder
    
    # Vérifier la création
    local posts_count=$(php artisan tinker --execute="echo App\\Models\\Article::where('is_post', true)->count();" --env=testing 2>/dev/null || echo "0")
    local total_articles=$(php artisan tinker --execute="echo App\\Models\\Article::count();" --env=testing 2>/dev/null || echo "0")
    print_success "$total_articles articles créés ($posts_count posts)"
}

# Seed des paiements
seed_payments() {
    print_step "Création des paiements de test..."
    
    php artisan db:seed --env=testing --class=Tests\\Database\\Seeders\\TestPaymentSeeder
    
    # Vérifier la création
    local payment_count=$(php artisan tinker --execute="echo App\\Models\\Payment::count();" --env=testing 2>/dev/null || echo "0")
    print_success "$payment_count paiements créés"
}

# Seed des fichiers
seed_files() {
    print_step "Création des fichiers de test..."
    
    # Créer les dossiers nécessaires
    mkdir -p storage/app/public/test
    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/documents
    mkdir -p storage/app/public/certificates
    mkdir -p storage/app/public/avatars
    
    php artisan db:seed --env=testing --class=Tests\\Database\\Seeders\\TestFileSeeder
    
    # Vérifier la création
    local certificates_count=$(php artisan tinker --execute="echo App\\Models\\File::where('extension', 'pdf')->where('path', 'like', '%certificate%')->count();" --env=testing 2>/dev/null || echo "0")
    local total_files=$(php artisan tinker --execute="echo App\\Models\\File::count();" --env=testing 2>/dev/null || echo "0")
    print_success "$total_files fichiers créés ($certificates_count certificats)"
}

# Seed complet avec un seeder
seed_all_with_seeder() {
    print_step "Exécution du seeder principal..."
    
    php artisan db:seed --env=testing --class="$SEEDER_CLASS"
    
    print_success "Seed principal terminé"
}

# Vérification post-seed
verify_seed_data() {
    print_step "Vérification des données créées..."
    
    echo
    print_info "=== STATISTIQUES DES DONNÉES ASCBP ==="
    
    # Statistiques utilisateurs
    local admin_count=$(php artisan tinker --execute="echo App\\Models\\User::where('email', 'like', '%gmail.com')->count();" --env=testing 2>/dev/null || echo "0")
    local user_count=$(php artisan tinker --execute="echo App\\Models\\User::count();" --env=testing 2>/dev/null || echo "0")
    echo -e "${BLUE}Utilisateurs:${NC} $user_count total ($admin_count ASCBP)"
    
    # Statistiques événements
    local upcoming_events=$(php artisan tinker --execute="echo App\\Models\\Event::where('start_date', '>', now())->count();" --env=testing 2>/dev/null || echo "0")
    local vtt_events=$(php artisan tinker --execute="echo App\\Models\\Event::where('category', 'vtt')->count();" --env=testing 2>/dev/null || echo "0")
    local total_events=$(php artisan tinker --execute="echo App\\Models\\Event::count();" --env=testing 2>/dev/null || echo "0")
    echo -e "${BLUE}Événements:${NC} $total_events total ($upcoming_events à venir, $vtt_events VTT)"
    
    # Statistiques articles
    local published_articles=$(php artisan tinker --execute="echo App\\Models\\Article::where('status', 'published')->count();" --env=testing 2>/dev/null || echo "0")
    local posts_count=$(php artisan tinker --execute="echo App\\Models\\Article::where('is_post', true)->count();" --env=testing 2>/dev/null || echo "0")
    local total_articles=$(php artisan tinker --execute="echo App\\Models\\Article::count();" --env=testing 2>/dev/null || echo "0")
    echo -e "${BLUE}Articles:${NC} $total_articles total ($published_articles publiés, $posts_count posts)"
    
    # Statistiques fichiers
    local certificates_count=$(php artisan tinker --execute="echo App\\Models\\File::where('extension', 'pdf')->where('path', 'like', '%certificate%')->count();" --env=testing 2>/dev/null || echo "0")
    local images_count=$(php artisan tinker --execute="echo App\\Models\\File::where('extension', 'jpg')->count();" --env=testing 2>/dev/null || echo "0")
    local total_files=$(php artisan tinker --execute="echo App\\Models\\File::count();" --env=testing 2>/dev/null || echo "0")
    echo -e "${BLUE}Fichiers:${NC} $total_files total ($images_count images, $certificates_count certificats)"
    
    echo
    print_info "=== COMPTES DE TEST ASCBP ==="
    echo -e "${YELLOW}Admin:${NC} admin@gmail.com / password"
    echo -e "${YELLOW}User:${NC} user@gmail.com / password"
    echo -e "${YELLOW}Animateur:${NC} animateur@gmail.com / password"
    
    print_success "Vérification terminée"
}

# Création de fichiers de test physiques
create_test_files() {
    print_step "Création des fichiers physiques de test..."
    
    # Image de test
    if command -v convert &> /dev/null; then
        convert -size 800x600 xc:lightblue -pointsize 40 -fill black -gravity center \
                -annotate +0+0 "Image de Test" storage/app/public/test/test-image.jpg 2>/dev/null || true
    else
        # Créer un fichier placeholder
        echo "Image de test - Placeholder" > storage/app/public/test/test-image.jpg
    fi
    
    # Document de test
    cat > storage/app/public/test/test-document.txt << 'EOF'
Document de Test
================

Ce document a été créé automatiquement pour les tests.

Contenu de test avec plusieurs lignes pour vérifier
les fonctionnalités de lecture et de traitement
des documents.

Date de création: $(date)
EOF
    
    # Certificat de test (PDF factice)
    cat > storage/app/public/test/test-certificate.pdf << 'EOF'
%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Certificat de Test) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000217 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
309
%%EOF
EOF
    
    print_success "Fichiers physiques créés"
}

# Affichage de l'aide
show_help() {
    echo -e "${BLUE}Script de Seed des Données de Test${NC}"
    echo
    echo -e "${YELLOW}Usage:${NC} $0 [OPTIONS]"
    echo
    echo -e "${YELLOW}Options:${NC}"
    echo "  -h, --help              Afficher cette aide"
    echo "  -u, --users-only        Créer uniquement les utilisateurs"
    echo "  -e, --events-only       Créer uniquement les événements"
    echo "  -a, --articles-only     Créer uniquement les articles"
    echo "  -p, --payments-only     Créer uniquement les paiements"
    echo "  -f, --files-only        Créer uniquement les fichiers"
    echo "  -c, --clean             Nettoyer avant de créer"
    echo "  --no-verify             Ne pas vérifier après création"
    echo "  --with-files            Créer aussi les fichiers physiques"
    echo
    echo -e "${YELLOW}Exemples:${NC}"
    echo "  $0                      # Seed complet"
    echo "  $0 --users-only         # Utilisateurs uniquement"
    echo "  $0 --clean              # Nettoyer puis créer"
    echo "  $0 --with-files         # Inclure fichiers physiques"
}

# Fonction principale
main() {
    local users_only=false
    local events_only=false
    local articles_only=false
    local payments_only=false
    local files_only=false
    local clean_first=false
    local no_verify=false
    local with_files=false
    
    # Analyse des arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -u|--users-only)
                users_only=true
                shift
                ;;
            -e|--events-only)
                events_only=true
                shift
                ;;
            -a|--articles-only)
                articles_only=true
                shift
                ;;
            -p|--payments-only)
                payments_only=true
                shift
                ;;
            -f|--files-only)
                files_only=true
                shift
                ;;
            -c|--clean)
                clean_first=true
                shift
                ;;
            --no-verify)
                no_verify=true
                shift
                ;;
            --with-files)
                with_files=true
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
    echo -e "${BLUE}===============================================${NC}"
    echo -e "${BLUE}      SEED DES DONNÉES DE TEST LARAVEL       ${NC}"
    echo -e "${BLUE}===============================================${NC}"
    echo
    
    # Vérifications
    check_environment
    check_database
    
    # Nettoyage si demandé
    if [ "$clean_first" = true ]; then
        clean_existing_data
    fi
    
    # Seed selon les options
    if [ "$users_only" = true ]; then
        seed_users
    elif [ "$events_only" = true ]; then
        seed_events
    elif [ "$articles_only" = true ]; then
        seed_articles
    elif [ "$payments_only" = true ]; then
        seed_payments
    elif [ "$files_only" = true ]; then
        seed_files
    else
        # Seed complet
        seed_all_with_seeder
    fi
    
    # Créer les fichiers physiques si demandé
    if [ "$with_files" = true ]; then
        create_test_files
    fi
    
    # Vérification finale
    if [ "$no_verify" = false ]; then
        verify_seed_data
    fi
    
    # Message final
    echo
    echo -e "${GREEN}===============================================${NC}"
    echo -e "${GREEN}            SEED TERMINÉ AVEC SUCCÈS         ${NC}"
    echo -e "${GREEN}===============================================${NC}"
    echo
    echo -e "${BLUE}Prochaines étapes:${NC}"
    echo "  • Lancer les tests: ./scripts/run-all-tests.sh"
    echo "  • Reset complet: ./scripts/reset-test-db.sh"
    echo "  • Tests spécifiques: vendor/bin/phpunit tests/Feature/UserTest.php"
    echo
}

# Gestion des erreurs
trap 'print_error "Seed interrompu"; exit 1' ERR

# Exécution
main "$@"
# ==============================================================================
# Makefile Ultra-Compact ASCBP - Une Commande Pour Tout Faire
# ==============================================================================

.PHONY: help test-all test-setup test-quick test-reset test-clean

# Couleurs pour les messages
GREEN = \033[0;32m
BLUE = \033[0;34m
YELLOW = \033[1;33m
NC = \033[0m

# Variables
ARTISAN = php artisan
COMPOSER = composer

help: ## 📋 Afficher l'aide
	@echo ""
	@echo "$(BLUE)╔══════════════════════════════════════════════╗$(NC)"
	@echo "$(BLUE)║         🧪 ASCBP TESTS - COMMANDES          ║$(NC)"
	@echo "$(BLUE)║              Laravel 11 Ready               ║$(NC)"
	@echo "$(BLUE)╚══════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo "$(YELLOW)Commandes principales :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

test-all: ## 🚀 TOUT EN UNE FOIS : Setup + Seed + Tests
	@echo "$(BLUE)🚀 Setup complet + Tests ASCBP...$(NC)"
	@$(ARTISAN) test:setup --run
	@echo "$(GREEN)✅ TERMINÉ ! Environnement prêt et testé$(NC)"

test-setup: ## ⚙️ Setup initial complet
	@echo "$(BLUE)⚙️ Setup environnement de test...$(NC)"
	@$(ARTISAN) test:setup
	@echo "$(GREEN)✅ Setup terminé$(NC)"

test-quick: ## ⚡ Setup + Tests rapide
	@echo "$(BLUE)⚡ Setup rapide + Tests...$(NC)"
	@$(ARTISAN) test:setup --quick --run
	@echo "$(GREEN)✅ Tests rapides terminés$(NC)"

test-reset: ## 🔄 Reset base + Nouveau seed
	@echo "$(BLUE)🔄 Reset base de données...$(NC)"
	@$(ARTISAN) test:setup --reset
	@echo "$(GREEN)✅ Base réinitialisée$(NC)"

test-seed: ## 🌱 Seed données uniquement
	@echo "$(BLUE)🌱 Création données de test...$(NC)"
	@$(ARTISAN) test:setup --seed-only
	@echo "$(GREEN)✅ Données créées$(NC)"

test-run: ## 🧪 Lancer tests uniquement
	@echo "$(BLUE)🧪 Exécution des tests...$(NC)"
	@if [ -f "vendor/bin/pest" ]; then \
		vendor/bin/pest --colors=always; \
	else \
		vendor/bin/phpunit --colors=always; \
	fi

test-coverage: ## 📊 Tests avec couverture
	@echo "$(BLUE)📊 Tests avec couverture de code...$(NC)"
	@if [ -f "vendor/bin/pest" ]; then \
		vendor/bin/pest --coverage --coverage-html=coverage-report; \
	else \
		vendor/bin/phpunit --coverage-html=coverage-report; \
	fi
	@echo "$(GREEN)✅ Rapport dans coverage-report/index.html$(NC)"

test-unit: ## 🔬 Tests unitaires uniquement
	@echo "$(BLUE)🔬 Tests unitaires...$(NC)"
	@if [ -f "vendor/bin/pest" ]; then \
		vendor/bin/pest tests/Unit --colors=always; \
	else \
		vendor/bin/phpunit tests/Unit --colors=always; \
	fi

test-feature: ## 🎯 Tests fonctionnels uniquement
	@echo "$(BLUE)🎯 Tests fonctionnels...$(NC)"
	@if [ -f "vendor/bin/pest" ]; then \
		vendor/bin/pest tests/Feature --colors=always; \
	else \
		vendor/bin/phpunit tests/Feature --colors=always; \
	fi

test-clean: ## 🧹 Nettoyage complet
	@echo "$(BLUE)🧹 Nettoyage environnement...$(NC)"
	@rm -rf .phpunit.cache coverage-report
	@rm -f .env.testing phpunit.xml
	@rm -rf tests/Database
	@echo "$(GREEN)✅ Nettoyage terminé$(NC)"

install: ## 📦 Installation complète projet
	@echo "$(BLUE)📦 Installation dépendances...$(NC)"
	@$(COMPOSER) install --optimize-autoloader
	@npm install
	@cp .env.example .env
	@$(ARTISAN) key:generate
	@$(ARTISAN) migrate
	@echo "$(GREEN)✅ Installation terminée$(NC)"

dev: ## 🔥 Démarrage environnement dev
	@echo "$(BLUE)🔥 Démarrage environnement de développement...$(NC)"
	@$(COMPOSER) run dev

# Commandes Docker (si nécessaire)
docker-test: ## 🐳 Tests dans Docker
	@echo "$(BLUE)🐳 Tests Docker...$(NC)"
	@docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit
	@docker-compose -f docker-compose.test.yml down -v

docker-clean: ## 🐳 Nettoyage Docker
	@echo "$(BLUE)🐳 Nettoyage Docker...$(NC)"
	@docker-compose -f docker-compose.test.yml down -v --remove-orphans
	@docker system prune -f

# Commandes de vérification
check: ## 🔍 Vérifications rapides
	@echo "$(BLUE)🔍 Vérifications système...$(NC)"
	@php --version
	@$(COMPOSER) --version
	@$(ARTISAN) --version
	@echo "$(GREEN)✅ Système vérifié$(NC)"

status: ## 📊 Statut environnement
	@echo "$(BLUE)📊 Statut environnement de test...$(NC)"
	@echo "$(YELLOW)Fichiers de configuration :$(NC)"
	@ls -la .env.testing phpunit.xml 2>/dev/null || echo "  ❌ Fichiers manquants - Lancez 'make test-setup'"
	@echo "$(YELLOW)Base de données de test :$(NC)"
	@$(ARTISAN) migrate:status --env=testing 2>/dev/null || echo "  ❌ Base non initialisée"
	@echo "$(YELLOW)Données de test :$(NC)"
	@$(ARTISAN) tinker --execute="echo 'Users: ' . App\\Models\\User::count(); echo '\\nEvents: ' . App\\Models\\Event::count();" --env=testing 2>/dev/null || echo "  ❌ Pas de données"

# Commande ultime : TOUT nettoyer et refaire
fresh: test-clean install test-all ## 🆕 RESET TOTAL : Nettoie tout et refait le setup complet
	@echo "$(GREEN)🎉 Environnement complètement rafraîchi !$(NC)"

# ==============================================================================
# USAGE RAPIDE :
# 
# make test-all     <- 🚀 LA COMMANDE MAGIQUE (setup + tests)
# make test-quick   <- ⚡ Version rapide
# make test-reset   <- 🔄 Reset des données
# make fresh        <- 🆕 Reset total et setup
# ==============================================================================
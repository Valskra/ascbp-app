# ==============================================================================
# docker/scripts/run-tests-docker.sh - Script pour Docker
# ==============================================================================

#!/bin/bash

set -e

echo "=== Démarrage des Tests Docker ==="

# Attendre que les services soient prêts
echo "Attente des services..."
sleep 10

# Vérifier PostgreSQL
until docker-compose -f docker-compose.test.yml exec postgres-test pg_isready -U laravel_test; do
  echo "Attente de PostgreSQL..."
  sleep 2
done

# Vérifier Redis
until docker-compose -f docker-compose.test.yml exec redis-test redis-cli ping; do
  echo "Attente de Redis..."
  sleep 2
done

echo "Services prêts !"

# Exécuter le setup complet
docker-compose -f docker-compose.test.yml exec laravel-test ./scripts/setup-tests.sh

# Lancer les tests
docker-compose -f docker-compose.test.yml exec laravel-test ./scripts/run-all-tests.sh

echo "=== Tests Docker Terminés ==="

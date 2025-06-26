#!/bin/bash

DEPLOY_DIR="/home/deploy/ascbp-deployment"
cd $DEPLOY_DIR

echo "🏥 Vérification de l'état de l'application ASCBP..."
echo "Timestamp: $(date)"
echo "=================================================="

# Vérification des conteneurs
echo ""
echo "📦 État des conteneurs:"
docker-compose -f docker-compose.production.yml ps

# Vérification de la connectivité de l'application
echo ""
echo "🌐 Test de connectivité HTTP:"
if curl -f -s http://localhost/api/health >/dev/null 2>&1; then
    echo "✅ HTTP accessible"
    echo "   Response: $(curl -s http://localhost/api/health)"
else
    echo "❌ HTTP non accessible"
fi

echo ""
echo "🔒 Test de connectivité HTTPS:"
if curl -f -s -k https://localhost/api/health >/dev/null 2>&1; then
    echo "✅ HTTPS accessible"
    echo "   Response: $(curl -s -k https://localhost/api/health)"
else
    echo "❌ HTTPS non accessible"
fi

# Vérification de la base de données
echo ""
echo "🗄️  Test de la base de données MySQL:"
if docker-compose -f docker-compose.production.yml exec -T db mysqladmin ping -h localhost >/dev/null 2>&1; then
    echo "✅ MySQL accessible"
    # Test de connexion Laravel
    if docker-compose -f docker-compose.production.yml exec -T app php artisan migrate:status >/dev/null 2>&1; then
        echo "✅ Laravel peut se connecter à MySQL"
    else
        echo "⚠️  Laravel ne peut pas se connecter à MySQL"
    fi
else
    echo "❌ Problème avec MySQL"
fi

# Vérification de Redis
echo ""
echo "🔴 Test de Redis:"
if docker-compose -f docker-compose.production.yml exec -T redis redis-cli ping >/dev/null 2>&1; then
    echo "✅ Redis accessible"
    # Test de connexion Laravel
    if docker-compose -f docker-compose.production.yml exec -T app php artisan tinker --execute="Cache::put('health-check', 'ok', 10); echo Cache::get('health-check');" 2>/dev/null | grep -q "ok"; then
        echo "✅ Laravel peut utiliser Redis"
    else
        echo "⚠️  Laravel ne peut pas utiliser Redis"
    fi
else
    echo "❌ Problème avec Redis"
fi

# Vérification des processus dans le conteneur app
echo ""
echo "⚙️  Processus dans le conteneur app:"
if docker-compose -f docker-compose.production.yml exec -T app supervisorctl status 2>/dev/null; then
    echo "✅ Supervisor fonctionne"
else
    echo "❌ Problème avec Supervisor"
fi

# Utilisation de l'espace disque
echo ""
echo "💾 Utilisation de l'espace disque:"
df -h / | head -2

# Utilisation des volumes Docker
echo ""
echo "📁 Volumes Docker:"
docker volume ls | grep ascbp || echo "Aucun volume ASCBP trouvé"

# Vérification des logs récents (erreurs)
echo ""
echo "📝 Dernières erreurs dans les logs (dernières 10 lignes):"
echo "--- Logs Application ---"
docker-compose -f docker-compose.production.yml logs --tail=10 app 2>/dev/null | grep -i error || echo "Aucune erreur récente dans l'application"

echo "--- Logs Base de données ---"
docker-compose -f docker-compose.production.yml logs --tail=5 db 2>/dev/null | grep -i error || echo "Aucune erreur récente dans MySQL"

echo "--- Logs Redis ---"
docker-compose -f docker-compose.production.yml logs --tail=5 redis 2>/dev/null | grep -i error || echo "Aucune erreur récente dans Redis"

echo "--- Logs Caddy ---"
docker-compose -f docker-compose.production.yml logs --tail=5 caddy 2>/dev/null | grep -i error || echo "Aucune erreur récente dans Caddy"

# Vérification des certificats SSL (si Caddy gère HTTPS)
echo ""
echo "🔐 Certificats SSL:"
if docker-compose -f docker-compose.production.yml exec -T caddy caddy list-certificates 2>/dev/null; then
    echo "✅ Certificats SSL configurés"
else
    echo "⚠️  Impossible de vérifier les certificats SSL"
fi

# Performance rapide
echo ""
echo "⚡ Test de performance (temps de réponse):"
response_time=$(curl -o /dev/null -s -w "%{time_total}" http://localhost/api/health 2>/dev/null || echo "N/A")
echo "Temps de réponse HTTP: ${response_time}s"

# Résumé
echo ""
echo "=================================================="
echo "📊 RÉSUMÉ DE SANTÉ:"

# Compteur de services fonctionnels
healthy_services=0
total_services=4

if curl -f -s http://localhost/api/health >/dev/null 2>&1; then
    echo "✅ Application Web"
    ((healthy_services++))
else
    echo "❌ Application Web"
fi

if docker-compose -f docker-compose.production.yml exec -T db mysqladmin ping -h localhost >/dev/null 2>&1; then
    echo "✅ Base de données"
    ((healthy_services++))
else
    echo "❌ Base de données"
fi

if docker-compose -f docker-compose.production.yml exec -T redis redis-cli ping >/dev/null 2>&1; then
    echo "✅ Cache Redis"
    ((healthy_services++))
else
    echo "❌ Cache Redis"
fi

if docker-compose -f docker-compose.production.yml ps | grep -q "Up"; then
    echo "✅ Conteneurs Docker"
    ((healthy_services++))
else
    echo "❌ Conteneurs Docker"
fi

echo ""
echo "🎯 Score de santé: ${healthy_services}/${total_services} services fonctionnels"

if [ $healthy_services -eq $total_services ]; then
    echo "🎉 Système entièrement fonctionnel!"
    exit 0
elif [ $healthy_services -gt $((total_services / 2)) ]; then
    echo "⚠️  Système partiellement fonctionnel"
    exit 1
else
    echo "🚨 Système en panne critique"
    exit 2
fi
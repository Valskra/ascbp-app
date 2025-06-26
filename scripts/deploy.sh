#!/bin/bash
set -e

# Configuration
DEPLOY_DIR="/home/deploy/ascbp-deployment"
PROJECT_DIR="/home/deploy/2024_grad"
REPO_URL="git@git.ecole-89.com:USERNAME/2024_grad.git"  # Remplacez USERNAME
DEPLOY_REPO_URL="git@git.ecole-89.com:USERNAME/2024_grad_deploy.git"
BRANCH="${1:-main}"

echo "🚀 Déploiement ASCBP (branche: $BRANCH)..."

# Création des répertoires si nécessaire
sudo -u deploy mkdir -p $DEPLOY_DIR
sudo -u deploy mkdir -p $PROJECT_DIR

cd $DEPLOY_DIR

# Clone ou mise à jour du dépôt de déploiement
if [ ! -d ".git" ]; then
    echo "📥 Clonage initial du dépôt de déploiement..."
    sudo -u deploy git clone $DEPLOY_REPO_URL .
else
    echo "📥 Mise à jour du dépôt de déploiement..."
    sudo -u deploy git pull origin main
fi

# Clone ou mise à jour du projet principal
cd $PROJECT_DIR
if [ ! -d ".git" ]; then
    echo "📥 Clonage initial du projet..."
    sudo -u deploy git clone $REPO_URL .
else
    echo "📥 Mise à jour du projet..."
    sudo -u deploy git fetch origin
    sudo -u deploy git reset --hard origin/$BRANCH
fi

cd $DEPLOY_DIR

# Copie des fichiers de configuration depuis le projet
echo "📋 Copie des fichiers de configuration..."
sudo -u deploy cp $PROJECT_DIR/docker-compose.yml ./docker-compose.production.yml
sudo -u deploy cp -r $PROJECT_DIR/docker .

# Modification du docker-compose pour la production
echo "🔧 Adaptation pour la production..."
sudo -u deploy sed -i "s|context: \.|context: $PROJECT_DIR|g" docker-compose.production.yml

# Vérification des prérequis
if [ ! -f ".env" ]; then
    echo "❌ Fichier .env manquant!"
    echo "📝 Créez .env à partir de .env.example"
    echo "💡 Exemple de commande: cp .env.example .env && nano .env"
    exit 1
fi

# Sauvegarde de la configuration actuelle
if [ -f ".env" ]; then
    echo "💾 Sauvegarde de la configuration..."
    sudo -u deploy cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
fi

# Vérification de la connectivité Docker
echo "🐳 Vérification de Docker..."
if ! docker --version >/dev/null 2>&1; then
    echo "❌ Docker non disponible"
    exit 1
fi

# Arrêt des services existants
echo "⏹️  Arrêt des services..."
docker-compose -f docker-compose.production.yml down || true

# Nettoyage des images orphelines
echo "🧹 Nettoyage des anciennes images..."
docker image prune -f

# Construction des images
echo "🔨 Construction des images Docker..."
docker-compose -f docker-compose.production.yml build --no-cache

# Démarrage des services
echo "▶️  Démarrage des services..."
docker-compose -f docker-compose.production.yml up -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services (60s)..."
sleep 60

# Vérification que les conteneurs sont actifs
echo "📦 Vérification des conteneurs..."
docker-compose -f docker-compose.production.yml ps

# Exécution des migrations
echo "🗄️  Exécution des migrations..."
docker-compose -f docker-compose.production.yml exec -T app php artisan migrate --force

# Optimisation de l'application
echo "⚡ Optimisation de l'application..."
docker-compose -f docker-compose.production.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.production.yml exec -T app php artisan view:cache

# Nettoyage du cache si nécessaire
docker-compose -f docker-compose.production.yml exec -T app php artisan cache:clear

# Vérification de la santé de l'application
echo "🏥 Vérification de l'état de l'application..."
docker-compose -f docker-compose.production.yml exec -T app php artisan about

# Test de connectivité
echo "🌐 Test de connectivité..."
sleep 10

# Test de l'endpoint de santé
if curl -f http://localhost/api/health >/dev/null 2>&1; then
    echo "✅ Application accessible via HTTP"
else
    echo "⚠️  HTTP non accessible, test HTTPS..."
    if curl -f -k https://localhost/api/health >/dev/null 2>&1; then
        echo "✅ Application accessible via HTTPS"
    else
        echo "❌ Application non accessible. Vérification des logs:"
        docker-compose -f docker-compose.production.yml logs --tail=20 app
        echo ""
        echo "🔍 Commandes de diagnostic:"
        echo "  docker-compose -f docker-compose.production.yml logs app"
        echo "  docker-compose -f docker-compose.production.yml exec app php artisan route:list"
        echo "  docker-compose -f docker-compose.production.yml exec app supervisorctl status"
        exit 1
    fi
fi

# Test de la base de données
echo "🗄️  Test de la base de données..."
if docker-compose -f docker-compose.production.yml exec -T db mysqladmin ping -h localhost >/dev/null 2>&1; then
    echo "✅ Base de données accessible"
else
    echo "❌ Problème avec la base de données"
fi

# Test de Redis
echo "🔴 Test de Redis..."
if docker-compose -f docker-compose.production.yml exec -T redis redis-cli ping >/dev/null 2>&1; then
    echo "✅ Redis accessible"
else
    echo "❌ Problème avec Redis"
fi

echo ""
echo "🎉 Déploiement terminé avec succès!"
echo ""
echo "📊 Statut des services:"
docker-compose -f docker-compose.production.yml ps
echo ""
echo "🌐 Application accessible sur:"
echo "  - HTTP:  http://$(curl -s ifconfig.me)/"
echo "  - HTTPS: https://$(curl -s ifconfig.me)/"
echo ""
echo "🔧 Commandes utiles:"
echo "  - Logs:    docker-compose -f docker-compose.production.yml logs -f"
echo "  - Shell:   docker-compose -f docker-compose.production.yml exec app bash"
echo "  - Restart: docker-compose -f docker-compose.production.yml restart"
echo "  - Stop:    docker-compose -f docker-compose.production.yml down"
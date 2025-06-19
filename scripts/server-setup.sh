#!/bin/bash
# ========================================
# scripts/server-setup.sh
# Script de préparation du serveur
# ========================================

set -e

echo "🚀 Configuration du serveur pour ASCBP..."

# Mise à jour du système
echo "📦 Mise à jour du système..."
sudo apt update && sudo apt upgrade -y

# Installation des paquets essentiels
echo "📋 Installation des paquets essentiels..."
sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    ufw \
    fail2ban

# Configuration du pare-feu
echo "🔒 Configuration du pare-feu..."
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# Installation de Docker
echo "🐳 Installation de Docker..."
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Installation de Docker Compose
echo "🐙 Installation de Docker Compose..."
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Création de l'utilisateur deploy
echo "👤 Création de l'utilisateur de déploiement..."
if ! id "deploy" &>/dev/null; then
    sudo useradd -m -s /bin/bash deploy
    sudo usermod -aG docker deploy
    echo "deploy ALL=(ALL) NOPASSWD:ALL" | sudo tee /etc/sudoers.d/deploy
fi

# Configuration SSH pour l'utilisateur deploy
echo "🔑 Configuration SSH..."
sudo -u deploy mkdir -p /home/deploy/.ssh
sudo -u deploy chmod 700 /home/deploy/.ssh

# Note: Les clés SSH doivent être ajoutées manuellement ou via un script séparé
echo "⚠️  N'oubliez pas d'ajouter vos clés SSH publiques dans /home/deploy/.ssh/authorized_keys"

# Création du répertoire de déploiement
echo "📁 Création des répertoires de déploiement..."
sudo -u deploy mkdir -p /home/deploy/ascbp-deployment
sudo -u deploy mkdir -p /home/deploy/ascbp-deployment/docker
sudo -u deploy mkdir -p /home/deploy/ascbp-deployment/docker/mysql/init
sudo -u deploy mkdir -p /home/deploy/ascbp-deployment/docker/php

# Configuration de fail2ban
echo "🛡️  Configuration de fail2ban..."
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Configuration du logging Docker
echo "📝 Configuration du logging Docker..."
sudo mkdir -p /etc/docker
cat << EOF | sudo tee /etc/docker/daemon.json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
EOF

sudo systemctl restart docker

echo "✅ Configuration du serveur terminée!"
echo ""
echo "🔧 Étapes suivantes :"
echo "1. Ajouter vos clés SSH publiques dans /home/deploy/.ssh/authorized_keys"
echo "2. Cloner le repository de déploiement"
echo "3. Configurer les variables d'environnement"
echo "4. Lancer le déploiement"

# ========================================
# scripts/setup-ssh-keys.sh
# Script pour configurer les clés SSH
# ========================================

#!/bin/bash
set -e

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <chemin-vers-cle-publique>"
    echo "Exemple: $0 ~/.ssh/id_rsa.pub"
    exit 1
fi

PUBLIC_KEY_FILE="$1"

if [ ! -f "$PUBLIC_KEY_FILE" ]; then
    echo "❌ Fichier de clé publique non trouvé: $PUBLIC_KEY_FILE"
    exit 1
fi

echo "🔑 Configuration de la clé SSH pour l'utilisateur deploy..."

# Ajouter la clé publique
sudo -u deploy mkdir -p /home/deploy/.ssh
sudo -u deploy chmod 700 /home/deploy/.ssh
sudo -u deploy touch /home/deploy/.ssh/authorized_keys
sudo -u deploy chmod 600 /home/deploy/.ssh/authorized_keys

# Vérifier si la clé existe déjà
if ! sudo -u deploy grep -q "$(cat $PUBLIC_KEY_FILE)" /home/deploy/.ssh/authorized_keys 2>/dev/null; then
    sudo -u deploy bash -c "cat $PUBLIC_KEY_FILE >> /home/deploy/.ssh/authorized_keys"
    echo "✅ Clé SSH ajoutée avec succès"
else
    echo "ℹ️  La clé SSH existe déjà"
fi

# Configuration SSH sécurisée
echo "🔒 Configuration SSH sécurisée..."
sudo cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup

# Configuration SSH recommandée
cat << 'EOF' | sudo tee /etc/ssh/sshd_config.d/99-ascbp-security.conf
# Configuration SSH sécurisée pour ASCBP
Protocol 2
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
AuthorizedKeysFile .ssh/authorized_keys
PermitEmptyPasswords no
ChallengeResponseAuthentication no
UsePAM yes
X11Forwarding no
ClientAliveInterval 300
ClientAliveCountMax 2
MaxAuthTries 3
MaxStartups 2
LoginGraceTime 30
EOF

sudo systemctl restart sshd
echo "✅ Configuration SSH terminée"

# ========================================
# scripts/deploy.sh
# Script de déploiement
# ========================================

#!/bin/bash
set -e

DEPLOY_DIR="/home/deploy/ascbp-deployment"
REPO_URL="https://github.com/votre-username/ascbp-deployment.git"  # À adapter
BRANCH="${1:-main}"

echo "🚀 Déploiement de ASCBP (branche: $BRANCH)..."

cd $DEPLOY_DIR

# Sauvegarde de la configuration actuelle
if [ -f ".env" ]; then
    echo "💾 Sauvegarde de la configuration..."
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
fi

# Mise à jour du code
echo "📥 Récupération du code..."
git fetch origin
git reset --hard origin/$BRANCH

# Vérification des prérequis
echo "🔍 Vérification des prérequis..."
if [ ! -f ".env" ]; then
    echo "❌ Fichier .env manquant. Créez-le à partir de .env.example"
    exit 1
fi

# Arrêt des services existants
echo "⏹️  Arrêt des services..."
docker-compose down || true

# Construction des images
echo "🔨 Construction des images Docker..."
docker-compose build --no-cache

# Nettoyage des images orphelines
echo "🧹 Nettoyage..."
docker image prune -f

# Démarrage des services
echo "▶️  Démarrage des services..."
docker-compose up -d

# Attente que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 30

# Migrations et optimisations
echo "🗄️  Exécution des migrations..."
docker-compose exec -T app php artisan migrate --force

echo "⚡ Optimisation de l'application..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Vérification de la santé de l'application
echo "🏥 Vérification de l'état de l'application..."
docker-compose exec -T app php artisan about

# Test de connectivité
echo "🌐 Test de connectivité..."
sleep 10
if curl -f http://localhost/health >/dev/null 2>&1; then
    echo "✅ Application déployée avec succès!"
else
    echo "❌ Problème détecté. Vérifiez les logs:"
    echo "docker-compose logs app"
    exit 1
fi

echo ""
echo "🎉 Déploiement terminé!"
echo "📊 Statut des services:"
docker-compose ps

# ========================================
# scripts/health-check.sh
# Script de vérification de santé
# ========================================

#!/bin/bash

DEPLOY_DIR="/home/deploy/ascbp-deployment"
cd $DEPLOY_DIR

echo "🏥 Vérification de l'état de l'application ASCBP..."

# Vérification des conteneurs
echo "📦 État des conteneurs:"
docker-compose ps

# Vérification de la connectivité de l'application
echo "🌐 Test de connectivité:"
if curl -f http://localhost/health >/dev/null 2>&1; then
    echo "✅ Application accessible"
else
    echo "❌ Application non accessible"
fi

# Vérification de la base de données
echo "🗄️  Test de la base de données:"
if docker-compose exec -T db mysqladmin ping -h localhost >/dev/null 2>&1; then
    echo "✅ Base de données accessible"
else
    echo "❌ Problème avec la base de données"
fi

# Vérification de Redis
echo "🔴 Test de Redis:"
if docker-compose exec -T redis redis-cli ping >/dev/null 2>&1; then
    echo "✅ Redis accessible"
else
    echo "❌ Problème avec Redis"
fi

# Utilisation de l'espace disque
echo "💾 Utilisation de l'espace disque:"
df -h /

# Logs récents (erreurs)
echo "📝 Dernières erreurs dans les logs:"
docker-compose logs --tail=10 app | grep -i error || echo "Aucune erreur récente trouvée"

echo ""
echo "✅ Vérification terminée"

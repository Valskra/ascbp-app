# 🚀 ASCBP - Déploiement

Application Laravel Breeze + Vue.js + Inertia avec déploiement Docker automatisé.

## 📋 Prérequis

### Dépendances externes et versions

#### Backend (Laravel)
- **PHP**: 8.2+ avec extensions :
  - pdo_mysql, pdo_sqlite (base de données)
  - gd (traitement d'images)  
  - zip, xml, mbstring, curl, openssl, tokenizer, ctype, json, bcmath
- **Composer**: Dernière version stable
- **Node.js**: 18+ (pour le build uniquement)

#### Infrastructure
- **Docker**: 20.04+
- **Docker Compose**: 2.0+
- **Nginx/Caddy**: Serveur web et proxy inverse
- **MySQL**: 8.0 (base de données)
- **Redis**: 7+ (cache et sessions)

### Dépendances de services

#### Base de données MySQL
- **URL**: `db:3306` (conteneur Docker)
- **Variables d'environnement**:
  ```bash
  DB_CONNECTION=mysql
  DB_HOST=db
  DB_PORT=3306
  DB_DATABASE=ascbp
  DB_USERNAME=ascbp_user
  DB_PASSWORD=votre_mot_de_passe
  ```

#### Cache Redis
- **URL**: `redis:6379` (conteneur Docker)
- **Variables d'environnement**:
  ```bash
  CACHE_STORE=redis
  SESSION_DRIVER=redis
  REDIS_HOST=redis
  REDIS_PORT=6379
  ```

#### Stockage S3 (OVH Cloud)
- **URL**: `https://s3.sbg.io.cloud.ovh.net`
- **Variables d'environnement**:
  ```bash
  FILESYSTEM_DISK=s3
  AWS_ACCESS_KEY_ID=votre_access_key
  AWS_SECRET_ACCESS_KEY=votre_secret_key
  AWS_DEFAULT_REGION=sbg
  AWS_BUCKET=ascbp-s3
  AWS_ENDPOINT=https://s3.sbg.io.cloud.ovh.net
  AWS_USE_PATH_STYLE_ENDPOINT=true
  AWS_URL=https://ascbp-s3.s3.sbg.io.cloud.ovh.net
  ```

#### Service de mail SMTP
- **Variables d'environnement à définir**:
  ```bash
  MAIL_MAILER=smtp
  MAIL_HOST=votre_serveur_smtp
  MAIL_PORT=587
  MAIL_USERNAME=votre_email
  MAIL_PASSWORD=votre_mot_de_passe_email
  MAIL_ENCRYPTION=tls
  MAIL_FROM_ADDRESS=noreply@ascbp.fr
  ```

## 🏗️ Architecture

```
[Internet] → [Caddy:80/443] → [Laravel App:80] → [MySQL:3306]
                                              → [Redis:6379]
                                              → [S3 OVH (externe)]
```

### Services Docker

| Service | Image | Port | Description |
|---------|-------|------|-------------|
| `app` | Custom (Laravel + Vue.js) | 80 | Application principale |
| `db` | mysql:8.0 | 3306 | Base de données |
| `redis` | redis:7-alpine | 6379 | Cache et sessions |
| `caddy` | caddy:2-alpine | 80, 443 | Proxy inverse + HTTPS |

## 🚀 Déploiement rapide

### 1. Cloner le repository
```bash
git clone https://github.com/votre-username/ascbp-deployment.git
cd ascbp-deployment
```

### 2. Configuration
```bash
# Copier et configurer l'environnement
cp .env.example .env
nano .env

# Variables importantes à modifier :
# - APP_URL=https://votre-domaine.com
# - DB_PASSWORD=mot_de_passe_securise
# - DOMAIN=votre-domaine.com
# - Clés AWS S3
# - Configuration SMTP
```

### 3. Déploiement
```bash
# Build et lancement
docker-compose up -d

# Migrations
docker-compose exec app php artisan migrate --force

# Optimisations
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### 4. Vérification
```bash
# État des services
docker-compose ps

# Test de santé
curl http://localhost/health
```

## 🔧 Scripts disponibles

### Déploiement automatique
```bash
# Déploiement complet
./scripts/deploy.sh

# Vérification de santé
./scripts/health-check.sh
```

### Configuration serveur (première fois)
```bash
# Configuration serveur Ubuntu/Debian
sudo ./scripts/server-setup.sh

# Configuration SSH
sudo ./scripts/setup-ssh-keys.sh ~/.ssh/id_rsa.pub
```

## 🛠️ Commandes utiles

### Gestion des services
```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Voir les logs
docker-compose logs -f app

# Reconstruire les images
docker-compose build --no-cache
```

### Maintenance Laravel
```bash
# Accéder au conteneur
docker-compose exec app bash

# Migrations
docker-compose exec app php artisan migrate

# Nettoyage du cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Créer un utilisateur admin
docker-compose exec app php artisan make:filament-user
```

### Base de données
```bash
# Sauvegarde
docker-compose exec db mysqldump -u root -p ascbp > backup.sql

# Restauration
docker-compose exec -T db mysql -u root -p ascbp < backup.sql

# Accès direct
docker-compose exec db mysql -u root -p ascbp
```

## 🔐 Sécurité

### Variables d'environnement sensibles
Assurez-vous de modifier ces valeurs dans `.env` :
- `DB_PASSWORD` et `DB_ROOT_PASSWORD`
- `APP_KEY` (générer avec `php artisan key:generate`)
- Clés AWS S3
- Identifiants SMTP

### Configuration pare-feu
```bash
# Ports autorisés
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

## 📊 Monitoring

### Logs
```bash
# Logs de l'application
docker-compose logs app

# Logs du serveur web
docker-compose logs caddy

# Logs de la base de données
docker-compose logs db
```

### Health checks
- Application : `GET /health`
- Base de données : Intégré dans Docker Compose
- Redis : Intégré dans Docker Compose

## 🔄 CI/CD

### GitHub Actions
Le workflow `.github/workflows/ci-cd.yml` automatise :
- Tests backend (PHPUnit/Pest)
- Tests frontend (ESLint, build)
- Build Docker
- Déploiement automatique sur `main`

### Secrets GitHub requis
```
DEPLOY_SSH_KEY=clé_privée_ssh
DEPLOY_USER=deploy
DEPLOY_HOST=ip_serveur
APP_URL=https://votre-domaine.com
```

### Déploiement manuel
```bash
# Sur le serveur
cd /home/deploy/ascbp-deployment
git pull origin main
./scripts/deploy.sh
```

## 🐛 Dépannage

### Problèmes courants

#### Application inaccessible
```bash
# Vérifier l'état des conteneurs
docker-compose ps

# Vérifier les logs
docker-compose logs app
docker-compose logs caddy

# Redémarrer les services
docker-compose restart
```

#### Erreurs de base de données
```bash
# Vérifier la connexion
docker-compose exec app php artisan tinker
# Dans tinker: DB::connection()->getPdo();

# Réinitialiser la base de données
docker-compose exec app php artisan migrate:fresh --seed
```

#### Problèmes de permissions
```bash
# Fixer les permissions
docker-compose exec app chown -R www:www /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

#### Espace disque insuffisant
```bash
# Nettoyer Docker
docker system prune -af
docker volume prune -f

# Nettoyer les logs
sudo journalctl --vacuum-time=7d
```

### Rollback
```bash
# Revenir à la version précédente
git log --oneline -10
git reset --hard <commit-hash>
./scripts/deploy.sh
```

## 📚 Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Docker Documentation](https://docs.docker.com/)
- [Caddy Documentation](https://caddyserver.com/docs/)

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📧 Support

Pour toute question ou problème :
- Créer une issue sur GitHub
- Consulter les logs avec `docker-compose logs`
- Vérifier la santé avec `./scripts/health-check.sh`

---

**Made with ❤️ for ASCBP**
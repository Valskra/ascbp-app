# ASCBP - Analyse des dépendances

## Dépendances externes

### Frontend (Node.js/Vue.js)
- **Node.js** : v18.x LTS (spécifié dans Dockerfile)
- **npm** : v9.x (inclus avec Node.js)
- **Build tools** : Vite, Tailwind CSS, PostCSS
- **Testing** : Vitest, Playwright (E2E)
- **Linting** : ESLint, Prettier

### Backend (PHP/Laravel)
- **PHP** : ^8.2 (spécifié dans Dockerfile)
- **Composer** : latest (installé via image officielle)
- **Extensions PHP requises** :
  - pdo_mysql, pdo_sqlite (bases de données)
  - mbstring, zip, exif, pcntl, bcmath (Laravel core)
  - gd (traitement d'images)
  - intl, xml, soap (services externes)

### Infrastructure
- **MySQL** : 8.0 (base de données production)
- **Redis** : 7-alpine (cache et sessions)
- **Caddy** : 2-alpine (proxy inverse + HTTPS automatique)
- **Nginx** : alpine (serveur web dans conteneur app)
- **Supervisor** : (gestionnaire de processus)

## Dépendances de service

### Application vers base de données
- **URL développement** : `sqlite:///database/database.sqlite`
- **URL production** : `mysql://ascbp_user:password@db:3306/ascbp`
- **Variables d'environnement** :
  ```env
  DB_CONNECTION=mysql
  DB_HOST=db
  DB_PORT=3306
  DB_DATABASE=ascbp
  DB_USERNAME=ascbp_user
  DB_PASSWORD=${DB_PASSWORD}
  ```

### Application vers cache Redis
- **URL interne** : `redis://redis:6379`
- **Variables d'environnement** :
  ```env
  CACHE_STORE=redis
  SESSION_DRIVER=redis
  REDIS_HOST=redis
  REDIS_PORT=6379
  ```

### Application vers services externes

#### OVH S3 Storage
- **URL** : `https://s3.sbg.io.cloud.ovh.net`
- **Variables d'environnement** :
  ```env
  FILESYSTEM_DISK=s3
  AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
  AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
  AWS_DEFAULT_REGION=sbg
  AWS_BUCKET=ascbp-s3
  AWS_ENDPOINT=https://s3.sbg.io.cloud.ovh.net
  AWS_USE_PATH_STYLE_ENDPOINT=true
  AWS_URL=https://ascbp-s3.s3.sbg.io.cloud.ovh.net
  ```

#### Stripe API (Paiements)
- **URL** : `https://api.stripe.com`
- **Variables d'environnement** :
  ```env
  STRIPE_KEY=${STRIPE_PUBLIC_KEY}
  STRIPE_SECRET=${STRIPE_SECRET_KEY}
  ```

### Proxy Caddy vers application
- **URL interne** : `http://app:80`
- **Exposition externe** : ports 80/443
- **Variables d'environnement** :
  ```env
  DOMAIN=${DOMAIN:-localhost}
  ```

## Distinction développement vs production

### Dépendances développement uniquement
**Frontend (package.json devDependencies)** :
- @vitejs/plugin-vue, vite
- eslint, prettier
- vitest, @playwright/test
- tailwindcss, autoprefixer
- concurrently

**Backend (composer.json require-dev)** :
- phpunit/phpunit
- laravel/pint
- fakerphp/faker
- nunomaduro/collision

### Dépendances production
**Frontend (package.json dependencies)** :
- @coreui/coreui, @coreui/vue
- @vuepic/vue-datepicker
- dayjs, vue-advanced-cropper

**Backend (composer.json require)** :
- laravel/framework
- inertiajs/inertia-laravel
- extensions PHP listées ci-dessus

## Optimisations de déploiement

### Multi-stage Docker
1. **Stage 1** : Build frontend avec toutes les devDependencies
2. **Stage 2** : Build backend avec dépendances PHP
3. **Stage 3** : Production avec uniquement les runtime dependencies

### Avantages
- **Taille d'image réduite** : Pas de devDependencies en production
- **Sécurité** : Pas d'outils de développement exposés
- **Performance** : Cache optimisé pour chaque couche
- **Coût** : Moins de ressources utilisées
# 📦 Dépendances et Services – Application ASCBP

## 1. 🧩 Dépendances internes

### 🖼️ Frontend (`package.json`)

#### ✅ Dépendances nécessaires en production (`dependencies`)
| Package                   | Version   | Description                                      |
|--------------------------|-----------|--------------------------------------------------|
| `@coreui/coreui`         | ^5.2.0    | Composants UI (basés sur Bootstrap)             |
| `@coreui/vue`            | ^5.4.1    | Intégration Vue 3 des composants CoreUI         |
| `@vuepic/vue-datepicker` | ^11.0.1   | Sélecteur de date avancé                        |
| `@vueuse/core`           | ^13.0.0   | Utilitaires Vue basés sur Composition API       |
| `dayjs`                  | ^1.11.13  | Manipulation de dates (alternative à moment.js) |
| `vue`                    | ^3.4.0    | Framework JavaScript frontend principal         |
| `vue-advanced-cropper`   | ^2.8.9    | Recadrage interactif d’images côté client       |

#### 🧪 Dépendances de développement (`devDependencies`)
| Package                         | Version   | Description                                         |
|----------------------------------|-----------|-----------------------------------------------------|
| `vite`                          | ^6.0      | Bundler moderne rapide                             |
| `@vitejs/plugin-vue`            | ^5.0.0    | Support Vue 3 pour Vite                            |
| `laravel-vite-plugin`           | ^1.0      | Intégration Vite + Laravel                         |
| `tailwindcss`, `@tailwindcss/forms` | ^3.2.1 | CSS utilitaire + plugin formulaires                |
| `eslint`, `eslint-plugin-vue`   | ^8.57.1   | Linting JS et Vue                                  |
| `prettier` + plugins            | ^3.3.0    | Formatage automatique                              |
| `@inertiajs/vue3`               | ^2.0.0    | Intégration Inertia.js avec Vue                    |
| `vue-tsc`                       | ^2.2.0    | Vérification des types dans les fichiers `.vue`    |
| `concurrently`                  | ^9.0.1    | Exécution parallèle de scripts npm                 |

---

### 🧩 Backend (`composer.json`)

#### ✅ Dépendances nécessaires en production (`require`)
| Package                        | Version   | Description                                                  |
|-------------------------------|-----------|--------------------------------------------------------------|
| `php`                         | ^8.2      | Langage PHP                                                  |
| `laravel/framework`           | ^11.31    | Framework principal backend                                 |
| `laravel/sanctum`             | ^4.0      | Authentification par token (API, SPA)                       |
| `laravel/tinker`              | ^2.9      | Console interactive Laravel                                 |
| `inertiajs/inertia-laravel`   | ^2.0      | Inertia côté Laravel                                        |
| `league/flysystem-aws-s3-v3` | ^3.29     | Intégration S3 pour le stockage de fichiers                 |
| `tightenco/ziggy`             | ^2.0      | Routes Laravel disponibles côté JS (Vue/Inertia)            |

#### 🧪 Dépendances de développement (`require-dev`)
| Package                         | Version   | Description                              |
|----------------------------------|-----------|------------------------------------------|
| `fakerphp/faker`                | ^1.23     | Données aléatoires pour tests            |
| `laravel/breeze`                | ^2.3      | Authentification simple Laravel          |
| `laravel/pail`                  | ^1.1      | Affichage des logs Laravel               |
| `laravel/pint`                  | ^1.13     | Formatage automatique du code PHP        |
| `laravel/sail`                  | ^1.26     | Dev environment basé sur Docker          |
| `mockery/mockery`               | ^1.6      | Mocks pour tests                         |
| `nunomaduro/collision`          | ^8.1      | Affichage d’erreurs en console           |
| `pestphp/pest`                  | ^3.7      | Framework de test moderne                |
| `pestphp/pest-plugin-laravel`   | ^3.0      | Plugin Laravel pour Pest                 |

---

## 2. 🌐 Dépendances de services

### 🔧 Configuration technique

| Service                  | URL prévue (ou locale)                          | Variables d’environnement requises                                  |
|--------------------------|--------------------------------------------------|---------------------------------------------------------------------|
| **Backend Laravel**      | `http://localhost` (dev)                        | `APP_URL`, `APP_NAME`, `APP_ENV`, `APP_DEBUG`                      |
| **Base de données (SQLite)** | Fichier local `database/database.sqlite`    | `DB_CONNECTION=sqlite`                                             |
| **(Optionnel MySQL)**    | `127.0.0.1:3306`                                 | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`  |
| **Sessions Laravel**     | Base de données                                  | `SESSION_DRIVER=database`, `SESSION_LIFETIME`, etc.               |
| **File d’attente**       | Base de données                                  | `QUEUE_CONNECTION=database`                                        |
| **Cache**                | Base de données ou Redis                         | `CACHE_STORE=database`, `CACHE_PREFIX`, `REDIS_*`, `CACHE_DRIVER`  |
| **Redis (optionnel)**    | `redis://127.0.0.1:6379`                         | `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_CLIENT`       |
| **Stockage (S3 - OVH)**  | `https://ascbp-s3.s3.sbg.io.cloud.ovh.net`       | `FILESYSTEM_DISK=s3`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_REGION`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` |
| **Service Mail**         | `127.0.0.1:2525` ou SMTP                        | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` |

---

## 3. 🔐 Variables d’environnement à définir

Ces variables doivent être définies sur l’environnement d’exécution :

### 🔑 Globales
- `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_TIMEZONE`
- `LOG_CHANNEL`, `LOG_LEVEL`

### 🔑 Sécurité / Hash
- `HASH_DRIVER`, `ARGON_MEMORY`, `ARGON_THREADS`, `ARGON_TIME`

### 🔑 Stockage (S3 OVH)
- `FILESYSTEM_DISK`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_DEFAULT_REGION`, `AWS_USE_PATH_STYLE_ENDPOINT`

### 🔑 Mail
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

### 🔑 Base de données
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

#!/bin/bash
# scripts/setup-e2e-environment.sh

echo "🚀 Configuration de l'environnement E2E..."

# 1. Copier le fichier d'environnement de test
cp .env.testing .env.e2e

# 2. Créer la base de données de test
php artisan migrate:fresh --env=testing --seed

# 3. Créer les dossiers de fixtures
mkdir -p tests/fixtures
mkdir -p tests/E2E/screenshots
mkdir -p tests/E2E/videos

# 4. Télécharger les fichiers de test
curl -o tests/fixtures/test-image.jpg https://via.placeholder.com/800x600.jpg
curl -o tests/fixtures/profile.jpg https://via.placeholder.com/300x300.jpg
curl -o tests/fixtures/event.jpg https://via.placeholder.com/1200x600.jpg

# 5. Créer des fichiers PDF de test
echo "Test Certificate Content" > tests/fixtures/certificate.pdf
echo "Test Large File Content" > tests/fixtures/large-file.pdf

# 6. Installer les dépendances Playwright
npm install @playwright/test
npx playwright install
 
# 7. Créer le fichier de routes de test
php artisan make:controller TestController

echo "✅ Environnement E2E configuré avec succès!"
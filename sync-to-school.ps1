# sync-to-school.ps1
$ErrorActionPreference = "Stop"

# Configuration
$SCHOOL_REPO = "https://git.ecole-89.com/alexis.raccah/2024_grad_deploy.git"
$SCHOOL_REMOTE = "school"
$GITHUB_REPO = "https://github.com/Valskra/ascbp-app.git"

Write-Host "🚀 Synchronisation complète vers le dépôt école..." -ForegroundColor Green

# Vérifier qu'on est dans le bon répertoire
if (-not (Test-Path "composer.json")) {
    Write-Host "❌ Exécute ce script depuis le répertoire racine de ton projet Laravel" -ForegroundColor Red
    exit 1
}

# Configuration Git si nécessaire
Write-Host "⚙️ Vérification de la configuration Git..." -ForegroundColor Yellow
try {
    $gitUser = git config user.name
    $gitEmail = git config user.email
    if (-not $gitUser -or -not $gitEmail) {
        Write-Host "Configuration Git manquante. Configuration automatique..." -ForegroundColor Yellow
        git config user.name "Alexis Raccah"
        git config user.email "alexis.raccah@ecole-89.com"
    }
}
catch {
    Write-Host "Configuration de Git..." -ForegroundColor Yellow
    git config user.name "Alexis Raccah"
    git config user.email "alexis.raccah@ecole-89.com"
}

# Configurer Windows Credential Manager
Write-Host "🔐 Configuration du gestionnaire de credentials..." -ForegroundColor Yellow
git config --global credential.helper manager-core

# Supprimer l'ancien remote école s'il existe
try {
    git remote remove $SCHOOL_REMOTE 2>$null
}
catch {
    # Pas grave si le remote n'existait pas
}

# Ajouter le remote école
Write-Host "➕ Ajout du remote école..." -ForegroundColor Yellow
git remote add $SCHOOL_REMOTE $SCHOOL_REPO

# S'assurer qu'on est sur main
Write-Host "🌿 Basculer sur la branche main..." -ForegroundColor Yellow
git checkout main

# Récupérer les dernières modifications depuis GitHub
Write-Host "📥 Récupération des modifications depuis GitHub..." -ForegroundColor Yellow
git fetch origin
git pull origin main

# Vérifier que les fichiers Docker sont présents
$requiredFiles = @(
    "Dockerfile",
    "docker-compose.yml",
    ".env.example",
    "README.md",
    ".github/workflows/ci-cd.yml"
)

Write-Host "📋 Vérification des fichiers requis..." -ForegroundColor Yellow
foreach ($file in $requiredFiles) {
    if (-not (Test-Path $file)) {
        Write-Host "❌ Fichier manquant: $file" -ForegroundColor Red
        Write-Host "Assure-toi d'avoir copié tous les artifacts dans ton projet" -ForegroundColor Red
        exit 1
    }
    else {
        Write-Host "✅ $file présent" -ForegroundColor Green
    }
}

# Ajouter tous les changements
Write-Host "📦 Ajout des fichiers..." -ForegroundColor Yellow
git add .

# Vérifier s'il y a des changements à commiter
$status = git status --porcelain
if ($status) {
    Write-Host "💾 Commit des changements..." -ForegroundColor Yellow
    git commit -m "Add deployment configuration and Docker setup

- Add Dockerfile for Laravel + Vue.js build
- Add docker-compose.yml with full stack (MySQL, Redis, Caddy)
- Add CI/CD pipeline with GitHub Actions
- Add deployment scripts and documentation
- Add comprehensive dependency analysis

Ready for deployment evaluation."
}

# Pousser vers GitHub d'abord
Write-Host "🚀 Push vers GitHub..." -ForegroundColor Yellow
git push origin main

# Pousser vers l'école
Write-Host "🎓 Push vers le dépôt école..." -ForegroundColor Yellow
Write-Host "Ceci peut demander tes identifiants école..." -ForegroundColor Cyan
try {
    git push $SCHOOL_REMOTE main:main --force
    Write-Host "✅ Synchronisation vers l'école réussie !" -ForegroundColor Green
}
catch {
    Write-Host "❌ Échec du push vers l'école. Vérification..." -ForegroundColor Red
    
    # Test de connectivité
    Write-Host "🔍 Test de l'accès au dépôt..." -ForegroundColor Yellow
    try {
        git ls-remote $SCHOOL_REMOTE
        Write-Host "✅ Dépôt accessible, réessai du push..." -ForegroundColor Green
        git push $SCHOOL_REMOTE main:main --force
        Write-Host "✅ Push réussi au second essai !" -ForegroundColor Green
    }
    catch {
        Write-Host "❌ Impossible d'accéder au dépôt école" -ForegroundColor Red
        Write-Host "Vérifiez tes identifiants et l'URL du dépôt" -ForegroundColor Yellow
        throw
    }
}

# Vérification finale
Write-Host "🏥 Vérification finale..." -ForegroundColor Yellow
git remote -v

Write-Host ""
Write-Host "✅ 🎉 SYNCHRONISATION TERMINÉE ! 🎉" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Résumé:" -ForegroundColor Yellow
Write-Host "• GitHub (ascbp-app): ✅ Synchronisé" -ForegroundColor Green
Write-Host "• École (2024_grad_deploy): ✅ Synchronisé" -ForegroundColor Green
Write-Host "• Fichiers Docker: ✅ Présents" -ForegroundColor Green
Write-Host "• Documentation: ✅ Complète" -ForegroundColor Green
Write-Host ""
Write-Host "🎯 Prochaines étapes:" -ForegroundColor Cyan
Write-Host "1. Donner les droits à 'delivery-collector' sur git.ecole-89.com" -ForegroundColor White
Write-Host "2. Préparer un serveur pour l'Étape 3" -ForegroundColor White
Write-Host "3. Tester le déploiement avec docker-compose up -d" -ForegroundColor White
# debug-git.ps1
$ErrorActionPreference = "Continue"

$SCHOOL_REPO = "https://git.ecole-89.com/alexis.raccah/2024_grad.git"
$SCHOOL_REMOTE = "school"

Write-Host "🔍 Diagnostic de synchronisation..." -ForegroundColor Green

# Vérifier l'état actuel
Write-Host "📍 Branche actuelle:" -ForegroundColor Yellow
git branch --show-current

Write-Host "📍 Remotes configurés:" -ForegroundColor Yellow
git remote -v

# Vérifier si le remote école existe
Write-Host "🔗 Vérification du remote école..." -ForegroundColor Yellow
try {
    $remoteUrl = git remote get-url $SCHOOL_REMOTE 2>$null
    if ($remoteUrl) {
        Write-Host "✅ Remote école existe: $remoteUrl" -ForegroundColor Green
    }
    else {
        Write-Host "➕ Ajout du remote école..." -ForegroundColor Yellow
        git remote add $SCHOOL_REMOTE $SCHOOL_REPO
        Write-Host "✅ Remote ajouté: $SCHOOL_REPO" -ForegroundColor Green
    }
}
catch {
    Write-Host "➕ Ajout du remote école..." -ForegroundColor Yellow
    git remote add $SCHOOL_REMOTE $SCHOOL_REPO
    Write-Host "✅ Remote ajouté: $SCHOOL_REPO" -ForegroundColor Green
}

# Test de connectivité
Write-Host "🌐 Test de connectivité vers git.ecole-89.com..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "https://git.ecole-89.com" -TimeoutSec 5 -UseBasicParsing
    Write-Host "✅ git.ecole-89.com accessible (Status: $($response.StatusCode))" -ForegroundColor Green
}
catch {
    Write-Host "❌ git.ecole-89.com non accessible: $($_.Exception.Message)" -ForegroundColor Red
}

# Vérifier les credentials Git
Write-Host "🔑 Configuration Git actuelle:" -ForegroundColor Yellow
try {
    $userName = git config user.name
    $userEmail = git config user.email
    Write-Host "user.name: $userName" -ForegroundColor Cyan
    Write-Host "user.email: $userEmail" -ForegroundColor Cyan
}
catch {
    Write-Host "Configuration Git incomplète" -ForegroundColor Red
}

# Vérifier les credential helpers
Write-Host "🔐 Credential helpers configurés:" -ForegroundColor Yellow
try {
    $credHelper = git config credential.helper
    if ($credHelper) {
        Write-Host "credential.helper: $credHelper" -ForegroundColor Cyan
    }
    else {
        Write-Host "Aucun credential helper configuré" -ForegroundColor Yellow
    }
}
catch {
    Write-Host "Aucun credential helper configuré" -ForegroundColor Yellow
}

# Test d'authentification
Write-Host "🔐 Test d'authentification (ceci peut demander tes identifiants)..." -ForegroundColor Yellow
try {
    git ls-remote $SCHOOL_REPO HEAD 2>$null | Out-Null
    Write-Host "✅ Authentification OK" -ForegroundColor Green
}
catch {
    Write-Host "❌ Échec authentification - Identifiants requis" -ForegroundColor Red
}

# Créer/basculer sur la branche de déploiement
Write-Host "🌿 Préparation de la branche deployment..." -ForegroundColor Yellow
git checkout -B deployment origin/main

Write-Host "📊 État de la branche deployment:" -ForegroundColor Yellow
git log --oneline -3

# Test de push (dry-run)
Write-Host "🧪 Test de push (simulation)..." -ForegroundColor Yellow
try {
    git push --dry-run $SCHOOL_REMOTE deployment:main 2>$null
    Write-Host "✅ Test de push réussi" -ForegroundColor Green
}
catch {
    Write-Host "❌ Échec du test de push" -ForegroundColor Red
}

Write-Host "✅ Diagnostic terminé" -ForegroundColor Green
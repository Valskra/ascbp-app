# debug-git.ps1 - Enhanced version
$ErrorActionPreference = "Continue"

$SCHOOL_REPO = "https://git.ecole-89.com/alexis.raccah/2024_grad.git"
$SCHOOL_REMOTE = "school"

function Write-Section {
    param([string]$Title, [string]$Color = "Green")
    Write-Host "`n=== $Title ===" -ForegroundColor $Color
}

function Test-GitCommand {
    param([string]$Command)
    try {
        $result = Invoke-Expression $Command 2>$null
        return @{ Success = $true; Output = $result }
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

Write-Section "🔍 Diagnostic de synchronisation Git"

# Vérifier si on est dans un repo Git
if (-not (Test-Path ".git")) {
    Write-Host "❌ Erreur: Pas dans un dépôt Git" -ForegroundColor Red
    exit 1
}

# Vérifier l'état actuel
Write-Section "📍 État actuel du repository"
Write-Host "Branche actuelle: " -NoNewline -ForegroundColor Yellow
git branch --show-current

Write-Host "Status du working directory:" -ForegroundColor Yellow
git status --porcelain | ForEach-Object {
    if ($_.Length -gt 0) {
        Write-Host "  $_" -ForegroundColor Red
    }
}
if ((git status --porcelain).Count -eq 0) {
    Write-Host "  ✅ Working directory propre" -ForegroundColor Green
}

Write-Host "Remotes configurés:" -ForegroundColor Yellow
git remote -v | ForEach-Object {
    Write-Host "  $_" -ForegroundColor Cyan
}

# Vérifier et configurer le remote école
Write-Section "🔗 Configuration du remote école"
$remoteTest = Test-GitCommand "git remote get-url $SCHOOL_REMOTE"
if ($remoteTest.Success) {
    Write-Host "✅ Remote école existe: $($remoteTest.Output)" -ForegroundColor Green
    
    # Vérifier si l'URL est correcte
    if ($remoteTest.Output -ne $SCHOOL_REPO) {
        Write-Host "⚠️  URL différente détectée, mise à jour..." -ForegroundColor Yellow
        git remote set-url $SCHOOL_REMOTE $SCHOOL_REPO
        Write-Host "✅ URL mise à jour" -ForegroundColor Green
    }
} else {
    Write-Host "➕ Ajout du remote école..." -ForegroundColor Yellow
    git remote add $SCHOOL_REMOTE $SCHOOL_REPO
    Write-Host "✅ Remote ajouté: $SCHOOL_REPO" -ForegroundColor Green
}

# Test de connectivité réseau
Write-Section "🌐 Test de connectivité réseau"
try {
    $ping = Test-NetConnection -ComputerName "git.ecole-89.com" -Port 443 -InformationLevel Quiet
    if ($ping) {
        Write-Host "✅ Connexion réseau vers git.ecole-89.com OK" -ForegroundColor Green
    } else {
        Write-Host "❌ Impossible de joindre git.ecole-89.com:443" -ForegroundColor Red
    }
} catch {
    Write-Host "⚠️  Test de connexion échoué: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Test HTTP
try {
    $response = Invoke-WebRequest -Uri "https://git.ecole-89.com" -TimeoutSec 10 -UseBasicParsing -ErrorAction Stop
    Write-Host "✅ Serveur web accessible (Status: $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "❌ Serveur web non accessible: $($_.Exception.Message)" -ForegroundColor Red
}

# Configuration Git
Write-Section "🔑 Configuration Git"
$gitConfig = @{
    "user.name" = git config user.name
    "user.email" = git config user.email
    "credential.helper" = git config credential.helper
}

foreach ($key in $gitConfig.Keys) {
    $value = $gitConfig[$key]
    if ($value) {
        Write-Host "✅ $key : $value" -ForegroundColor Green
    } else {
        Write-Host "⚠️  $key : non configuré" -ForegroundColor Yellow
    }
}

# Vérifier les credentials stockés
Write-Host "Credentials Windows:" -ForegroundColor Yellow
try {
    $creds = cmdkey /list | Select-String "git.ecole-89.com"
    if ($creds) {
        Write-Host "✅ Credentials Windows trouvés" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Aucun credential Windows trouvé" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Impossible de vérifier les credentials Windows" -ForegroundColor Yellow
}

# Test d'authentification
Write-Section "🔐 Test d'authentification"
Write-Host "Test de ls-remote (peut demander des identifiants)..." -ForegroundColor Yellow
$authTest = Test-GitCommand "git ls-remote $SCHOOL_REPO HEAD"
if ($authTest.Success) {
    Write-Host "✅ Authentification réussie" -ForegroundColor Green
    Write-Host "HEAD distant: $($authTest.Output)" -ForegroundColor Cyan
} else {
    Write-Host "❌ Échec authentification" -ForegroundColor Red
    Write-Host "Erreur: $($authTest.Error)" -ForegroundColor Red
}

# Gestion des branches
Write-Section "🌿 Gestion des branches"
Write-Host "Branches locales:" -ForegroundColor Yellow
git branch -v | ForEach-Object {
    Write-Host "  $_" -ForegroundColor Cyan
}

Write-Host "Branches distantes:" -ForegroundColor Yellow
git branch -r | ForEach-Object {
    Write-Host "  $_" -ForegroundColor Cyan
}

# Préparation de la branche deployment
Write-Host "Préparation de la branche deployment..." -ForegroundColor Yellow
$currentBranch = git branch --show-current

# Sauvegarder l'état actuel si nécessaire
$hasChanges = (git status --porcelain).Count -gt 0
if ($hasChanges) {
    Write-Host "⚠️  Changements détectés, création d'un stash..." -ForegroundColor Yellow
    git stash push -m "Auto-stash avant diagnostic deployment"
}

# Créer/basculer sur deployment
git checkout -B deployment origin/main
Write-Host "✅ Branche deployment créée/mise à jour" -ForegroundColor Green

# Historique récent
Write-Host "Historique récent (3 derniers commits):" -ForegroundColor Yellow
git log --oneline -3 | ForEach-Object {
    Write-Host "  $_" -ForegroundColor Cyan
}

# Test de push
Write-Section "🧪 Test de push"
Write-Host "Simulation de push vers $SCHOOL_REMOTE..." -ForegroundColor Yellow
$pushTest = Test-GitCommand "git push --dry-run $SCHOOL_REMOTE deployment:main"
if ($pushTest.Success) {
    Write-Host "✅ Test de push réussi" -ForegroundColor Green
    if ($pushTest.Output) {
        Write-Host "Détails: $($pushTest.Output)" -ForegroundColor Cyan
    }
} else {
    Write-Host "❌ Échec du test de push" -ForegroundColor Red
    Write-Host "Erreur: $($pushTest.Error)" -ForegroundColor Red
}

# Restaurer l'état initial
Write-Section "🔄 Restauration"
git checkout $currentBranch
Write-Host "✅ Retour sur la branche $currentBranch" -ForegroundColor Green

if ($hasChanges) {
    Write-Host "Restauration du stash..." -ForegroundColor Yellow
    git stash pop
    Write-Host "✅ Changements restaurés" -ForegroundColor Green
}

# Résumé final
Write-Section "📋 Résumé du diagnostic" "Magenta"
Write-Host "Script terminé. Vérifiez les éléments marqués ⚠️ ou ❌ ci-dessus." -ForegroundColor White
Write-Host "Pour un push réel, utilisez: git push $SCHOOL_REMOTE deployment:main" -ForegroundColor Yellow
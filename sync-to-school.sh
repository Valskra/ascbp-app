#!/bin/bash
set -e

SCHOOL_REPO="https://git.ecole-89.com/alexis.raccah/2024_grad.git"
SCHOOL_REMOTE="school"

echo "🔄 Synchronisation vers le dépôt école..."

# Vérifier si le remote école existe
if ! git remote get-url $SCHOOL_REMOTE >/dev/null 2>&1; then
    echo "➕ Ajout du remote école..."
    git remote add $SCHOOL_REMOTE $SCHOOL_REPO
fi

# Récupérer les dernières modifications
echo "📥 Récupération des modifications..."
git fetch origin

# Créer/basculer sur la branche de déploiement
echo "🌿 Préparation de la branche deployment..."
git checkout -B deployment origin/main

# Pousser vers l'école
echo "🚀 Push vers l'école..."
git push $SCHOOL_REMOTE deployment:main --force

echo "✅ Synchronisation terminée !"
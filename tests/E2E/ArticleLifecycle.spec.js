// tests/E2E/ArticleLifecycle.spec.js
import { test, expect } from '@playwright/test';
import { TestHelpers } from './helpers/test-helpers.js';

test.describe('Article Lifecycle', () => {
    let helpers;

    test.beforeEach(async ({ page }) => {
        helpers = new TestHelpers(page);
        await helpers.createTestUser();
    });

    test('complete article creation flow', async ({ page }) => {
        await helpers.loginAsUser();

        // 1. Accéder à la création d'article
        await page.goto('/articles/create');
        await expect(page.locator('h1')).toContainText('Créer un article');

        // 2. Remplir le formulaire
        await page.fill('[name="title"]', 'Mon Premier Article');
        await page.fill('[name="excerpt"]', 'Ceci est un extrait de mon article');

        // 3. Remplir l'éditeur de contenu
        await page.click('[data-testid="content-editor"]');
        await page.fill('[data-testid="content-editor"]', 'Contenu détaillé de mon article avec beaucoup de texte intéressant.');

        // 4. Upload d'une image mise en avant
        await helpers.uploadFile('[data-testid="featured-image"]', 'tests/fixtures/test-image.jpg');
        await page.waitForSelector('[data-testid="image-preview"]');

        // 5. Définir la date de publication
        await page.fill('[name="publish_date"]', '2025-07-01');

        // 6. Publier l'article
        await page.click('[data-testid="publish-button"]');

        // 7. Vérifier la redirection et le succès
        await expect(page).toHaveURL(/.*articles\/\d+/);
        await expect(page.locator('h1')).toContainText('Mon Premier Article');
        await expect(page.locator('.alert-success')).toContainText('Article publié');
    });

    test('article editing and updating', async ({ page }) => {
        // Créer un article existant
        const article = await page.request.post('/test/create-article', {
            data: {
                title: 'Article à Modifier',
                content: 'Contenu original',
                user_id: 1,
                status: 'published'
            }
        });
        const articleData = await article.json();

        await helpers.loginAsUser();
        await page.goto(`/articles/${articleData.id}/edit`);

        // 1. Modifier le titre
        await page.fill('[name="title"]', 'Article Modifié');

        // 2. Modifier le contenu
        await page.click('[data-testid="content-editor"]');
        await page.fill('[data-testid="content-editor"]', 'Contenu mis à jour avec de nouvelles informations.');

        // 3. Épingler l'article
        await page.check('[name="is_pinned"]');

        // 4. Sauvegarder
        await page.click('[data-testid="update-button"]');

        // 5. Vérifier les modifications
        await expect(page.locator('h1')).toContainText('Article Modifié');
        await expect(page.locator('[data-testid="pinned-badge"]')).toBeVisible();
        await expect(page.locator('.alert-success')).toContainText('Article mis à jour');
    });

    test('article commenting system', async ({ page }) => {
        // Créer un article et des utilisateurs
        const article = await page.request.post('/test/create-article', {
            data: { title: 'Article avec Commentaires', user_id: 1 }
        });
        const articleData = await article.json();

        const user2 = await helpers.createTestUser({ email: 'commenter@test.com' });

        await helpers.loginAsUser();
        await page.goto(`/articles/${articleData.id}`);

        // 1. Ajouter un commentaire
        await page.fill('[data-testid="comment-input"]', 'Excellent article, merci pour le partage !');
        await page.click('[data-testid="submit-comment"]');

        // 2. Vérifier l'affichage du commentaire
        await expect(page.locator('[data-testid="comment-1"]')).toContainText('Excellent article');
        await expect(page.locator('[data-testid="comment-author"]')).toContainText('Test User');

        // 3. Se connecter avec un autre utilisateur
        await page.click('[data-testid="logout"]');
        await helpers.loginAsUser('commenter@test.com');
        await page.goto(`/articles/${articleData.id}`);

        // 4. Répondre au commentaire
        await page.click('[data-testid="reply-to-comment-1"]');
        await page.fill('[data-testid="reply-input"]', 'Je suis d\'accord, très informatif !');
        await page.click('[data-testid="submit-reply"]');

        // 5. Vérifier la réponse
        await expect(page.locator('[data-testid="reply-1"]')).toContainText('Je suis d\'accord');

        // 6. Liker le commentaire original
        await page.click('[data-testid="like-comment-1"]');
        await expect(page.locator('[data-testid="comment-1-likes"]')).toContainText('1');
    });

    test('article like and interaction system', async ({ page }) => {
        const article = await page.request.post('/test/create-article', {
            data: { title: 'Article Populaire', user_id: 1 }
        });
        const articleData = await article.json();

        await helpers.loginAsUser();
        await page.goto(`/articles/${articleData.id}`);

        // 1. Liker l'article
        await page.click('[data-testid="like-article"]');
        await expect(page.locator('[data-testid="like-count"]')).toContainText('1');
        await expect(page.locator('[data-testid="like-article"]')).toHaveClass(/liked/);

        // 2. Unliker l'article
        await page.click('[data-testid="like-article"]');
        await expect(page.locator('[data-testid="like-count"]')).toContainText('0');
        await expect(page.locator('[data-testid="like-article"]')).not.toHaveClass(/liked/);

        // 3. Vérifier le compteur de vues
        const initialViews = await page.locator('[data-testid="view-count"]').textContent();
        await page.reload();
        const newViews = await page.locator('[data-testid="view-count"]').textContent();
        expect(parseInt(newViews)).toBeGreaterThan(parseInt(initialViews));
    });

    test('article permissions and access control', async ({ page }) => {
        // Créer un article par un utilisateur
        const user1 = await helpers.createTestUser({ email: 'author@test.com' });
        const user2 = await helpers.createTestUser({ email: 'reader@test.com' });

        const article = await page.request.post('/test/create-article', {
            data: { title: 'Article Privé', user_id: user1.id }
        });
        const articleData = await article.json();

        // 1. L'auteur peut modifier
        await helpers.loginAsUser('author@test.com');
        await page.goto(`/articles/${articleData.id}`);
        await expect(page.locator('[data-testid="edit-article"]')).toBeVisible();
        await expect(page.locator('[data-testid="delete-article"]')).toBeVisible();

        // 2. Un autre utilisateur ne peut pas modifier
        await page.click('[data-testid="logout"]');
        await helpers.loginAsUser('reader@test.com');
        await page.goto(`/articles/${articleData.id}`);
        await expect(page.locator('[data-testid="edit-article"]')).not.toBeVisible();
        await expect(page.locator('[data-testid="delete-article"]')).not.toBeVisible();

        // 3. Tentative d'accès direct à l'édition
        await page.goto(`/articles/${articleData.id}/edit`);
        await expect(page).toHaveURL(/.*403/);
    });

    test('article deletion workflow', async ({ page }) => {
        const article = await page.request.post('/test/create-article', {
            data: { title: 'Article à Supprimer', user_id: 1 }
        });
        const articleData = await article.json();

        await helpers.loginAsUser();
        await page.goto(`/articles/${articleData.id}`);

        // 1. Cliquer sur supprimer
        await page.click('[data-testid="delete-article"]');

        // 2. Confirmer dans la modal
        await expect(page.locator('[data-testid="delete-modal"]')).toBeVisible();
        await page.fill('[data-testid="delete-confirmation"]', 'SUPPRIMER');
        await page.click('[data-testid="confirm-delete"]');

        // 3. Vérifier la redirection et le message
        await expect(page).toHaveURL(/.*articles/);
        await expect(page.locator('.alert-success')).toContainText('Article supprimé');

        // 4. Vérifier que l'article n'est plus accessible
        await page.goto(`/articles/${articleData.id}`);
        await expect(page).toHaveURL(/.*404/);
    });
});
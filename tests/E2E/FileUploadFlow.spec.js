// tests/E2E/FileUploadFlow.spec.js
import { test, expect } from '@playwright/test';
import { TestHelpers } from './helpers/test-helpers.js';

test.describe('File Upload Flow', () => {
    let helpers;

    test.beforeEach(async ({ page }) => {
        helpers = new TestHelpers(page);
        await helpers.createTestUser();
    });

    test('profile picture upload and management', async ({ page }) => {
        await helpers.loginAsUser();
        await page.goto('/profile');

        // 1. Upload d'une photo de profil
        await helpers.uploadFile('[data-testid="profile-picture-input"]', 'tests/fixtures/profile.jpg');

        // 2. Attendre le traitement et vérifier l'aperçu
        await page.waitForSelector('[data-testid="profile-picture-preview"]');
        await expect(page.locator('[data-testid="profile-picture-preview"]')).toBeVisible();

        // 3. Sauvegarder
        await page.click('[data-testid="save-profile-picture"]');
        await helpers.waitForNotification('Photo de profil mise à jour');

        // 4. Vérifier l'affichage sur le profil
        await page.reload();
        await expect(page.locator('[data-testid="current-profile-picture"]')).toBeVisible();

        // 5. Supprimer la photo
        await page.click('[data-testid="delete-profile-picture"]');
        await page.click('[data-testid="confirm-delete-picture"]');
        await helpers.waitForNotification('Photo supprimée');
    });

    test('certificate upload via link', async ({ page }) => {
        await helpers.loginAsUser();

        // 1. Générer un lien d'upload
        await page.goto('/certificats');
        await page.click('[data-testid="generate-upload-link"]');

        // 2. Récupérer le lien généré
        const linkElement = await page.locator('[data-testid="upload-link"]');
        const uploadLink = await linkElement.getAttribute('href');
        expect(uploadLink).toContain('/u/');

        // 3. Utiliser le lien (simulation d'usage externe)
        await page.goto(uploadLink);
        await expect(page.locator('h1')).toContainText('Upload de certificat');

        // 4. Upload d'un certificat
        await helpers.uploadFile('[data-testid="certificate-file"]', 'tests/fixtures/certificate.pdf');
        await page.fill('[data-testid="certificate-title"]', 'Certificat médical 2025');
        await page.fill('[data-testid="expiry-date"]', '2025-12-31');
        await page.click('[data-testid="upload-certificate"]');

        // 5. Vérifier le succès
        await expect(page).toHaveURL(/.*upload-success/);
        await expect(page.locator('.success-message')).toContainText('Certificat uploadé');
    });

    test('event image upload during creation', async ({ page }) => {
        await page.request.post('/test/make-user-animator', { data: { user_id: 1 } });
        await helpers.loginAsUser();

        await page.goto('/events/create');

        // 1. Remplir les informations de base
        await page.fill('[name="title"]', 'Événement avec Image');
        await page.fill('[name="description"]', 'Description de l\'événement');
        await page.fill('[name="start_date"]', '2025-08-01T10:00');
        await page.fill('[name="end_date"]', '2025-08-01T18:00');

        // 2. Upload d'une image d'illustration
        await helpers.uploadFile('[data-testid="event-image"]', 'tests/fixtures/event.jpg');
        await page.waitForSelector('[data-testid="image-preview"]');

        // 3. Compléter et sauvegarder
        await page.fill('[name="max_participants"]', '20');
        await page.click('[data-testid="create-event"]');

        // 4. Vérifier l'événement créé avec image
        await expect(page).toHaveURL(/.*events\/\d+/);
        await expect(page.locator('[data-testid="event-image"]')).toBeVisible();
    });

    test('file validation and error handling', async ({ page }) => {
        await helpers.loginAsUser();
        await page.goto('/certificats');

        // 1. Tenter d'uploader un fichier trop volumineux
        await helpers.uploadFile('[data-testid="certificate-file"]', 'tests/fixtures/large-file.pdf');
        await page.click('[data-testid="upload-certificate"]');
        await expect(page.locator('.error-message')).toContainText('fichier trop volumineux');

        // 2. Tenter un format non supporté
        await helpers.uploadFile('[data-testid="certificate-file"]', 'tests/fixtures/document.txt');
        await page.click('[data-testid="upload-certificate"]');
        await expect(page.locator('.error-message')).toContainText('format non supporté');

        // 3. Upload sans fichier
        await page.click('[data-testid="upload-certificate"]');
        await expect(page.locator('.error-message')).toContainText('fichier requis');
    });

    test('multiple file upload in article', async ({ page }) => {
        await helpers.loginAsUser();
        await page.goto('/articles/create');

        // 1. Upload de plusieurs images
        await helpers.uploadFile('[data-testid="media-files"]', [
            'tests/fixtures/image1.jpg',
            'tests/fixtures/image2.jpg',
            'tests/fixtures/document.pdf'
        ]);

        // 2. Vérifier l'aperçu des fichiers
        await expect(page.locator('[data-testid="media-preview"]')).toHaveCount(3);
        await expect(page.locator('[data-testid="media-item-image1"]')).toBeVisible();
        await expect(page.locator('[data-testid="media-item-document"]')).toBeVisible();

        // 3. Supprimer un fichier de l'aperçu
        await page.click('[data-testid="remove-media-image2"]');
        await expect(page.locator('[data-testid="media-preview"]')).toHaveCount(2);

        // 4. Publier l'article avec les médias
        await page.fill('[name="title"]', 'Article avec Médias');
        await page.fill('[data-testid="content-editor"]', 'Contenu avec fichiers attachés');
        await page.click('[data-testid="publish-button"]');

        // 5. Vérifier l'affichage des médias dans l'article
        await expect(page.locator('[data-testid="article-media"]')).toBeVisible();
        await expect(page.locator('[data-testid="media-gallery"]')).toHaveCount(2);
    });
});
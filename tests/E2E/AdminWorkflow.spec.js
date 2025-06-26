// tests/E2E/AdminWorkflow.spec.js
import { test, expect } from '@playwright/test';
import { TestHelpers } from './helpers/test-helpers.js';

test.describe('Admin Workflow', () => {
    let helpers;

    test.beforeEach(async ({ page }) => {
        helpers = new TestHelpers(page);
        await page.request.post('/test/create-admin-user');
    });

    test('admin dashboard access and navigation', async ({ page }) => {
        await helpers.loginAsAdmin();

        // 1. Vérifier l'accès au dashboard admin
        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('text=Administration')).toBeVisible();

        // 2. Naviguer vers la gestion des utilisateurs
        await page.click('text=Gestion des utilisateurs');
        await expect(page).toHaveURL(/.*admin\/users/);
        await expect(page.locator('h1')).toContainText('Gestion des utilisateurs');

        // 3. Naviguer vers la gestion des événements
        await page.click('[href="/admin/events"]');
        await expect(page).toHaveURL(/.*admin\/events/);
        await expect(page.locator('h1')).toContainText('Gestion des événements');
    });

    test('user management workflow', async ({ page }) => {
        // Créer des utilisateurs de test
        await helpers.createTestUser({ email: 'user1@test.com', firstname: 'User1' });
        await helpers.createTestUser({ email: 'user2@test.com', firstname: 'User2' });

        await helpers.loginAsAdmin();
        await page.goto('/admin/users');

        // 1. Vérifier la liste des utilisateurs
        await expect(page.locator('text=User1')).toBeVisible();
        await expect(page.locator('text=User2')).toBeVisible();

        // 2. Filtrer les utilisateurs
        await page.fill('[data-testid="user-search"]', 'User1');
        await page.click('[data-testid="search-button"]');
        await expect(page.locator('text=User1')).toBeVisible();
        await expect(page.locator('text=User2')).not.toBeVisible();

        // 3. Exporter les utilisateurs
        await page.click('[data-testid="export-users"]');
        // Vérifier que le téléchargement commence
        const downloadPromise = page.waitForEvent('download');
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toContain('.xlsx');
    });

    test('event management by admin', async ({ page }) => {
        const event = await helpers.createTestEvent({
            title: 'Event Admin Test',
            max_participants: 5
        });

        await helpers.loginAsAdmin();
        await page.goto('/admin/events');

        // 1. Voir l'événement dans la liste
        await expect(page.locator('text=Event Admin Test')).toBeVisible();

        // 2. Accéder aux participants
        await page.click(`[data-testid="event-${event.id}-participants"]`);
        await expect(page).toHaveURL(new RegExp(`admin/events/${event.id}/participants`));

        // 3. Créer des inscriptions de test
        const user1 = await helpers.createTestUser({ email: 'participant1@test.com' });
        const user2 = await helpers.createTestUser({ email: 'participant2@test.com' });

        await page.request.post(`/test/register-user-to-event`, {
            data: { user_id: user1.id, event_id: event.id }
        });
        await page.request.post(`/test/register-user-to-event`, {
            data: { user_id: user2.id, event_id: event.id }
        });

        // 4. Recharger et vérifier les participants
        await page.reload();
        await expect(page.locator(`text=${user1.firstname}`)).toBeVisible();
        await expect(page.locator(`text=${user2.firstname}`)).toBeVisible();

        // 5. Désinscrire un participant
        await page.click(`[data-testid="unregister-${user1.id}"]`);
        await page.click('[data-testid="confirm-unregister"]');
        await helpers.waitForNotification('Participant désinscrit');
        await expect(page.locator(`text=${user1.firstname}`)).not.toBeVisible();

        // 6. Exporter la liste des participants
        await page.click('[data-testid="export-participants"]');
        const downloadPromise = page.waitForEvent('download');
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toContain('.xlsx');
    });

    test('certificate validation workflow', async ({ page }) => {
        // Créer un utilisateur avec un certificat à valider
        const user = await helpers.createTestUser();
        await page.request.post('/test/upload-certificate', {
            data: { user_id: user.id, filename: 'certificate.pdf' }
        });

        await helpers.loginAsAdmin();
        await page.goto('/admin/certificates'); // Supposant cette route

        // 1. Voir les certificats en attente
        await expect(page.locator('[data-testid="pending-certificates"]')).toBeVisible();
        await expect(page.locator(`text=${user.firstname}`)).toBeVisible();

        // 2. Valider un certificat
        await page.click(`[data-testid="validate-certificate-${user.id}"]`);
        await page.fill('[data-testid="expiry-date"]', '2025-12-31');
        await page.click('[data-testid="confirm-validate"]');

        // 3. Vérifier la validation
        await helpers.waitForNotification('Certificat validé');
        await expect(page.locator(`[data-testid="certificate-${user.id}"]`)).toHaveClass(/validated/);
    });

    test('prevents non-admin access', async ({ page }) => {
        await helpers.createTestUser(); // utilisateur normal
        await helpers.loginAsUser();

        // Tentative d'accès à l'admin
        await page.goto('/admin/users');
        await expect(page).toHaveURL(/.*403/); // Page d'erreur
        await expect(page.locator('text=403')).toBeVisible();
    });
});
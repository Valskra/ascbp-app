// tests/E2E/EventRegistrationFlow.spec.js
import { test, expect } from '@playwright/test';

test.describe('Event Registration Flow', () => {
    test.beforeEach(async ({ page }) => {
        // Préparer la base de données avec des données de test
        await page.request.post('/test/setup', {
            data: {
                create_test_user: true,
                create_test_event: true
            }
        });
    });

    test('complete event registration journey', async ({ page }) => {
        // 1. Aller sur la page d'accueil
        await page.goto('/');
        await expect(page).toHaveTitle(/ASCBP/);

        // 2. Se connecter
        await page.click('text=Se connecter');
        await page.fill('[name="email"]', 'test@example.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');

        // Vérifier redirection vers dashboard
        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('h1')).toContainText('Tableau de bord');

        // 3. Naviguer vers les événements
        await page.click('text=Événements');
        await expect(page).toHaveURL(/.*events/);

        // Vérifier que la liste des événements s'affiche
        await expect(page.locator('[data-testid="event-card"]')).toBeVisible();

        // 4. Sélectionner un événement
        await page.click('[data-testid="event-card"]:first-child');
        await expect(page.locator('h1')).toContainText('Tournoi de Tennis');

        // Vérifier les détails de l'événement
        await expect(page.locator('[data-testid="event-description"]')).toBeVisible();
        await expect(page.locator('[data-testid="event-organizer"]')).toBeVisible();
        await expect(page.locator('[data-testid="participants-count"]')).toBeVisible();

        // 5. Cliquer sur "S'inscrire"
        await page.click('[data-testid="register-button"]');
        await expect(page).toHaveURL(/.*registration/);

        // 6. Remplir le formulaire d'inscription
        await page.fill('[name="firstname"]', 'Jean');
        await page.fill('[name="lastname"]', 'Dupont');
        await page.fill('[name="email"]', 'jean.dupont@example.com');
        await page.fill('[name="phone"]', '0123456789');
        await page.fill('[name="birth_date"]', '1990-01-01');

        // 7. Soumettre l'inscription
        await page.click('button[type="submit"]');

        // 8. Vérifier le succès de l'inscription
        await expect(page).toHaveURL(/.*events\/\d+/);
        await expect(page.locator('.alert-success')).toContainText('Inscription réussie');
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Inscrit');

        // 9. Vérifier que le bouton de désinscription est présent
        await expect(page.locator('[data-testid="unregister-button"]')).toBeVisible();
    });

    test('prevents duplicate registration', async ({ page }) => {
        // Se connecter et aller sur un événement où l'utilisateur est déjà inscrit
        await page.goto('/login');
        await page.fill('[name="email"]', 'registered@example.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');

        await page.goto('/events/1');

        // Vérifier que le statut "Inscrit" est affiché
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Inscrit');

        // Vérifier que le bouton d'inscription n'est pas présent
        await expect(page.locator('[data-testid="register-button"]')).not.toBeVisible();
    });

    test('handles full event registration', async ({ page }) => {
        await page.goto('/login');
        await page.fill('[name="email"]', 'test@example.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');

        // Aller sur un événement complet
        await page.goto('/events/2'); // Événement configuré comme complet

        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Complet');
        await expect(page.locator('[data-testid="register-button"]')).not.toBeVisible();
    });

    test('requires membership for members-only events', async ({ page }) => {
        await page.goto('/login');
        await page.fill('[name="email"]', 'nonmember@example.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');

        // Aller sur un événement réservé aux membres
        await page.goto('/events/3');

        await page.click('[data-testid="register-button"]');

        // Devrait être redirigé vers la page d'adhésion
        await expect(page).toHaveURL(/.*membership/);
        await expect(page.locator('.alert-error')).toContainText('réservé aux adhérents');
    });

    test('unregistration flow works correctly', async ({ page }) => {
        // Se connecter avec un utilisateur déjà inscrit
        await page.goto('/login');
        await page.fill('[name="email"]', 'registered@example.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');

        await page.goto('/events/1');

        // Vérifier l'état inscrit
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Inscrit');

        // Cliquer sur se désinscrire
        await page.click('[data-testid="unregister-button"]');

        // Confirmer dans la modal
        await page.click('[data-testid="confirm-unregister"]');

        // Vérifier la désinscription
        await expect(page.locator('.alert-success')).toContainText('Désinscription réussie');
        await expect(page.locator('[data-testid="register-button"]')).toBeVisible();
        await expect(page.locator('[data-testid="unregister-button"]')).not.toBeVisible();
    });

    test('validates registration form correctly', async ({ page }) => {
        await page.goto('/login');
        await page.fill('[name="email"]', 'test@example.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');

        await page.goto('/events/1');
        await page.click('[data-testid="register-button"]');

        // Soumettre le formulaire vide
        await page.click('button[type="submit"]');

        // Vérifier les messages d'erreur
        await expect(page.locator('.error-firstname')).toContainText('requis');
        await expect(page.locator('.error-lastname')).toContainText('requis');
        await expect(page.locator('.error-email')).toContainText('requis');
        await expect(page.locator('.error-phone')).toContainText('requis');
        await expect(page.locator('.error-birth_date')).toContainText('requis');

        // Remplir avec des données invalides
        await page.fill('[name="email"]', 'email-invalide');
        await page.fill('[name="birth_date"]', '2030-01-01'); // Date future
        await page.click('button[type="submit"]');

        await expect(page.locator('.error-email')).toContainText('valide');
        await expect(page.locator('.error-birth_date')).toContainText('passée');
    });
});
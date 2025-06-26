// tests/E2E/AuthenticationFlow.spec.js
import { test, expect } from '@playwright/test';
import { TestHelpers } from './helpers/test-helpers.js';

test.describe('Authentication Flow', () => {
    let helpers;

    test.beforeEach(async ({ page }) => {
        helpers = new TestHelpers(page);
        await page.request.post('/test/reset-auth-data');
    });

    test('complete user registration flow', async ({ page }) => {
        // 1. Accéder à la page d'inscription
        await page.goto('/register');
        await expect(page).toHaveTitle(/ASCBP/);

        // 2. Remplir le formulaire d'inscription
        await page.fill('[name="firstname"]', 'Jean');
        await page.fill('[name="lastname"]', 'Dupont');
        await page.fill('[name="email"]', 'jean.dupont@test.com');
        await page.fill('[name="password"]', 'password123');
        await page.fill('[name="password_confirmation"]', 'password123');
        await page.fill('[name="birth_date"]', '1990-05-15');
        await page.fill('[name="phone"]', '0123456789');

        // 3. Soumettre l'inscription
        await page.click('button[type="submit"]');

        // 4. Vérifier redirection vers vérification email
        await expect(page).toHaveURL(/.*verify-email/);
        await expect(page.locator('h1')).toContainText('Vérifiez votre email');

        // 5. Simuler la vérification d'email
        const user = await helpers.createTestUser({ email: 'jean.dupont@test.com' });
        await page.request.post(`/test/verify-email/${user.id}`);

        // 6. Se connecter après vérification
        await page.goto('/login');
        await page.fill('[name="email"]', 'jean.dupont@test.com');
        await page.fill('[name="password"]', 'password123');
        await page.click('button[type="submit"]');

        // 7. Vérifier l'accès au dashboard
        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('h1')).toContainText('Espace Personnel');
        await expect(page.locator('text=Jean Dupont')).toBeVisible();
    });

    test('login and logout flow', async ({ page }) => {
        await helpers.createTestUser();

        // 1. Se connecter
        await helpers.loginAsUser();
        await expect(page).toHaveURL(/.*dashboard/);

        // 2. Vérifier les éléments du dashboard
        await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
        await expect(page.locator('text=Espace Personnel')).toBeVisible();

        // 3. Se déconnecter
        await page.click('[data-testid="user-menu"]');
        await page.click('text=Déconnexion');

        // 4. Vérifier redirection vers accueil
        await expect(page).toHaveURL('/');
        await expect(page.locator('text=Se connecter')).toBeVisible();
    });

    test('password reset flow', async ({ page }) => {
        await helpers.createTestUser({ email: 'reset@test.com' });

        // 1. Accéder à la page de reset
        await page.goto('/forgot-password');
        await page.fill('[name="email"]', 'reset@test.com');
        await page.click('button[type="submit"]');

        // 2. Vérifier le message de confirmation
        await expect(page.locator('.alert-success')).toContainText('lien de réinitialisation');

        // 3. Simuler le clic sur le lien de reset
        const resetToken = await page.request.post('/test/generate-reset-token', {
            data: { email: 'reset@test.com' }
        });
        const token = (await resetToken.json()).token;

        // 4. Accéder à la page de nouveau mot de passe
        await page.goto(`/reset-password/${token}?email=reset@test.com`);
        await page.fill('[name="password"]', 'newpassword123');
        await page.fill('[name="password_confirmation"]', 'newpassword123');
        await page.click('button[type="submit"]');

        // 5. Vérifier la connexion automatique
        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('.alert-success')).toContainText('Mot de passe modifié');
    });

    test('prevents access with invalid credentials', async ({ page }) => {
        await page.goto('/login');
        await page.fill('[name="email"]', 'invalid@test.com');
        await page.fill('[name="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        await expect(page.locator('.error-message')).toContainText('identifiants incorrects');
        await expect(page).toHaveURL(/.*login/);
    });

    test('validates registration form', async ({ page }) => {
        await page.goto('/register');
        await page.click('button[type="submit"]');

        // Vérifier les messages d'erreur
        await expect(page.locator('.error-firstname')).toContainText('requis');
        await expect(page.locator('.error-email')).toContainText('requis');
        await expect(page.locator('.error-password')).toContainText('requis');

        // Tester email invalide
        await page.fill('[name="email"]', 'email-invalide');
        await page.click('button[type="submit"]');
        await expect(page.locator('.error-email')).toContainText('format valide');
    });
});
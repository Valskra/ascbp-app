// tests/E2E/PaymentFlow.spec.js
import { test, expect } from '@playwright/test';
import { TestHelpers } from './helpers/test-helpers.js';

test.describe('Payment Flow', () => {
    let helpers;

    test.beforeEach(async ({ page }) => {
        helpers = new TestHelpers(page);
        await helpers.createTestUser();
        await page.request.post('/test/setup-stripe-test-mode');
    });

    test('successful event registration with payment', async ({ page }) => {
        // Créer un événement payant
        const event = await helpers.createTestEvent({
            title: 'Formation Payante',
            price: '25€',
            max_participants: 10
        });

        await helpers.loginAsUser();
        await page.goto(`/events/${event.id}`);

        // 1. Cliquer sur s'inscrire
        await page.click('[data-testid="register-button"]');
        await expect(page).toHaveURL(/.*registration/);

        // 2. Vérifier l'affichage du prix
        await expect(page.locator('[data-testid="event-price"]')).toContainText('25€');

        // 3. Remplir les informations
        await page.fill('[name="firstname"]', 'Jean');
        await page.fill('[name="lastname"]', 'Dupont');
        await page.fill('[name="email"]', 'jean@test.com');
        await page.fill('[name="phone"]', '0123456789');

        // 4. Procéder au paiement
        await page.click('[data-testid="proceed-to-payment"]');

        // 5. Attendre la redirection vers Stripe
        await page.waitForURL('**/checkout.stripe.com/**', { timeout: 10000 });
        await expect(page.locator('text=Formation Payante')).toBeVisible();

        // 6. Remplir les informations de carte de test
        await page.fill('[data-testid="cardNumber"]', '4242424242424242');
        await page.fill('[data-testid="cardExpiry"]', '12/25');
        await page.fill('[data-testid="cardCvc"]', '123');
        await page.fill('[data-testid="billingName"]', 'Jean Dupont');

        // 7. Confirmer le paiement
        await page.click('[data-testid="submit-payment"]');

        // 8. Vérifier la redirection de succès
        await page.waitForURL('**/events/**/registration/success', { timeout: 15000 });
        await expect(page.locator('.success-message')).toContainText('Paiement réussi');
        await expect(page.locator('[data-testid="registration-confirmed"]')).toBeVisible();

        // 9. Vérifier l'inscription dans l'événement
        await page.goto(`/events/${event.id}`);
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Inscrit');
    });

    test('failed payment handling and recovery', async ({ page }) => {
        const event = await helpers.createTestEvent({
            title: 'Event Paiement Échoué',
            price: '30€'
        });

        await helpers.loginAsUser();
        await page.goto(`/events/${event.id}/register`);

        // 1. Remplir l'inscription
        await page.fill('[name="firstname"]', 'Test');
        await page.fill('[name="lastname"]', 'User');
        await page.fill('[name="email"]', 'test@test.com');
        await page.fill('[name="phone"]', '0123456789');

        await page.click('[data-testid="proceed-to-payment"]');
        await page.waitForURL('**/checkout.stripe.com/**');

        // 2. Utiliser une carte qui échoue
        await page.fill('[data-testid="cardNumber"]', '4000000000000002'); // Carte déclinée
        await page.fill('[data-testid="cardExpiry"]', '12/25');
        await page.fill('[data-testid="cardCvc"]', '123');
        await page.fill('[data-testid="billingName"]', 'Test User');

        await page.click('[data-testid="submit-payment"]');

        // 3. Vérifier l'erreur de paiement
        await expect(page.locator('.error-message')).toContainText('carte déclinée');

        // 4. Réessayer avec une carte valide
        await page.fill('[data-testid="cardNumber"]', '4242424242424242');
        await page.click('[data-testid="submit-payment"]');

        // 5. Vérifier le succès du second essai
        await page.waitForURL('**/events/**/registration/success');
        await expect(page.locator('.success-message')).toContainText('Paiement réussi');
    });

    test('membership payment flow', async ({ page }) => {
        await helpers.loginAsUser();
        await page.goto('/membership/create');

        // 1. Sélectionner le type d'adhésion
        await page.click('[data-testid="membership-annual"]');
        await expect(page.locator('[data-testid="price-display"]')).toContainText('45€');

        // 2. Remplir les informations personnelles
        await page.fill('[name="address"]', '123 Rue de la Paix');
        await page.fill('[name="city"]', 'Paris');
        await page.fill('[name="postal_code"]', '75001');

        // 3. Procéder au paiement
        await page.click('[data-testid="pay-membership"]');
        await page.waitForURL('**/checkout.stripe.com/**');

        // 4. Compléter le paiement
        await page.fill('[data-testid="cardNumber"]', '4242424242424242');
        await page.fill('[data-testid="cardExpiry"]', '12/25');
        await page.fill('[data-testid="cardCvc"]', '123');
        await page.fill('[data-testid="billingName"]', 'Test User');
        await page.click('[data-testid="submit-payment"]');

        // 5. Vérifier l'activation de l'adhésion
        await page.waitForURL('**/membership/success');
        await expect(page.locator('.success-message')).toContainText('Adhésion activée');

        // 6. Vérifier le statut sur le dashboard
        await page.goto('/dashboard');
        await expect(page.locator('[data-testid="membership-status"]')).toContainText('Actif');
    });

    test('payment security and validation', async ({ page }) => {
        const event = await helpers.createTestEvent({ price: '20€' });
        await helpers.loginAsUser();

        // 1. Tentative de contournement du paiement
        await page.goto(`/events/${event.id}/registration/success`);
        await expect(page).toHaveURL(/.*403/); // Accès refusé

        // 2. Vérifier que l'inscription n'a pas eu lieu
        await page.goto(`/events/${event.id}`);
        await expect(page.locator('[data-testid="register-button"]')).toBeVisible();
        await expect(page.locator('[data-testid="registration-status"]')).not.toContainText('Inscrit');

        // 3. Vérifier la protection CSRF
        const response = await page.request.post(`/events/${event.id}/register`, {
            data: { /* données sans token CSRF */ }
        });
        expect(response.status()).toBe(419); // Token CSRF manquant
    });

    test('refund and cancellation flow', async ({ page }) => {
        // Créer une inscription payée
        const event = await helpers.createTestEvent({ price: '25€' });
        const user = await helpers.createTestUser();

        await page.request.post('/test/create-paid-registration', {
            data: {
                user_id: user.id,
                event_id: event.id,
                payment_intent: 'pi_test_123456'
            }
        });

        await helpers.loginAsUser();
        await page.goto(`/events/${event.id}`);

        // 1. Vérifier l'état inscrit
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Inscrit');

        // 2. Demander l'annulation
        await page.click('[data-testid="cancel-registration"]');
        await expect(page.locator('[data-testid="cancellation-modal"]')).toBeVisible();

        // 3. Confirmer l'annulation
        await page.fill('[data-testid="cancellation-reason"]', 'Empêchement de dernière minute');
        await page.click('[data-testid="confirm-cancellation"]');

        // 4. Vérifier le traitement
        await helpers.waitForNotification('Demande d\'annulation envoyée');
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Annulation demandée');

        // 5. Simuler l'approbation admin du remboursement
        await page.request.post('/test/approve-refund', {
            data: { user_id: user.id, event_id: event.id }
        });

        // 6. Vérifier le remboursement
        await page.reload();
        await expect(page.locator('[data-testid="registration-status"]')).toContainText('Remboursé');
        await expect(page.locator('[data-testid="register-button"]')).toBeVisible();
    });
});
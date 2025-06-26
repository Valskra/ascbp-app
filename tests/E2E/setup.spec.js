// tests/E2E/setup.spec.js
import { test as setup, expect } from '@playwright/test';

setup('prepare test database', async ({ request }) => {
    // Reset et seed la base de données de test
    await request.post('/test/reset-database');
    await request.post('/test/seed-test-data');

    // Vérifier que la base est prête
    const response = await request.get('/test/health-check');
    expect(response.ok()).toBeTruthy();
});
javascript// tests/E2E/helpers/test-helpers.js
export class TestHelpers {
    constructor(page) {
        this.page = page;
    }

    async loginAsUser(email = 'test@example.com', password = 'password') {
        await this.page.goto('/login');
        await this.page.fill('[name="email"]', email);
        await this.page.fill('[name="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForURL('**/dashboard');
    }

    async loginAsAdmin() {
        await this.loginAsUser('admin@example.com', 'password');
        await this.page.waitForSelector('[data-testid="admin-dashboard"]');
    }

    async createTestUser(userData = {}) {
        const response = await this.page.request.post('/test/create-user', {
            data: {
                firstname: 'Test',
                lastname: 'User',
                email: 'test@example.com',
                password: 'password',
                ...userData
            }
        });
        return response.json();
    }

    async createTestEvent(eventData = {}) {
        const response = await this.page.request.post('/test/create-event', {
            data: {
                title: 'Test Event',
                category: 'competition',
                start_date: new Date(Date.now() + 86400000).toISOString(),
                max_participants: 10,
                ...eventData
            }
        });
        return response.json();
    }

    async waitForNotification(message) {
        await this.page.waitForSelector(`text=${message}`, { timeout: 5000 });
    }

    async uploadFile(selector, filePath) {
        await this.page.setInputFiles(selector, filePath);
    }
}
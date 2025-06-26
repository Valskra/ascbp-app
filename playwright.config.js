import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/E2E',
    fullyParallel: false, // Séquentiel pour éviter les conflits de DB
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 1,
    workers: process.env.CI ? 1 : 1,
    reporter: [
        ['html'],
        ['json', { outputFile: 'test-results.json' }],
        ['junit', { outputFile: 'test-results.xml' }]
    ],
    timeout: 30000,
    expect: {
        timeout: 10000,
    },

    use: {
        baseURL: process.env.APP_URL || 'http://localhost:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        actionTimeout: 10000,
        navigationTimeout: 15000,
    },

    projects: [
        {
            name: 'setup',
            testMatch: '**/setup.spec.js',
        },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
            dependencies: ['setup'],
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 5'] },
            dependencies: ['setup'],
        },
    ],

    webServer: {
        command: 'php artisan serve --env=testing',
        url: 'http://localhost:8000',
        reuseExistingServer: !process.env.CI,
        timeout: 30000,
    },
});
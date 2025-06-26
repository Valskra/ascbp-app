// vite.config.js - Configuration mise à jour avec tests
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { resolve } from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'public/fonts',
                    dest: 'fonts',
                },
            ],
        }),
    ],
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./tests/Frontend/setup.js'],
        include: [
            'tests/Frontend/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
            'tests/Frontend/**/*Test.js'
        ],
        exclude: [
            'node_modules',
            'dist',
            '.idea',
            '.git',
            '.cache'
        ],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html'],
            include: ['resources/js/**/*.{vue,js}'],
            exclude: [
                'resources/js/app.js',
                'resources/js/bootstrap.js',
                '**/*.config.js'
            ]
        },
        deps: {
            inline: ['@vue', '@vueuse', 'vue-router']
        }
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
            '~': resolve(__dirname, './resources'),
        },
    },
})

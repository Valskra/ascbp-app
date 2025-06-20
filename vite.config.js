import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { defineConfig } from 'vitest/config'
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
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
            '~': resolve(__dirname, './resources'),
        },
    },
})
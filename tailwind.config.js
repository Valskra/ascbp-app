import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Scandia', ...defaultTheme.fontFamily.sans],
                scandia: ['Scandia', ...defaultTheme.fontFamily.sans],
                garet: ['Garet', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'spin-reverse': 'spin-reverse .7s linear infinite',
            },
            keyframes: {
                'spin-reverse': {
                    from: {
                        transform: 'rotate(360deg)',
                    },
                    to: {
                        transform: 'rotate(0deg)',
                    },
                },
            },
        },
    },

    plugins: [forms],
};


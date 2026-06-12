import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                bible: {
                    black: '#0f0f0f',
                    dark: '#1a1a1a',
                    gold: '#c9a227',
                    'gold-light': '#e8d48b',
                    cream: '#f5f0e8',
                    green: '#1a5c38',
                    'green-light': '#2d7a52',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Georgia', 'Cambria', 'Times New Roman', 'serif'],
            },
        },
    },

    plugins: [forms],
};

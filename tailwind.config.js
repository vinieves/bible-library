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
                    black: '#0a0a0a',
                    dark: '#141414',
                    'dark-elevated': '#1c1c1c',
                    gold: '#c9a227',
                    'gold-light': '#e8d48b',
                    cream: '#f5f0e8',
                    green: '#1a5c38',
                    'green-light': '#2d7a52',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Playfair Display', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
            },
            backgroundImage: {
                'public-mesh': 'radial-gradient(ellipse 80% 50% at 50% -20%, rgba(201, 162, 39, 0.15), transparent), radial-gradient(ellipse 60% 40% at 100% 100%, rgba(26, 92, 56, 0.12), transparent)',
            },
        },
    },

    plugins: [forms],
};

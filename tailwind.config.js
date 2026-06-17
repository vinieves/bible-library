import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Enums/**/*.php',
    ],

    safelist: [
        'badge-tone-gold',
        'badge-tone-green',
        'badge-tone-emerald',
        'badge-tone-blue',
        'badge-tone-purple',
        'badge-tone-rose',
        'badge-tone-amber',
        'badge-tone-muted',
        'icon-thumb-gold',
        'icon-thumb-green',
        'icon-thumb-emerald',
        'icon-thumb-blue',
        'icon-thumb-purple',
        'icon-thumb-rose',
        'icon-thumb-amber',
    ],

    theme: {
        extend: {
            colors: {
                bible: {
                    black: '#0a0907',
                    dark: '#14110a',
                    'dark-solid': '#1c1711',
                    'dark-elevated': '#231c14',
                    'card': 'rgba(28, 23, 17, 0.72)',
                    gold: '#f0c75e',
                    'gold-light': '#f5dfa0',
                    cream: '#f5f0e8',
                    muted: '#9a9588',
                    'muted-warm': '#c9b98a',
                    green: '#1a5c38',
                    'green-light': '#2d7a52',
                },
                member: {
                    paper: '#F2EDE4',
                    card: '#FBF8F2',
                    input: '#F6F1E8',
                    gold: '#B08D4F',
                    'gold-dark': '#A07E45',
                    'gold-light': '#C9A86A',
                    title: '#4A3B2A',
                    body: '#6B5D4A',
                    placeholder: '#9B9182',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Playfair Display', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
            },
            backgroundImage: {
                'public-mesh': 'radial-gradient(ellipse 80% 50% at 50% -20%, rgba(201, 162, 39, 0.15), transparent), radial-gradient(ellipse 60% 40% at 100% 100%, rgba(26, 92, 56, 0.12), transparent)',
                'members-divine': 'radial-gradient(120% 90% at 50% -10%, #3d3018 0%, #1a1610 38%, #0f0d09 72%, #0a0907 100%)',
                'member-paper': 'linear-gradient(180deg, #FBF8F2 0%, #F2EDE4 100%)',
            },
        },
    },

    plugins: [forms],
};

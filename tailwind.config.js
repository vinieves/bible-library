import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Tailwind color helper: enables `bg-brown/10`-style opacity modifiers
 *  while sourcing the actual RGB from resources/css/tokens.css. */
function withOpacity(channelVar) {
    return ({ opacityValue }) =>
        opacityValue === undefined
            ? `rgb(var(${channelVar}))`
            : `rgb(var(${channelVar}) / ${opacityValue})`;
}

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
                ink: withOpacity('--ink-channel'),
                'brown-deep': withOpacity('--brown-deep-channel'),
                brown: withOpacity('--brown-channel'),
                gold: withOpacity('--gold-channel'),
                tan: withOpacity('--tan-channel'),
                caramel: withOpacity('--caramel-channel'),
                beige: withOpacity('--beige-channel'),
                cream: withOpacity('--cream-channel'),
                paper: withOpacity('--paper-channel'),
                line: withOpacity('--line-channel'),
                muted: withOpacity('--muted-channel'),
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Playfair Display', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
                // Fonte dedicada da home pública (resources/views/public/home.blade.php) — não afeta o resto do app.
                display: ['Lora', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
                ui: ['DM Sans', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                DEFAULT: '0 1px 3px 0 rgb(var(--ink-channel) / 0.1), 0 1px 2px -1px rgb(var(--ink-channel) / 0.1)',
                sm: '0 1px 2px 0 rgb(var(--ink-channel) / 0.05)',
                md: '0 4px 6px -1px rgb(var(--ink-channel) / 0.1), 0 2px 4px -2px rgb(var(--ink-channel) / 0.1)',
                lg: '0 10px 15px -3px rgb(var(--ink-channel) / 0.1), 0 4px 6px -4px rgb(var(--ink-channel) / 0.1)',
                xl: '0 20px 25px -5px rgb(var(--ink-channel) / 0.1), 0 8px 10px -6px rgb(var(--ink-channel) / 0.1)',
                '2xl': '0 25px 50px -12px rgb(var(--ink-channel) / 0.25)',
                inner: 'inset 0 2px 4px 0 rgb(var(--ink-channel) / 0.05)',
            },
        },
    },

    plugins: [forms],
};

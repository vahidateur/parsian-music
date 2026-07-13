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
            fontFamily: {
                sans: ['Vazirmatn', ...defaultTheme.fontFamily.sans],
                fa: ['Vazirmatn', 'system-ui', '-apple-system'],
                playfair: ['"Playfair Display"', 'serif'],
            },
            colors: {
                'p-bg': '#0E1018',
                'p-hero': '#11131B',
                'p-gold': '#D5AF58',
                'p-gold-light': '#F4D28B',
                'p-text-muted': '#CFC7B2',
            },
            backdropBlur: {
                'sm': '4px',
                '3xl': '28px',
            },
            opacity: {
                '04': '0.04',
                '08': '0.08',
            },
            keyframes: {
                cardFadeUp: {
                    '0%':   { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                glowPulse: {
                    '0%, 100%': { opacity: '0.4' },
                    '50%':      { opacity: '0.7' },
                },
            },
            animation: {
                'card-fade-up': 'cardFadeUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) both',
                'glow-pulse': 'glowPulse 2s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};

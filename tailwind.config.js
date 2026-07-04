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
            },
            backdropBlur: {
                'sm': '4px',    // Subtle blur for glass effects
            },
            opacity: {
                '04': '0.04',   // Very light transparency
                '08': '0.08',
            },
        },
    },

    plugins: [forms],
};

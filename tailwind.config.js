import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './app/Providers/Filament/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#edfaf8',
                    100: '#d0f3ee',
                    200: '#a5e8df',
                    300: '#6dd6ca',
                    400: '#36bcb0',
                    500: '#0F9D8A',
                    600: '#0c8373',
                    700: '#0a6a5d',
                    800: '#09524a',
                    900: '#07403a',
                    950: '#042e29',
                },
                navy: {
                    50:  '#eef2f9',
                    100: '#d5e0f0',
                    200: '#aec3e2',
                    300: '#7e9fd0',
                    400: '#527cbd',
                    500: '#1E4D8C',
                    600: '#194176',
                    700: '#14345f',
                    800: '#0f2849',
                    900: '#0a1d35',
                    950: '#061222',
                },
            },
        },
    },

    plugins: [forms],
};

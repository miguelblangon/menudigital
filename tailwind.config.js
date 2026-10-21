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
                // Figtree: panel de administración (Breeze).
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Inter: carta pública.
                body: ['Inter', ...defaultTheme.fontFamily.sans],
                // Playfair Display: titulares de la carta.
                display: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
        },

    },

    plugins: [forms],
};

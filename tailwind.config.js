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
                sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#F5F5DC',
                    100: '#efedca',
                    200: '#dfd99f',
                    300: '#cfc174',
                    400: '#b7a45d',
                    500: '#8A9A5B',
                    600: '#71804b',
                    700: '#59653b',
                    800: '#434d30',
                    900: '#36454F',
                },
                sage: {
                    DEFAULT: '#8A9A5B',
                    50: '#f1f3e8',
                    100: '#e0e5ce',
                    200: '#c6d0a7',
                    300: '#acba80',
                    400: '#99aa68',
                    500: '#8A9A5B',
                    600: '#6f7c49',
                    700: '#58623a',
                    800: '#434b2d',
                    900: '#2f3521',
                },
                terracotta: {
                    DEFAULT: '#E2725B',
                    50: '#fdf1ee',
                    100: '#f9ddd6',
                    200: '#f2b9aa',
                    300: '#eb957f',
                    400: '#E2725B',
                    500: '#d75e45',
                    600: '#b74834',
                    700: '#91392c',
                    800: '#6f2f27',
                    900: '#4d231f',
                },
                oat: {
                    DEFAULT: '#F5F5DC',
                    50: '#fffef4',
                    100: '#F5F5DC',
                    200: '#e8e5bf',
                    300: '#d6cf98',
                    400: '#c0b36f',
                    500: '#a89751',
                    600: '#89773e',
                    700: '#6b5b32',
                    800: '#504429',
                    900: '#362f20',
                },
                charcoal: {
                    DEFAULT: '#36454F',
                    50: '#eef1f2',
                    100: '#d9dee2',
                    200: '#b6c0c7',
                    300: '#91a0a9',
                    400: '#6f808b',
                    500: '#536672',
                    600: '#41525e',
                    700: '#36454F',
                    800: '#2a363e',
                    900: '#1d262c',
                },
            },
            boxShadow: {
                soft: '0 4px 24px -4px rgba(226, 114, 91, 0.16)',
                card: '0 2px 12px -2px rgba(15, 23, 42, 0.06)',
            },
        },
    },

    plugins: [forms],
};

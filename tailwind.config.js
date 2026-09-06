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
                // Sparing tertiary accent — muted gold, used only for Sale badges,
                // star ratings, and rare one-off highlights ("Cream + Terracotta +
                // Gold" direction).
                gold: {
                    DEFAULT: '#C9A227',
                    50: '#fbf6e7',
                    100: '#f5eac4',
                    200: '#ead590',
                    300: '#debf5d',
                    400: '#d3ac3f',
                    500: '#C9A227',
                    600: '#a6841f',
                    700: '#82661a',
                    800: '#5e4a14',
                    900: '#3d2f0d',
                },
                // Secondary accent — unchanged.
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
                // Primary accent — hex bug fixed: true brand hex is now correctly
                // at 500 (previously 500 was an off-shade, #d75e45, one step away
                // from the real brand color #E2725B stored at 400).
                terracotta: {
                    DEFAULT: '#E2725B',
                    50: '#fdf3f0',
                    100: '#fbe6e0',
                    200: '#f5c7ba',
                    300: '#eea890',
                    400: '#e68f70',
                    500: '#E2725B',
                    600: '#c15a44',
                    700: '#9c4635',
                    800: '#78372a',
                    900: '#4d231f',
                },
                // Warm-neutral surface/background family — lightened base to
                // match the new warm-white (#FBF7F2), was cream #F5F5DC.
                oat: {
                    DEFAULT: '#FBF7F2',
                    50: '#fefcfa',
                    100: '#FBF7F2',
                    200: '#f1e7dc',
                    300: '#e4d2bf',
                    400: '#d3b896',
                    500: '#bc9b72',
                    600: '#9b7d57',
                    700: '#786047',
                    800: '#564636',
                    900: '#362e24',
                },
                // Headings/ink — kept for headings only; body/meta text uses
                // Tailwind's stock slate scale instead (see app.css/views).
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

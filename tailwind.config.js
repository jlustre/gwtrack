import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
        './app/**/*.php',
        './config/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Greatway Financial brand (logo). Hex values (not CSS vars)
                // so Tailwind opacity modifiers like bg-gw-primary/60 work.
                gw: {
                    primary: {
                        DEFAULT: '#2D3E50',
                        hover: '#465565',
                        dark: '#263544',
                        soft: '#F2F3F4',
                        muted: '#E6E8EA',
                    },
                    accent: {
                        DEFAULT: '#84BB41',
                        hover: '#93C358',
                        dark: '#74A539',
                        soft: '#F5FAF0',
                        muted: '#EDF5E2',
                    },
                    neutral: {
                        DEFAULT: '#687480',
                        soft: '#F3F4F5',
                    },
                    surface: '#FFFFFF',
                    'on-primary': '#FFFFFF',
                    'on-accent': '#2D3E50',
                },
            },
        },
    },

    safelist: [
        'z-[200]',
        'z-[210]',
    ],

    plugins: [forms],
};

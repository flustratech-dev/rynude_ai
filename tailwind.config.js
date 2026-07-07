import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                'claude-response': ['"Anthropic Serif"', 'Georgia', '"Arial"', 'Helvetica', 'sans-serif'],
                serif: ['Tiêm', 'Iowan Old Style', 'Apple Garamond', 'Baskerville', 'Times New Roman', 'Droid Serif', 'Times', 'Source Serif Pro', 'serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'],
            },
            colors: {
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                // Claude specific exact colors
                'claude-bg-light': '#FDFCFB',
                'claude-bg-dark': '#121212',
                'claude-sidebar-dark': '#0A0A0A',
                'claude-border-light': '#E5E5E5',
                'claude-border-dark': '#3A3A38',
                'claude-accent': '#DE7356', // Typical terracotta accent
                primary: {
                    DEFAULT: 'hsl(var(--primary))',
                    foreground: 'hsl(var(--primary-foreground))',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted))',
                    foreground: 'hsl(var(--muted-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
                // Keep legacy claude-* scale for backward compat
                claude: {
                    '50': '#fafaf8',
                    '100': '#f5f5f0',
                    '200': '#e8e8e3',
                    '300': '#d1d1cc',
                    '400': '#a8a8a3',
                    '500': '#7a7a75',
                    '600': '#525250',
                    '700': '#3A3A38',
                    '800': '#2C2C2A',
                    '900': '#232321',
                },
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
        },
    },

    plugins: [
        forms,
        require('@tailwindcss/typography'),
    ],
};

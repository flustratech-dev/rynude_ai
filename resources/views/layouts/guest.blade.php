<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-[#FFFDF9] dark:bg-[#121212]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#FFFDF9">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            (function() {
                const theme = localStorage.getItem('theme') || 'system';
                const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
                document.addEventListener('DOMContentLoaded', function() {
                    const meta = document.querySelector('meta[name="theme-color"]');
                    if (meta) meta.content = isDark ? '#121212' : '#FFFDF9';
                });
            })();
        </script>

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Manifest & Setup -->
        <link rel="manifest" href="/manifest.json">
        <script>
            window.deferredPrompt = null;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                window.deferredPrompt = e;
            });
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#2D2825] antialiased bg-[#FFFDF9] dark:bg-[#121212] dark:text-stone-200">
        @if(request()->routeIs('login') || request()->routeIs('register'))
            {{ $slot }}
        @else
            <div class="min-h-screen flex flex-col items-center justify-center bg-[#F9F8F6] dark:bg-[#121212]">
                <div class="mb-8">
                    <a href="/" class="flex items-center gap-2 text-[#2D2825] dark:text-stone-200">
                    </a>
                </div>

                <div class="w-full max-w-md px-6">
                    <div class="bg-white dark:bg-[#1C1C1C] rounded-2xl shadow-sm dark:shadow-none border border-[#E5E5E5] dark:border-stone-700 p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        @endif

        <x-alert-dialog />

        <script>
            window.showAlert = function(message, type, title) {
                if (!type) type = 'info';
                if (!title) {
                    switch(type) {
                        case 'error': title = 'Error'; break;
                        case 'success': title = 'Success'; break;
                        case 'warning': title = 'Warning'; break;
                        default: title = 'Info';
                    }
                }
                return new Promise(function(resolve) {
                    window._alertResolve = resolve;
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: { message: message, type: type, title: title }
                    }));
                });
            };
            window.showConfirm = function(message) {
                return new Promise(function(resolve) {
                    window._alertResolve = resolve;
                    window.dispatchEvent(new CustomEvent('show-confirm', {
                        detail: { message: message, title: 'Confirm', type: 'warning' }
                    }));
                });
            };
        </script>
    </body>
</html>

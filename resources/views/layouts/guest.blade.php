<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#2D2825] antialiased bg-[#F9F8F6]">
        @if(request()->routeIs('login') || request()->routeIs('register'))
            {{ $slot }}
        @else
            <div class="min-h-screen flex flex-col items-center justify-center bg-[#F9F8F6]">
                <div class="mb-8">
                    <a href="/" class="flex items-center gap-2 text-[#2D2825]">
                    </a>
                </div>

                <div class="w-full max-w-md px-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-[#E5E5E5] p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        @endif

    </body>
</html>

<x-guest-layout>
    <div class="min-h-screen bg-[#FFFDF9] dark:bg-[#121212] flex flex-col font-claude-response text-[#2D2825] dark:text-stone-200">
        <!-- Header -->
        <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 flex items-center justify-between px-6 py-4 lg:px-10 lg:py-4 w-full bg-[#FFFDF9]/80 dark:bg-[#121212]/80 backdrop-blur-md border-b border-gray-100 dark:border-stone-800/50 transition-all duration-300">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo_rynudee.png') }}" alt="Rynude Logo" class="h-8 w-auto object-contain">
                <span class="font-claude-response text-[22px] font-medium tracking-tight">rynude</span>
            </div>

            <!-- Desktop Right Navigation (Links + Buttons) -->
            <div class="hidden lg:flex items-center gap-10 ml-auto mr-4">
                <!-- Desktop Menu with Premium Hover Dropdowns -->
                <div class="flex items-center gap-6 text-[14px] font-medium text-gray-700 dark:text-stone-300">
                    <!-- Dropdown: Meet rynude -->
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button class="flex items-center gap-1 hover:text-black dark:hover:text-white transition-colors focus:outline-none py-2">
                            <span>Meet rynude</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute left-0 mt-1 w-64 rounded-2xl bg-[#FFFDF9] dark:bg-[#1E1E1E] border border-stone-200/50 dark:border-stone-800 shadow-xl py-2.5 z-50"
                             style="display: none;">
                            <button @click="const el = document.querySelector('#hero-tagline'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#hero-tagline'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Satu Antarmuka</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Semua AI terbaik dunia dalam satu tempat</span>
                            </button>
                            <button @click="const el = document.querySelector('#marquee-provider'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#marquee-provider'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Integrasi Provider</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Terhubung ke Claude, GPT, Ollama Lokal, dll.</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown: Fitur -->
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button class="flex items-center gap-1 hover:text-black dark:hover:text-white transition-colors focus:outline-none py-2">
                            <span>Fitur</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute left-0 mt-1 w-64 rounded-2xl bg-[#FFFDF9] dark:bg-[#1E1E1E] border border-stone-200/50 dark:border-stone-800 shadow-xl py-2.5 z-50"
                             style="display: none;">
                            <button @click="const el = document.querySelector('#fitur'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#fitur'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Mengapa Rynude AI?</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Streaming Real-time, Artifacts, RTK, dll.</span>
                            </button>
                            <button @click="const el = document.querySelector('#local-engine'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#local-engine'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Rynude Local Engine</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">GGUF lokal offline & privat tanpa kuota</span>
                            </button>
                            <button @click="const el = document.querySelector('#mengapa-beralih'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#mengapa-beralih'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Rynude vs Konvensional</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Tabel perbandingan keuntungan beralih</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown: Harga -->
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button class="flex items-center gap-1 hover:text-black dark:hover:text-white transition-colors focus:outline-none py-2">
                            <span>Harga</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute left-0 mt-1 w-64 rounded-2xl bg-[#FFFDF9] dark:bg-[#1E1E1E] border border-stone-200/50 dark:border-stone-800 shadow-xl py-2.5 z-50"
                             style="display: none;">
                            <button @click="const el = document.querySelector('#harga'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#harga'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Paket Gratis (Free)</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Akses model open-source dasar selamanya</span>
                            </button>
                            <button @click="const el = document.querySelector('#harga'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#harga'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Paket Premium (Pro)</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Model AI unggulan dengan limit tinggi</span>
                            </button>
                            <button @click="const el = document.querySelector('#harga'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#harga'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Paket Max (Unlimited)</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Akses tak terbatas ke semua AI terbaik dunia</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown: Instalasi -->
                    <div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
                        <button class="flex items-center gap-1 hover:text-black dark:hover:text-white transition-colors focus:outline-none py-2">
                            <span>Instalasi</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute left-0 mt-1 w-64 rounded-2xl bg-[#FFFDF9] dark:bg-[#1E1E1E] border border-stone-200/50 dark:border-stone-800 shadow-xl py-2.5 z-50"
                             style="display: none;">
                            <button @click="const el = document.querySelector('#step-1'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#step-1'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Langkah 1: Pasang Package</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Salin & jalankan satu perintah NPM CLI</span>
                            </button>
                            <button @click="const el = document.querySelector('#step-2'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#step-2'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Langkah 2: Konfigurasi Engine</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Installer cerdas menyiapkan semua dependensi</span>
                            </button>
                            <button @click="const el = document.querySelector('#step-3'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); } else { window.location.href = '{{ route('login') }}#step-3'; }" 
                                    class="w-full text-left px-4 py-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors flex flex-col gap-0.5 mt-1">
                                <span class="font-semibold text-[13px] text-stone-800 dark:text-stone-100">Langkah 3: Jalankan Engine</span>
                                <span class="text-[11px] text-gray-500 dark:text-stone-500">Nyalakan server & mulai chatting offline</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop Buttons -->
                <div class="flex items-center gap-4">
                    <a href="https://github.com/flustratech-dev/rynude_ai" target="_blank" rel="noopener noreferrer" class="text-[14px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-xl transition-colors flex items-center gap-1.5" title="GitHub Repository">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
                        </svg>
                        <span>GitHub</span>
                    </a>
                    <a href="{{ route('login') }}" class="text-[14px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white px-4 py-2 border border-gray-300 dark:border-stone-600 rounded-xl transition-colors">Masuk</a>
                    <a href="{{ route('register') }}" class="text-[14px] font-medium text-gray-900 hover:text-black px-4 py-2 border border-gray-300 rounded-xl bg-white transition-colors">Register</a>
                </div>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="lg:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white focus:outline-none p-1">
                    <svg x-show="!mobileMenuOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="mobileMenuOpen" style="display: none;" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 @click.away="mobileMenuOpen = false"
                 class="absolute top-full left-0 right-0 bg-[#FFFDF9] dark:bg-[#1C1C1C] border-b border-gray-200 dark:border-stone-700 shadow-lg lg:hidden"
                 style="display: none;">
                <div class="px-6 py-5 flex flex-col gap-4">
                    <a href="#" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Meet rynude</a>
                    <a href="#fitur" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Fitur</a>
                    <a href="#harga" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Harga</a>
                    <a href="#instalasi" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Instalasi</a>
                    <div class="h-px bg-gray-200 dark:bg-stone-700 my-2"></div>
                    <a href="https://github.com/flustratech-dev/rynude_ai" target="_blank" rel="noopener noreferrer" class="text-[15px] font-medium text-center text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white px-4 py-3 border border-gray-300 dark:border-stone-600 rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
                        </svg>
                        <span>GitHub</span>
                    </a>
                    <a href="{{ route('login') }}" class="text-[15px] font-medium text-center text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white px-4 py-3 border border-gray-300 dark:border-stone-600 rounded-xl transition-colors">Masuk</a>
                    <a href="{{ route('register') }}" class="text-[15px] font-medium text-center text-white bg-[#1C1A19] hover:bg-black dark:hover:bg-[#3A3A38] px-4 py-3 rounded-xl transition-colors">Register</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col lg:flex-row w-full max-w-[1500px] mx-auto px-6 lg:px-12 gap-12 lg:gap-16 pb-10">
            <!-- Left Side -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center pt-6 lg:pt-0">
                <div class="text-center mb-4 max-w-lg">
                    <h1 class="font-claude-response text-[36px] lg:text-[44px] leading-[1.1] text-[#2D2825] dark:text-stone-100 mb-2 tracking-tight">
                        Meet rynude Design
                    </h1>
                    <p class="text-[16px] text-gray-600 dark:text-stone-400">
                        Prototypes, slides, and websites, built with your design system.
                    </p>
                </div>

                <!-- Login Card -->
                <div class="w-full max-w-[400px] bg-gradient-to-b from-[#E2EEFF] to-[#F0F5FF] dark:from-[#1E293B] dark:to-[#0F172A] rounded-[32px] border border-[#C2D6FF] dark:border-blue-900/50 overflow-hidden relative shadow-sm dark:shadow-none">
                    <!-- Banner -->
                    <div class="pt-4 pb-5 px-4 text-center flex items-center justify-center gap-2">
                        <span class="bg-[#C6DCFF] dark:bg-blue-900/50 text-[#1D4ED8] dark:text-blue-300 text-[12px] font-semibold px-2.5 py-0.5 rounded-md">New</span>
                        <span class="text-[14px] text-[#2563EB] dark:text-blue-300 font-medium">rynude Design: available for Max plans</span>
                    </div>

                    <div class="bg-[#FFFDF9] dark:bg-[#1C1C1C] rounded-t-[32px] p-6 border-t border-[#E5E7EB] dark:border-stone-700 shadow-[0_-4px_10px_rgba(0,0,0,0.02)] dark:shadow-none flex flex-col">
                        <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 dark:border-stone-600 rounded-2xl hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors mb-4 shadow-sm bg-white dark:bg-[#323232]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92(3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200">Continue with Google</span>
                        </a>

                        <div class="text-center text-[12px] font-medium text-gray-500 dark:text-stone-500 uppercase tracking-wider mb-4">
                            OR
                        </div>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-4">
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" class="w-full px-4 py-3 rounded-2xl border border-gray-300 dark:border-stone-600 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-500 dark:placeholder-stone-500 focus:outline-none focus:border-[#2D2825] dark:focus:border-stone-400 focus:ring-1 focus:ring-[#2D2825] dark:focus:ring-stone-400 transition-all shadow-sm bg-white dark:bg-[#323232]" required autofocus>
                                @error('email') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="w-full py-3 bg-[#1C1A19] hover:bg-black dark:bg-stone-700 dark:hover:bg-stone-600 text-white rounded-2xl text-[16px] font-semibold transition-colors shadow-md border border-transparent dark:border-stone-600">
                                Continue with email
                            </button>
                        </form>

                        <p class="text-[13px] text-gray-500 dark:text-stone-400 text-center mt-4">
                            By continuing, you acknowledge rynude's <a href="#" class="underline hover:text-gray-700 dark:hover:text-stone-300">Privacy Policy</a>.
                        </p>

                    </div>
                </div>

                <div class="mt-6" x-data="{}">
                    <button type="button" 
                            @click="if (window.deferredPrompt) { window.deferredPrompt.prompt(); window.deferredPrompt.userChoice.then((choiceResult) => { if (choiceResult.outcome === 'accepted') { window.deferredPrompt = null; } }) } else { alert('Gagal memicu instalasi otomatis. Pastikan Anda menggunakan Chrome/Edge, mengakses lewat Localhost/HTTPS, atau mungkin aplikasi sudah terinstal.') }"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-stone-600 rounded-xl hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors bg-white dark:bg-[#323232] shadow-sm text-sm font-medium text-gray-700 dark:text-stone-300">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Download desktop app
                    </button>
                </div>
            </div>

            <!-- Right Side (Video Container) -->
            <div class="w-full lg:w-1/2 flex items-start justify-center p-4 pt-10 lg:pt-4">
                <div class="w-full max-w-[760px] aspect-[4/5] bg-[#0D0F12] rounded-[1.5rem] overflow-hidden relative shadow-2xl border border-gray-800 dark:border-stone-600 flex items-center justify-center group lg:sticky lg:top-24">

                    <!-- Video placeholder (User will change src here) -->
                    <!-- Upload your video file and set the source below -->
                    <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover opacity-90 transition-opacity duration-500 group-hover:opacity-100" id="hero-video">
                        <source src="{{ asset('video/model-launch-login-hero.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                </div>
            </div>
        </main>

        {{-- ═══════════════════════════════════════════════════════════════
             LANDING SECTIONS — Redesign Total (Bahasa Indonesia)
             Semua section di bawah Form Login dan Video
        ════════════════════════════════════════════════════════════════ --}}

        {{-- ── 1. HERO TAGLINE ──────────────────────────────────────── --}}
        <section id="hero-tagline" class="relative overflow-hidden bg-[#FFFDF9] dark:bg-[#121212] border-b border-gray-100 dark:border-stone-800">
            <div class="relative max-w-6xl mx-auto px-6 pt-24 pb-20 text-center">
                {{-- Badge kecil --}}
                <div class="lp-reveal inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white dark:bg-[#1C1C1A] border border-gray-200 dark:border-stone-800 text-[12px] font-medium text-stone-800 dark:text-stone-200 mb-8 shadow-sm lp-float">
                    <span class="w-1.5 h-1.5 rounded-full bg-stone-800 dark:bg-stone-200 inline-block"></span>
                    Open-source &middot; Gratis Selamanya &middot; Multi-Provider AI
                </div>

                {{-- Heading utama --}}
                <h2 class="lp-reveal lp-reveal-delay-1 font-claude-response text-[42px] md:text-[62px] lg:text-[72px] leading-[1.04] font-medium tracking-tight text-[#2D2825] dark:text-stone-50 max-w-4xl mx-auto">
                    Satu antarmuka untuk<br>
                    <span class="text-black dark:text-white font-semibold">semua AI terbaik dunia</span>
                </h2>

                {{-- Sub-heading --}}
                <p class="lp-reveal lp-reveal-delay-2 mt-6 text-[17px] md:text-[19px] text-gray-500 dark:text-stone-400 max-w-2xl mx-auto leading-relaxed">
                    Rynude AI adalah platform chat open-source yang menyatukan Claude, GPT, Gemini, Llama, dan ratusan model lainnya&mdash;dalam satu antarmuka elegan yang berjalan 100% di komputer Anda sendiri.
                </p>

                {{-- CTA Buttons --}}
                <div class="lp-reveal lp-reveal-delay-3 flex flex-col sm:flex-row items-center justify-center gap-3 mt-10">
                    <a href="{{ route('register') }}" id="lp-cta-primary"
                       class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-[#191919] hover:bg-[#000000] text-white rounded-2xl text-[15px] font-semibold transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5">
                        Mulai Gratis Sekarang
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="https://github.com/flustratech-dev/rynude_ai" target="_blank"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-white dark:bg-[#2a2a28] border border-gray-200 dark:border-stone-700 hover:border-gray-300 dark:hover:border-stone-600 rounded-2xl text-[15px] font-medium text-[#2D2825] dark:text-stone-200 transition-all shadow-sm hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 4.42 2.87 8.17 6.84 9.49.5.09.66-.22.66-.48l-.01-1.7c-2.78.6-3.37-1.34-3.37-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.89 1.52 2.34 1.08 2.91.83.09-.65.35-1.08.63-1.33-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.56 9.56 0 0112 6.8c.85.004 1.71.11 2.51.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.85l-.01 2.75c0 .27.16.58.67.48A10.01 10.01 0 0022 12c0-5.52-4.48-10-10-10z"/></svg>
                        Lihat di GitHub
                    </a>
                </div>

                {{-- Stats strip --}}
                <div class="lp-reveal lp-reveal-delay-4 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 mt-14 text-[13.5px] text-gray-500 dark:text-stone-400">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-[#2D2825] dark:text-stone-200 text-[15px]">9+</span>
                        Provider AI terdukung
                    </div>
                    <div class="w-px h-4 bg-gray-300 dark:bg-stone-700 hidden sm:block"></div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-[#2D2825] dark:text-stone-200 text-[15px]">100%</span>
                        Data di komputer Anda
                    </div>
                    <div class="w-px h-4 bg-gray-300 dark:bg-stone-700 hidden sm:block"></div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-[#2D2825] dark:text-stone-200 text-[15px]">Rp.0</span>
                        Biaya antarmuka
                    </div>
                    <div class="w-px h-4 bg-gray-300 dark:bg-stone-700 hidden sm:block"></div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-[#2D2825] dark:text-stone-200 text-[15px]">Tanpa Batas</span>
                        Pesan per hari
                    </div>
                </div>
            </div>
        </section>

        {{-- ── 2. MARQUEE PROVIDER ──────────────────────────────────── --}}
        <section id="marquee-provider" class="py-10 border-y border-gray-100 dark:border-stone-800 bg-white/50 dark:bg-[#1a1a18]/50 overflow-hidden">
            <p class="text-center text-[11.5px] font-medium uppercase tracking-widest text-gray-400 dark:text-stone-600 mb-6">Terhubung ke Provider AI Terkemuka</p>
            <div class="relative">
                <div class="lp-marquee-track items-center gap-8 px-6">
                    @php
                        $providers = [
                            ['name' => 'Anthropic Claude',    'color' => 'text-black dark:text-stone-300',  'icon' => 'M17.3041 3.541h-3.6718l6.696 16.918H24Zm-10.6082 0L0 20.459h3.7442l1.3693-3.5527h7.0052l1.3693 3.5528h3.7442L10.5363 3.5409Zm-.3712 10.2232 2.2914-5.9456 2.2914 5.9456Z'],
                            ['name' => 'OpenAI GPT',          'color' => 'text-emerald-600','icon' => 'M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A6.0651 6.0651 0 0 0 19.0192 19.818a5.9847 5.9847 0 0 0 3.9977-2.9 6.0462 6.0462 0 0 0-.735-7.0969zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.8956zm16.0993 3.8558L12.5973 8.3829V6.0505a.0804.0804 0 0 1 .0332-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66l-.142-.0852-4.7782-2.7582a.7758.7758 0 0 0-.7853 0zm2.0107-3.0231l-4.8002 2.7663a.7664.7664 0 0 0-.3879-.6765V7.4628l2.0201-1.1685a.0757.0757 0 0 1 .071 0l4.8303 2.7866a4.504 4.504 0 0 1-1.7335 8.0498v-5.4194zm-10.4907 4.249l-2.92-1.6841v-3.3687L10.01 9.6083l2.92 1.6841v3.3687l-2.92 1.684z'],
                            ['name' => 'Google Gemini',       'color' => 'text-blue-500',   'icon' => 'M11.04 19.32Q12 21.51 12 24q0-2.49.93-4.68.96-2.19 2.58-3.81t3.81-2.55Q21.51 12 24 12q-2.49 0-4.68-.93a12.3 12.3 0 0 1-3.81-2.58 12.3 12.3 0 0 1-2.58-3.81Q12 2.49 12 0q0 2.49-.96 4.68-.93 2.19-2.55 3.81a12.3 12.3 0 0 1-3.81 2.58Q2.49 12 0 12q2.49 0 4.68.96 2.19.93 3.81 2.55t2.55 3.81'],
                            ['name' => 'Hugging Face',        'color' => 'text-amber-500',  'icon' => 'M12.025 1.13c-5.77 0-10.449 4.647-10.449 10.378 0 1.112.178 2.181.503 3.185.064-.222.203-.444.416-.577a.96.96 0 0 1 .524-.15c.293 0 .584.124.84.284.278.173.48.408.71.694.226.282.458.611.684.951v-.014c.017-.324.106-.622.264-.874s.403-.487.762-.543c.3-.047.596.06.787.203s.31.313.4.467c.15.257.212.468.233.542.01.026.653 1.552 1.657 2.54.616.605 1.01 1.223 1.082 1.912.055.537-.096 1.059-.38 1.572.637.121 1.294.187 1.967.187.657 0 1.298-.063 1.921-.178-.287-.517-.44-1.041-.384-1.581.07-.69.465-1.307 1.081-1.913 1.004-.987 1.647-2.513 1.657-2.539.021-.074.083-.285.233-.542.09-.154.208-.323.4-.467a1.08 1.08 0 0 1 .787-.203c.359.056.604.29.762.543s.247.55.265.874v.015c.225-.34.457-.67.683-.952.23-.286.432-.52.71-.694.257-.16.547-.284.84-.285a.97.97 0 0 1 .524.151c.228.143.373.388.43.625l.006.04a10.3 10.3 0 0 0 .534-3.273c0-5.731-4.678-10.378-10.449-10.378M8.327 6.583a1.5 1.5 0 0 1 .713.174 1.487 1.487 0 0 1 .617 2.013c-.183.343-.762-.214-1.102-.094-.38.134-.532.914-.917.71a1.487 1.487 0 0 1 .69-2.803m7.486 0a1.487 1.487 0 0 1 .689 2.803c-.385.204-.536-.576-.916-.71-.34-.12-.92.437-1.103.094a1.487 1.487 0 0 1 .617-2.013 1.5 1.5 0 0 1 .713-.174m-10.68 1.55a.96.96 0 1 1 0 1.921.96.96 0 0 1 0-1.92m13.838 0a.96.96 0 1 1 0 1.92.96.96 0 0 1 0-1.92M8.489 11.458c.588.01 1.965 1.157 3.572 1.164 1.607-.007 2.984-1.155 3.572-1.164.196-.003.305.12.305.454 0 .886-.424 2.328-1.563 3.202-.22-.756-1.396-1.366-1.63-1.32q-.011.001-.02.006l-.044.026-.01.008-.03.024q-.018.017-.035.036l-.032.04a1 1 0 0 0-.058.09l-.014.025q-.049.088-.11.19a1 1 0 0 1-.083.116 1.2 1.2 0 0 1-.173.18q-.035.029-.075.058a1.3 1.3 0 0 1-.251-.243 1 1 0 0 1-.076-.107c-.124-.193-.177-.363-.337-.444-.034-.016-.104-.008-.2.022q-.094.03-.216.087-.06.028-.125.063l-.13.074q-.067.04-.136.086a3 3 0 0 0-.135.096 3 3 0 0 0-.26.219 2 2 0 0 0-.12.121 2 2 0 0 0-.106.128l-.002.002a2 2 0 0 0-.09.132l-.001.001a1.2 1.2 0 0 0-.105.212q-.013.036-.024.073c-1.139-.875-1.563-2.317-1.563-3.203 0-.334.109-.457.305-.454m.836 10.354c.824-1.19.766-2.082-.365-3.194-1.13-1.112-1.789-2.738-1.789-2.738s-.246-.945-.806-.858-.97 1.499.202 2.362c1.173.864-.233 1.45-.685.64-.45-.812-1.683-2.896-2.322-3.295s-1.089-.175-.938.647 2.822 2.813 2.562 3.244-1.176-.506-1.176-.506-2.866-2.567-3.49-1.898.473 1.23 2.037 2.16c1.564.932 1.686 1.178 1.464 1.53s-3.675-2.511-4-1.297c-.323 1.214 3.524 1.567-3.287 2.405-.238.839-2.71-1.587-3.216-.642-.506.946 3.49 2.056 3.522 2.064 1.29.33 4.568 1.028 5.713-.624m5.349 0c-.824-1.19-.766-2.082.365-3.194 1.13-1.112 1.789-2.738 1.789-2.738s.246-.945.806-.858.97 1.499-.202 2.362c-1.173.864.233 1.45.685.64.451-.812 1.683-2.896 2.322-3.295s1.089-.175.938.647-2.822 2.813-2.562 3.244 1.176-.506 1.176-.506 2.866-2.567 3.49-1.898-.473 1.23-2.037 2.16c-1.564.932-1.686 1.178-1.464 1.53s3.675-2.511 4-1.297c.323 1.214-3.524 1.567-3.287 2.405.238.839 2.71-1.587 3.216-.642.506.946-3.49 2.056-3.522 2.064-1.29.33-4.568 1.028-5.713-.624'],
                            ['name' => 'Ollama Lokal',        'color' => 'text-violet-600', 'icon' => 'M16.361 10.26a.894.894 0 0 0-.558.47l-.072.148.001.207c0 .193.004.217.059.353.076.193.152.312.291.448.24.238.51.3.872.205a.86.86 0 0 0 .517-.436.752.752 0 0 0 .08-.498c-.064-.453-.33-.782-.724-.897a1.06 1.06 0 0 0-.466 0zm-9.203.005c-.305.096-.533.32-.65.639a1.187 1.187 0 0 0-.06.52c.057.309.31.59.598.667.362.095.632.033.872-.205.14-.136.215-.255.291-.448.055-.136.059-.16.059-.353l.001-.207-.072-.148a.894.894 0 0 0-.565-.472 1.02 1.02 0 0 0-.474.007Zm4.184 2c-.131.071-.223.25-.195.383.031.143.157.288.353.407.105.063.112.072.117.136.004.038-.01.146-.029.243-.02.094-.036.194-.036.222.002.074.07.195.143.253.064.052.076.054.255.059.164.005.198.001.264-.03.169-.082.212-.234.15-.525-.052-.243-.042-.28.087-.355.137-.08.281-.219.324-.314a.365.365 0 0 0-.175-.48.394.394 0 0 0-.181-.033c-.126 0-.207.03-.355.124l-.085.053-.053-.032c-.219-.13-.259-.145-.391-.143a.396.396 0 0 0-.193.032zm.39-2.195c-.373.036-.475.05-.654.086-.291.06-.68.195-.951.328-.94.46-1.589 1.226-1.787 2.114-.04.176-.045.234-.045.53 0 .294.005.357.043.524.264 1.16 1.332 2.017 2.714 2.173.3.033 1.596.033 1.896 0 1.11-.125 2.064-.727 2.493-1.571.114-.226.169-.372.22-.602.039-.167.044-.23.044-.523 0-.297-.005-.355-.045-.531-.288-1.29-1.539-2.304-3.072-2.497a6.873 6.873 0 0 0-.855-.031zm.645.937a3.283 3.283 0 0 1 1.44.514c.223.148.537.458.671.662.166.251.26.508.303.82.02.143.01.251-.043.482-.08.345-.332.705-.672.957a3.115 3.115 0 0 1-.689.348c-.382.122-.632.144-1.525.138-.582-.006-.686-.01-.853-.042-.57-.107-1.022-.334-1.35-.68-.264-.28-.385-.535-.45-.946-.03-.192.025-.509.137-.776.136-.326.488-.73.836-.963.403-.269.934-.46 1.422-.512.187-.02.586-.02.773-.002zm-5.503-11a1.653 1.653 0 0 0-.683.298C5.617.74 5.173 1.666 4.985 2.819c-.07.436-.119 1.04-.119 1.503 0 .544.064 1.24.155 1.721.02.107.031.202.023.208a8.12 8.12 0 0 1-.187.152 5.324 5.324 0 0 0-.949 1.02 5.49 5.49 0 0 0-.94 2.339 6.625 6.625 0 0 0-.023 1.357c.091.78.325 1.438.727 2.04l.13.195-.037.064c-.269.452-.498 1.105-.605 1.732-.084.496-.095.629-.095 1.294 0 .67.009.803.088 1.266.095.555.288 1.143.503 1.534.071.128.243.393.264.407.007.003-.014.067-.046.141a7.405 7.405 0 0 0-.548 1.873c-.062.417-.071.552-.071.991 0 .56.031.832.148 1.279L3.42 24h1.478l-.05-.091c-.297-.552-.325-1.575-.068-2.597.117-.472.25-.819.498-1.296l.148-.29v-.177c0-.165-.003-.184-.057-.293a.915.915 0 0 0-.194-.25 1.74 1.74 0 0 1-.385-.543c-.424-.92-.506-2.286-.208-3.451.124-.486.329-.918.544-1.154a.787.787 0 0 0 .223-.531c0-.195-.07-.355-.224-.522a3.136 3.136 0 0 1-.817-1.729c-.14-.96.114-2.005.69-2.834.563-.814 1.353-1.336 2.237-1.475.199-.033.57-.028.776.01.226.04.367.028.512-.041.179-.085.268-.19.374-.431.093-.215.165-.333.36-.576.234-.29.46-.489.822-.729.413-.27.884-.467 1.352-.561.17-.035.25-.04.569-.04.319 0 .398.005.569.04a4.07 4.07 0 0 1 1.914.997c.117.109.398.457.488.602.034.057.095.177.132.267.105.241.195.346.374.43.14.068.286.082.503.045.343-.058.607-.053.943.016 1.144.23 2.14 1.173 2.581 2.437.385 1.108.276 2.267-.296 3.153-.097.15-.193.27-.333.419-.301.322-.301.722-.001 1.053.493.539.801 1.866.708 3.036-.062.772-.26 1.463-.533 1.854a2.096 2.096 0 0 1-.224.258.916.916 0 0 0-.194.25c-.054.109-.057.128-.057.293v.178l.148.29c.248.476.38.823.498 1.295.253 1.008.231 2.01-.059 2.581a.845.845 0 0 0-.044.098c0 .006.329.009.732.009h.73l.02-.074.036-.134c.019-.076.057-.3.088-.516.029-.217.029-1.016 0-1.258-.11-.875-.295-1.57-.597-2.226-.032-.074-.053-.138-.046-.141.008-.005.057-.074.108-.152.376-.569.607-1.284.724-2.228.031-.26.031-1.378 0-1.628-.083-.645-.182-1.082-.348-1.525a6.083 6.083 0 0 0-.329-.7l-.038-.064.131-.194c.402-.604.636-1.262.727-2.04a6.625 6.625 0 0 0-.024-1.358 5.512 5.512 0 0 0-.939-2.339 5.325 5.325 0 0 0-.95-1.02 8.097 8.097 0 0 1-.186-.152.692.692 0 0 1 .023-.208c.208-1.087.201-2.443-.017-3.503-.19-.924-.535-1.658-.98-2.082-.354-.338-.716-.482-1.15-.455-.996.059-1.8 1.205-2.116 3.01a6.805 6.805 0 0 0-.097.726c0 .036-.007.066-.015.066a.96.96 0 0 1-.149-.078A4.857 4.857 0 0 0 12 3.03c-.832 0-1.687.243-2.456.698a.958.958 0 0 1-.148.078c-.008 0-.015-.03-.015-.066a6.71 6.71 0 0 0-.097-.725C8.997 1.392 8.337.319 7.46.048a2.096 2.096 0 0 0-.585-.041Zm.293 1.402c.248.197.523.759.682 1.388.03.113.06.244.069.292.007.047.026.152.041.233.067.365.098.76.102 1.24l.002.475-.12.175-.118.178h-.278c-.324 0-.646.041-.954.124l-.238.06c-.033.007-.038-.003-.057-.144a8.438 8.438 0 0 1 .016-2.323c.124-.788.413-1.501.696-1.711.067-.05.079-.049.157.013zm9.825-.012c.17.126.358.46.498.888.28.854.36 2.028.212 3.145-.019.14-.024.151-.057.144l-.238-.06a3.693 3.693 0 0 0-.954-.124h-.278l-.119-.178-.119-.175.002-.474c.004-.669.066-1.19.214-1.772.157-.623.434-1.185.68-1.382.078-.062.09-.063.159-.012z'],
                            ['name' => 'GLM / Z.ai',          'color' => 'text-teal-600',   'icon' => 'M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z'],
                            ['name' => 'Kimi Moonshot',       'color' => 'text-indigo-500', 'icon' => 'M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z'],
                            ['name' => 'Qwen Alibaba',        'color' => 'text-stone-800 dark:text-stone-200', 'icon' => 'M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6z'],
                            ['name' => 'Rynude Local Engine', 'color' => 'text-black dark:text-stone-300',  'icon' => '', 'image' => 'images/logo_rynudee.png'],
                        ];
                        $doubled = array_merge($providers, $providers);
                    @endphp
                    @foreach($doubled as $p)
                        <div class="flex-shrink-0 flex items-center gap-3 px-5 py-3 bg-white dark:bg-[#232321] border border-gray-200 dark:border-stone-800 rounded-2xl shadow-sm hover:shadow-md transition-shadow cursor-default">
                            @if(!empty($p['image']))
                                <img src="{{ asset($p['image']) }}" class="w-4 h-4 object-contain" alt="{{ $p['name'] }}">
                            @else
                                <svg class="w-4 h-4 {{ $p['color'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="{{ $p['icon'] }}"/>
                                </svg>
                            @endif
                            <span class="text-[13px] font-medium text-[#2D2825] dark:text-stone-300 whitespace-nowrap">{{ $p['name'] }}</span>
                        </div>
                    @endforeach
                </div>
                {{-- fade edges --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 w-20 bg-gradient-to-r from-white dark:from-[#121212] to-transparent"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 w-20 bg-gradient-to-l from-white dark:from-[#121212] to-transparent"></div>
            </div>
        </section>

 {{-- ── 3. FITUR UNGGULAN ────────────────────────────────────── --}}
<section id="fitur" class="max-w-6xl mx-auto w-full px-6 py-24">
    <div class="text-center mb-16">
        <p class="lp-reveal text-[11.5px] uppercase tracking-widest font-medium text-black dark:text-white mb-3">Mengapa Rynude AI?</p>
        <h2 class="lp-reveal lp-reveal-delay-1 font-claude-response text-[36px] md:text-[44px] font-medium tracking-tight text-[#2D2825] dark:text-stone-50 leading-[1.1]">
            Fitur yang tidak akan Anda<br>temukan di tempat lain
        </h2>
        <p class="lp-reveal lp-reveal-delay-2 text-[15px] text-gray-500 dark:text-stone-400 mt-4 max-w-xl mx-auto leading-relaxed">
            Dirancang untuk developer, researcher, dan siapapun yang ingin pengalaman AI tanpa batas dan tanpa biaya tersembunyi.
        </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
            $fitur = [
                [
                    'index' => '01',
                    'judul' => 'Streaming Real-time (SSE)',
                    'desc'  => 'Teks AI mengalir seketika via Server-Sent Events. Tidak ada loading panjang—respons langsung muncul karakter per karakter layaknya mengetik.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>'
                ],
                [
                    'index' => '02',
                    'judul' => 'Artifacts Panel Cerdas',
                    'desc'  => 'Bukan sekadar chat biasa. Render source code, dokumen HTML, hingga komponen UI secara langsung layaknya IDE sungguhan—lengkap dengan versioning.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>'
                ],
                [
                    'index' => '03',
                    'judul' => 'RTK: Hemat Token hingga 50%',
                    'desc'  => 'Teknologi Response Token Kuration secara otomatis mengompresi output boilerplate sebelum dikirim ke LLM. Lebih hemat, lebih cepat, tanpa kehilangan konteks.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>'
                ],
                [
                    'index' => '04',
                    'judul' => 'Connect Account Tanpa API Key',
                    'desc'  => 'Punya akun claude.ai atau Gemini gratis? Sambungkan via Rynude Connector extension dan chat gratis sepenuhnya—tanpa API key sama sekali.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>'
                ],
                [
                    'index' => '05',
                    'judul' => 'Konfigurasi API Dinamis',
                    'desc'  => 'Ganti API Key, ubah Base URL, dan tambah Custom Provider langsung dari menu Settings—tanpa menyentuh satu baris source code pun.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.645-.869L9.594 3.94z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
                ],
                [
                    'index' => '06',
                    'judul' => 'Dark & Light Mode Otomatis',
                    'desc'  => 'Tema pintar yang tersinkronisasi dengan pengaturan sistem operasi Anda secara real-time, dengan transisi warna yang lembut dan nyaman di mata.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>'
                ],
                [
                    'index' => '07',
                    'judul' => 'Mesin AI Lokal (rynude Engine)',
                    'desc'  => 'Jalankan model GGUF lokal—dari rynude Lyric 4.5 (1.5B) hingga rynude Magnum (14B)—langsung di komputer Anda, 100% offline tanpa kuota.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5m0-6a3 3 0 013-3m-3 3a3 3 0 100 6M5.25 5.25h13.5m-13.5 0a3 3 0 00-3 3m3-3a3 3 0 110 6h13.5m0-6a3 3 0 003 3m-3-3a3 3 0 110 6M4.5 9h.008v.008H4.5V9zm0 6h.008v.008H4.5V15zm0 6h.008v.008H4.5V21zm15-12h.008v.008H19.5V9zm0 6h.008v.008H19.5V15zm0 6h.008v.008H19.5V21z"/></svg>'
                ],
                [
                    'index' => '08',
                    'judul' => 'Bagikan Chat & Artifact',
                    'desc'  => 'Buat tautan publik read-only untuk satu percakapan atau artifact kapan saja lewat menu titik-tiga. Cocok untuk kolaborasi tim dan dokumentasi.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186l5.302-3.03m-5.302 5.216l5.302 3.03m1.42-3.03a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zm0-6a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zm0 12a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0z"/></svg>'
                ],
                [
                    'index' => '09',
                    'judul' => 'Dikte Suara & Baca Lantang',
                    'desc'  => 'Gunakan mikrofon untuk mendikte pesan, atau biarkan AI membacakan balasannya menggunakan Web Speech API bawaan browser—tanpa plugin tambahan.',
                    'icon'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/></svg>'
                ]
            ];
        @endphp

        @foreach($fitur as $i => $f)
            <div class="lp-reveal lp-reveal-delay-{{ min($i % 3 + 1, 6) }} group relative overflow-hidden bg-white dark:bg-[#1C1C1A] border border-stone-200/60 dark:border-stone-800/80 rounded-[22px] p-8 transition-all duration-500 ease-out hover:-translate-y-1 hover:shadow-[0_20px_50px_-15px_rgba(0,0,0,0.12)] dark:hover:shadow-[0_20px_50px_-15px_rgba(0,0,0,0.5)] hover:border-stone-300/80 dark:hover:border-stone-700">

                {{-- Subtle gradient glow on hover --}}
                <div class="pointer-events-none absolute -inset-px rounded-[22px] opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-br from-[#D97757]/[0.06] via-transparent to-transparent"></div>

                {{-- Index number --}}
                <span class="absolute top-7 right-8 font-claude-response text-[13px] font-semibold text-stone-300/80 dark:text-stone-700 select-none tabular-nums tracking-wider">
                    {{ $f['index'] }}
                </span>

                {{-- Icon container --}}
                <div class="relative mb-6 inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-[#F5F1EA] dark:bg-stone-800/60 text-[#D97757] dark:text-[#E8A488] transition-all duration-500 group-hover:bg-[#D97757] group-hover:text-white group-hover:scale-110 group-hover:rotate-3">
                    {!! $f['icon'] !!}
                </div>

                {{-- Text content --}}
                <h3 class="relative font-claude-response text-[17px] font-semibold text-[#2D2825] dark:text-stone-100 mb-2.5 leading-snug tracking-tight">
                    {{ $f['judul'] }}
                </h3>
                <p class="relative text-[13px] text-stone-500 dark:text-stone-400 leading-relaxed font-light">
                    {{ $f['desc'] }}
                </p>

                {{-- Bottom accent line --}}
                <div class="absolute bottom-0 left-8 right-8 h-px bg-gradient-to-r from-transparent via-[#D97757]/0 to-transparent group-hover:via-[#D97757]/40 transition-all duration-500"></div>
            </div>
        @endforeach
    </div>
</section>

{{-- ── 4. MESIN LOKAL ───────────────────────────────────────── --}}
<section id="local-engine" class="border-y border-gray-100 dark:border-stone-800 bg-[#FDFCFB] dark:bg-[#1a1a18]">
    <div class="max-w-6xl mx-auto px-6 py-24">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <p class="lp-reveal text-[11.5px] uppercase tracking-widest font-medium text-black dark:text-white mb-3">Rynude Local Engine</p>
                <h2 class="lp-reveal lp-reveal-delay-1 font-claude-response text-[36px] md:text-[42px] font-medium tracking-tight text-[#2D2825] dark:text-stone-50 leading-[1.1] mb-5">
                    AI kelas dunia,<br>berjalan di laptop Anda
                </h2>
                <p class="lp-reveal lp-reveal-delay-2 text-[15.5px] text-gray-500 dark:text-stone-400 leading-relaxed mb-8">
                    Rynude hadir dengan mesin inferensi lokal bawaan untuk model <code class="text-[13px] bg-gray-100 dark:bg-stone-800 px-1.5 py-0.5 rounded font-mono">.gguf</code>. Enam model tersedia dari yang paling ringan hingga yang paling powerful—semuanya berjalan sepenuhnya offline tanpa batasan kuota.
                </p>
                <div class="lp-reveal lp-reveal-delay-3 flex items-center gap-3">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#191919] hover:bg-[#000000] text-white rounded-xl text-[14px] font-medium transition-all shadow-sm hover:-translate-y-0.5">
                        Coba Sekarang
                    </a>
                    <span class="text-[13px] text-gray-500 dark:text-stone-500">GPU tidak wajib untuk model kecil</span>
                </div>
            </div>

            <div class="lp-reveal lp-reveal-delay-2 grid grid-cols-2 sm:grid-cols-3 gap-3">
                @php
                    $models = [
                        ['nama' => 'rynude Vignette',   'param' => '0.5B', 'ram' => '~2 GB',  'badge' => 'Paling Ringan', 'featured' => false],
                        ['nama' => 'rynude Lyric 4.5',  'param' => '1.5B', 'ram' => '~4 GB',  'badge' => '⭐ Default',    'featured' => true],
                        ['nama' => 'rynude Lyric 4.6',  'param' => '1.7B', 'ram' => '~4 GB',  'badge' => '✨ Tuned',      'featured' => false],
                        ['nama' => 'rynude Stanza',     'param' => '3B',   'ram' => '~6 GB',  'badge' => 'Ringan',        'featured' => false],
                        ['nama' => 'rynude Canto',    'param' => '7B',   'ram' => '~8 GB',  'badge' => 'Seimbang',      'featured' => false],
                        ['nama' => 'rynude Symphony', 'param' => '8B',   'ram' => '~10 GB', 'badge' => 'Pintar',        'featured' => false],
                        ['nama' => 'rynude Magnum',   'param' => '14B',  'ram' => '~16 GB', 'badge' => 'Paling Cerdas', 'featured' => false],
                    ];
                @endphp
                @foreach($models as $m)
                    <div class="group relative overflow-hidden bg-white dark:bg-[#232321] border {{ $m['featured'] ? 'border-stone-900/70 dark:border-stone-200/40' : 'border-gray-200 dark:border-stone-800' }} rounded-2xl p-4 transition-all duration-300 ease-out hover:-translate-y-0.5 hover:border-stone-400 dark:hover:border-stone-600 hover:shadow-[0_10px_30px_-10px_rgba(0,0,0,0.1)] dark:hover:shadow-[0_10px_30px_-10px_rgba(0,0,0,0.4)] cursor-default">

                        <span class="inline-block text-[10.5px] font-semibold px-2 py-0.5 rounded-lg mb-3 {{ $m['featured'] ? 'bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900' : 'bg-stone-100 dark:bg-stone-800 text-stone-500 dark:text-stone-400' }}">
                            {{ $m['badge'] }}
                        </span>

                        <p class="text-[13px] font-semibold text-[#2D2825] dark:text-stone-200 mb-1 leading-snug">{{ $m['nama'] }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-stone-600">{{ $m['param'] }} &middot; RAM {{ $m['ram'] }}</p>

                        {{-- subtle bottom accent, monochrome --}}
                        <div class="absolute bottom-0 left-4 right-4 h-px bg-stone-900/0 group-hover:bg-stone-900/10 dark:group-hover:bg-stone-100/10 transition-all duration-300"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

        {{-- ── 5. PERBANDINGAN ──────────────────────────────────────── --}}
        <section id="mengapa-beralih" class="max-w-6xl mx-auto px-6 py-24">
            <div class="text-center mb-14">
                <p class="lp-reveal text-[11.5px] uppercase tracking-widest font-medium text-black dark:text-white mb-3">Mengapa beralih?</p>
                <h2 class="lp-reveal lp-reveal-delay-1 font-claude-response text-[36px] md:text-[42px] font-medium tracking-tight text-[#2D2825] dark:text-stone-50 leading-[1.1]">
                    Rynude vs. Langganan Konvensional
                </h2>
                <p class="lp-reveal lp-reveal-delay-2 text-[15px] text-gray-500 dark:text-stone-400 mt-3 max-w-lg mx-auto">
                    Kenapa harus bayar mahal, kalau ada yang gratis and lebih powerful?
                </p>
            </div>

            <div class="lp-reveal lp-reveal-delay-2 max-w-4xl mx-auto overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-[15px]">
                    <thead>
                        <tr class="border-b-2 border-gray-900 dark:border-stone-100">
                            <th class="py-5 pr-6 font-semibold text-[#2D2825] dark:text-stone-100 w-2/5">Fitur</th>
                            <th class="py-5 px-6 font-semibold text-gray-500 dark:text-stone-400">Langganan Konvensional</th>
                            <th class="py-5 pl-6 font-semibold text-[#2D2825] dark:text-stone-100">
                                <span class="inline-flex items-center gap-2">
                                    <img src="{{ asset('images/logo_rynudee.png') }}" class="w-5 h-5 rounded-sm" alt="">
                                    Rynude AI
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/60 dark:divide-stone-800/60">
                        @php
                            $rows = [
                                ['fitur' => 'Biaya Antarmuka (UI)',        'konv' => '$20/bulan (~Rp 320.000)',   'rynude' => 'Gratis 100%'],
                                ['fitur' => 'Pilihan Model AI',            'konv' => 'Terkunci 1 ekosistem',      'rynude' => 'Multi-provider bebas'],
                                ['fitur' => 'Akses Model Premium',         'konv' => 'Berbayar penuh',            'rynude' => 'Bisa gratis via Proxy'],
                                ['fitur' => 'Batas Pesan per Hari',        'konv' => 'Maks 40 pesan / 3 jam',     'rynude' => 'Tanpa batas (unlimited)'],
                                ['fitur' => 'Privasi & Keamanan Data',     'konv' => 'Disimpan di cloud server',  'rynude' => 'Lokal 100% di perangkat Anda'],
                                ['fitur' => 'Kompresi Token (RTK)',        'konv' => 'Tidak tersedia',            'rynude' => 'Tersedia (hemat hingga 50%)'],
                                ['fitur' => 'Kustomisasi Tema',            'konv' => 'Sangat terbatas',           'rynude' => 'Sepenuhnya bebas'],
                            ];
                        @endphp
                        @foreach($rows as $r)
                            <tr class="group hover:bg-gray-50/50 dark:hover:bg-stone-800/30 transition-colors">
                                <td class="py-5 pr-6 font-medium text-[#2D2825] dark:text-stone-200">{{ $r['fitur'] }}</td>
                                <td class="py-5 px-6 text-gray-500 dark:text-stone-400">{{ $r['konv'] }}</td>
                                <td class="py-5 pl-6 text-[#2D2825] dark:text-stone-100 font-medium flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 dark:text-stone-100 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ $r['rynude'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── 6. PRICING ───────────────────────────────────────────── --}}
        <section id="harga" class="border-y border-gray-100 dark:border-stone-800 bg-[#FDFCFB] dark:bg-[#1a1a18]">
            <div class="max-w-6xl mx-auto px-6 py-24">
                <div class="text-center mb-16">
                    <p class="lp-reveal text-[11.5px] uppercase tracking-widest font-medium text-black dark:text-white mb-3">Harga yang jujur</p>
                    <h2 class="lp-reveal lp-reveal-delay-1 font-claude-response text-[36px] md:text-[46px] font-medium tracking-tight text-[#2D2825] dark:text-stone-50 leading-[1.08]">
                        Semua plan, satu harga:<br>
                        <span class="text-black dark:text-white font-semibold">Sepenuhnya Gratis</span>
                    </h2>
                    <p class="lp-reveal lp-reveal-delay-2 text-[15.5px] text-gray-500 dark:text-stone-400 mt-5 max-w-xl mx-auto leading-relaxed">
                        Rynude AI adalah software open-source. Tidak ada biaya tersembunyi, tidak ada trial period, tidak ada kartu kredit. Untuk selamanya.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 items-stretch">

                    {{-- Card 1: Free (Pemula) --}}
                    <div class="lp-reveal lp-reveal-delay-1 flex flex-col bg-white dark:bg-[#1E1E1C] border border-gray-200 dark:border-stone-800 rounded-[24px] p-8 transition-all duration-300 hover:shadow-lg">
                        {{-- Claude Tree Icon 1 (Free Style) --}}
                        <div class="text-stone-800 dark:text-stone-200">
                            <svg class="w-12 h-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                                <!-- Main Trunk -->
                                <path d="M24 44V22" stroke-linecap="round"/>
                                <!-- Branches -->
                                <path d="M24 34C18 34 16 30 16 26" stroke-linecap="round"/>
                                <path d="M24 30C30 30 32 26 32 22" stroke-linecap="round"/>
                                <path d="M24 22C20 18 20 14 20 10" stroke-linecap="round"/>
                                <path d="M24 22C28 18 28 14 28 10" stroke-linecap="round"/>
                                <!-- Hollow Nodes -->
                                <circle cx="16" cy="26" r="2.5" fill="white" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="32" cy="22" r="2.5" fill="white" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="20" cy="10" r="2.5" fill="white" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="28" cy="10" r="2.5" fill="white" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="24" cy="6" r="2.5" fill="white" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M24 10V6"/>
                            </svg>
                        </div>
                        
                        <div class="mt-6 mb-2">
                            <h3 class="font-claude-response text-[32px] font-semibold text-[#2D2825] dark:text-stone-100 tracking-tight leading-none">Free</h3>
                            <p class="text-[14px] text-gray-500 dark:text-stone-400 mt-2">Coba Rynude</p>
                        </div>

                        <div class="my-5">
                            <p class="text-[13px] text-gray-400 dark:text-stone-600 line-through mb-0.5">Normalnya: Rp 79.000</p>
                            <div class="flex items-baseline gap-1">
                                <span class="font-claude-response text-[36px] font-medium text-[#2D2825] dark:text-stone-50 leading-none">Rp.0</span>
                            </div>
                            <p class="text-[13px] text-gray-500 dark:text-stone-400 mt-2">Gratis untuk semua orang</p>
                        </div>

                        <div class="mb-6">
                            <a href="{{ route('register') }}" class="block w-full text-center py-3 bg-[#191919] hover:bg-[#000000] text-white text-[14px] font-semibold rounded-xl transition-all duration-200">
                                Coba Rynude
                            </a>
                        </div>

                        <hr class="border-t border-gray-150 dark:border-stone-800 mb-6">

                        <ul class="space-y-4 flex-1">
                            @php
                                $freeFeatures = [
                                    'Chat di web, iOS, Android, dan desktop Anda',
                                    'Hasilkan kode dan visualisasikan data',
                                    'Tulis, edit, dan buat konten',
                                    'Kemampuan untuk mencari web',
                                    'Memori lintas percakapan',
                                    'Buat file dan jalankan kode',
                                    'Akses lebih banyak fitur dengan ekstensi desktop',
                                    'Hubungkan layanan Slack dan Google Workspace',
                                    'Integrasikan konteks atau alat dengan remote MCP',
                                    'Mode berpikir mendalam (Extended thinking) untuk tugas kompleks'
                                ];
                            @endphp
                            @foreach($freeFeatures as $item)
                            <li class="flex items-start text-[13px] text-[#2D2825] dark:text-stone-300 leading-snug">
                                <span class="text-stone-400 dark:text-stone-500 mr-3 flex-shrink-0 text-[14px] font-semibold">&#10003;</span>
                                <span>{{ $item }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Card 2: Pro (Pengguna Aktif) --}}
                    <div class="lp-reveal lp-reveal-delay-2 flex flex-col bg-white dark:bg-[#1E1E1C] border border-gray-200 dark:border-stone-800 rounded-[24px] p-8 transition-all duration-300 hover:shadow-lg">
                        {{-- Claude Tree Icon 2 (Pro Style) --}}
                        <div class="text-stone-800 dark:text-stone-200">
                            <svg class="w-12 h-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                                <!-- Main Trunk -->
                                <path d="M24 44V18" stroke-linecap="round"/>
                                <!-- More complex branches -->
                                <path d="M24 36C16 36 14 30 14 24" stroke-linecap="round"/>
                                <path d="M24 32C32 32 34 26 34 20" stroke-linecap="round"/>
                                <path d="M24 24C18 20 18 16 18 12" stroke-linecap="round"/>
                                <path d="M24 24C30 20 30 16 30 12" stroke-linecap="round"/>
                                <path d="M14 24C10 24 8 20 8 16" stroke-linecap="round"/>
                                <path d="M34 20C38 20 40 16 40 12" stroke-linecap="round"/>
                                <!-- Filled Nodes -->
                                <circle cx="14" cy="24" r="2.5" fill="currentColor"/>
                                <circle cx="34" cy="20" r="2.5" fill="currentColor"/>
                                <circle cx="18" cy="12" r="2.5" fill="currentColor"/>
                                <circle cx="30" cy="12" r="2.5" fill="currentColor"/>
                                <circle cx="8" cy="16" r="2.5" fill="currentColor"/>
                                <circle cx="40" cy="12" r="2.5" fill="currentColor"/>
                                <circle cx="24" cy="8" r="2.5" fill="currentColor"/>
                                <path d="M24 18V8"/>
                            </svg>
                        </div>

                        <div class="mt-6 mb-2">
                            <h3 class="font-claude-response text-[32px] font-semibold text-[#2D2825] dark:text-stone-100 tracking-tight leading-none">Pro</h3>
                            <p class="text-[14px] text-gray-500 dark:text-stone-400 mt-2">Untuk produktivitas harian</p>
                        </div>

                        <div class="my-5">
                            <p class="text-[13px] text-gray-400 dark:text-stone-600 line-through mb-0.5">Normalnya: Rp 199.000</p>
                            <div class="flex items-baseline gap-1">
                                <span class="font-claude-response text-[36px] font-medium text-[#2D2825] dark:text-stone-50 leading-none">Rp.0</span>
                            </div>
                            <p class="text-[13px] text-gray-500 dark:text-stone-400 mt-2">Gratis Selamanya untuk Pengguna Aktif</p>
                        </div>

                        <div class="mb-6">
                            <a href="{{ route('register') }}" class="block w-full text-center py-3 bg-[#191919] hover:bg-[#000000] text-white text-[14px] font-semibold rounded-xl transition-all duration-200">
                                Coba Rynude
                            </a>
                        </div>

                        <hr class="border-t border-gray-150 dark:border-stone-850 mb-6">

                        <p class="text-[13px] font-bold text-[#2D2825] dark:text-stone-200 mb-4">Semua fitur Gratis, plus:</p>

                        <ul class="space-y-4 flex-1">
                            @php
                                $proFeatures = [
                                    'Penggunaan lebih tinggi*',
                                    'Termasuk Rynude Code (Claude Code clone)',
                                    'Termasuk Rynude Cowork (Claude Cowork clone)',
                                    'Termasuk Rynude Design (Claude Design clone)',
                                    'Termasuk Rynude Science (Claude Science clone)',
                                    'Akses proyek tak terbatas untuk mengelompokkan chat dan dokumen',
                                    'Akses ke fitur riset (Web Search)',
                                    'Kemampuan menggunakan model AI lebih banyak',
                                    'Rynude untuk Microsoft 365'
                                ];
                            @endphp
                            @foreach($proFeatures as $item)
                            <li class="flex items-start text-[13px] text-[#2D2825] dark:text-stone-300 leading-snug">
                                <span class="text-stone-400 dark:text-stone-500 mr-3 flex-shrink-0 text-[14px] font-semibold">&#10003;</span>
                                <span>{{ $item }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Card 3: Max (Tim & Enterprise) — Subtle Blue/Indigo Highlight --}}
                    <div class="lp-reveal lp-reveal-delay-3 flex flex-col bg-white dark:bg-[#1E1E1C] border border-[#C2D6FF] dark:border-blue-900/50 rounded-[24px] p-8 transition-all duration-300 hover:shadow-lg shadow-[0_8px_30px_rgba(194,214,255,0.18)]">
                        {{-- Claude Tree Icon 3 (Max Style - Colored Nodes) --}}
                        <div>
                            <svg class="w-12 h-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                                <!-- Main Trunk -->
                                <path d="M24 44V18" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <!-- Complex branch system -->
                                <path d="M24 36C16 36 14 30 14 24" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <path d="M24 32C32 32 34 26 34 20" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <path d="M24 24C18 20 18 16 18 12" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <path d="M24 24C30 20 30 16 30 12" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <path d="M14 24C10 24 8 20 8 16" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <path d="M34 20C38 20 40 16 40 12" stroke-linecap="round" class="text-stone-800 dark:text-stone-200"/>
                                <!-- Node 1 (blue) -->
                                <circle cx="14" cy="24" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <!-- Node 2 (blue) -->
                                <circle cx="34" cy="20" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <!-- Node 3 (blue) -->
                                <circle cx="18" cy="12" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <!-- Node 4 (blue) -->
                                <circle cx="30" cy="12" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <!-- Node 5 (blue) -->
                                <circle cx="8" cy="16" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <!-- Node 6 (blue) -->
                                <circle cx="40" cy="12" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <!-- Top Node (blue) -->
                                <circle cx="24" cy="8" r="2.5" fill="#4285F4" stroke="#4285F4"/>
                                <path d="M24 18V8" class="text-stone-800 dark:text-stone-200"/>
                            </svg>
                        </div>

                        <div class="mt-6 mb-2">
                            <h3 class="font-claude-response text-[32px] font-semibold text-[#2D2825] dark:text-stone-100 tracking-tight leading-none">Max</h3>
                            <p class="text-[14px] text-gray-500 dark:text-stone-400 mt-2">Dapatkan yang terbaik dari Rynude</p>
                        </div>

                        <div class="my-5">
                            <p class="text-[13px] text-gray-400 dark:text-stone-600 line-through mb-0.5">Normalnya: Mulai Rp 499.000</p>
                            <div class="flex items-baseline gap-1">
                                <span class="font-claude-response text-[36px] font-medium text-[#2D2825] dark:text-stone-50 leading-none">Rp.0</span>
                            </div>
                            <p class="text-[13px] text-gray-500 dark:text-stone-400 mt-2">Gratis Selamanya untuk Organisasi</p>
                        </div>

                        <div class="mb-6">
                            <a href="{{ route('register') }}" class="block w-full text-center py-3 bg-[#191919] hover:bg-[#000000] text-white text-[14px] font-semibold rounded-xl transition-all duration-200">
                                Coba Rynude
                            </a>
                        </div>

                        <hr class="border-t border-gray-100 dark:border-stone-850 mb-6">

                        <p class="text-[13px] font-bold text-[#2D2825] dark:text-stone-200 mb-4">Semua fitur Pro, plus:</p>

                        <ul class="space-y-4 flex-1">
                            @php
                                $maxFeatures = [
                                    'Pilih penggunaan 5x atau 20x lebih banyak dibanding Pro*',
                                    'Batas output yang lebih tinggi untuk semua tugas',
                                    'Akses awal ke canggih Rynude berikutnya',
                                    'Akses prioritas tinggi saat trafik sibuk'
                                ];
                            @endphp
                            @foreach($maxFeatures as $item)
                            <li class="flex items-start text-[13px] text-[#2D2825] dark:text-stone-300 leading-snug">
                                <span class="text-stone-400 dark:text-stone-500 mr-3 flex-shrink-0 text-[14px] font-semibold">&#10003;</span>
                                <span>{{ $item }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                </div>{{-- /grid pricing --}}

                <p class="lp-reveal lp-reveal-delay-4 text-center text-[13px] text-gray-400 dark:text-stone-600 mt-8">
                    Biaya API (ke Anthropic, OpenAI, dll.) tetap ditanggung sendiri sesuai pemakaian. Rynude sebagai antarmuka selalu gratis.
                </p>
            </div>
        </section>

        {{-- ── 7. INSTALASI MUDAH ───────────────────────────────────── --}}
        <section id="instalasi" class="max-w-6xl mx-auto px-6 py-24">
            <div class="text-center mb-16">
                <p class="lp-reveal text-[11.5px] uppercase tracking-widest font-medium text-black dark:text-white mb-3">Mulai dalam hitungan detik</p>
                <h2 class="lp-reveal lp-reveal-delay-1 font-claude-response text-[36px] md:text-[44px] font-medium tracking-tight text-[#2D2825] dark:text-stone-50 leading-[1.1]">
                    Instalasi "One-Click"<br>yang benar-benar mudah
                </h2>
                <p class="lp-reveal lp-reveal-delay-2 text-[15.5px] text-gray-500 dark:text-stone-400 mt-4 max-w-lg mx-auto leading-relaxed">
                    Lupakan setup manual yang menyiksa. Satu perintah terminal sudah cukup&mdash;sisanya ditangani secara otomatis.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                @php
                    $steps = [
                        ['num' => '01', 'judul' => 'Salin & Jalankan',     'desc' => 'Buka terminal dari mana saja—Desktop, Documents, atau folder apapun—lalu ketikkan perintah instalasi ini.', 'code' => 'npx install-rynude', 'delay' => '1'],
                        ['num' => '02', 'judul' => 'Nyalakan & Mulai Chat','desc' => 'Jalankan perintah ini kapan saja dari terminal untuk menghidupkan Rynude AI secara otomatis.', 'code' => 'rynude', 'delay' => '2'],
                        ['num' => '03', 'judul' => 'Selalu Terbarui',      'desc' => 'Perbarui ke versi terbaru dan dapatkan seluruh fitur canggih terbaru dengan satu baris pembaruan.', 'code' => 'npx install-rynude@latest', 'delay' => '3'],
                    ];
                @endphp
                @foreach($steps as $i => $s)
                    <div id="step-{{ $i + 1 }}" class="lp-reveal lp-reveal-delay-{{ $s['delay'] }} relative flex flex-col items-center text-center">
                        <div class="relative {{ $i < count($steps)-1 ? 'lp-step-line' : '' }} w-14 h-14 rounded-2xl bg-stone-900/10 dark:bg-stone-100/10 border border-stone-200 dark:border-stone-800 flex items-center justify-center mb-5">
                            <span class="font-claude-response text-[18px] font-semibold text-black dark:text-white">{{ $s['num'] }}</span>
                        </div>
                        <h3 class="font-semibold text-[16px] text-[#2D2825] dark:text-stone-100 mb-2">{{ $s['judul'] }}</h3>
                        <p class="text-[13.5px] text-gray-500 dark:text-stone-500 leading-relaxed mb-4">{{ $s['desc'] }}</p>
                        @if($s['code'])
                            <div x-data="{ copied: false }" 
                                 @click="navigator.clipboard.writeText('{{ $s['code'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                 class="w-full relative group bg-[#1C1A19] dark:bg-[#111110] rounded-xl px-4 py-3 font-mono text-[13px] text-stone-300 border border-transparent dark:border-stone-800 shadow-sm text-left cursor-pointer hover:border-gray-500 dark:hover:border-stone-500 transition-colors"
                                 title="Klik untuk menyalin">
                                
                                <div class="flex items-center justify-between">
                                    <span>$ {{ $s['code'] }}</span>
                                    <span class="text-stone-500 group-hover:text-stone-300 transition-colors">
                                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copied" style="display: none;" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </div>

                                <div x-show="copied" x-transition class="absolute -top-10 left-1/2 -translate-x-1/2 bg-[#2D2825] dark:bg-stone-200 text-white dark:text-[#2D2825] text-[11.5px] font-sans font-medium px-2.5 py-1 rounded-md shadow-lg whitespace-nowrap z-10">
                                    Berhasil disalin!
                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[#2D2825] dark:bg-stone-200 transform rotate-45"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="lp-reveal lp-reveal-delay-3 flex flex-wrap items-center justify-center gap-3 mt-12">
                <p class="text-[13px] text-gray-400 dark:text-stone-600 mr-2">Prasyarat sistem:</p>
                @foreach(['PHP &ge; 8.2', 'Node.js &ge; 18', 'Composer', 'Git'] as $req)
                    <span class="inline-flex items-center gap-1.5 text-[12.5px] px-3 py-1 rounded-lg bg-gray-100 dark:bg-stone-800 text-gray-600 dark:text-stone-400 font-medium">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {!! $req !!}
                    </span>
                @endforeach
            </div>
        </section>

        {{-- Brand Sign-off Watermark --}}
        <div class="lp-reveal text-center py-10 md:py-16 px-6 overflow-hidden select-none pointer-events-none">
            <h2 class="font-claude-response text-[#2D2825]/[0.06] dark:text-stone-100/[0.018] text-[68px] sm:text-[100px] md:text-[150px] lg:text-[210px] font-semibold tracking-[0.45em] -mr-[0.45em] uppercase leading-none">
                RYNUDE
            </h2>
        </div>

        {{-- ── 9. FOOTER ─────────────────────────────────────────────── --}}
        <footer class="border-t border-gray-100 dark:border-stone-800">
            <div class="max-w-6xl mx-auto px-6 py-12">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                    <div class="col-span-2 md:col-span-1">
                        <div class="flex items-center gap-2.5 mb-3">
                            <img src="{{ asset('images/logo_rynudee.png') }}" alt="Rynude" class="w-7 h-7 rounded-lg object-contain">
                            <span class="font-claude-response text-[18px] font-medium text-[#2D2825] dark:text-stone-100">Rynude AI</span>
                        </div>
                        <p class="text-[13px] text-gray-500 dark:text-stone-500 leading-relaxed">Platform chat AI open-source yang memberikan kebebasan penuh kepada Anda.</p>
                    </div>
                    <div>
                        <p class="text-[11.5px] font-semibold uppercase tracking-widest text-gray-400 dark:text-stone-600 mb-4">Produk</p>
                        <ul class="space-y-2.5">
                            <li><a href="#fitur"    class="text-[13.5px] text-gray-500 dark:text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">Fitur Unggulan</a></li>
                            <li><a href="#harga"    class="text-[13.5px] text-gray-500 dark:text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">Harga & Plan</a></li>
                            <li><a href="#instalasi"class="text-[13.5px] text-gray-500 dark:text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">Cara Instalasi</a></li>
                            <li><a href="#mulai"    class="text-[13.5px] text-gray-500 dark:text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">Mulai Sekarang</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-[11.5px] font-semibold uppercase tracking-widest text-gray-400 dark:text-stone-600 mb-4">Sumber Daya</p>
                        <ul class="space-y-2.5">
                            @foreach(['Dokumentasi', 'GitHub', 'Komunitas Discord', 'Blog'] as $item)
                                <li><a href="#" class="text-[13.5px] text-gray-500 dark:text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">{{ $item }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <p class="text-[11.5px] font-semibold uppercase tracking-widest text-gray-400 dark:text-stone-600 mb-4">Legal</p>
                        <ul class="space-y-2.5">
                            @foreach(['Kebijakan Privasi', 'Syarat Penggunaan', 'Lisensi Apache 2.0'] as $item)
                                <li><a href="#" class="text-[13.5px] text-gray-500 dark:text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">{{ $item }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-stone-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-[12.5px] text-gray-400 dark:text-stone-600">&copy; {{ date('Y') }} Rynude AI. Hak cipta dilindungi. Dirilis di bawah lisensi Apache 2.0.</p>
                    <div class="flex items-center gap-5">
                        <a href="https://github.com" target="_blank" class="text-gray-400 dark:text-stone-600 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors" aria-label="GitHub">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 4.42 2.87 8.17 6.84 9.49.5.09.66-.22.66-.48l-.01-1.7c-2.78.6-3.37-1.34-3.37-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.61.07-.61 1 .07 1.53 1.03 1.53 1.03.89 1.52 2.34 1.08 2.91.83.09-.65.35-1.08.63-1.33-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.56 9.56 0 0112 6.8c.85.004 1.71.11 2.51.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.85l-.01 2.75c0 .27.16.58.67.48A10.01 10.01 0 0022 12c0-5.52-4.48-10-10-10z"/></svg>
                        </a>
                        <a href="https://discord.com" target="_blank" class="text-gray-400 dark:text-stone-600 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors" aria-label="Discord">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </footer>

    </div>{{-- /min-h-screen wrapper --}}

    <style>
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .floaty { animation: floaty 6s ease-in-out infinite; }
    </style>

    <script>
        /* ── Scroll-reveal via IntersectionObserver ── */
        (function () {
            var els = document.querySelectorAll('.lp-reveal');
            if (!els.length) return;
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('lp-visible');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            els.forEach(function (el) { io.observe(el); });
        })();
    </script>

</x-guest-layout>

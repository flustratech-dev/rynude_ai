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
                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode" type="button" class="p-2 rounded-xl text-gray-600 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-[#3A3A38] transition-colors" title="Toggle theme">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>
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
                    <a href="{{ route('login') }}" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Meet rynude</a>
                    <a href="{{ route('login') }}#fitur" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Fitur</a>
                    <a href="{{ route('login') }}#harga" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Harga</a>
                    <a href="{{ route('login') }}#instalasi" class="text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white transition-colors">Instalasi</a>
                    <div class="h-px bg-gray-200 dark:bg-stone-700 my-2"></div>
                    <a href="https://github.com/flustratech/rynude" target="_blank" rel="noopener noreferrer" class="text-[15px] font-medium text-center text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white px-4 py-3 border border-gray-300 dark:border-stone-600 rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
                        </svg>
                        <span>GitHub</span>
                    </a>
                    <a href="{{ route('login') }}" class="text-[15px] font-medium text-center text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white px-4 py-3 border border-gray-300 dark:border-stone-600 rounded-xl transition-colors">Masuk</a>
                    <a href="{{ route('register') }}" class="text-[15px] font-medium text-center text-white bg-[#1C1A19] hover:bg-black dark:hover:bg-[#3A3A38] px-4 py-3 rounded-xl transition-colors">Register</a>
                    <button @click="darkMode = !darkMode; mobileMenuOpen = false" type="button" class="flex items-center gap-2 px-4 py-3 text-[15px] font-medium text-gray-700 dark:text-stone-300 hover:text-black dark:hover:text-white border border-gray-300 dark:border-stone-600 rounded-xl transition-colors">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span x-text="darkMode ? 'Mode Terang' : 'Mode Gelap'"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col lg:flex-row w-full max-w-[1400px] mx-auto px-6 lg:px-10 gap-10 pb-10">
            <!-- Left Side -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center pt-10 lg:pt-0">
                <div class="text-center mb-8 max-w-md">
                    <h1 class="font-claude-response text-[42px] lg:text-[56px] leading-[1.1] text-[#2D2825] dark:text-stone-100 mb-4 tracking-tight">
                        Join rynude Design
                    </h1>
                    <p class="text-[18px] text-gray-600 dark:text-stone-400">
                        Create an account to build prototypes, slides, and websites.
                    </p>
                </div>

                <!-- Register Card -->
                <div class="w-full max-w-[400px] bg-gradient-to-b from-[#E2EEFF] to-[#F0F5FF] dark:from-[#1E293B] dark:to-[#0F172A] rounded-[32px] border border-[#C2D6FF] dark:border-blue-900/50 overflow-hidden relative shadow-sm dark:shadow-none">
                    <!-- Banner -->
                    <div class="pt-5 pb-7 px-4 text-center flex items-center justify-center gap-2">
                        <span class="bg-[#C6DCFF] dark:bg-blue-900/50 text-[#1D4ED8] dark:text-blue-300 text-[12px] font-semibold px-2.5 py-0.5 rounded-md">New</span>
                        <span class="text-[14px] text-[#2563EB] dark:text-blue-300 font-medium">Get started with rynude Design today</span>
                    </div>

                    <div class="bg-[#FFFDF9] dark:bg-[#1C1C1C] rounded-t-[32px] p-8 border-t border-[#E5E7EB] dark:border-stone-700 shadow-[0_-4px_10px_rgba(0,0,0,0.02)] dark:shadow-none flex flex-col">
                        <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 px-4 py-3.5 border border-gray-300 dark:border-stone-600 rounded-2xl hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors mb-6 shadow-sm bg-white dark:bg-[#323232]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200">Sign up with Google</span>
                        </a>

                        <div class="text-center text-[12px] font-medium text-gray-500 dark:text-stone-500 uppercase tracking-wider mb-6">
                            OR
                        </div>

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="mb-4">
                                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your name" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 dark:border-stone-600 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-500 dark:placeholder-stone-500 focus:outline-none focus:border-[#2D2825] dark:focus:border-stone-400 focus:ring-1 focus:ring-[#2D2825] dark:focus:ring-stone-400 transition-all shadow-sm bg-white dark:bg-[#323232]" required autofocus>
                                @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-4">
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 dark:border-stone-600 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-500 dark:placeholder-stone-500 focus:outline-none focus:border-[#2D2825] dark:focus:border-stone-400 focus:ring-1 focus:ring-[#2D2825] dark:focus:ring-stone-400 transition-all shadow-sm bg-white dark:bg-[#323232]" required>
                                @error('email') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-4">
                                <input id="password" type="password" name="password" placeholder="Password" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 dark:border-stone-600 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-500 dark:placeholder-stone-500 focus:outline-none focus:border-[#2D2825] dark:focus:border-stone-400 focus:ring-1 focus:ring-[#2D2825] dark:focus:ring-stone-400 transition-all shadow-sm bg-white dark:bg-[#323232]" required>
                                @error('password') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-6">
                                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm password" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 dark:border-stone-600 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-500 dark:placeholder-stone-500 focus:outline-none focus:border-[#2D2825] dark:focus:border-stone-400 focus:ring-1 focus:ring-[#2D2825] dark:focus:ring-stone-400 transition-all shadow-sm bg-white dark:bg-[#323232]" required>
                                @error('password_confirmation') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="w-full py-3.5 bg-[#1C1A19] hover:bg-black dark:bg-stone-700 dark:hover:bg-stone-600 text-white rounded-2xl text-[16px] font-semibold transition-colors shadow-md border border-transparent dark:border-stone-600">
                                Create account
                            </button>
                        </form>

                        <p class="text-[13px] text-gray-500 dark:text-stone-400 text-center mt-6">
                            By registering, you acknowledge rynude's <a href="#" class="underline hover:text-gray-700 dark:hover:text-stone-300">Privacy Policy</a>.
                        </p>
                        
                        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-stone-700 text-center w-full">
                            <p class="text-[14px] text-gray-600 dark:text-stone-400">
                                Already have an account? <a href="{{ route('login') }}" class="text-[#2D2825] dark:text-stone-200 font-semibold underline hover:text-gray-800 dark:hover:text-stone-300">Sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (Video Container) -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-4">
                <div class="w-full max-w-[640px] aspect-video bg-[#0D0F12] rounded-[1.5rem] overflow-hidden relative shadow-2xl border border-gray-800 dark:border-stone-600 flex items-center justify-center group">
                    
                    <!-- Video placeholder (User will change src here) -->
                    <!-- Upload your video file and set the source below -->
                    <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover opacity-90 transition-opacity duration-500 group-hover:opacity-100" id="hero-video">
                        <source src="{{ asset('video/video_halaman_utama.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Overlay content (mimics the 'Designi' badge) -->
                    <div class="absolute bottom-10 right-5 z-10 bg-white dark:bg-[#323232] rounded-lg px-3 py-1.5 shadow-md flex items-center gap-1.5 border border-gray-100 dark:border-stone-600">
                        <svg class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                        </svg>
                        <span class="font-claude-response text-[15px] font-medium text-[#2D2825] dark:text-stone-200 tracking-tight">Rynude</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .animate-float { animation: float 6s infinite ease-in-out; }
    </style>
</x-guest-layout>

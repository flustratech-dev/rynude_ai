<x-guest-layout>
    <div class="min-h-screen bg-[#FFFDF9] flex flex-col font-claude-response text-[#2D2825]">
        <!-- Header -->
        <header x-data="{ mobileMenuOpen: false }" class="relative z-50 flex items-center justify-between px-6 py-4 lg:px-10 lg:py-6 w-full bg-[#FFFDF9]">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo_rynudee.png') }}" alt="Rynude Logo" class="h-8 w-auto object-contain">
                <span class="font-claude-response text-[22px] font-medium tracking-tight">rynude</span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-6 text-[14px] font-medium text-gray-700">
                <a href="#" class="hover:text-black transition-colors">Meet rynude</a>
                <a href="#" class="hover:text-black transition-colors">Platform</a>
                <a href="#" class="hover:text-black transition-colors">Solutions</a>
                <a href="#" class="hover:text-black transition-colors">Pricing</a>
                <a href="#" class="hover:text-black transition-colors">Resources</a>
            </div>
            <div class="hidden lg:flex items-center gap-4">
                <a href="#" class="text-[14px] font-medium text-gray-700 hover:text-black px-4 py-2 border border-gray-300 rounded-xl transition-colors">Contact sales</a>
                <a href="#" class="text-[14px] font-medium text-white bg-[#1C1A19] hover:bg-black px-4 py-2 rounded-xl transition-colors">Try rynude</a>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="lg:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-gray-700 hover:text-black focus:outline-none p-1">
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
                 class="absolute top-full left-0 right-0 bg-[#FFFDF9] border-b border-gray-200 shadow-lg lg:hidden"
                 style="display: none;">
                <div class="px-6 py-5 flex flex-col gap-4">
                    <a href="#" class="text-[15px] font-medium text-gray-700 hover:text-black transition-colors">Meet rynude</a>
                    <a href="#" class="text-[15px] font-medium text-gray-700 hover:text-black transition-colors">Platform</a>
                    <a href="#" class="text-[15px] font-medium text-gray-700 hover:text-black transition-colors">Solutions</a>
                    <a href="#" class="text-[15px] font-medium text-gray-700 hover:text-black transition-colors">Pricing</a>
                    <a href="#" class="text-[15px] font-medium text-gray-700 hover:text-black transition-colors">Resources</a>
                    <div class="h-px bg-gray-200 my-2"></div>
                    <a href="#" class="text-[15px] font-medium text-center text-gray-700 hover:text-black px-4 py-3 border border-gray-300 rounded-xl transition-colors">Contact sales</a>
                    <a href="#" class="text-[15px] font-medium text-center text-white bg-[#1C1A19] hover:bg-black px-4 py-3 rounded-xl transition-colors">Try rynude</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col lg:flex-row w-full max-w-[1400px] mx-auto px-6 lg:px-10 gap-10 pb-10">
            <!-- Left Side -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center pt-10 lg:pt-0">
                <div class="text-center mb-8 max-w-md">
                    <h1 class="font-claude-response text-[42px] lg:text-[56px] leading-[1.1] text-[#2D2825] mb-4 tracking-tight">
                        Join rynude Design
                    </h1>
                    <p class="text-[18px] text-gray-600">
                        Create an account to build prototypes, slides, and websites.
                    </p>
                </div>

                <!-- Register Card -->
                <div class="w-full max-w-[400px] bg-gradient-to-b from-[#E2EEFF] to-[#F0F5FF] rounded-[32px] border border-[#C2D6FF] overflow-hidden relative shadow-sm">
                    <!-- Banner -->
                    <div class="pt-5 pb-7 px-4 text-center flex items-center justify-center gap-2">
                        <span class="bg-[#C6DCFF] text-[#1D4ED8] text-[12px] font-semibold px-2.5 py-0.5 rounded-md">New</span>
                        <span class="text-[14px] text-[#2563EB] font-medium">Get started with rynude Design today</span>
                    </div>

                    <div class="bg-[#FFFDF9] rounded-t-[32px] p-8 border-t border-[#E5E7EB] shadow-[0_-4px_10px_rgba(0,0,0,0.02)] flex flex-col">
                        <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-3.5 border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors mb-6 shadow-sm bg-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span class="text-[15px] font-semibold text-[#2D2825]">Sign up with Google</span>
                        </button>

                        <div class="text-center text-[12px] font-medium text-gray-500 uppercase tracking-wider mb-6">
                            OR
                        </div>

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="mb-4">
                                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your name" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 text-[15px] text-[#2D2825] placeholder-gray-500 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm bg-white" required autofocus>
                                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-4">
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 text-[15px] text-[#2D2825] placeholder-gray-500 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm bg-white" required>
                                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-4">
                                <input id="password" type="password" name="password" placeholder="Password" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 text-[15px] text-[#2D2825] placeholder-gray-500 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm bg-white" required>
                                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-6">
                                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm password" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 text-[15px] text-[#2D2825] placeholder-gray-500 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm bg-white" required>
                                @error('password_confirmation') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="w-full py-3.5 bg-[#1C1A19] hover:bg-black text-white rounded-2xl text-[16px] font-semibold transition-colors shadow-md">
                                Create account
                            </button>
                        </form>

                        <p class="text-[13px] text-gray-500 text-center mt-6">
                            By registering, you acknowledge rynude's <a href="#" class="underline hover:text-gray-700">Privacy Policy</a>.
                        </p>
                        
                        <div class="mt-6 pt-5 border-t border-gray-100 text-center w-full">
                            <p class="text-[14px] text-gray-600">
                                Already have an account? <a href="{{ route('login') }}" class="text-[#2D2825] font-semibold underline hover:text-gray-800">Sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (Video Container) -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-4">
                <div class="w-full max-w-[640px] aspect-video bg-[#0D0F12] rounded-[1.5rem] overflow-hidden relative shadow-2xl border border-gray-800 flex items-center justify-center group">
                    
                    <!-- Video placeholder (User will change src here) -->
                    <!-- Upload your video file and set the source below -->
                    <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover opacity-90 transition-opacity duration-500 group-hover:opacity-100" id="hero-video">
                        <source src="{{ asset('video/video_halaman_utama.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Overlay content (mimics the 'Designi' badge) -->
                    <div class="absolute bottom-10 right-5 z-10 bg-white rounded-lg px-3 py-1.5 shadow-md flex items-center gap-1.5 border border-gray-100">
                        <svg class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                        </svg>
                        <span class="font-claude-response text-[15px] font-medium text-[#2D2825] tracking-tight">Rynude</span>
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

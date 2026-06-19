<x-guest-layout>
    <div class="min-h-screen bg-[#FFFDF9] flex flex-col font-sans text-[#2D2825]">
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 lg:px-10 lg:py-6 w-full">
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-[#D97757]" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                </svg>
                <span class="font-serif text-[22px] font-medium tracking-tight">rynude</span>
            </div>
            <div class="hidden lg:flex items-center gap-6 text-[14px] font-medium text-gray-700">
                <a href="#" class="hover:text-black">Meet rynude</a>
                <a href="#" class="hover:text-black">Platform</a>
                <a href="#" class="hover:text-black">Solutions</a>
                <a href="#" class="hover:text-black">Pricing</a>
                <a href="#" class="hover:text-black">Resources</a>
            </div>
            <div class="hidden lg:flex items-center gap-4">
                <a href="#" class="text-[14px] font-medium text-gray-700 hover:text-black px-4 py-2 border border-gray-300 rounded-xl">Contact sales</a>
                <a href="#" class="text-[14px] font-medium text-white bg-[#1C1A19] hover:bg-black px-4 py-2 rounded-xl">Try rynude</a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col lg:flex-row w-full max-w-[1400px] mx-auto px-6 lg:px-10 gap-10 pb-10">
            <!-- Left Side -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center pt-10 lg:pt-0">
                <div class="text-center mb-8 max-w-md">
                    <h1 class="font-serif text-[42px] lg:text-[56px] leading-[1.1] text-[#2D2825] mb-4 tracking-tight">
                        Join rynude Design
                    </h1>
                    <p class="text-[18px] text-gray-600">
                        Create an account to build prototypes, slides, and websites.
                    </p>
                </div>

                <!-- Register Card -->
                <div class="w-full max-w-[360px] bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative">
                    <!-- Banner -->
                    <div class="bg-gradient-to-r from-[#EBF2FF] to-[#F2EFFF] py-3 px-4 text-center border-b border-gray-100">
                        <span class="bg-[#D1E0FF] text-[#1D4ED8] text-[11px] font-bold px-2 py-0.5 rounded-full mr-2">New</span>
                        <span class="text-[13px] text-gray-700 font-medium">Get started with rynude Design today</span>
                    </div>

                    <div class="p-6">
                        <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-2.5 border border-gray-200 rounded-2xl hover:bg-gray-50 transition-colors mb-6 shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span class="text-[15px] font-medium text-[#2D2825]">Sign up with Google</span>
                        </button>

                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-px bg-gray-200 flex-1"></div>
                            <span class="text-[11px] font-medium text-gray-400 tracking-wider">OR</span>
                            <div class="h-px bg-gray-200 flex-1"></div>
                        </div>

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="mb-3">
                                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your name" class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm" required autofocus>
                                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm" required>
                                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-3">
                                <input id="password" type="password" name="password" placeholder="Password" class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm" required>
                                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-4">
                                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm password" class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm" required>
                                @error('password_confirmation') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="w-full py-2.5 bg-[#1C1A19] hover:bg-black text-white rounded-2xl text-[14px] font-medium transition-colors shadow-md">
                                Create account
                            </button>
                        </form>

                        <p class="text-[11px] text-gray-400 text-center mt-6">
                            By registering, you acknowledge rynude's <a href="#" class="underline hover:text-gray-600">Privacy Policy</a>.
                        </p>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                            <p class="text-[13px] text-gray-500">
                                Already have an account? <a href="{{ route('login') }}" class="text-[#2D2825] font-medium underline hover:text-gray-600">Sign in</a>
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
                        <source src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Overlay content (mimics the 'Designi' badge) -->
                    <div class="relative z-10 bg-white rounded-2xl px-8 py-6 shadow-2xl flex items-center gap-3 animate-float border border-gray-100">
                        <svg class="w-8 h-8 text-[#D97757]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                        </svg>
                        <span class="font-serif text-[28px] font-medium text-[#2D2825] tracking-tight">Designi</span>
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

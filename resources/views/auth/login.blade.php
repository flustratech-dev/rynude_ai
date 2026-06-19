<x-guest-layout>
    <!-- Non-scrollable Fullscreen Wrapper -->
    <div class="h-[100dvh] w-full flex items-center justify-center bg-[#F9F8F6] overflow-hidden px-4 sm:px-6">
        
        <!-- Main Card (No Scrolling) -->
        <div class="w-full max-w-[1000px] h-[600px] bg-white rounded-[2rem] shadow-lg border border-[#E5E5E5] overflow-hidden flex flex-row relative">
            
            <!-- Left Side: Login Form -->
            <div class="w-full lg:w-[45%] h-full flex flex-col p-8 sm:p-10">
                <!-- Top Logo -->
                <div class="flex items-center gap-2 mb-8 shrink-0">
                    <svg class="w-6 h-6 text-[#D97757]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                    </svg>
                    <span class="font-serif text-[22px] font-medium text-[#2D2825]">rynude</span>
                </div>

                <div class="flex-1 flex flex-col justify-center">
                    <h1 class="font-serif text-[32px] leading-[1.1] text-[#2D2825] mb-2 tracking-tight">
                        Welcome back
                    </h1>
                    <p class="text-[14px] text-gray-500 mb-6">
                        Sign in to continue exploring and creating.
                    </p>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-2.5 border border-[#E5E5E5] rounded-xl hover:bg-gray-50 transition-colors mb-5">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="text-[14px] font-medium text-[#2D2825]">Continue with Google</span>
                    </button>

                    <div class="flex items-center gap-4 mb-5">
                        <div class="h-px bg-[#E5E5E5] flex-1"></div>
                        <span class="text-[11px] font-medium text-gray-400">OR</span>
                        <div class="h-px bg-[#E5E5E5] flex-1"></div>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-4">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all" required autofocus>
                            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <input id="password" type="password" name="password" placeholder="Password" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all" required>
                            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between mt-2 mb-4">
                            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                                <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-[#2D2825] shadow-sm focus:ring-[#2D2825]">
                                <span class="text-[12px] text-gray-500">Remember me</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-[12px] text-gray-500 hover:text-[#2D2825] transition-colors">Forgot password?</a>
                            @endif
                        </div>

                        <button type="submit" class="w-full py-3 bg-[#1C1A19] hover:bg-black text-white rounded-xl text-[14px] font-medium transition-colors">
                            Continue with email
                        </button>
                    </form>

                    <div class="mt-auto pt-4 border-t border-transparent text-center">
                        <p class="text-[12px] text-gray-500">
                            Don't have an account? <a href="{{ route('register') }}" class="text-[#2D2825] font-medium underline hover:text-gray-600">Sign up</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Animated Card Area -->
            <div class="hidden lg:flex w-[55%] bg-[#100F0F] relative items-center justify-center m-2 rounded-[1.5rem] overflow-hidden shadow-inner">
                <style>
                    @keyframes blob {
                        0% { transform: translate(0px, 0px) scale(1); }
                        33% { transform: translate(30px, -50px) scale(1.1); }
                        66% { transform: translate(-20px, 20px) scale(0.9); }
                        100% { transform: translate(0px, 0px) scale(1); }
                    }
                    @keyframes float {
                        0% { transform: translateY(0px); }
                        50% { transform: translateY(-10px); }
                        100% { transform: translateY(0px); }
                    }
                    @keyframes pulse-soft {
                        0% { opacity: 0.8; transform: scale(0.98); }
                        50% { opacity: 1; transform: scale(1.02); }
                        100% { opacity: 0.8; transform: scale(0.98); }
                    }
                    .animate-blob { animation: blob 8s infinite ease-in-out; }
                    .animation-delay-2000 { animation-delay: 2s; }
                    .animation-delay-4000 { animation-delay: 4s; }
                    .animate-float { animation: float 6s infinite ease-in-out; }
                    .animate-pulse-soft { animation: pulse-soft 4s infinite ease-in-out; }
                </style>

                <!-- Base Dark Gradient -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#1A1817] to-[#0A0909]"></div>

                <!-- Animated Abstract Blobs -->
                <div class="absolute inset-0 flex items-center justify-center opacity-60 mix-blend-screen pointer-events-none">
                    <div class="absolute w-[400px] h-[400px] bg-[#D97757] rounded-full filter blur-[100px] animate-blob opacity-40"></div>
                    <div class="absolute w-[350px] h-[350px] bg-[#E89377] rounded-full filter blur-[120px] animate-blob animation-delay-2000 opacity-30 right-1/4 top-1/4"></div>
                    <div class="absolute w-[500px] h-[500px] bg-[#8B7C75] rounded-full filter blur-[140px] animate-blob animation-delay-4000 opacity-20 left-1/4 bottom-1/4"></div>
                </div>
                
                <!-- Fine Noise Texture Overlay -->
                <div class="absolute inset-0 opacity-[0.04] mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

                <!-- Glassmorphic Grid Overlay -->
                <div class="absolute inset-0 pointer-events-none" style="background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px); background-size: 64px 64px; transform: perspective(1000px) rotateX(60deg) scale(2.5) translateY(-20%); transform-origin: center top;"></div>

                <!-- Center Focus Floating Element -->
                <div class="relative z-10 animate-float">
                    <div class="absolute inset-0 bg-[#D97757] blur-[50px] opacity-20 animate-pulse-soft rounded-full"></div>
                    <div class="relative bg-white/10 backdrop-blur-xl border border-white/20 rounded-[1.25rem] px-8 py-6 shadow-2xl flex flex-col items-center gap-3">
                        <svg class="w-10 h-10 text-[#D97757] animate-pulse-soft" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                        </svg>
                        <span class="font-serif text-[24px] font-medium text-white tracking-wide">rynude AI</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-guest-layout>

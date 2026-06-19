<x-guest-layout>
    <!-- Non-scrollable Fullscreen Wrapper -->
    <div class="h-[100dvh] w-full flex items-center justify-center bg-[#F9F8F6] overflow-hidden px-4 sm:px-6">
        
        <!-- Main Card (No Scrolling) -->
        <div class="w-full max-w-[1000px] max-h-[700px] lg:h-[650px] bg-white rounded-[2rem] shadow-lg border border-[#E5E5E5] flex flex-row relative overflow-hidden">
            
            <!-- Left Side: Register Form -->
            <div class="w-full lg:w-[45%] h-full flex flex-col p-8 sm:p-10 overflow-y-auto scrollbar-hide">
                <!-- Top Logo -->
                <div class="flex items-center gap-2 mb-6 shrink-0">
                    <svg class="w-6 h-6 text-[#D97757]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l2.4 7.6H22l-6.2 4.5 2.4 7.6-6.2-4.5-6.2 4.5 2.4-7.6L2 9.6h7.6z"/>
                    </svg>
                    <span class="font-serif text-[22px] font-medium text-[#2D2825]">rynude</span>
                </div>

                <div class="flex-1 flex flex-col justify-center">
                    <h1 class="font-serif text-[32px] leading-[1.1] text-[#2D2825] mb-2 tracking-tight">
                        Create Account
                    </h1>
                    <p class="text-[14px] text-gray-500 mb-6">
                        Join rynude to start exploring and creating.
                    </p>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        
                        <div class="mb-4">
                            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your name" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all" required autofocus>
                            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all" required>
                            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <input id="password" type="password" name="password" placeholder="Password" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all" required>
                            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-6">
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm password" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] text-[14px] text-[#2D2825] placeholder-gray-400 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all" required>
                            @error('password_confirmation') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="w-full py-3 bg-[#1C1A19] hover:bg-black text-white rounded-xl text-[14px] font-medium transition-colors mb-4">
                            Create account
                        </button>
                    </form>

                    <div class="mt-auto pt-4 border-t border-transparent text-center">
                        <p class="text-[12px] text-gray-500">
                            Already have an account? <a href="{{ route('login') }}" class="text-[#2D2825] font-medium underline hover:text-gray-600">Sign in</a>
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

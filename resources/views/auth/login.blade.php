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
                        Meet rynude Design
                    </h1>
                    <p class="text-[18px] text-gray-600">
                        Prototypes, slides, and websites, built with your design system.
                    </p>
                </div>

                <!-- Login Card -->
                <div class="w-full max-w-[400px] bg-gradient-to-b from-[#E2EEFF] to-[#F0F5FF] rounded-[32px] border border-[#C2D6FF] overflow-hidden relative shadow-sm">
                    <!-- Banner -->
                    <div class="pt-5 pb-7 px-4 text-center flex items-center justify-center gap-2">
                        <span class="bg-[#C6DCFF] text-[#1D4ED8] text-[12px] font-semibold px-2.5 py-0.5 rounded-md">New</span>
                        <span class="text-[14px] text-[#2563EB] font-medium">rynude Design: available for Max plans</span>
                    </div>

                    <div class="bg-[#FFFDF9] rounded-t-[32px] p-8 border-t border-[#E5E7EB] shadow-[0_-4px_10px_rgba(0,0,0,0.02)] flex flex-col">
                        <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-3.5 border border-gray-300 rounded-2xl hover:bg-gray-50 transition-colors mb-6 shadow-sm bg-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span class="text-[15px] font-semibold text-[#2D2825]">Continue with Google</span>
                        </button>

                        <div class="text-center text-[12px] font-medium text-gray-500 uppercase tracking-wider mb-6">
                            OR
                        </div>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-6">
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" class="w-full px-4 py-3.5 rounded-2xl border border-gray-300 text-[15px] text-[#2D2825] placeholder-gray-500 focus:outline-none focus:border-[#2D2825] focus:ring-1 focus:ring-[#2D2825] transition-all shadow-sm bg-white" required autofocus>
                                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="w-full py-3.5 bg-[#1C1A19] hover:bg-black text-white rounded-2xl text-[16px] font-semibold transition-colors shadow-md">
                                Continue with email
                            </button>
                        </form>

                        <p class="text-[13px] text-gray-500 text-center mt-6">
                            By continuing, you acknowledge rynude's <a href="#" class="underline hover:text-gray-700">Privacy Policy</a>.
                        </p>
                        
                        <div class="mt-6 pt-5 border-t border-gray-100 text-center w-full">
                            <p class="text-[14px] text-gray-600">
                                Don't have an account? <a href="{{ route('register') }}" class="text-[#2D2825] font-semibold underline hover:text-gray-800">Sign up</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="button" class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors bg-white shadow-sm text-sm font-medium text-gray-700">
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

        {{-- ===== Landing sections (below the login form) ===== --}}
        {{-- Hero --}}
        <section>
            <div class="max-w-6xl mx-auto px-6 pt-20 pb-16 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-gray-200 text-[12.5px] text-gray-600 mb-7 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#D97757]"></span>
                    Now with Cowork, Design &amp; multi-model chat
                </div>
                <h1 class="font-claude-response text-[44px] md:text-[60px] leading-[1.05] font-medium tracking-tight max-w-3xl mx-auto">
                    The AI workspace that<br>
                    <span class="text-[#D97757]">works the way you think</span>
                </h1>
                <p class="text-[16px] md:text-[18px] text-gray-500 max-w-2xl mx-auto mt-6 leading-relaxed">
                    Chat, build artifacts, hand off tasks, and design — all in one place. Bring your own keys for Rynude, GPT, Gemini, and Mistral.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-9">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-6 py-3 bg-[#D97757] hover:bg-[#c56647] text-white rounded-xl text-[15px] font-medium transition-colors shadow-sm">
                        Start for free
                    </a>
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-6 py-3 bg-white border border-gray-200 hover:border-gray-300 rounded-xl text-[15px] font-medium transition-colors shadow-sm">
                        Get started
                    </a>
                </div>

                {{-- Hero mock window --}}
                <div class="floaty mt-16 max-w-4xl mx-auto">
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-[0_30px_80px_-20px_rgba(0,0,0,0.18)] overflow-hidden text-left">
                        <div class="flex items-center gap-1.5 px-4 py-3 border-b border-gray-100">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-end">
                                <div class="bg-[#EAE9E5] rounded-2xl rounded-br-sm px-4 py-2.5 text-[13.5px] max-w-[75%]">Design a pricing page for my SaaS and draft a launch email.</div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-lg bg-[#D97757]/15 flex items-center justify-center text-[#D97757] flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6z"/></svg>
                                </div>
                                <div class="space-y-2 flex-1">
                                    <div class="h-2.5 bg-gray-100 rounded-full w-full"></div>
                                    <div class="h-2.5 bg-gray-100 rounded-full w-[85%]"></div>
                                    <div class="h-2.5 bg-gray-100 rounded-full w-[60%]"></div>
                                    <div class="flex gap-2 pt-1">
                                        <span class="text-[11px] px-2 py-1 rounded-md bg-[#D97757]/10 text-[#D97757] font-medium">Artifact: pricing.html</span>
                                        <span class="text-[11px] px-2 py-1 rounded-md bg-blue-500/10 text-blue-600 font-medium">Task queued</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="max-w-6xl mx-auto w-full px-6 py-20">
            <div class="text-center mb-12">
                <h2 class="font-claude-response text-[34px] font-medium tracking-tight">Everything in one workspace</h2>
                <p class="text-gray-500 mt-3 text-[15.5px]">A focused set of tools that work together.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['title' => 'Multi-model chat', 'desc' => 'Rynude, GPT, Gemini, and Mistral side by side. Bring your own API keys.', 'color' => 'text-[#D97757]', 'bg' => 'bg-[#D97757]/10', 'path' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
                        ['title' => 'Live artifacts', 'desc' => 'Generate code and HTML with syntax highlighting, live preview, and versions.', 'color' => 'text-blue-600', 'bg' => 'bg-blue-500/10', 'path' => 'M16 18l6-6-6-6M8 6l-6 6 6 6'],
                        ['title' => 'Cowork tasks', 'desc' => 'Hand off complex work, assign a model, and come back to a finished result.', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-500/10', 'path' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
                        ['title' => 'Design generation', 'desc' => 'Describe what you want and get a ready-to-use, live-previewed design.', 'color' => 'text-fuchsia-600', 'bg' => 'bg-fuchsia-500/10', 'path' => 'M3 3h18v18H3zM3 9h18M9 21V9'],
                        ['title' => 'Projects &amp; skills', 'desc' => 'Organize chats into projects with custom instructions and reusable skills.', 'color' => 'text-amber-600', 'bg' => 'bg-amber-500/10', 'path' => 'M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'],
                        ['title' => 'Usage tracking', 'desc' => 'See real token usage per model so you always know where your spend goes.', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-500/10', 'path' => 'M18 20V10M12 20V4M6 20v-6'],
                    ];
                @endphp
                @foreach($features as $f)
                    <div class="group bg-white border border-gray-200 rounded-2xl p-6 hover:border-gray-300 hover:shadow-lg transition-all">
                        <div class="w-11 h-11 rounded-xl {{ $f['bg'] }} {{ $f['color'] }} flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $f['path'] }}"/></svg>
                        </div>
                        <h3 class="text-[16px] font-semibold mb-1.5">{!! $f['title'] !!}</h3>
                        <p class="text-[13.5px] text-gray-500 leading-relaxed">{!! $f['desc'] !!}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Models --}}
        <section id="models" class="border-y border-gray-200 bg-[#FFFDF9]">
            <div class="max-w-6xl mx-auto px-6 py-20 text-center">
                <h2 class="font-claude-response text-[34px] font-medium tracking-tight">Your favorite models, one interface</h2>
                <p class="text-gray-500 mt-3 text-[15.5px] max-w-2xl mx-auto">Switch providers without switching tools. Add your keys in Settings and start chatting.</p>
                <div class="flex flex-wrap items-center justify-center gap-3 mt-10">
                    @foreach(['Rynude Opus 4.8', 'Rynude Sonnet 4.6', 'GPT', 'Gemini 2.5 Pro', 'Mistral Large', 'Codestral'] as $m)
                        <span class="px-5 py-2.5 bg-white border border-gray-200 rounded-full text-[14px] font-medium shadow-sm">{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Workspace CTA --}}
        <section id="workspace" class="max-w-6xl mx-auto w-full px-6 py-24">
            <div class="rounded-3xl border border-gray-800 px-8 py-16 text-center relative overflow-hidden">
                <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover">
                    <source src="{{ asset('video/video_halaman_utama.mp4') }}" type="video/mp4">
                </video>
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="relative">
                    <h2 class="font-claude-response text-[36px] md:text-[42px] font-medium text-white tracking-tight max-w-2xl mx-auto leading-tight">Ready to build with Rynude?</h2>
                    <p class="text-gray-300 mt-4 text-[16px] max-w-xl mx-auto">Start free in seconds. No credit card required.</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                        <a href="{{ route('register') }}" class="w-full sm:w-auto px-7 py-3 bg-[#D97757] hover:bg-[#c56647] text-white rounded-xl text-[15px] font-medium transition-colors shadow-lg">
                            Create your account
                        </a>
                        <a href="{{ route('register') }}" class="w-full sm:w-auto px-7 py-3 bg-white/10 hover:bg-white/15 border border-white/15 text-white rounded-xl text-[15px] font-medium transition-colors">Get started</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-gray-200">
            <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo_rynudee.png') }}" alt="Rynude" class="w-6 h-6 rounded-md object-contain">
                    <span class="font-claude-response text-[16px] font-medium">Rynude</span>
                </div>
                <div class="flex items-center gap-6 text-[13.5px] text-gray-500">
                    <a href="#features" class="hover:text-[#2D2825] transition-colors">Features</a>
                    <a href="#models" class="hover:text-[#2D2825] transition-colors">Models</a>
                    <a href="#workspace" class="hover:text-[#2D2825] transition-colors">Workspace</a>
                </div>
                <p class="text-[12.5px] text-gray-400">&copy; {{ date('Y') }} Rynude. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <style>
        .hero-gradient {
            background:
                radial-gradient(900px 500px at 15% -10%, rgba(217,119,87,0.18), transparent 60%),
                radial-gradient(700px 500px at 95% 0%, rgba(94,114,228,0.12), transparent 55%);
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .animate-float { animation: float 6s infinite ease-in-out; }
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .floaty { animation: floaty 6s ease-in-out infinite; }
    </style>
</x-guest-layout>

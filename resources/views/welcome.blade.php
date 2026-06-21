<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Rynude') }} — Your AI workspace</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (function () {
                const t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        <style>
            .hero-gradient {
                background:
                    radial-gradient(900px 500px at 15% -10%, rgba(217,119,87,0.18), transparent 60%),
                    radial-gradient(700px 500px at 95% 0%, rgba(94,114,228,0.12), transparent 55%);
            }
            .dark .hero-gradient {
                background:
                    radial-gradient(900px 500px at 15% -10%, rgba(217,119,87,0.22), transparent 60%),
                    radial-gradient(700px 500px at 95% 0%, rgba(94,114,228,0.16), transparent 55%);
            }
            @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
            .floaty { animation: floaty 6s ease-in-out infinite; }
            @keyframes fadeup { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform:none; } }
            .fadeup { animation: fadeup .7s ease-out both; }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#F9F8F6] dark:bg-stone-950 text-[#2D2825] dark:text-stone-100">

        {{-- ===== Nav ===== --}}
        <header class="sticky top-0 z-30 backdrop-blur-md bg-[#F9F8F6]/80 dark:bg-stone-950/80 border-b border-stone-200/70 dark:border-stone-800/70">
            <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo_rynudee.png') }}" alt="Rynude" class="w-8 h-8 rounded-lg object-contain">
                    <span class="font-serif text-[20px] font-medium tracking-tight">Rynude</span>
                </a>
                <nav class="hidden md:flex items-center gap-7 text-[14px] text-stone-600 dark:text-stone-300">
                    <a href="#features" class="hover:text-[#2D2825] dark:hover:text-white transition-colors">Features</a>
                    <a href="#models" class="hover:text-[#2D2825] dark:hover:text-white transition-colors">Models</a>
                    <a href="#workspace" class="hover:text-[#2D2825] dark:hover:text-white transition-colors">Workspace</a>
                </nav>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('chat') }}" class="px-4 py-2 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13.5px] font-medium transition-colors shadow-sm">Open app</a>
                    @else
                        <a href="{{ route('login') }}" class="text-[13.5px] text-stone-600 dark:text-stone-300 hover:text-[#2D2825] dark:hover:text-white transition-colors">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-4 py-2 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13.5px] font-medium transition-colors shadow-sm">Get started</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        {{-- ===== Hero ===== --}}
        <section class="hero-gradient">
            <div class="max-w-6xl mx-auto px-6 pt-20 pb-16 text-center">
                <div class="fadeup inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 text-[12.5px] text-stone-600 dark:text-stone-300 mb-7 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#D97757]"></span>
                    Now with Cowork, Design &amp; multi-model chat
                </div>
                <h1 class="fadeup font-serif text-[44px] md:text-[60px] leading-[1.05] font-medium tracking-tight max-w-3xl mx-auto">
                    The AI workspace that<br>
                    <span class="text-[#D97757]">works the way you think</span>
                </h1>
                <p class="fadeup text-[16px] md:text-[18px] text-stone-500 dark:text-stone-400 max-w-2xl mx-auto mt-6 leading-relaxed">
                    Chat, build artifacts, hand off tasks, and design — all in one place. Bring your own keys for Claude, GPT, Gemini, and Mistral.
                </p>
                <div class="fadeup flex flex-col sm:flex-row items-center justify-center gap-3 mt-9">
                    <a href="{{ auth()->check() ? route('chat') : route('register') }}" class="w-full sm:w-auto px-6 py-3 bg-[#D97757] hover:bg-[#c56647] text-white rounded-xl text-[15px] font-medium transition-colors shadow-sm">
                        {{ auth()->check() ? 'Open Rynude' : 'Start for free' }}
                    </a>
                    <a href="{{ route('chat') }}" class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 hover:border-stone-300 dark:hover:border-stone-600 rounded-xl text-[15px] font-medium transition-colors shadow-sm">
                        Try the chat
                    </a>
                </div>

                {{-- Hero mock window --}}
                <div class="fadeup floaty mt-16 max-w-4xl mx-auto">
                    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.18)] overflow-hidden text-left">
                        <div class="flex items-center gap-1.5 px-4 py-3 border-b border-stone-100 dark:border-stone-800">
                            <span class="w-3 h-3 rounded-full bg-red-400"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-end">
                                <div class="bg-[#EAE9E5] dark:bg-stone-800 rounded-2xl rounded-br-sm px-4 py-2.5 text-[13.5px] max-w-[75%]">Design a pricing page for my SaaS and draft a launch email.</div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-lg bg-[#D97757]/15 flex items-center justify-center text-[#D97757] flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6z"/></svg>
                                </div>
                                <div class="space-y-2 flex-1">
                                    <div class="h-2.5 bg-stone-100 dark:bg-stone-800 rounded-full w-full"></div>
                                    <div class="h-2.5 bg-stone-100 dark:bg-stone-800 rounded-full w-[85%]"></div>
                                    <div class="h-2.5 bg-stone-100 dark:bg-stone-800 rounded-full w-[60%]"></div>
                                    <div class="flex gap-2 pt-1">
                                        <span class="text-[11px] px-2 py-1 rounded-md bg-[#D97757]/10 text-[#D97757] font-medium">Artifact: pricing.html</span>
                                        <span class="text-[11px] px-2 py-1 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 font-medium">Task queued</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Features ===== --}}
        <section id="features" class="max-w-6xl mx-auto px-6 py-20">
            <div class="text-center mb-12">
                <h2 class="font-serif text-[34px] font-medium tracking-tight">Everything in one workspace</h2>
                <p class="text-stone-500 dark:text-stone-400 mt-3 text-[15.5px]">A focused set of tools that work together.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['title' => 'Multi-model chat', 'desc' => 'Claude, GPT, Gemini, and Mistral side by side. Bring your own API keys.', 'color' => 'text-[#D97757]', 'bg' => 'bg-[#D97757]/10', 'path' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
                        ['title' => 'Live artifacts', 'desc' => 'Generate code and HTML with syntax highlighting, live preview, and versions.', 'color' => 'text-blue-600 dark:text-blue-400', 'bg' => 'bg-blue-500/10', 'path' => 'M16 18l6-6-6-6M8 6l-6 6 6 6'],
                        ['title' => 'Cowork tasks', 'desc' => 'Hand off complex work, assign a model, and come back to a finished result.', 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-500/10', 'path' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
                        ['title' => 'Design generation', 'desc' => 'Describe what you want and get a ready-to-use, live-previewed design.', 'color' => 'text-fuchsia-600 dark:text-fuchsia-400', 'bg' => 'bg-fuchsia-500/10', 'path' => 'M3 3h18v18H3zM3 9h18M9 21V9'],
                        ['title' => 'Projects &amp; skills', 'desc' => 'Organize chats into projects with custom instructions and reusable skills.', 'color' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-500/10', 'path' => 'M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'],
                        ['title' => 'Usage tracking', 'desc' => 'See real token usage per model so you always know where your spend goes.', 'color' => 'text-indigo-600 dark:text-indigo-400', 'bg' => 'bg-indigo-500/10', 'path' => 'M18 20V10M12 20V4M6 20v-6'],
                    ];
                @endphp
                @foreach($features as $f)
                    <div class="group bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl p-6 hover:border-stone-300 dark:hover:border-stone-700 hover:shadow-lg transition-all">
                        <div class="w-11 h-11 rounded-xl {{ $f['bg'] }} {{ $f['color'] }} flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $f['path'] }}"/></svg>
                        </div>
                        <h3 class="text-[16px] font-semibold mb-1.5">{!! $f['title'] !!}</h3>
                        <p class="text-[13.5px] text-stone-500 dark:text-stone-400 leading-relaxed">{!! $f['desc'] !!}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ===== Models ===== --}}
        <section id="models" class="border-y border-stone-200 dark:border-stone-800 bg-white/50 dark:bg-stone-900/40">
            <div class="max-w-6xl mx-auto px-6 py-20 text-center">
                <h2 class="font-serif text-[34px] font-medium tracking-tight">Your favorite models, one interface</h2>
                <p class="text-stone-500 dark:text-stone-400 mt-3 text-[15.5px] max-w-2xl mx-auto">Switch providers without switching tools. Add your keys in Settings and start chatting.</p>
                <div class="flex flex-wrap items-center justify-center gap-3 mt-10">
                    @foreach(['Claude Opus 4.8', 'Claude Sonnet 4.6', 'GPT', 'Gemini 2.5 Pro', 'Mistral Large', 'Codestral'] as $m)
                        <span class="px-5 py-2.5 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-full text-[14px] font-medium shadow-sm">{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== Workspace CTA ===== --}}
        <section id="workspace" class="max-w-6xl mx-auto px-6 py-24">
            <div class="rounded-3xl bg-[#191919] dark:bg-stone-900 border border-stone-800 px-8 py-16 text-center relative overflow-hidden">
                <div class="hero-gradient absolute inset-0 opacity-60"></div>
                <div class="relative">
                    <h2 class="font-serif text-[36px] md:text-[42px] font-medium text-white tracking-tight max-w-2xl mx-auto leading-tight">Ready to build with Rynude?</h2>
                    <p class="text-stone-300 mt-4 text-[16px] max-w-xl mx-auto">Start free in seconds. No credit card required.</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                        <a href="{{ auth()->check() ? route('chat') : route('register') }}" class="w-full sm:w-auto px-7 py-3 bg-[#D97757] hover:bg-[#c56647] text-white rounded-xl text-[15px] font-medium transition-colors shadow-lg">
                            {{ auth()->check() ? 'Open Rynude' : 'Create your account' }}
                        </a>
                        <a href="{{ route('chat') }}" class="w-full sm:w-auto px-7 py-3 bg-white/10 hover:bg-white/15 border border-white/15 text-white rounded-xl text-[15px] font-medium transition-colors">Explore the chat</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Footer ===== --}}
        <footer class="border-t border-stone-200 dark:border-stone-800">
            <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo_rynudee.png') }}" alt="Rynude" class="w-6 h-6 rounded-md object-contain">
                    <span class="font-serif text-[16px] font-medium">Rynude</span>
                </div>
                <div class="flex items-center gap-6 text-[13.5px] text-stone-500 dark:text-stone-400">
                    <a href="{{ route('chat') }}" class="hover:text-[#2D2825] dark:hover:text-white transition-colors">Chat</a>
                    <a href="#features" class="hover:text-[#2D2825] dark:hover:text-white transition-colors">Features</a>
                    @guest<a href="{{ route('login') }}" class="hover:text-[#2D2825] dark:hover:text-white transition-colors">Log in</a>@endguest
                </div>
                <p class="text-[12.5px] text-stone-400 dark:text-stone-500">&copy; {{ date('Y') }} Rynude. All rights reserved.</p>
            </div>
        </footer>
    </body>
</html>

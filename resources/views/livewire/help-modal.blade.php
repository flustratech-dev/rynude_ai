<div
    x-data="{ open: $wire.entangle('isOpen') }"
    x-init="
        $watch('open', value => {
            if (value) {
                document.body.classList.add('overflow-hidden');
            } else {
                document.body.classList.remove('overflow-hidden');
            }
        });
    "
    @keydown.escape.window="open = false; $wire.closeModal()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-[#1F1E1B]/40 backdrop-blur-sm"
        @click="$wire.closeModal()"
    ></div>

    {{-- Modal --}}
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="$wire.closeModal()"
            class="bg-white w-full max-w-2xl max-h-[80vh] rounded-[1.5rem] shadow-2xl flex flex-col overflow-hidden border border-[#E5E5E5] relative"
        >
            {{-- Close Button --}}
            <button
                @click="$wire.closeModal()"
                class="absolute top-4 right-4 z-10 p-1.5 rounded-lg text-[#A3A3A3] hover:text-[#2D2825] hover:bg-[#F3F3F3] transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Tab Headers --}}
            <div class="flex border-b border-[#E5E5E5] px-6 pt-4">
                <button
                    wire:click="$set('activeTab', 'help')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'help' ? 'border-claude-800 text-claude-800' : 'border-transparent text-claude-400 hover:text-claude-600' }}"
                >
                    Get Help
                </button>
                <button
                    wire:click="$set('activeTab', 'apps')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'apps' ? 'border-claude-800 text-claude-800' : 'border-transparent text-claude-400 hover:text-claude-600' }}"
                >
                    Apps & Extensions
                </button>
            </div>

            {{-- Content --}}
            <div class="flex-1 overflow-y-auto p-6">
                {{-- Help Tab --}}
                @if($activeTab === 'help')
                    <h2 class="font-serif text-2xl text-claude-800 mb-2">How can we help?</h2>
                    <p class="text-sm text-claude-500 mb-6">Find answers to common questions or reach out to our support team.</p>

                    {{-- FAQ Items --}}
                    <div class="space-y-2">
                        @php
                            $faqs = [
                                ['id' => 'what-is', 'question' => 'What is Rynude?', 'answer' => 'Rynude is an AI-powered chat interface, designed to help you with writing, analysis, coding, math, and more. It provides a sleek, centralized experience for all your AI interactions.'],
                                ['id' => 'byok', 'question' => 'What is BYOK (Bring Your Own Key)?', 'answer' => 'BYOK allows you to use your own Anthropic API key instead of the system-provided quota. This gives you direct access to Rynude with your own billing and higher rate limits.'],
                                ['id' => 'artifacts', 'question' => 'What are Artifacts?', 'answer' => 'Artifacts are self-contained pieces of content that Rynude creates for you — code snippets, documents, analyses, and more. They appear in a side panel and can be copied, downloaded, or referenced in follow-up messages.'],
                                ['id' => 'projects', 'question' => 'What are Projects?', 'answer' => 'Projects let you organize related chats together. Create a project for a specific topic or task, and all associated conversations will be grouped in one place.'],
                                ['id' => 'models', 'question' => 'Which Claude models are available?', 'answer' => 'Rynude supports Claude Opus 4, Claude Sonnet 4, and Claude Haiku 3.5. Each model offers different capabilities — Opus for the most complex tasks, Sonnet for balanced performance, and Haiku for fast responses.'],
                            ];
                        @endphp

                        @foreach($faqs as $faq)
                            <div class="rounded-xl border border-[#E5E5E5] overflow-hidden">
                                <button
                                    wire:click="toggleFaq('{{ $faq['id'] }}')"
                                    class="w-full flex items-center justify-between px-4 py-3 text-left text-[15px] text-claude-700 hover:bg-[#F9F8F6] transition-colors"
                                >
                                    <span class="font-medium">{{ $faq['question'] }}</span>
                                    <svg
                                        class="w-4 h-4 text-claude-400 flex-shrink-0 transition-transform duration-200 {{ $expandedFaq === $faq['id'] ? 'rotate-180' : '' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                    </svg>
                                </button>
                                @if($expandedFaq === $faq['id'])
                                    <div class="px-4 pb-4 text-sm text-claude-500 leading-relaxed">
                                        {{ $faq['answer'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Contact Support --}}
                    <div class="mt-8 p-4 rounded-xl bg-[#F9F8F6] border border-[#E5E5E5]">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#E5E5E5] flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-claude-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-claude-700">Still need help?</p>
                                <a href="mailto:support@rynude.com" class="text-sm text-claude-500 hover:text-claude-700 transition-colors">Contact support →</a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Apps Tab --}}
                @if($activeTab === 'apps')
                    <h2 class="font-serif text-2xl text-claude-800 mb-2">Apps & Extensions</h2>
                    <p class="text-sm text-claude-500 mb-6">Access Rynude across all your devices and platforms.</p>

                    <div class="space-y-4">
                        {{-- Chrome Extension --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-[#E5E5E5] hover:border-claude-300 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-[#F9F8F6] border border-[#E5E5E5] flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-claude-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-[15px] font-medium text-claude-800">Chrome Extension</h3>
                                <p class="text-sm text-claude-500">Access Rynude from any webpage with our browser extension.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-claude-100 text-claude-600 flex-shrink-0">Coming Soon</span>
                        </div>

                        {{-- VS Code Extension --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-[#E5E5E5] hover:border-claude-300 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-[#F9F8F6] border border-[#E5E5E5] flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-claude-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-[15px] font-medium text-claude-800">VS Code Extension</h3>
                                <p class="text-sm text-claude-500">AI-powered coding assistance directly in your editor.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-claude-100 text-claude-600 flex-shrink-0">Coming Soon</span>
                        </div>

                        {{-- Mobile App --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-[#E5E5E5] hover:border-claude-300 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-[#F9F8F6] border border-[#E5E5E5] flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-claude-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-[15px] font-medium text-claude-800">Mobile App</h3>
                                <p class="text-sm text-claude-500">Chat with Rynude on iOS and Android.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-claude-100 text-claude-600 flex-shrink-0">Coming Soon</span>
                        </div>

                        {{-- API Access --}}
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-[#E5E5E5] hover:border-claude-300 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-[#F9F8F6] border border-[#E5E5E5] flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-claude-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-[15px] font-medium text-claude-800">API Access</h3>
                                <p class="text-sm text-claude-500">Integrate Rynude into your own applications via API.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600 flex-shrink-0">Available</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div
    class="flex w-full h-screen overflow-hidden font-sans bg-claude-bg-light dark:bg-claude-bg-dark text-[#2D2825] dark:text-stone-200"
    x-data="claudeCodeState()"
    x-init="init()"
>
    <style>
        @keyframes rynude-bob {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25%      { transform: translateY(-3px) rotate(-5deg); }
            50%      { transform: translateY(0) rotate(0deg); }
            75%      { transform: translateY(-3px) rotate(5deg); }
        }
        .rynude-mascot { animation: rynude-bob 2.4s ease-in-out infinite; transform-origin: bottom center; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    {{-- Left Sidebar --}}
    <div x-show="sidebarOpen" class="w-[300px] m-3 rounded-2xl border border-[#E5E5E5] dark:border-stone-800 bg-[#F9F8F6] dark:bg-stone-900 flex flex-col transition-all duration-300 shadow-sm overflow-hidden h-[calc(100vh-24px)] flex-shrink-0">
        
        {{-- Sidebar Header --}}
        <div class="flex items-center justify-between px-3 py-3 mt-1">
            <div class="flex items-center gap-2">
                <a href="/" class="font-serif text-[17px] font-medium text-[#2D2825] dark:text-stone-100 whitespace-nowrap hover:opacity-80 transition-opacity">Rynude Code</a>
                <span class="text-[10px] font-medium px-1.5 py-0.5 bg-[#EAE9E5] dark:bg-stone-800 text-stone-600 dark:text-stone-300 rounded-[4px] whitespace-nowrap">Research preview</span>
            </div>
            <div class="flex items-center gap-1 text-stone-400">
                <button @click="sidebarOpen = false" class="group p-1 hover:bg-stone-200 dark:hover:bg-stone-800 rounded-md transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line class="transition-transform duration-300 group-hover:-translate-x-0.5" x1="9" y1="3" x2="9" y2="21"></line></svg>
                </button>
            </div>
        </div>

        {{-- Sidebar Menu --}}
        <div class="px-2 mt-2 space-y-0.5">
            <button @click="newSession()" class="group w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] font-medium transition-colors hover:bg-[#EAE9E5] dark:hover:bg-stone-800" :class="currentView==='chat'&&!isStarted?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200':'text-stone-600 dark:text-stone-400'">
                <span class="text-stone-400 font-normal inline-block transition-transform duration-300 group-hover:rotate-90">+</span> New session
            </button>
            <button @click="currentView='routines'" class="group w-full flex items-center gap-2.5 px-2 py-1.5 text-stone-600 dark:text-stone-400 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg text-[13px] transition-colors" :class="currentView==='routines'?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200':''">
                <svg class="w-3.5 h-3.5 transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:scale-110 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg> Routines
            </button>
            <a href="/chat?panel=customize" class="group w-full flex items-center gap-2.5 px-2 py-1.5 text-stone-600 dark:text-stone-400 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg text-[13px] transition-colors">
                <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-500 group-hover:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6M4.93 19.07l4.24-4.24M14.83 9.17l4.24-4.24"></path></svg> Customize
            </a>
            
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" class="group w-full flex items-center gap-2.5 px-2 py-1.5 text-stone-600 dark:text-stone-400 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg text-[13px] transition-colors">
                    <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-300 group-hover:translate-y-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg> More
                </button>
                
                <div x-show="open" style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute left-0 top-full mt-1 w-48 bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 rounded-xl shadow-lg z-50 py-1">
                    <button class="w-full text-left px-3 py-2 hover:bg-stone-50 dark:hover:bg-stone-800 text-[13px] text-[#2D2825] dark:text-stone-200 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="7" y="4" width="10" height="16" rx="2" ry="2"></rect><path d="M12 2v2"></path><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            Dispatch
                        </div>
                        <span class="text-[10px] font-medium px-1.5 py-0.5 bg-[#F5F5F5] dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-md">Beta</span>
                    </button>
                    <div class="my-1 border-t border-[#E5E5E5] dark:border-stone-800 mx-3"></div>
                    <a href="/chat?panel=customize" class="w-full text-left px-3 py-2 hover:bg-stone-50 dark:hover:bg-stone-800 text-[13px] text-[#2D2825] dark:text-stone-200 flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        Customize sidebar
                    </a>
                </div>
            </div>
        </div>

        {{-- Recents --}}
        <div class="mt-6 px-3 flex-1 overflow-y-auto scrollbar-hide">
            <div class="flex items-center justify-between text-[11px] font-medium text-stone-400 uppercase tracking-wider mb-2">
                <span>Recents</span>
            </div>
            
            <div class="space-y-0.5">
                <template x-for="session in recentSessions" :key="session.id">
                    <div class="group relative flex items-center rounded-lg" :class="conversationId===session.id?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200':''">
                        <button @click="loadSession(session.id)" class="flex-1 flex items-center gap-2 px-2 py-2 text-left min-w-0">
                            <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="conversationId===session.id?'bg-[#D97757] animate-pulse':'bg-stone-300 dark:bg-stone-700'"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] truncate font-medium" :class="conversationId===session.id?'text-[#2D2825] dark:text-stone-100':'text-stone-600 dark:text-stone-400'" x-text="session.title"></p>
                            </div>
                        </button>
                        <button @click.stop="deleteSession(session.id)" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-stone-200 dark:hover:bg-stone-700 transition-all text-stone-400 hover:text-red-500 absolute right-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>
                </template>
                <div x-show="recentSessions.length === 0" class="text-[12px] text-stone-400 italic px-1 py-1">
                    No recent sessions
                </div>
            </div>
        </div>

        {{-- Profile --}}
        <div class="mt-auto border-t border-[#E5E5E5] dark:border-stone-800 p-2 relative" x-data="{ profileMenuOpen: false }">
            <button @click="profileMenuOpen = !profileMenuOpen" @click.away="profileMenuOpen = false" class="w-full flex items-center justify-between px-2 py-2 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg transition-colors">
                <div class="flex items-center gap-2.5">
                    <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="8" cy="8" r="8" fill="#5C92D1"/>
                        <rect x="6" y="6" width="4" height="4" fill="#DBE0ED"/>
                        <rect x="7" y="3" width="2" height="3" fill="#DBE0ED"/>
                        <rect x="7" y="10" width="2" height="3" fill="#DBE0ED"/>
                        <rect x="3" y="7" width="3" height="2" fill="#DBE0ED"/>
                        <rect x="10" y="7" width="3" height="2" fill="#DBE0ED"/>
                        <rect x="4" y="4" width="2" height="2" fill="#DBE0ED"/>
                        <rect x="10" y="4" width="2" height="2" fill="#DBE0ED"/>
                        <rect x="4" y="10" width="2" height="2" fill="#DBE0ED"/>
                        <rect x="10" y="10" width="2" height="2" fill="#DBE0ED"/>
                    </svg>
                    <span class="text-[13px] text-[#2D2825] dark:text-stone-200 font-medium" x-text="userName + ' · Max'"></span>
                </div>
                <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-200" :class="profileMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>

            {{-- Profile Dropdown Menu --}}
            <div x-show="profileMenuOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-2 mb-2 w-[240px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                <div class="px-3 py-1.5 text-[13px] font-medium text-stone-500 truncate mb-1" x-text="email"></div>
                
                <button @click="$dispatch('open-settings-ui'); profileMenuOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.341 3.897C10.665 2.553 12.012 2 13 2s2.335.553 2.659 1.897l.11.455a1.75 1.75 0 001.37 1.328l.458.114c1.343.334 1.897 1.68 1.897 2.668 0 .988-.554 2.334-1.897 2.668l-.458.114a1.75 1.75 0 00-1.37 1.328l-.11.455A2.75 2.75 0 0113 16a2.75 2.75 0 01-2.659-1.897l-.11-.455a1.75 1.75 0 00-1.37-1.328l-.458-.114A2.75 2.75 0 016.5 9.54c0-.988.554-2.334 1.897-2.668l.458-.114A1.75 1.75 0 0010.231 4.35l.11-.453zM13 11a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
                        Settings
                    </div>
                </button>
                <div class="my-1.5 mx-3 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                <form method="POST" action="/logout" class="w-full">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <button type="submit" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                            Log out
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Area --}}
    <div class="flex-1 flex flex-col h-full relative overflow-hidden bg-claude-bg-light dark:bg-claude-bg-dark">
        
        {{-- Sidebar Toggle button if closed --}}
        <div x-show="!sidebarOpen" class="absolute top-4 left-4 z-30">
            <button @click="sidebarOpen = true" class="p-1.5 bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 text-stone-400 rounded-md transition-colors shadow-sm">
                <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
            </button>
        </div>

        {{-- Chat routines lists --}}
        <div class="flex-1 overflow-y-auto w-full flex flex-col" id="code-chat-container">
            <template x-if="currentView === 'chat'">
                <div class="flex-1 flex flex-col h-full overflow-hidden">
                    <template x-if="!isStarted">
                        {{-- Empty State --}}
                        <div class="flex-1 flex flex-col items-center justify-center min-h-[50vh] px-4">
                            <div class="flex items-center gap-3">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#D97757" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rynude-mascot"><path d="M12 2v20M17 5l-10 14M22 12H2M19 17L5 7"></path></svg>
                                <h1 class="font-serif text-[22px] text-[#2D2825] dark:text-stone-200 font-semibold font-claude-response" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;">What's up next, <span x-text="userName"></span>?</h1>
                            </div>
                        </div>
                    </template>

                    <template x-if="isStarted">
                        {{-- Messages list --}}
                        <div class="flex-1 overflow-y-auto px-4 py-8 space-y-6 max-w-5xl mx-auto w-full" x-ref="messagesContainer">
                            <template x-for="(msg, idx) in messages" :key="idx">
                                <div class="flex w-full" :class="msg.role==='user'?'justify-end':'justify-start'">
                                    <template x-if="msg.role==='user'">
                                        <div class="max-w-[80%] bg-[#F3F3F3] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 px-4 py-3 rounded-2xl rounded-br-sm text-[15px] whitespace-pre-wrap break-words" x-text="msg.content"></div>
                                    </template>
                                    <template x-if="msg.role!=='user'">
                                        <div class="max-w-[85%] text-[#2D2825] dark:text-stone-200 text-[15px] prose dark:prose-invert prose-stone font-claude-response" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;">
                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 mt-1">
                                                    <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 leading-relaxed custom-prose markdown-body text-[15px] break-words" x-html="renderContent(msg.content)"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Live stream indicator --}}
                            <div x-show="isStreaming" class="w-full flex justify-start">
                                <div class="max-w-[85%] text-[#2D2825] dark:text-stone-200 text-[15px] prose dark:prose-invert prose-stone font-claude-response" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 mt-1">
                                            <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 leading-relaxed custom-prose">
                                            <div x-html="renderContent(streamContent)"></div>
                                            <div class="text-stone-400 text-sm flex items-center gap-2 mt-1.5">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <span>Rynude Code is thinking...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="currentView === 'routines'">
                <div class="flex-1 max-w-5xl mx-auto w-full px-4 py-8">
                    @include('livewire.routines-list')
                </div>
            </template>
            <template x-if="currentView === 'new-routine'">
                <div class="flex-1 max-w-5xl mx-auto w-full px-4 py-8">
                    @include('livewire.new-routine')
                </div>
            </template>
        </div>

        {{-- Input Area --}}
        <template x-if="currentView === 'chat'">
            <div class="w-full max-w-5xl mx-auto px-4 pb-6 pt-2 z-20">
                <div class="relative w-full">
                    
                    {{-- Floating Selector Pills & Mascot --}}
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2 ml-1">
                            {{-- Env Selector --}}
                            <div x-data="{ envOpen: false }" class="relative z-20">
                                <button @click="envOpen = !envOpen" @click.away="envOpen = false" class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[#E5E5E5] dark:border-stone-700 bg-stone-100 dark:bg-stone-850 hover:bg-stone-200 dark:hover:bg-stone-800 rounded-lg text-[13px] font-medium text-stone-800 dark:text-stone-200 transition-colors">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                                    Default
                                </button>
                                {{-- Env Dropdown --}}
                                <div x-show="envOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[280px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                                    <!-- Local Section -->
                                    <div class="px-3 py-1.5 flex items-center gap-2 opacity-50 cursor-not-allowed">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Local <span class="text-stone-400">Desktop only</span></span>
                                    </div>
                                    <div class="my-1.5 mx-3 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                                    <!-- Cloud Section -->
                                    <div class="px-3 py-1.5 text-[12px] text-stone-500">Cloud</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                                            Default
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                                    </button>
                                    <div class="my-1.5 mx-3 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                                    <!-- Remote Control Section -->
                                    <div class="px-3 py-1.5 text-[12px] text-stone-500">Remote Control</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-start gap-2 text-left">
                                        <svg class="w-4 h-4 mt-0.5 text-stone-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                        <div>
                                            <div class="text-[13px] text-[#2D2825] dark:text-stone-200 font-medium">Set up Remote Control</div>
                                            <div class="text-[12px] text-stone-500 mt-0.5 leading-snug">Run <code class="bg-[#F3F3F3] dark:bg-stone-800 px-1 py-0.5 rounded text-[11px] font-mono">rynude rc</code> on your machine to code from here.</div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {{-- Repo Selector --}}
                            <div x-data="{ repoOpen: false }" class="relative z-20">
                                <button @click="repoOpen = !repoOpen" @click.away="repoOpen = false" class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[#E5E5E5] dark:border-stone-700 bg-stone-100 dark:bg-stone-850 hover:bg-stone-200 dark:hover:bg-stone-800 rounded-lg text-[13px] font-medium text-stone-600 dark:text-stone-400 transition-colors">
                                    <span class="text-stone-400 font-normal">+</span> Select repo...
                                </button>
                                {{-- Repo Dropdown --}}
                                <div x-show="repoOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[280px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-2 z-50">
                                    <div class="px-3 pb-2 text-[13px] text-stone-500 leading-snug">
                                        Connect GitHub to pick a repository for this session<br>
                                        <a href="#" class="text-stone-500 underline decoration-stone-300 hover:text-stone-850 transition-colors mt-1 inline-block">Connect to GitHub</a>
                                    </div>
                                    <div class="px-2">
                                        <div class="flex items-center gap-2 px-2 py-1.5 bg-white dark:bg-stone-900 border-none rounded">
                                            <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                            <input type="text" placeholder="Search repos..." class="bg-transparent border-0 focus:ring-0 p-0 text-[13px] w-full placeholder-stone-400 text-stone-800 dark:text-stone-200">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Pixel Mascot --}}
                        <div class="mr-4 text-[#D97757]">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="rynude-mascot">
                                <rect x="6" y="8" width="12" height="8"></rect>
                                <rect x="4" y="10" width="2" height="4"></rect>
                                <rect x="18" y="10" width="2" height="4"></rect>
                                <rect x="8" y="6" width="8" height="2"></rect>
                                <rect x="8" y="10" width="2" height="2" fill="white"></rect>
                                <rect x="14" y="10" width="2" height="2" fill="white"></rect>
                                <rect x="7" y="16" width="2" height="4"></rect>
                                <rect x="15" y="16" width="2" height="4"></rect>
                                <rect x="5" y="18" width="2" height="2"></rect>
                                <rect x="17" y="18" width="2" height="2"></rect>
                            </svg>
                        </div>
                    </div>

                    {{-- The Input Box --}}
                    <div class="relative w-full rounded-2xl border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-900 shadow-sm flex items-end px-4 py-3 focus-within:ring-2 focus-within:ring-[#E5E5E5] dark:focus-within:ring-stone-700 transition-shadow">
                        <textarea 
                            x-model="message" 
                            @keydown.enter.prevent="sendMessage()"
                            rows="1" 
                            placeholder="Describe a task or ask a question" 
                            class="w-full max-h-[200px] resize-none bg-transparent border-0 focus:ring-0 p-0 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-stone-400"
                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                        ></textarea>
                        
                        <button @click="sendMessage()" :disabled="sending||!message.trim()" class="ml-2 p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors" :class="message.trim()?'text-[#D97757] bg-orange-50 hover:bg-orange-100':''">
                            <svg x-show="!isStreaming" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 10l3-3 3 3M12 7v10"></path></svg>
                            <svg x-show="isStreaming" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>
                    </div>

                    {{-- Input Footer --}}
                    <div class="flex items-center justify-between mt-2.5 px-1">
                        <div class="flex items-center gap-2">
                            {{-- Accept Edits Menu --}}
                            <div x-data="{ modeOpen: false }" class="relative z-20">
                                <button @click="modeOpen = !modeOpen" @click.away="modeOpen = false" class="px-2.5 py-1 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 bg-[#EAE9E5] dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-md transition-colors">
                                    Accept edits
                                </button>
                                
                                {{-- Mode Dropdown --}}
                                <div x-show="modeOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[180px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                                    <div class="px-3 py-1.5 text-[12px] text-stone-500">Mode</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                                        Accept edits
                                        <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Model Dropdown --}}
                        <div x-data="{ open: false, moreModelsOpen: false, closeTimer: null }" class="relative" @mouseleave="closeTimer = setTimeout(() => { open = false; moreModelsOpen = false }, 400)">
                            <button @click="open = !open" @mouseenter="clearTimeout(closeTimer)" type="button" class="flex items-center gap-1 px-2.5 py-1 text-[13px] text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-md transition-colors">
                                <span class="text-stone-400 font-normal">Model:</span>
                                <span x-text="selectedModelName"></span>
                                <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>

                            <div x-show="open" x-cloak class="absolute bottom-full right-0 mb-2 w-[240px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5">
                                <template x-for="model in codeModels" :key="model.code">
                                    <button @click="selectedModel = model.code; open = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-750 transition-colors flex items-center justify-between group">
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="model.name"></span>
                                            </div>
                                        </div>
                                        <svg x-show="selectedModel === model.code" class="w-4 h-4 text-[#D97757] shrink-0 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                </template>
                                <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                <!-- More Models -->
                                <div class="relative" @mouseenter="clearTimeout(closeTimer); moreModelsOpen = true" @mouseleave="closeTimer = setTimeout(() => { moreModelsOpen = false }, 250)">
                                    <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-750 transition-colors flex items-center justify-between group">
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">More models</span>
                                        <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    
                                    <!-- Sub-menu -->
                                    <div x-show="moreModelsOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full sm:bottom-0 mb-1 sm:mb-0 w-[200px] bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50 max-h-[300px] overflow-y-auto">
                                        <template x-for="mModel in moreModels" :key="mModel.code">
                                            <button @click="selectedModel = mModel.code; open = false; moreModelsOpen = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                                <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="mModel.name"></span>
                                                <svg x-show="selectedModel === mModel.code" class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Settings Modal UI --}}
    @include('livewire.settings-modal')
</div>

<script>
function claudeCodeState() {
    return {
        sidebarOpen: true,
        isStarted: false,
        isStreaming: false,
        currentView: 'chat',
        conversationId: null,
        recentSessions: [],
        messages: [],
        message: '',
        sending: false,
        streamContent: '',
        selectedModel: 'claude-sonnet-4-6',
        codeModels: [],
        moreModels: [],
        userName: '{{ Auth::check() ? Auth::user()->name : "Guest" }}',
        email: '{{ Auth::check() ? Auth::user()->email : "guest@example.com" }}',

        get selectedModelName() {
            var m = this.codeModels.find(function(m) { return m.code === this.selectedModel; }.bind(this));
            if (!m) m = this.moreModels.find(function(m) { return m.code === this.selectedModel; }.bind(this));
            return m ? m.name : 'Sonnet 4.6';
        },

        init: function() {
            this.codeModels = [
                {code:'claude-opus-4-8',name:'Opus 4.8'},
                {code:'claude-sonnet-4-6',name:'Sonnet 4.6'},
                {code:'claude-haiku-4-5',name:'Haiku 4.5'},
            ];
            this.loadRecentSessions();
            this.loadSettings();

            // Handle theme initialization
            document.addEventListener('theme-changed', function(e) {
                var theme = e.detail.theme;
                if (theme === 'dark') document.documentElement.classList.add('dark');
                else if (theme === 'light') document.documentElement.classList.remove('dark');
            });
        },

        loadSettings: function() {
            var self = this;
            fetch('/api/settings', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    self.moreModels = resp.more_models || [];
                });
        },

        loadRecentSessions: function() {
            var self = this;
            fetch('/api/chats', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    self.recentSessions = resp.data || [];
                });
        },

        newSession: function() {
            this.messages = [];
            this.conversationId = null;
            this.isStarted = false;
            this.isStreaming = false;
            this.message = '';
            this.currentView = 'chat';
        },

        loadSession: function(id) {
            var self = this;
            this.currentView = 'chat';
            fetch('/api/chats/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        self.conversationId = resp.data.id;
                        self.messages = resp.data.messages || [];
                        self.isStarted = true;
                        self.$nextTick(function() {
                            self.scrollToBottom();
                        });
                    }
                });
        },

        deleteSession: function(id) {
            if (!confirm('Delete this session?')) return;
            var self = this;
            fetch('/api/chats/' + id, {method:'DELETE',headers:{'Accept':'application/json'}})
                .then(function(r){
                    if (r.ok) {
                        if (self.conversationId === id) { self.newSession(); }
                        self.loadRecentSessions();
                    }
                });
        },

        sendMessage: function() {
            var text = this.message.trim();
            if (!text || this.sending) return;
            this.sending = true;
            this.isStreaming = true;
            this.isStarted = true;
            
            var self = this;
            self.messages.push({role:'user',content:text});
            self.message = '';
            self.streamContent = '';
            self.$nextTick(function() { self.scrollToBottom(); });

            var payload = {
                prompt: text,
                model: self.selectedModel
            };
            if (self.conversationId) {
                payload.conversation_id = self.conversationId;
            }

            fetch('/api/chats/send', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'text/event-stream'},
                body: JSON.stringify(payload)
            })
            .then(function(response) {
                // If it created a new conversation, get the id from headers if present, or we can reload sessions
                self.loadRecentSessions();
                
                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';

                function read() {
                    reader.read().then(function(result) {
                        if (result.done) {
                            self.messages.push({role:'assistant',content:self.streamContent});
                            self.isStreaming=false;
                            self.sending=false;
                            self.streamContent='';
                            self.loadRecentSessions();
                            return;
                        }
                        buffer += decoder.decode(result.value, {stream:true});
                        var lines = buffer.split('\n');
                        buffer = lines.pop() || '';
                        lines.forEach(function(line) {
                            var trimmedLine = line.trim();
                            if (trimmedLine.startsWith('data: ')) {
                                try {
                                    var d=JSON.parse(trimmedLine.slice(6).trim());
                                    if(d.type==='content') {
                                        self.streamContent+=d.data;
                                    } else if(d.type==='conversation_id') {
                                        self.conversationId = d.data;
                                    }
                                } catch(e) {}
                            }
                        });
                        self.scrollToBottom();
                        read();
                    });
                }
                read();
            })
            .catch(function(){self.isStreaming=false;self.sending=false;});
        },

        scrollToBottom: function() {
            var container = document.getElementById('code-chat-container');
            if (container) container.scrollTop = container.scrollHeight;
        },

        renderContent: function(content) {
            if (!content) return '';
            if (window.marked && window.marked.parse) {
                return window.marked.parse(content);
            }
            return content.replace(/\n/g, '<br>');
        }
    };
}
</script>
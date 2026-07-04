<div
    class="flex w-full h-screen overflow-hidden font-mono bg-[#1A1A1A] text-[#E5E5E5] dark"
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
        
        /* Force monospace and terminal look for custom elements inside routines */
        .font-mono-override * { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important; }
    </style>

    {{-- LEFT SIDEBAR --}}
    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="-translate-x-full opacity-0"
         class="w-[260px] flex-shrink-0 flex flex-col bg-[#141414] border-r border-[#2A2A2A] h-full overflow-hidden">
        <div class="flex items-center justify-between px-3 py-3 border-b border-[#2A2A2A] flex-shrink-0">
            <div class="flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 100 100" class="text-[#D97757] fill-current flex-shrink-0"><path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"/></svg>
                <span class="text-[13px] font-semibold text-[#E5E5E5] tracking-tight">Rynude Code</span>
                <span class="text-[9px] px-1 py-0.5 bg-[#D97757]/20 text-[#D97757] rounded font-sans font-medium">Preview</span>
            </div>
            <button @click="sidebarOpen = false" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#999]">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
        </div>

        <div class="px-2 pt-2 pb-1 flex-shrink-0">
            <button @click="newSession()" class="w-full flex items-center gap-2 px-2.5 py-2 text-[12px] text-[#DDD] hover:text-[#E5E5E5] hover:bg-[#252525] rounded-md transition-colors group font-sans">
                <svg class="w-3.5 h-3.5 group-hover:rotate-90 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New session <kbd class="ml-auto text-[10px] bg-[#1F1F1F] border border-[#2A2A2A] text-[#888] px-1 rounded">N</kbd>
            </button>
        </div>

        <div class="px-2 space-y-0.5 flex-shrink-0">
            <button @click="currentView='routines'" class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] rounded-md transition-colors font-sans" :class="currentView==='routines'||currentView==='new-routine'?'bg-[#252525] text-[#E5E5E5]':'text-[#BBB] hover:text-[#CCC] hover:bg-[#1F1F1F]'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Routines
            </button>
            <a href="/chat?panel=customize" class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] text-[#BBB] hover:text-[#CCC] hover:bg-[#1F1F1F] rounded-md transition-colors font-sans">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6M4.93 19.07l4.24-4.24M14.83 9.17l4.24-4.24"/></svg> Customize
            </a>
            
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] text-[#BBB] hover:text-[#CCC] hover:bg-[#1F1F1F] rounded-md transition-colors font-sans">
                    <svg class="w-3.5 h-3.5 text-stone-500 transition-transform duration-300 group-hover:translate-y-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg> More
                </button>
                
                <div x-show="open" style="display: none;" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute left-0 top-full mt-1 w-48 bg-[#1F1F1F] border border-[#333] rounded-xl shadow-lg z-50 py-1">
                    <button class="w-full text-left px-3 py-2 hover:bg-[#252525] text-[13px] text-[#CCC] flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-[#777]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="7" y="4" width="10" height="16" rx="2" ry="2"></rect><path d="M12 2v2"></path><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            Dispatch
                        </div>
                        <span class="text-[10px] font-medium px-1.5 py-0.5 bg-[#252525] text-[#D97757] rounded-md">Beta</span>
                    </button>
                    <div class="my-1 border-t border-[#333] mx-3"></div>
                    <a href="/chat?panel=customize" class="w-full text-left px-3 py-2 hover:bg-[#252525] text-[13px] text-[#CCC] flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-[#777]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        Customize sidebar
                    </a>
                </div>
            </div>
        </div>

        <div class="mx-3 my-2 border-t border-[#222] flex-shrink-0"></div>

        {{-- Recents --}}
        <div class="flex-1 overflow-y-auto scrollbar-hide px-2 min-h-0">
            <p class="px-2.5 text-[10px] font-semibold text-[#777] uppercase tracking-widest mb-1.5 font-sans">Recents</p>
            <template x-for="session in recentSessions" :key="session.id">
                <div class="group relative flex items-center rounded-md mb-0.5" :class="conversationId===session.id?'bg-[#252525] border border-[#333]':'hover:bg-[#1F1F1F]'">
                    <button @click="loadSession(session.id)" class="flex-1 flex items-center gap-2 px-2.5 py-2 text-left min-w-0 font-sans">
                        <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="conversationId===session.id?'bg-[#4ADE80] animate-pulse':'bg-[#333] group-hover:bg-[#555]'"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] truncate transition-colors" :class="conversationId===session.id?'text-[#E5E5E5]':'text-[#CCC]'" x-text="session.title"></p>
                        </div>
                    </button>
                    <button @click.stop="deleteSession(session.id)" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-[#333] transition-all text-[#777] hover:text-[#F87171] absolute right-1" title="Delete session">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </template>
            <template x-if="recentSessions.length === 0">
                <p class="text-[11px] text-[#555] px-2.5 py-3 font-sans">No recent sessions</p>
            </template>
        </div>

        {{-- Profile Footer --}}
        <div class="mt-auto border-t border-[#2A2A2A] p-2 relative bg-[#141414]" x-data="{ profileMenuOpen: false }">
            <button @click="profileMenuOpen = !profileMenuOpen" @click.away="profileMenuOpen = false" class="w-full flex items-center justify-between px-2 py-2 hover:bg-[#252525] rounded-lg transition-colors font-sans">
                <div class="flex items-center gap-2.5">
                    <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="8" cy="8" r="8" fill="#8BA4A7"/>
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
                    <span class="text-[13px] text-[#DDD] font-medium" x-text="userName + ' · Max'"></span>
                </div>
                <svg class="w-3.5 h-3.5 text-[#777] transition-transform duration-200" :class="profileMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>

            {{-- Profile Dropdown Menu --}}
            <div x-show="profileMenuOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-2 mb-2 w-[240px] bg-[#1F1F1F] border border-[#333] rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                <div class="px-3 py-1.5 text-[13px] font-medium text-[#777] truncate mb-1" x-text="email"></div>
                
                <button @click="$dispatch('open-settings-ui'); profileMenuOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between text-[13px] text-[#CCC]">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#777]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.341 3.897C10.665 2.553 12.012 2 13 2s2.335.553 2.659 1.897l.11.455a1.75 1.75 0 001.37 1.328l.458.114c1.343.334 1.897 1.68 1.897 2.668 0 .988-.554 2.334-1.897 2.668l-.458.114a1.75 1.75 0 00-1.37 1.328l-.11.455A2.75 2.75 0 0113 16a2.75 2.75 0 01-2.659-1.897l-.11-.455a1.75 1.75 0 00-1.37-1.328l-.458-.114A2.75 2.75 0 016.5 9.54c0-.988.554-2.334 1.897-2.668l.458-.114A1.75 1.75 0 0010.231 4.35l.11-.453zM13 11a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
                        Settings
                    </div>
                </button>
                <div class="my-1.5 mx-3 border-t border-[#333]"></div>
                <form method="POST" action="/logout" class="w-full">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <button type="submit" class="w-full text-left px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between text-[13px] text-[#CCC]">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#777]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                            Log out
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-[#1A1A1A]">
        {{-- Top bar --}}
        <div class="flex items-center justify-between px-3 py-1.5 border-b border-[#2A2A2A] flex-shrink-0">
            <div class="flex items-center gap-2">
                <button @click="sidebarOpen = !sidebarOpen" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#999]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div x-data="{ open: false, moreModelsOpen: false, closeTimer: null }" class="relative" @mouseleave="closeTimer = setTimeout(() => { open = false; moreModelsOpen = false }, 400)">
                    <button @click="open=!open" @mouseenter="clearTimeout(closeTimer)" type="button" class="flex items-center gap-1.5 px-2 py-1 rounded bg-[#252525] hover:bg-[#2A2A2A] transition-colors text-[13px] text-[#CCC]">
                        <span class="max-w-[120px] truncate" x-text="selectedModelName"></span>
                        <svg class="w-3 h-3 text-[#777]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 top-full mt-1 w-[220px] bg-[#1F1F1F] border border-[#333] rounded-md shadow-lg py-1 z-50">
                        <template x-for="m in codeModels" :key="m.code">
                            <button @click="selectedModel=m.code; open=false" 
                                    class="w-full text-left px-3 py-1.5 text-[12px] hover:bg-[#252525] transition-colors flex items-center justify-between" 
                                    :class="[selectedModel===m.code?'text-white':'text-[#999]', !m.is_available?'opacity-50 cursor-not-allowed':'']"
                                    :disabled="!m.is_available">
                                <span x-text="m.name"></span>
                                <template x-if="!m.is_available">
                                    <span class="text-[9px] px-1 py-0.5 bg-[#333] text-stone-500 rounded font-sans">Unavailable</span>
                                </template>
                            </button>
                        </template>
                        <div class="h-px bg-[#333] mx-3 my-1"></div>
                        
                        <!-- More Models -->
                        <div class="relative" @mouseenter="clearTimeout(closeTimer); moreModelsOpen = true" @mouseleave="closeTimer = setTimeout(() => { moreModelsOpen = false }, 250)">
                            <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between group">
                                <span class="text-[12px] text-[#CCC]">More models</span>
                                <svg class="w-3.5 h-3.5 text-[#777] group-hover:text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            
                            <!-- Sub-menu -->
                            <div x-show="moreModelsOpen" x-cloak class="absolute right-full top-0 mr-1 w-[180px] bg-[#1F1F1F] border border-[#333] rounded-md shadow-lg py-1 z-50 max-h-[250px] overflow-y-auto">
                                <template x-for="mModel in moreModels" :key="mModel.code">
                                    <button @click="selectedModel=mModel.code; open=false; moreModelsOpen=false" 
                                            class="w-full text-left px-3 py-1.5 text-[12px] hover:bg-[#252525] transition-colors flex items-center justify-between" 
                                            :class="[selectedModel===mModel.code?'text-white':'text-[#999]', !mModel.is_available?'opacity-50 cursor-not-allowed':'']"
                                            :disabled="!mModel.is_available">
                                        <span x-text="mModel.name"></span>
                                        <template x-if="!mModel.is_available">
                                            <span class="text-[9px] px-1 py-0.5 bg-[#333] text-stone-500 rounded font-sans">Unavailable</span>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <button @click="rightPanelOpen=!rightPanelOpen" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#999]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
                </button>
            </div>
        </div>

        {{-- Chat routines lists --}}
        <div class="flex-1 overflow-y-auto w-full flex flex-col" id="code-chat-container">
            <template x-if="currentView === 'chat'">
                <div class="flex-1 flex flex-col h-full overflow-hidden">
                    <template x-if="!isStarted">
                        {{-- Empty State --}}
                        <div class="flex-1 flex flex-col items-center justify-center min-h-[50vh] px-4 font-mono">
                            <div class="flex flex-col items-center gap-4 text-center max-w-md">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#D97757" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="rynude-mascot"><path d="M12 2v20M17 5l-10 14M22 12H2M19 17L5 7"></path></svg>
                                <h1 class="text-xl font-bold text-[#E5E5E5] tracking-tight">Rynude Code CLI</h1>
                                <p class="text-[12px] text-[#999] leading-relaxed">
                                    Welcome to Rynude Code (Research Preview).
                                    A fast, agentic coding tool directly connected to your workspace.
                                </p>
                                <div class="bg-[#141414] border border-[#2A2A2A] rounded-lg px-3 py-2 font-mono text-[11px] text-[#777] text-left w-full mt-2">
                                    <div class="flex items-center gap-1.5"><span class="text-[#D97757]">></span> <span>Type your task below to begin</span></div>
                                    <div class="flex items-center gap-1.5 mt-1"><span class="text-[#D97757]">></span> <span>Rynude can search, view files, and write code</span></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="isStarted">
                        {{-- Messages list --}}
                        <div class="flex-1 overflow-y-auto px-4 py-8 space-y-6 max-w-5xl mx-auto w-full" x-ref="messagesContainer">
                            <template x-for="(msg, idx) in messages" :key="idx">
                                <div class="flex w-full" :class="msg.role==='user'?'justify-end':'justify-start'">
                                    <template x-if="msg.role==='user'">
                                        <div class="max-w-[80%] bg-[#252525] border border-[#333] text-[#E5E5E5] px-4 py-3 rounded-2xl rounded-br-sm text-[14px] font-mono whitespace-pre-wrap break-words" x-text="msg.content"></div>
                                    </template>
                                    <template x-if="msg.role!=='user'">
                                        <div class="max-w-[85%] text-[#E5E5E5] text-[14px] font-mono prose dark:prose-invert prose-stone">
                                            <div class="flex gap-4">
                                                <div class="flex-shrink-0 mt-1">
                                                    <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 leading-relaxed custom-prose markdown-body text-[14px] break-words" x-html="renderContent(msg.content)"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Live stream indicator --}}
                            <div x-show="isStreaming" class="w-full flex justify-start pb-4">
                                <div class="max-w-[85%] text-[#E5E5E5] text-[14px] font-mono prose dark:prose-invert prose-stone">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 mt-1">
                                            <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 leading-relaxed custom-prose">
                                            <div x-html="renderContent(streamContent)"></div>
                                            <div class="text-stone-500 dark:text-stone-400 text-[12px] flex items-center gap-2 mt-2 font-mono">
                                                <img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin h-3.5 w-3.5 object-contain">
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
                <div class="flex-1 max-w-5xl mx-auto w-full px-4 py-8 font-mono-override">
                    @include('livewire.routines-list')
                </div>
            </template>
            <template x-if="currentView === 'new-routine'">
                <div class="flex-1 max-w-5xl mx-auto w-full px-4 py-8 font-mono-override">
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
                            <div x-data="{ envOpen: false }" class="relative z-20 font-mono">
                                <button @click="envOpen = !envOpen" @click.away="envOpen = false" class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[#333] bg-[#252525] hover:bg-[#2A2A2A] rounded-lg text-[13px] font-medium text-[#DDD] transition-colors">
                                    <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                                    Default
                                </button>
                                {{-- Env Dropdown --}}
                                <div x-show="envOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[280px] bg-[#1F1F1F] border border-[#333] rounded-xl shadow-lg py-1.5 z-50">
                                    <!-- Local Section -->
                                    <div class="px-3 py-1.5 flex items-center gap-2 opacity-50 cursor-not-allowed">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                        <span class="text-[13px] text-[#888]">Local <span class="text-[#555]">Desktop only</span></span>
                                    </div>
                                    <div class="my-1.5 mx-3 border-t border-[#333]"></div>
                                    <!-- Cloud Section -->
                                    <div class="px-3 py-1.5 text-[12px] text-stone-500">Cloud</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between text-[13px] text-[#CCC]">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                                            Default
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                                    </button>
                                    <div class="my-1.5 mx-3 border-t border-[#333]"></div>
                                    <!-- Remote Control Section -->
                                    <div class="px-3 py-1.5 text-[12px] text-stone-500">Remote Control</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-start gap-2 text-left">
                                        <svg class="w-4 h-4 mt-0.5 text-stone-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                        <div>
                                            <div class="text-[13px] text-[#CCC] font-medium">Set up Remote Control</div>
                                            <div class="text-[12px] text-stone-500 mt-0.5 leading-snug">Runs <code class="bg-[#141414] px-1 py-0.5 rounded text-[11px] font-mono text-[#D97757]">rynude rc</code> on your machine to code from here.</div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {{-- Repo Selector --}}
                            <div x-data="{ repoOpen: false }" class="relative z-20 font-mono">
                                <button @click="repoOpen = !repoOpen" @click.away="repoOpen = false" class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[#333] bg-[#252525] hover:bg-[#2A2A2A] rounded-lg text-[13px] font-medium text-[#BBB] transition-colors">
                                    <span class="text-[#D97757] font-normal">+</span> Select repo...
                                </button>
                                {{-- Repo Dropdown --}}
                                <div x-show="repoOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[280px] bg-[#1F1F1F] border border-[#333] rounded-xl shadow-lg py-2 z-50">
                                    <div class="px-3 pb-2 text-[13px] text-stone-500 leading-snug">
                                        Connect GitHub to pick a repository for this session<br>
                                        <a href="#" @click.prevent="repoModalOpen = true; repoOpen = false" class="text-[#D97757] underline decoration-[#D97757]/35 hover:text-white transition-colors mt-1 inline-block">Connect to GitHub</a>
                                    </div>
                                    <div class="px-2">
                                        <div class="flex items-center gap-2 px-2 py-1.5 bg-[#141414] border border-[#333] rounded">
                                            <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                            <input type="text" placeholder="Search repos..." class="bg-transparent border-0 focus:ring-0 p-0 text-[13px] w-full placeholder-stone-600 text-[#DDD]">
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
                    <div class="relative w-full rounded-2xl border border-[#333] bg-[#252525] shadow-sm flex items-end px-4 py-3 focus-within:ring-2 focus-within:ring-[#444] transition-shadow">
                        <textarea 
                            x-model="message" 
                            @keydown.enter.prevent="sendMessage()"
                            rows="1" 
                            placeholder="Describe a task or ask a question" 
                            class="w-full max-h-[200px] resize-none bg-transparent border-0 focus:ring-0 p-0 text-[15px] text-[#E5E5E5] placeholder-[#555] font-mono"
                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                        ></textarea>
                        
                        <button @click="sendMessage()" :disabled="sending||!message.trim()" class="ml-2 p-1.5 rounded-lg text-stone-500 hover:text-stone-300 hover:bg-[#2A2A2A] transition-colors" :class="message.trim()?'text-stone-400 bg-[#D97757]/10':''">
                            <svg x-show="!isStreaming" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 10l3-3 3 3M12 7v10"></path></svg>
                            <img x-show="isStreaming" src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-4 h-4 object-contain">
                        </button>
                    </div>

                    {{-- Input Footer --}}
                    <div class="flex items-center justify-between mt-2.5 px-1 font-mono">
                        <div class="flex items-center gap-2">
                            {{-- Accept Edits Menu --}}
                            <div x-data="{ modeOpen: false }" class="relative z-20">
                                <button @click="modeOpen = !modeOpen" @click.away="modeOpen = false" class="px-2.5 py-1 text-[13px] font-medium text-[#CCC] bg-[#1F1F1F] border border-[#333] hover:bg-[#252525] rounded-md transition-colors font-mono">
                                    Accept edits
                                </button>
                                
                                {{-- Mode Dropdown --}}
                                <div x-show="modeOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[180px] bg-[#1F1F1F] border border-[#333] rounded-xl shadow-lg py-1.5 z-50">
                                    <div class="px-3 py-1.5 text-[12px] text-stone-500">Mode</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between text-[13px] text-[#CCC]">
                                        Accept edits
                                        <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Model Dropdown --}}
                        <div x-data="{ open: false, moreModelsOpen: false, closeTimer: null }" class="relative" @mouseleave="closeTimer = setTimeout(() => { open = false; moreModelsOpen = false }, 400)">
                            <button @click="open = !open" @mouseenter="clearTimeout(closeTimer)" type="button" class="flex items-center gap-1 px-2.5 py-1 text-[13px] text-stone-400 hover:bg-[#2A2A2A] rounded-md transition-colors font-mono">
                                <span class="text-stone-500 font-normal">Model:</span>
                                <span x-text="selectedModelName"></span>
                                <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>

                            <div x-show="open" x-cloak class="absolute bottom-full right-0 mb-2 w-[240px] bg-[#1F1F1F] border border-[#333] rounded-xl shadow-lg z-50 py-1.5">
                                <template x-for="model in codeModels" :key="model.code">
                                    <button @click="selectedModel = model.code; open = false" type="button" 
                                            class="w-full text-left px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between group"
                                            :class="!model.is_available ? 'opacity-50 cursor-not-allowed' : ''"
                                            :disabled="!model.is_available">
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[13px] text-[#CCC]" x-text="model.name"></span>
                                                <template x-if="!model.is_available">
                                                    <span class="text-[9px] px-1 py-0.5 bg-[#333] text-stone-500 rounded font-sans">Unavailable</span>
                                                </template>
                                            </div>
                                            <div class="text-[11px] text-stone-500 mt-0.5" x-text="model.description"></div>
                                        </div>
                                        <svg x-show="selectedModel === model.code" class="w-4 h-4 text-[#D97757] shrink-0 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                </template>
                                <div class="h-px bg-[#333] mx-3 my-1.5"></div>

                                <!-- More Models -->
                                <div class="relative" @mouseenter="clearTimeout(closeTimer); moreModelsOpen = true" @mouseleave="closeTimer = setTimeout(() => { moreModelsOpen = false }, 250)">
                                    <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between group">
                                        <span class="text-[13px] text-[#CCC]">More models</span>
                                        <svg class="w-4 h-4 text-[#777] group-hover:text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    
                                    <!-- Sub-menu -->
                                    <div x-show="moreModelsOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full sm:bottom-0 mb-1 sm:mb-0 w-[200px] bg-[#1F1F1F] border border-[#333] rounded-xl shadow-lg py-1.5 z-50 max-h-[300px] overflow-y-auto">
                                        <template x-for="mModel in moreModels" :key="mModel.code">
                                            <button @click="selectedModel = mModel.code; open = false; moreModelsOpen = false" type="button" 
                                                    class="w-full text-left px-3 py-1.5 hover:bg-[#252525] transition-colors flex items-center justify-between group"
                                                    :class="!mModel.is_available ? 'opacity-50 cursor-not-allowed' : ''"
                                                    :disabled="!mModel.is_available">
                                                <span class="text-[13px] text-[#CCC]" x-text="mModel.name"></span>
                                                <template x-if="!mModel.is_available">
                                                    <span class="text-[9px] px-1 py-0.5 bg-[#333] text-stone-500 rounded font-sans">Unavailable</span>
                                                </template>
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

    {{-- RIGHT PANEL --}}
    <div x-show="rightPanelOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="w-[300px] flex-shrink-0 bg-[#141414] border-l border-[#2A2A2A] h-full flex flex-col z-10 font-mono">
        <div class="flex items-center justify-between px-3 py-2 border-b border-[#2A2A2A]">
            <div class="flex items-center gap-2">
                <button @click="rightPanelTab='files'" class="px-2 py-1 text-[11px] rounded transition-colors" :class="rightPanelTab==='files'?'bg-[#252525] text-[#CCC]':'text-[#666] hover:text-[#999]'">Files</button>
                <button @click="rightPanelTab='repo'" class="px-2 py-1 text-[11px] rounded transition-colors" :class="rightPanelTab==='repo'?'bg-[#252525] text-[#CCC]':'text-[#666] hover:text-[#999]'">Repo</button>
            </div>
            <button @click="rightPanelOpen=false" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#666]">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto p-3">
            <template x-if="rightPanelTab==='files'">
                <div class="space-y-2">
                    <p class="text-[12px] text-[#666]">Attached files will appear here.</p>
                </div>
            </template>
            <template x-if="rightPanelTab==='repo'">
                <div class="space-y-2">
                    <p x-show="repoConnected" class="flex items-center gap-2 text-[12px] text-[#4ADE80]">
                        <span class="w-2 h-2 rounded-full bg-[#4ADE80]"></span>Connected: <span x-text="repoUrl" class="text-[#CCC]"></span>
                        <button @click="disconnectRepo()" class="text-[#2A4A3A] hover:text-[#F87171] transition-colors ml-0.5">✕</button>
                    </p>
                    <p x-show="!repoConnected" class="text-[12px] text-[#666]">No repo connected.</p>
                </div>
            </template>
        </div>
    </div>

    {{-- Repo Modal --}}
    <template x-if="repoModalOpen">
        <div class="fixed inset-0 z-[70] bg-black/70 flex items-center justify-center font-mono">
            <div class="bg-[#1F1F1F] border border-[#333] rounded-xl p-6 w-full max-w-md">
                <h3 class="text-[15px] font-semibold text-[#E5E5E5] mb-4">Connect GitHub Repository</h3>
                <input x-model="repoUrl" type="text" placeholder="https://github.com/user/repo"
                    class="w-full px-3 py-2 bg-[#252525] border border-[#333] rounded-lg text-[13px] text-[#CCC] placeholder-[#555] focus:outline-none focus:border-[#D97757] mb-4">
                <div class="flex justify-end gap-2">
                    <button @click="repoModalOpen=false" class="px-3 py-1.5 text-[12px] text-[#999] hover:text-[#CCC] transition-colors">Cancel</button>
                    <button @click="connectRepo()" class="px-3 py-1.5 text-[12px] bg-[#D97757] text-white rounded-lg hover:bg-[#C56647] transition-colors">Connect</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Settings Modal UI --}}
    @include('livewire.settings-modal')
</div>

<script>
function claudeCodeState() {
    return {
        sidebarOpen: true,
        rightPanelOpen: false,
        rightPanelTab: 'files',
        repoModalOpen: false,
        repoUrl: '',
        repoConnected: false,
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
                {code:'fable-5',name:'Fable 5'},
                {code:'claude-sonnet-5',name:'Sonnet 5'},
                {code:'claude-opus-4-8',name:'Opus 4.8'},
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
                    if (resp.models && resp.models.length > 0) {
                        self.codeModels = resp.models;
                    }
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
            this.repoConnected = false;
            this.repoUrl = '';
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
                        
                        // Parse repo from metadata if present
                        var meta = resp.data.metadata || {};
                        self.repoConnected = meta.repo || false;
                        self.repoUrl = meta.repo ? 'https://github.com/' + meta.repo : '';

                        self.$nextTick(function() {
                            self.scrollToBottom();
                        });
                    }
                });
        },

        deleteSession: function(id) {
            if (!confirm('Delete this session?')) return;
            var self = this;
            var csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
            fetch('/api/chats/' + id, {
                method:'DELETE',
                headers:{
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
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
            if (self.repoConnected && self.repoUrl) {
                payload.repo_url = self.repoUrl;
            }

            var csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
            fetch('/api/chats/send', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'Accept':'text/event-stream',
                    'X-CSRF-TOKEN': csrfToken
                },
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
                                    var d = JSON.parse(trimmedLine.slice(6).trim());
                                    if (d.type === 'init') {
                                        if (d.data && d.data.conversation_id) {
                                            self.conversationId = d.data.conversation_id;
                                        }
                                    } else if (d.type === 'content') {
                                        self.streamContent += d.data;
                                    } else if (d.type === 'done') {
                                        if (d.data && d.data.conversation_id) {
                                            self.conversationId = d.data.conversation_id;
                                        }
                                    } else if (d.type === 'error') {
                                        self.streamContent = '<div class="text-red-500 font-medium">Error: ' + d.data + '</div>';
                                        self.isStreaming = false;
                                        self.sending = false;
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
        },

        connectRepo: function() {
            var self = this;
            var url = this.repoUrl.trim();
            if (!url) return;
            
            var payload = { repo_url: url };
            if (this.conversationId) {
                payload.conversation_id = this.conversationId;
            }
            
            var csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
            
            fetch('/api/chats/connect-repo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(function(r) {
                if (r.ok) return r.json();
                throw new Error('Connection failed');
            })
            .then(function(data) {
                self.repoConnected = data.repo;
                self.repoUrl = 'https://github.com/' + data.repo;
                self.repoModalOpen = false;
            })
            .catch(function(err) {
                alert('Failed to connect repository. Make sure GitHub PAT is set in settings.');
            });
        },

        disconnectRepo: function() {
            var self = this;
            var payload = {};
            if (this.conversationId) {
                payload.conversation_id = this.conversationId;
            }
            
            var csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
            
            fetch('/api/chats/disconnect-repo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(function() {
                self.repoConnected = false;
                self.repoUrl = '';
            });
        }
    };
}
</script>
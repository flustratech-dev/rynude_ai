<div
    class="flex w-full h-screen overflow-hidden font-mono bg-[#1A1A1A] text-[#E5E5E5]"
    x-data="{
        sidebarOpen: true,
        rightPanelOpen: false,
        rightPanelTab: 'files',
        isStarted: @entangle('isStarted'),
        isStreaming: @entangle('isStreaming'),
        repoModalOpen: @entangle('repoModalOpen'),
    }"
>

    {{-- ═══════════════════════════ LEFT SIDEBAR ═══════════════════════════ --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-full opacity-0"
        class="w-[240px] flex-shrink-0 flex flex-col bg-[#141414] border-r border-[#2A2A2A] h-full overflow-hidden"
    >
        {{-- Logo --}}
        <div class="flex items-center justify-between px-3 py-3 border-b border-[#2A2A2A] flex-shrink-0">
            <div class="flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 100 100" class="text-[#CC785C] fill-current flex-shrink-0">
                    <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"/>
                </svg>
                <span class="text-[13px] font-semibold text-[#E5E5E5] tracking-tight">Rynude Code</span>
                <span class="text-[9px] px-1 py-0.5 bg-[#CC785C]/20 text-[#CC785C] rounded font-sans font-medium">Preview</span>
            </div>
            <button @click="sidebarOpen = false" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#555]">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
        </div>

        {{-- New Session --}}
        <div class="px-2 pt-2 pb-1 flex-shrink-0">
            <button wire:click="newSession"
                class="w-full flex items-center gap-2 px-2.5 py-2 text-[12px] text-[#999] hover:text-[#E5E5E5] hover:bg-[#252525] rounded-md transition-colors group font-sans">
                <svg class="w-3.5 h-3.5 group-hover:rotate-90 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New session
                <kbd class="ml-auto text-[10px] bg-[#1F1F1F] border border-[#2A2A2A] text-[#444] px-1 rounded">N</kbd>
            </button>
        </div>

        {{-- Nav --}}
        <div class="px-2 space-y-0.5 flex-shrink-0">
            <button wire:click="$set('currentView', 'routines')"
                class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] rounded-md transition-colors font-sans {{ $currentView === 'routines' || $currentView === 'new-routine' ? 'bg-[#252525] text-[#E5E5E5]' : 'text-[#777] hover:text-[#CCC] hover:bg-[#1F1F1F]' }}">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Routines
            </button>
            <a href="{{ route('chat', ['panel' => 'customize']) }}"
                class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] text-[#777] hover:text-[#CCC] hover:bg-[#1F1F1F] rounded-md transition-colors font-sans">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6M4.93 19.07l4.24-4.24M14.83 9.17l4.24-4.24"/></svg>
                Customize
            </a>
        </div>

        <div class="mx-3 my-2 border-t border-[#222] flex-shrink-0"></div>

        {{-- Recent Sessions --}}
        <div class="flex-1 overflow-y-auto scrollbar-hide px-2 min-h-0">
            <p class="px-2.5 text-[10px] font-semibold text-[#383838] uppercase tracking-widest mb-1.5 font-sans">Recents</p>

            @if($isStarted && $conversation && !collect($recentSessions)->contains('id', $conversation->id))
                <div class="flex items-center gap-2 px-2.5 py-2 bg-[#252525] rounded-md border border-[#333] mb-1">
                    <div class="w-1.5 h-1.5 rounded-full bg-[#4ADE80] flex-shrink-0 animate-pulse"></div>
                    <span class="text-[12px] text-[#E5E5E5] truncate font-sans">{{ $conversation->title ?? 'Active session' }}</span>
                </div>
            @endif

            @forelse($recentSessions as $session)
                <div class="group relative flex items-center rounded-md mb-0.5 {{ ($conversation?->id === $session['id']) ? 'bg-[#252525] border border-[#333]' : 'hover:bg-[#1F1F1F]' }}">
                    <button wire:click="loadSession({{ $session['id'] }})"
                        class="flex-1 flex items-center gap-2 px-2.5 py-2 text-left min-w-0">
                        @if($conversation?->id === $session['id'])
                            <div class="w-1.5 h-1.5 rounded-full bg-[#4ADE80] flex-shrink-0 animate-pulse"></div>
                        @else
                            <div class="w-1.5 h-1.5 rounded-full bg-[#333] flex-shrink-0 group-hover:bg-[#555]"></div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-[#888] group-hover:text-[#CCC] truncate font-sans transition-colors {{ ($conversation?->id === $session['id']) ? 'text-[#E5E5E5]' : '' }}">
                                {{ $session['title'] }}
                            </p>
                            <p class="text-[10px] text-[#333] font-sans mt-0.5">{{ $session['ago'] }}</p>
                        </div>
                    </button>
                    <button wire:click="deleteSession({{ $session['id'] }})"
                        wire:confirm="Delete this session?"
                        class="opacity-0 group-hover:opacity-100 p-1.5 mr-1 text-[#444] hover:text-[#F87171] transition-all rounded">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            @empty
                <div class="px-2.5 py-2 text-[11px] text-[#333] italic font-sans">No previous sessions</div>
            @endforelse
        </div>

        {{-- Profile --}}
        <div class="border-t border-[#2A2A2A] p-2 flex-shrink-0" x-data="{ profileMenuOpen: false }">
            <button @click="profileMenuOpen = !profileMenuOpen" @click.away="profileMenuOpen = false"
                class="w-full flex items-center gap-2 px-2 py-1.5 hover:bg-[#252525] rounded-md transition-colors">
                <div class="w-5 h-5 rounded-full bg-[#CC785C]/20 border border-[#CC785C]/40 flex items-center justify-center flex-shrink-0">
                    <span class="text-[9px] text-[#CC785C] font-bold font-sans">{{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}</span>
                </div>
                <span class="text-[12px] text-[#777] flex-1 text-left truncate font-sans">{{ auth()->user()?->name ?? 'Guest' }}</span>
                <svg class="w-3 h-3 text-[#383838] transition-transform duration-200" :class="profileMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div x-show="profileMenuOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute bottom-14 left-2 w-[220px] bg-[#1F1F1F] border border-[#333] rounded-lg shadow-xl py-1 z-50 font-sans">
                <div class="px-3 py-1.5 text-[11px] text-[#444] truncate border-b border-[#2A2A2A] mb-1">{{ auth()->user()?->email ?? '' }}</div>
                <button @click="$dispatch('open-settings-modal'); profileMenuOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-[#2A2A2A] text-[13px] text-[#CCC] flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-[#555]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    Settings
                </button>
                <div class="mx-3 my-1 border-t border-[#2A2A2A]"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-1.5 hover:bg-[#2A2A2A] text-[13px] text-[#CCC] flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-[#555]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════ MAIN AREA ═══════════════════════════ --}}
    <div class="flex-1 flex flex-col h-full min-w-0">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-2 px-3 py-2 border-b border-[#2A2A2A] bg-[#141414] flex-shrink-0">
            <button x-show="!sidebarOpen" @click="sidebarOpen = true"
                class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#444] hover:text-[#888]">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            </button>

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1 text-[12px] text-[#444] flex-1 min-w-0">
                <span class="text-[#CC785C]">~</span><span>/</span>
                <span class="text-[#666]">session</span>
                @if($isStarted && $conversation)
                    <span>/</span>
                    <span class="text-[#999] truncate max-w-[200px]">{{ Str::slug(substr($conversation->title ?? 'active', 0, 35)) }}</span>
                @endif
            </div>

            {{-- Token counter --}}
            @if($sessionTokens > 0)
            <div class="flex items-center gap-1 px-2 py-1 bg-[#1A1A1A] border border-[#252525] rounded text-[11px] font-sans flex-shrink-0">
                <svg class="w-3 h-3 text-[#444]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                <span class="text-[#555]">~{{ $this->formattedTokens() }} tokens</span>
            </div>
            @endif

            {{-- Model badge --}}
            <div class="flex items-center gap-1.5 px-2 py-1 bg-[#1F1F1F] border border-[#2A2A2A] rounded text-[11px] font-sans flex-shrink-0">
                <div class="w-1.5 h-1.5 rounded-full bg-[#4ADE80]"></div>
                <span class="text-[#777]">{{ collect($models)->concat($moreModels)->firstWhere('code', $selectedModel)?->name ?? 'Rynude' }}</span>
            </div>

            {{-- Mode badge --}}
            <div class="flex items-center gap-1.5 px-2 py-1 border rounded text-[11px] font-sans flex-shrink-0
                {{ $selectedMode === 'plan' ? 'bg-[#1A2030] border-[#60A5FA]/20 text-[#60A5FA]' : ($selectedMode === 'auto' ? 'bg-[#1A2A1A] border-[#4ADE80]/20 text-[#4ADE80]' : 'bg-[#231F1A] border-[#CC785C]/20 text-[#CC785C]') }}">
                <div class="w-1.5 h-1.5 rounded-full
                    {{ $selectedMode === 'plan' ? 'bg-[#60A5FA]' : ($selectedMode === 'auto' ? 'bg-[#4ADE80]' : 'bg-[#CC785C]') }}"></div>
                <span>{{ $selectedMode === 'plan' ? 'Plan' : ($selectedMode === 'auto' ? 'Auto' : 'Accept') }}</span>
            </div>

            {{-- Repo indicator --}}
            @if($repoConnected)
            <div class="flex items-center gap-1.5 px-2 py-1 bg-[#1F2933] border border-[#2A3A4A] rounded text-[11px] font-sans flex-shrink-0">
                <svg class="w-3 h-3 text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                <span class="text-[#4ADE80] max-w-[120px] truncate">{{ $repoConnected }}</span>
                <button wire:click="disconnectRepo" class="text-[#2A4A3A] hover:text-[#F87171] transition-colors ml-0.5">×</button>
            </div>
            @endif

            <button @click="rightPanelOpen = !rightPanelOpen" class="p-1.5 rounded hover:bg-[#2A2A2A] transition-colors text-[#444] hover:text-[#888] flex-shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
            </button>
        </div>

        {{-- CONTENT + RIGHT PANEL ROW --}}
        <div class="flex flex-1 min-h-0">

            {{-- MAIN CHAT COLUMN --}}
            <div class="flex flex-col flex-1 min-w-0 min-h-0">

                @if($currentView === 'chat')

                    {{-- Messages --}}
                    <div class="flex-1 overflow-y-auto scrollbar-hide" id="code-chat-container">

                        @if(!$isStarted)
                        {{-- EMPTY STATE --}}
                        <div class="flex flex-col items-center justify-center h-full min-h-[60vh] px-8 text-center">
                            <svg width="40" height="40" viewBox="0 0 100 100" class="text-[#CC785C] fill-current mb-6 opacity-70">
                                <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"/>
                            </svg>
                            <h1 class="font-sans text-[22px] font-semibold text-[#E5E5E5] mb-2 tracking-tight">What are we building?</h1>
                            <p class="font-sans text-[13px] text-[#444] max-w-sm leading-relaxed mb-8">Describe a task, ask a question, or paste some code. I can read files, run commands, and edit your codebase.</p>
                            <div class="flex flex-wrap gap-2 justify-center max-w-md">
                                @foreach([['M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4','Fix a bug'],['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','Write tests'],['M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z','Explain code'],['M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12','Refactor']] as [$path,$label])
                                <button wire:click="$set('message', '{{ $label }}')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-[#1F1F1F] hover:bg-[#252525] border border-[#2A2A2A] hover:border-[#3A3A3A] rounded-lg text-[12px] text-[#777] hover:text-[#CCC] transition-all font-sans">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $path }}" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @else

                        {{-- MESSAGES LIST --}}
                        <div class="max-w-3xl mx-auto px-4 pt-6 pb-4 flex flex-col gap-0" id="messages-list">

                            @foreach($messages as $idx => $msg)
                            @if($msg['role'] === 'user')
                            {{-- USER --}}
                            <div class="flex items-start gap-3 py-4 border-b border-[#1F1F1F]">
                                <div class="flex-shrink-0 mt-0.5 text-[13px]"><span class="text-[#CC785C] select-none">❯</span></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] text-[#383838] font-sans uppercase tracking-widest">you</span>
                                    </div>
                                    {{-- Attachments --}}
                                    @if(!empty($msg['attachments']))
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach($msg['attachments'] as $att)
                                        <div class="flex items-center gap-1.5 px-2 py-1 bg-[#1F2933] border border-[#2A3A4A] rounded text-[11px] font-sans">
                                            <svg class="w-3 h-3 text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span class="text-[#4ADE80] max-w-[150px] truncate">{{ $att['file_name'] }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                    <p class="text-[14px] text-[#DEDEDE] leading-relaxed whitespace-pre-wrap break-words">{{ $msg['content'] }}</p>
                                </div>
                            </div>
                            @else
                            {{-- ASSISTANT --}}
                            <div class="py-4 border-b border-[#1F1F1F]">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg width="14" height="14" viewBox="0 0 100 100" class="text-[#CC785C] fill-current flex-shrink-0">
                                        <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"/>
                                    </svg>
                                    <span class="text-[10px] text-[#383838] font-sans uppercase tracking-widest">rynude</span>
                                    <span class="text-[10px] text-[#2A2A2A]">·</span>
                                    <span class="text-[10px] text-[#383838] font-sans">{{ collect($models)->concat($moreModels)->firstWhere('code', $selectedModel)?->name ?? 'Sonnet' }}</span>
                                </div>
                                <div class="cc-prose text-[14px] leading-7 text-[#CCCCCC]">
                                    {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                                </div>
                            </div>
                            @endif
                            @endforeach

                            {{-- Streaming --}}
                            <div wire:loading wire:target="sendMessage, generateResponse" class="py-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg width="14" height="14" viewBox="0 0 100 100" class="text-[#CC785C] fill-current animate-pulse">
                                        <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"/>
                                    </svg>
                                    <span class="text-[10px] text-[#383838] font-sans uppercase tracking-widest">rynude</span>
                                </div>
                                <div class="mb-2 flex items-center gap-2 px-3 py-2 bg-[#1A1A1A] border border-[#252525] rounded-md w-fit">
                                    <div class="flex gap-1">
                                        <div class="w-1.5 h-1.5 rounded-full bg-[#CC785C] animate-bounce" style="animation-delay:0ms"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-[#CC785C] animate-bounce" style="animation-delay:150ms"></div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-[#CC785C] animate-bounce" style="animation-delay:300ms"></div>
                                    </div>
                                    <span class="text-[12px] text-[#555] font-sans">Thinking…</span>
                                </div>
                                <div wire:stream="message-stream" class="cc-prose text-[14px] leading-7 text-[#CCCCCC]"></div>
                            </div>

                        </div>
                        @endif
                    </div>

                    {{-- INPUT AREA --}}
                    <div class="flex-shrink-0 border-t border-[#2A2A2A] bg-[#141414] px-4 pt-3 pb-4">

                        {{-- File attachment hidden input --}}
                        <input type="file" wire:model="attachments" id="code-file-upload" class="hidden" multiple
                            accept="image/*,.pdf,.doc,.docx,.txt,.js,.ts,.php,.py,.go,.rs,.java,.cpp,.c,.h,.json,.yaml,.yml,.md,.sql,.sh,.bash">

                        {{-- Attachment previews --}}
                        @if(!empty($attachmentPreviews))
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($attachmentPreviews as $idx => $att)
                            <div class="flex items-center gap-1.5 px-2 py-1 bg-[#1A2530] border border-[#2A3A4A] rounded-md font-sans">
                                <svg class="w-3 h-3 text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                <span class="text-[11px] text-[#6EE7B7] max-w-[120px] truncate">{{ $att['name'] }}</span>
                                <button wire:click="removeAttachment({{ $idx }})" class="text-[#2A4A3A] hover:text-[#F87171] transition-colors text-sm leading-none">×</button>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Uploading indicator --}}
                        <div wire:loading wire:target="attachments" class="flex items-center gap-2 mb-2 text-[12px] text-[#555] font-sans">
                            <svg class="animate-spin w-3.5 h-3.5 text-[#CC785C]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Uploading…
                        </div>

                        {{-- Context pills --}}
                        <div class="flex items-center gap-2 mb-2 flex-wrap">

                            {{-- Env pill --}}
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.away="open = false"
                                    class="flex items-center gap-1.5 px-2 py-1 bg-[#1F1F1F] hover:bg-[#252525] border border-[#2A2A2A] rounded text-[11px] text-[#666] hover:text-[#CCC] transition-colors font-sans">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                                    Default
                                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute bottom-full left-0 mb-1 w-64 bg-[#1F1F1F] border border-[#2A2A2A] rounded-lg shadow-xl py-1.5 z-50 font-sans">
                                    <div class="px-3 py-1 text-[10px] text-[#383838] uppercase tracking-wider">Cloud</div>
                                    <button class="w-full px-3 py-1.5 hover:bg-[#252525] text-[12px] text-[#CCC] flex items-center justify-between">
                                        <span class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-[#444]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>Default</span>
                                        <svg class="w-3 h-3 text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                    <div class="px-3 py-1 mt-1 border-t border-[#2A2A2A] pt-2 text-[10px] text-[#383838] uppercase tracking-wider">Local Device</div>
                                    <button onclick="document.getElementById('code-folder-upload').click();" class="w-full px-3 py-1.5 hover:bg-[#252525] text-[12px] text-[#CCC] flex items-center justify-between transition-colors">
                                        <span class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-[#444]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Upload Folder</span>
                                    </button>
                                </div>
                                <input type="file" id="code-folder-upload" class="hidden" wire:model="attachments" webkitdirectory directory multiple>
                            </div>

                            {{-- Repo pill --}}
                            @if($repoConnected)
                                <div class="flex items-center gap-1.5 px-2 py-1 bg-[#1A2530] border border-[#2A3A4A] rounded text-[11px] font-sans">
                                    <svg class="w-3 h-3 text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                                    <span class="text-[#4ADE80] max-w-[100px] truncate">{{ $repoConnected }}</span>
                                </div>
                            @else
                                <button wire:click="$set('repoModalOpen', true)"
                                    class="flex items-center gap-1.5 px-2 py-1 bg-[#1F1F1F] hover:bg-[#252525] border border-[#2A2A2A] hover:border-[#3A4A3A] rounded text-[11px] text-[#555] hover:text-[#4ADE80] transition-colors font-sans group">
                                    <svg class="w-3 h-3 group-hover:text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                                    + Connect repo
                                </button>
                            @endif

                            {{-- Model selector --}}
                            <div x-data="{ open: false, selectedModel: @entangle('selectedModel'), moreOpen: false }" class="relative ml-auto">
                                <button @click="open = !open" @click.away="open = false; moreOpen = false"
                                    class="flex items-center gap-1.5 px-2 py-1 bg-[#1F1F1F] hover:bg-[#252525] border border-[#2A2A2A] rounded text-[11px] text-[#777] hover:text-[#CCC] transition-colors font-sans">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                    {{ collect($models)->concat($moreModels)->firstWhere('code', $selectedModel)?->name ?? 'Select' }}
                                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.away="open = false; moreOpen = false"
                                    class="absolute bottom-full right-0 mb-1 w-64 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl shadow-2xl py-1.5 z-50 font-sans">
                                    @foreach($models as $model)
                                    <button wire:click="$set('selectedModel', '{{ $model->code }}')" @click="open = false"
                                        class="w-full px-3 py-2 hover:bg-[#252525] text-left flex items-start justify-between gap-2 transition-colors {{ !$model->is_available ? 'opacity-30 cursor-not-allowed' : '' }}"
                                        {{ !$model->is_available ? 'disabled' : '' }}>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[13px] text-[#CCC]">{{ $model->name }}</span>
                                                @if(!$model->is_available)
                                                <span class="text-[9px] bg-[#2A2A2A] text-[#555] px-1 rounded">Soon</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-[#444] mt-0.5">{{ $model->description }}</div>
                                        </div>
                                        <svg x-show="selectedModel === '{{ $model->code }}'" class="w-3.5 h-3.5 text-[#CC785C] shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                    @endforeach

                                    <div class="mx-3 my-1.5 border-t border-[#252525]"></div>

                                    {{-- More models submenu --}}
                                    @if(count($moreModels) > 0)
                                    <div class="relative" x-data="{ moreOpen: false }">
                                        <button @mouseenter="moreOpen = true" @mouseleave="moreOpen = false"
                                            class="w-full px-3 py-2 hover:bg-[#252525] text-[13px] text-[#888] flex items-center justify-between transition-colors">
                                            <span>More models</span>
                                            <svg class="w-3.5 h-3.5 text-[#444]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                        </button>
                                        <div x-show="moreOpen" @mouseenter="moreOpen = true" @mouseleave="moreOpen = false" x-cloak
                                            class="absolute left-0 bottom-0 -translate-x-full w-56 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl shadow-2xl py-1.5 max-h-64 overflow-y-auto scrollbar-hide">
                                            @foreach($moreModels as $mm)
                                            <button wire:click="$set('selectedModel', '{{ $mm->code }}')" @click="open = false; moreOpen = false"
                                                class="w-full px-3 py-1.5 hover:bg-[#252525] text-left flex items-center justify-between text-[12px] text-[#888] hover:text-[#CCC] transition-colors {{ !$mm->is_available ? 'opacity-30 cursor-not-allowed' : '' }}"
                                                {{ !$mm->is_available ? 'disabled' : '' }}>
                                                {{ $mm->name }}
                                                <svg x-show="selectedModel === '{{ $mm->code }}'" class="w-3 h-3 text-[#CC785C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Mode --}}
                            <div x-data="{
                                open: false,
                                mode: @entangle('selectedMode'),
                                modes: [
                                    { value: 'accept', label: 'Accept edits', key: '1', desc: 'Write code directly, no confirmation needed' },
                                    { value: 'plan',   label: 'Plan mode',    key: '2', desc: 'Outlines a plan first, then you say go' },
                                    { value: 'auto',   label: 'Auto mode',    key: '3', desc: 'Fully autonomous, executes everything at once' },
                                ]
                            }" class="relative">
                                <button @click="open = !open" @click.away="open = false"
                                    class="flex items-center gap-1.5 px-2 py-1 bg-[#1F1F1F] hover:bg-[#252525] border rounded text-[11px] transition-colors font-sans"
                                    :class="{
                                        'border-[#CC785C]/40 text-[#CC785C]': mode === 'accept',
                                        'border-[#60A5FA]/40 text-[#60A5FA]': mode === 'plan',
                                        'border-[#4ADE80]/40 text-[#4ADE80]': mode === 'auto',
                                    }">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                        :class="{
                                            'bg-[#CC785C]': mode === 'accept',
                                            'bg-[#60A5FA]': mode === 'plan',
                                            'bg-[#4ADE80]': mode === 'auto',
                                        }"></span>
                                    <span x-text="modes.find(m=>m.value===mode)?.label ?? 'Accept edits'"></span>
                                    <svg class="w-2.5 h-2.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute bottom-full right-0 mb-1 w-64 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl shadow-2xl py-1.5 z-50 font-sans">
                                    <div class="px-3 py-1.5 text-[10px] text-[#383838] uppercase tracking-widest border-b border-[#252525] mb-1">Agent Mode</div>
                                    <template x-for="m in modes" :key="m.value">
                                        <button @click="mode = m.value; open = false"
                                            class="w-full px-3 py-2 hover:bg-[#252525] text-left flex items-start justify-between gap-3 transition-colors">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                        :class="{
                                                            'bg-[#CC785C]': m.value === 'accept',
                                                            'bg-[#60A5FA]': m.value === 'plan',
                                                            'bg-[#4ADE80]': m.value === 'auto',
                                                        }"></span>
                                                    <span class="text-[13px] text-[#CCC]" x-text="m.label"></span>
                                                    <span class="text-[10px] text-[#333] border border-[#2A2A2A] px-1 rounded" x-text="m.key"></span>
                                                </div>
                                                <p class="text-[11px] text-[#444] mt-0.5 pl-3.5 leading-relaxed" x-text="m.desc"></p>
                                            </div>
                                            <svg x-show="mode === m.value" class="w-3.5 h-3.5 text-[#CC785C] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        </button>
                                    </template>
                                    <div class="px-3 py-2 mt-1 border-t border-[#252525]">
                                        <p class="text-[10px] text-[#333] leading-relaxed">Mode affects how the AI structures its responses. You can switch anytime mid-session.</p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Main Input --}}
                        <div class="relative flex items-end gap-2 bg-[#1A1A1A] border border-[#2E2E2E] rounded-xl focus-within:border-[#3A3A3A] focus-within:ring-1 focus-within:ring-[#CC785C]/20 transition-all duration-150 px-3 py-2.5">
                            <textarea
                                wire:model="message"
                                wire:keydown.enter.prevent="sendMessage"
                                rows="1"
                                placeholder="How can I help with your code?"
                                class="flex-1 bg-transparent border-0 focus:ring-0 p-0 text-[14px] text-[#DEDEDE] placeholder-[#333] resize-none font-mono leading-relaxed max-h-[180px]"
                                oninput="this.style.height=''; this.style.height=this.scrollHeight+'px'"
                            ></textarea>

                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                {{-- Attach file --}}
                                <button onclick="document.getElementById('code-file-upload').click()"
                                    class="p-1.5 rounded-md text-[#383838] hover:text-[#777] hover:bg-[#252525] transition-colors" title="Attach file">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                </button>

                                {{-- Stop button (shown while streaming) --}}
                                <button wire:loading wire:target="sendMessage, generateResponse"
                                    wire:click="stopGeneration"
                                    class="p-1.5 rounded-md bg-[#F87171]/10 border border-[#F87171]/30 text-[#F87171] hover:bg-[#F87171]/20 transition-colors" title="Stop generation">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                                </button>

                                {{-- Send button --}}
                                <button wire:click="sendMessage"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage, generateResponse"
                                    wire:loading.class="hidden"
                                    class="p-1.5 rounded-md transition-all duration-150 disabled:opacity-40 {{ trim($message) || !empty($attachmentPreviews) ? 'bg-[#CC785C] text-white hover:bg-[#B86A4F]' : 'text-[#2A2A2A] bg-[#1F1F1F] cursor-not-allowed' }}">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-2 px-1">
                            <span class="text-[10px] text-[#2A2A2A] font-sans">
                                <kbd class="bg-[#1A1A1A] border border-[#252525] px-1 py-0.5 rounded text-[#383838]">Enter</kbd> send ·
                                <kbd class="bg-[#1A1A1A] border border-[#252525] px-1 py-0.5 rounded text-[#383838]">Shift+Enter</kbd> newline ·
                                <kbd class="bg-[#1A1A1A] border border-[#252525] px-1 py-0.5 rounded text-[#383838]">Ctrl+U</kbd> attach
                            </span>
                            <a href="{{ route('home') }}" class="text-[10px] text-[#2A2A2A] hover:text-[#555] font-sans transition-colors">← Back to Rynude</a>
                        </div>
                    </div>

                @elseif($currentView === 'routines')
                    @include('livewire.routines-list')
                @elseif($currentView === 'new-routine')
                    @include('livewire.new-routine')
                @endif
            </div>

            {{-- RIGHT PANEL --}}
            <div x-show="rightPanelOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="w-[280px] flex-shrink-0 border-l border-[#2A2A2A] bg-[#141414] flex flex-col h-full">
                <div class="flex items-center gap-0 border-b border-[#2A2A2A] px-2 pt-2 flex-shrink-0">
                    @foreach([['files','Files'],['diff','Diff'],['tools','Tools']] as [$tab,$label])
                    <button @click="rightPanelTab = '{{ $tab }}'"
                        class="px-3 py-1.5 text-[11px] font-sans rounded-t transition-colors"
                        :class="rightPanelTab==='{{ $tab }}'?'bg-[#1A1A1A] text-[#CCC] border-t border-x border-[#2A2A2A]':'text-[#383838] hover:text-[#666]'">{{ $label }}</button>
                    @endforeach
                    <button @click="rightPanelOpen = false" class="ml-auto p-1 text-[#2A2A2A] hover:text-[#555] transition-colors mb-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
                <div x-show="rightPanelTab==='files'" class="flex-1 overflow-y-auto scrollbar-hide p-2 font-sans">
                    <p class="text-[10px] text-[#2A2A2A] uppercase tracking-widest px-2 py-1">EXPLORER</p>
                    @php $ec=['php'=>'#7E9FC7','js'=>'#E5C07B','ts'=>'#3178C6','css'=>'#56B6C2','json'=>'#D19A66','env'=>'#98C379','md'=>'#98C379']; @endphp
                    
                    {{-- Local Files --}}
                    @if(count($localFilesTree) > 0)
                        <p class="text-[9px] text-[#444] uppercase tracking-widest px-2 py-1 mt-2">Local Files</p>
                        @foreach($localFilesTree as $item)
                        <div class="flex items-center gap-1.5 py-0.5 px-2 hover:bg-[#1F1F1F] rounded group">
                            <span class="w-3 flex-shrink-0"></span>
                            <span class="text-[9px] font-bold flex-shrink-0" style="color:{{ $ec[$item['extra']]??'#555' }}">{{ strtoupper($item['extra']) }}</span>
                            <span class="text-[12px] text-[#555] group-hover:text-[#CCC] truncate">{{ $item['name'] }}</span>
                        </div>
                        @endforeach
                    @endif

                    {{-- Repo Files --}}
                    @if(count($repoTree) > 0)
                        <p class="text-[9px] text-[#444] uppercase tracking-widest px-2 py-1 mt-2 mb-1">Repository: {{ basename($repoConnected) }}</p>
                        <div class="relative">
                            {{-- Loading overlay when fetching file --}}
                            <div wire:loading wire:target="loadFileFromRepo" class="absolute inset-0 bg-[#141414]/80 flex items-center justify-center z-10">
                                <svg class="w-5 h-5 text-[#4ADE80] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>

                            @foreach($repoTree as $item)
                            @php 
                                $isSelected = collect($selectedFilesContext)->contains('path', $item['path']);
                            @endphp
                            <div @if($item['type']==='file') wire:click="loadFileFromRepo('{{ $item['path'] }}')" @endif
                                class="flex items-center gap-1.5 py-0.5 hover:bg-[#1F1F1F] rounded group {{ $item['type']==='file'?'cursor-pointer':'' }} {{ $isSelected ? 'bg-[#2A2A2A]' : '' }}" style="padding-left:{{ $item['depth']*12+8 }}px">
                                @if($item['type']==='dir')
                                <svg class="w-3 h-3 text-[#383838] flex-shrink-0 {{ $item['extra']?'rotate-90':'' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                <svg class="w-3 h-3 text-[#CC785C]/50 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                <span class="text-[12px] text-[#666] group-hover:text-[#CCC] truncate">{{ $item['name'] }}</span>
                                @else
                                <span class="w-3 flex-shrink-0"></span>
                                <span class="text-[9px] font-bold flex-shrink-0" style="color:{{ $ec[$item['extra']]??'#555' }}">{{ strtoupper($item['extra']) }}</span>
                                <span class="text-[12px] truncate {{ $isSelected ? 'text-[#4ADE80]' : 'text-[#555] group-hover:text-[#CCC]' }}">{{ $item['name'] }}</span>
                                @if($isSelected)
                                <svg class="w-3 h-3 text-[#4ADE80] ml-auto mr-2 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                @endif
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @elseif(empty($localFilesTree))
                        <div class="text-center mt-10 px-4">
                            <p class="text-[11px] text-[#444] mb-2">No files loaded.</p>
                            <p class="text-[10px] text-[#333]">Connect a repository or upload files to see them here.</p>
                        </div>
                    @endif
                </div>
                <div x-show="rightPanelTab==='diff'" class="flex-1 p-4 font-sans text-center">
                    <p class="text-[12px] text-[#2A2A2A] mt-8">No file changes yet.</p>
                </div>
                <div x-show="rightPanelTab==='tools'" class="flex-1 overflow-y-auto scrollbar-hide p-3 font-sans">
                    <p class="text-[10px] text-[#2A2A2A] uppercase tracking-widest mb-3">Tool calls</p>
                    <p class="text-[12px] text-[#2A2A2A] text-center mt-6">Tool calls will appear here during the session.</p>
                </div>
            </div>

        </div>
    </div>

    {{-- ═══════════════ REPO CONNECT MODAL ═══════════════ --}}
    <div x-show="repoModalOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="repoModalOpen = false; $wire.set('repoModalOpen', false)"></div>
        <div class="relative w-full max-w-md bg-[#1A1A1A] border border-[#2A2A2A] rounded-2xl shadow-2xl p-6 font-sans">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl bg-[#4ADE80]/10 border border-[#4ADE80]/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#4ADE80]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-semibold text-[#E5E5E5]">Connect Repository</h3>
                    <p class="text-[12px] text-[#555] mt-0.5">Add context from a GitHub or GitLab repo</p>
                </div>
                <button @click="repoModalOpen = false; $wire.set('repoModalOpen', false)" class="ml-auto p-1.5 text-[#383838] hover:text-[#888] transition-colors rounded-lg hover:bg-[#252525]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] text-[#555] uppercase tracking-widest mb-1.5">Repository URL</label>
                    <input wire:model="repoUrl"
                        type="url"
                        placeholder="https://github.com/username/repo"
                        class="w-full bg-[#141414] border border-[#2A2A2A] focus:border-[#4ADE80]/40 focus:ring-1 focus:ring-[#4ADE80]/20 rounded-lg px-3 py-2.5 text-[13px] text-[#CCC] placeholder-[#333] font-mono transition-colors outline-none">
                    @if($repoError)
                    <p class="text-[11px] text-[#F87171] mt-1">{{ $repoError }}</p>
                    @endif
                </div>

                <div class="bg-[#141414] border border-[#252525] rounded-lg p-3">
                    <p class="text-[11px] text-[#444] leading-relaxed">
                        The repository URL will be added as context to your session. Rynude Code will reference this repo when answering questions.
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button @click="repoModalOpen = false; $wire.set('repoModalOpen', false)"
                        class="flex-1 px-4 py-2 text-[13px] font-medium text-[#888] bg-[#1F1F1F] hover:bg-[#252525] border border-[#2A2A2A] rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button wire:click="connectRepo"
                        class="flex-1 px-4 py-2 text-[13px] font-medium text-white bg-[#4ADE80]/80 hover:bg-[#4ADE80] rounded-lg transition-colors">
                        Connect
                    </button>
                </div>
            </div>
        </div>
    </div>

    <livewire:settings-modal />

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('message-added', () => {
                setTimeout(() => {
                    const c = document.getElementById('code-chat-container');
                    if (c) c.scrollTop = c.scrollHeight;
                }, 50);
                @this.call('generateResponse');
            });

            Livewire.on('scroll-to-bottom', () => {
                setTimeout(() => {
                    const c = document.getElementById('code-chat-container');
                    if (c) c.scrollTop = c.scrollHeight;
                }, 100);
            });

            // Ctrl+U shortcut to attach file
            document.addEventListener('keydown', e => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
                    e.preventDefault();
                    document.getElementById('code-file-upload')?.click();
                }
            });
        });
    </script>
</div>

{{-- Rynude Code–specific prose styles --}}
<style>
    .cc-prose { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; }
    .cc-prose p { margin: 0.5rem 0; color: #CCCCCC; font-size: 14px; line-height: 1.75; }
    .cc-prose h1,.cc-prose h2,.cc-prose h3 { color: #E5E5E5; font-weight: 600; margin: 1.25rem 0 0.5rem; }
    .cc-prose h1 { font-size: 18px; }
    .cc-prose h2 { font-size: 15px; }
    .cc-prose h3 { font-size: 13px; color: #888; }
    .cc-prose ul,.cc-prose ol { padding-left: 1.25rem; margin: 0.5rem 0; }
    .cc-prose li { margin: 0.2rem 0; color: #CCCCCC; font-size: 14px; }
    .cc-prose strong { color: #E5E5E5; font-weight: 600; }
    .cc-prose code:not(pre code) { background: #1F2933; color: #CC785C; padding: 1px 5px; border-radius: 4px; font-size: 12.5px; font-family: ui-monospace, monospace; border: 1px solid #2A3A4A; }
    .cc-prose pre { background: #0D1117; border: 1px solid #2A2A2A; border-radius: 8px; padding: 0; margin: 0.75rem 0; overflow: hidden; }
    .cc-prose pre > code { display: block; background: transparent; color: #E5E5E5; padding: 14px 16px; font-size: 12.5px; line-height: 1.7; border: none; overflow-x: auto; white-space: pre; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    .cc-prose blockquote { border-left: 2px solid #CC785C; padding-left: 12px; margin: 0.75rem 0; color: #777; font-style: italic; }
    .cc-prose a { color: #CC785C; text-decoration: underline; text-underline-offset: 2px; }
    .cc-prose table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .cc-prose th { background: #1F1F1F; color: #777; font-weight: 600; text-align: left; padding: 6px 10px; border-bottom: 1px solid #2A2A2A; }
    .cc-prose td { padding: 6px 10px; border-bottom: 1px solid #1A1A1A; color: #CCC; }
    .cc-prose hr { border: none; border-top: 1px solid #1F1F1F; margin: 1rem 0; }
</style>

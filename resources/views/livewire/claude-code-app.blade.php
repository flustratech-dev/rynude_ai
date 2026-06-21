<div class="flex w-full h-screen overflow-hidden bg-[#F9F8F6] dark:bg-stone-900 font-sans text-stone-800 dark:text-stone-200" x-data="{ sidebarOpen: true, isStarted: @entangle('isStarted') }">
    
    {{-- Left Sidebar --}}
    <div x-show="sidebarOpen" class="w-[300px] m-3 rounded-2xl border border-[#E5E5E5] dark:border-stone-800 bg-[#F9F8F6] dark:bg-stone-900 flex flex-col transition-all duration-300 shadow-sm overflow-hidden h-[calc(100vh-24px)] flex-shrink-0">
        
        {{-- Sidebar Header --}}
        <div class="flex items-center justify-between px-3 py-3 mt-1">
            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="font-serif text-[17px] font-medium text-[#2D2825] dark:text-stone-100 whitespace-nowrap hover:opacity-80 transition-opacity">Rynude Code</a>
                <span class="text-[10px] font-medium px-1.5 py-0.5 bg-[#EAE9E5] dark:bg-stone-800 text-stone-600 dark:text-stone-300 rounded-[4px] whitespace-nowrap">Research preview</span>
            </div>
            <div class="flex items-center gap-1 text-stone-400">
                <button @click="sidebarOpen = false" class="p-1 hover:bg-stone-200 dark:hover:bg-stone-800 rounded-md transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                </button>
                <button class="p-1 hover:bg-stone-200 dark:hover:bg-stone-800 rounded-md transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </div>
        </div>

        {{-- Sidebar Menu --}}
        <div class="px-2 mt-2 space-y-0.5">
            <button wire:click="$set('currentView', 'chat'); $set('isStarted', false)" class="w-full flex items-center gap-2.5 px-2 py-1.5 bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 rounded-lg text-[13px] font-medium transition-colors">
                <span class="text-stone-500 font-normal">+</span> New session
            </button>
            <button wire:click="$set('currentView', 'routines')" class="w-full flex items-center gap-2.5 px-2 py-1.5 text-stone-600 dark:text-stone-400 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg text-[13px] transition-colors {{ $currentView === 'routines' || $currentView === 'new-routine' ? 'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200' : '' }}">
                <svg class="w-3.5 h-3.5 {{ $currentView === 'routines' || $currentView === 'new-routine' ? 'text-[#2D2825] dark:text-stone-200' : 'text-stone-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg> Routines
            </button>
            <a href="{{ route('chat', ['panel' => 'customize']) }}" class="w-full flex items-center gap-2.5 px-2 py-1.5 text-stone-600 dark:text-stone-400 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg text-[13px] transition-colors">
                <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6M4.93 19.07l4.24-4.24M14.83 9.17l4.24-4.24"></path></svg> Customize
            </a>
            
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" class="w-full flex items-center gap-2.5 px-2 py-1.5 text-stone-600 dark:text-stone-400 hover:bg-[#EAE9E5] dark:hover:bg-stone-800 rounded-lg text-[13px] transition-colors">
                    <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg> More
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
                    <a href="{{ route('chat', ['panel' => 'customize']) }}" class="w-full text-left px-3 py-2 hover:bg-stone-50 dark:hover:bg-stone-800 text-[13px] text-[#2D2825] dark:text-stone-200 flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        Customize sidebar
                    </a>
                </div>
            </div>
        </div>

        {{-- Recents --}}
        <div class="mt-6 px-3">
            <div class="flex items-center justify-between text-[11px] font-medium text-stone-400 uppercase tracking-wider mb-2">
                <span>Recents</span>
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
            </div>
            
            {{-- No recent items yet --}}
            <div class="text-[12px] text-stone-400 italic px-1 py-1">
                No recent sessions
            </div>
        </div>

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
                    <span class="text-[13px] text-[#2D2825] dark:text-stone-200 font-medium">{{ auth()->user()?->name ?? 'Guest' }} <span class="text-stone-400 font-normal">· Max</span></span>
                </div>
                <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-200" :class="profileMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>

            {{-- Profile Dropdown Menu --}}
            <div x-show="profileMenuOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-2 mb-2 w-[240px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                <div class="px-3 py-1.5 text-[13px] font-medium text-stone-500 truncate mb-1">
                    {{ auth()->user()?->email ?? 'guest@example.com' }}
                </div>
                
                <button @click="$dispatch('open-settings-modal'); profileMenuOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.341 3.897C10.665 2.553 12.012 2 13 2s2.335.553 2.659 1.897l.11.455a1.75 1.75 0 001.37 1.328l.458.114c1.343.334 1.897 1.68 1.897 2.668 0 .988-.554 2.334-1.897 2.668l-.458.114a1.75 1.75 0 00-1.37 1.328l-.11.455A2.75 2.75 0 0113 16a2.75 2.75 0 01-2.659-1.897l-.11-.455a1.75 1.75 0 00-1.37-1.328l-.458-.114A2.75 2.75 0 016.5 9.54c0-.988.554-2.334 1.897-2.668l.458-.114A1.75 1.75 0 0010.231 4.35l.11-.453zM13 11a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
                        Settings
                    </div>
                    <span class="text-stone-400 text-[11px] font-sans">Ctrl&uarr;,</span>
                </button>
                
                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.974 0-5.742-.505-8.127-1.364" /></svg>
                        Language
                    </div>
                    <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
                
                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                        Get help
                    </div>
                </button>
                
                <div class="my-1.5 mx-3 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                
                <button @click="$dispatch('open-settings-modal', { tab: 'billing' }); profileMenuOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-9l-3 3m0 0l3 3m-3-3h11.25" /></svg>
                        View all plans
                    </div>
                </button>
                
                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Get apps and extensions
                    </div>
                </button>
                
                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                        Learn more
                    </div>
                    <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
                
                <div class="my-1.5 mx-3 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
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
    <div class="flex-1 flex flex-col h-full relative">
        
        {{-- Show Sidebar Toggle if Closed --}}
        <div x-show="!sidebarOpen" class="absolute top-4 left-4 z-10">
            <button @click="sidebarOpen = true" class="p-1.5 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-400 rounded-md transition-colors">
                <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
            </button>
        </div>

        {{-- Chat/Canvas Area --}}
        @if($currentView === 'chat')
            <div class="flex-1 overflow-y-auto w-full flex flex-col" id="code-chat-container">
            
            @if(!$isStarted)
                {{-- Empty State --}}
                <div class="flex-1 flex flex-col items-center justify-center min-h-[50vh]">
                    <div class="flex items-center gap-2.5">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D97757" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5l-10 14M22 12H2M19 17L5 7"></path></svg>
                        <h1 class="text-[22px] text-[#2D2825] dark:text-stone-200">What's up next, {{ auth()->user()?->name ?? 'Guest' }}?</h1>
                    </div>
                </div>
            @else
                {{-- Messages List --}}
                <div class="flex-1 max-w-5xl w-full mx-auto px-4 py-8 flex flex-col gap-6">
                    @foreach($messages as $msg)
                        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }} w-full">
                            @if($msg['role'] === 'user')
                                <div class="max-w-[80%] bg-[#F3F3F3] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 px-4 py-3 rounded-2xl rounded-br-sm text-[15px]">
                                    {{ $msg['content'] }}
                                </div>
                            @else
                                <div class="max-w-[85%] text-[#2D2825] dark:text-stone-200 text-[15px] prose dark:prose-invert prose-stone">
                                    <div class="flex gap-4">
                                        <div class="flex-shrink-0 mt-1">
                                            <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 leading-relaxed custom-prose markdown-body">
                                            {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Loading Indicator / Streaming Target --}}
                <div wire:loading wire:target="sendMessage, generateResponse" class="flex-1 max-w-5xl w-full mx-auto px-4 pb-8 flex flex-col gap-6">
                    <div class="flex justify-start w-full">
                        <div class="max-w-[85%] text-[#2D2825] dark:text-stone-200 text-[15px] prose dark:prose-invert prose-stone">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                    </svg>
                                </div>
                                <div class="flex-1 leading-relaxed custom-prose" wire:stream="message-stream">
                                    <div class="text-stone-400 text-sm flex items-center gap-2 mt-1.5">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span>Rynude Code is thinking...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Input Area --}}
        <div class="w-full max-w-5xl mx-auto px-4 pb-6 pt-2">
            <div class="relative w-full">
                
                {{-- Floating Pills & Mascot --}}
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2 ml-1">
                        {{-- Env Selector --}}
                        <div x-data="{ envOpen: false }" class="relative z-20">
                            <button @click="envOpen = !envOpen" @click.away="envOpen = false" class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[#E5E5E5] dark:border-stone-700 bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-lg text-[13px] font-medium text-stone-800 dark:text-stone-200 transition-colors">
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
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                    </div>
                                </button>
                                <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center gap-2 text-[13px] text-stone-500 dark:text-stone-400">
                                    <span>+</span> Add cloud environment...
                                </button>
                                
                                <div class="my-1.5 mx-3 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                                
                                <!-- Remote Control Section -->
                                <div class="px-3 py-1.5 text-[12px] text-stone-500">Remote Control</div>
                                <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-start gap-2 text-left">
                                    <svg class="w-4 h-4 mt-0.5 text-stone-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    <div>
                                        <div class="text-[13px] text-[#2D2825] dark:text-stone-200">Set up Remote Control</div>
                                        <div class="text-[12px] text-stone-500 mt-0.5 leading-snug">Run <code class="bg-[#F3F3F3] dark:bg-stone-800 px-1 py-0.5 rounded text-[11px] font-mono">rynude rc</code> on your machine to code from here.</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {{-- Repo Selector --}}
                        <div x-data="{ repoOpen: false }" class="relative z-20">
                            <button @click="repoOpen = !repoOpen" @click.away="repoOpen = false" class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[#E5E5E5] dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg text-[13px] font-medium text-stone-600 dark:text-stone-400 transition-colors">
                                <span class="text-stone-400 font-normal">+</span> Select repo...
                            </button>
                            {{-- Repo Dropdown --}}
                            <div x-show="repoOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[280px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-2 z-50">
                                <div class="px-3 pb-2 text-[13px] text-stone-500 leading-snug">
                                    Connect GitHub to pick a repository for this session<br>
                                    <a href="#" class="text-stone-500 underline decoration-stone-300 hover:text-stone-800 transition-colors mt-1 inline-block">Connect to GitHub</a>
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
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
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
                        wire:model="message" 
                        wire:keydown.enter.prevent="sendMessage"
                        rows="1" 
                        placeholder="Describe a task or ask a question" 
                        class="w-full max-h-[200px] resize-none bg-transparent border-0 focus:ring-0 p-0 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-stone-400"
                        oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                    ></textarea>
                    
                    <button wire:click="sendMessage" wire:loading.attr="disabled" wire:target="sendMessage, generateResponse" class="ml-2 p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors {{ trim($message) ? 'text-[#D97757] bg-orange-50 hover:bg-orange-100' : '' }} disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="sendMessage, generateResponse" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 10l3-3 3 3M12 7v10"></path></svg>
                        <svg wire:loading wire:target="sendMessage, generateResponse" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>

                {{-- Input Footer --}}
                <div class="flex items-center justify-between mt-2.5 px-1">
                    <div class="flex items-center gap-2">
                        {{-- Accept Edits Menu --}}
                        <div x-data="{ modeOpen: false }" class="relative z-20">
                            <button @click="modeOpen = !modeOpen" @click.away="modeOpen = false" class="px-2.5 py-1 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 bg-[#E5E5E5] dark:bg-stone-800 hover:bg-stone-300 dark:hover:bg-stone-700 rounded-md transition-colors">
                                Accept edits
                            </button>
                            
                            {{-- Mode Dropdown --}}
                            <div x-show="modeOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[180px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                                <div class="px-3 py-1.5 text-[12px] text-stone-500">Mode</div>
                                <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                                    <span>Accept edits</span>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-[#2D2825] dark:text-stone-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <span class="text-stone-400">1</span>
                                    </div>
                                </button>
                                <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                                    <span>Plan mode</span>
                                    <span class="text-stone-400">2</span>
                                </button>
                                <button class="w-full px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors flex items-center justify-between text-[13px] text-[#2D2825] dark:text-stone-200">
                                    <span>Auto mode</span>
                                    <span class="text-stone-400">3</span>
                                </button>
                            </div>
                        </div>

                        <span class="text-stone-400 font-normal">+</span>

                        {{-- Microphone Menu --}}
                        <div x-data="{ micOpen: false, isRecording: true }" class="relative z-20">
                            <button @click="micOpen = !micOpen" @click.away="micOpen = false" class="flex items-center gap-1 px-2 py-1 bg-[#E5E5E5] dark:bg-stone-800 hover:bg-stone-300 dark:hover:bg-stone-700 rounded-md text-stone-600 dark:text-stone-300 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>

                            {{-- Mic Dropdown --}}
                            <div x-show="micOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute bottom-full left-0 mb-2 w-[200px] bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50">
                                <div class="px-3 pb-1 pt-0.5 text-[12px] text-stone-500">Microphone</div>
                                <div class="px-3 py-1 flex items-center justify-between">
                                    <span class="text-[13px] text-[#2D2825] dark:text-stone-200">Hold to record</span>
                                    <div @click="isRecording = !isRecording" class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out cursor-pointer" :class="isRecording ? 'bg-[#2563EB]' : 'bg-gray-200 dark:bg-stone-700'">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition duration-200 ease-in-out" :class="isRecording ? 'translate-x-4' : 'translate-x-[3px]'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-[11.5px] text-stone-500 dark:text-stone-400">
                        <div x-data="{ open: false, selectedModel: @entangle('selectedModel'), extendedMode: true, moreModelsOpen: false, closeTimer: null }" class="relative">
                            <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 px-2.5 py-1.5 rounded-lg transition-colors">
                                <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[120px] truncate">{{ collect($models)->concat($moreModels)->firstWhere('code', $selectedModel)?->name ?? 'Select Model' }}</span>
                                <span class="text-[13px] text-stone-500 hidden sm:inline" x-show="extendedMode">Extended</span>
                                <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak class="absolute bottom-full right-0 mb-2 w-[240px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
                                @foreach($models as $model)
                                <button wire:click="$set('selectedModel', '{{ $model->code }}')" @click="open = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group {{ !$model->is_available ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$model->is_available ? 'disabled' : '' }}>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200" style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;">{{ $model->name }}</span>
                                            @if(!$model->is_available)
                                                <span class="inline-flex items-center gap-1 px-1 py-0.5 rounded text-[10px] font-medium bg-stone-100 dark:bg-stone-700 text-stone-500">
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                                    Unavailable
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[12px] text-stone-400 dark:text-stone-500 font-medium mt-0.5">{{ $model->description }}</div>
                                    </div>
                                    <svg x-show="selectedModel === '{{ $model->code }}'" class="w-4 h-4 text-[#2563EB] shrink-0 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                                @endforeach
                                <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                <!-- Extended Toggle -->
                                <div class="px-3 py-1.5 flex items-center justify-between cursor-pointer hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors group" @click="extendedMode = !extendedMode">
                                    <div>
                                        <div class="text-[13px] text-stone-800 dark:text-stone-200" style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;">Extended</div>
                                        <p class="text-[12px] text-stone-400 dark:text-stone-500 font-medium mt-0.5">Always uses deep reasoning</p>
                                    </div>
                                    <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out" :class="extendedMode ? 'bg-[#2563EB]' : 'bg-gray-200'">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white dark:bg-stone-800 shadow transition duration-200 ease-in-out" :class="extendedMode ? 'translate-x-4' : 'translate-x-[3px]'"></span>
                                    </div>
                                </div>
                                <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                <!-- More Models -->
                                <div class="relative" @mouseenter="clearTimeout(closeTimer); moreModelsOpen = true" @mouseleave="closeTimer = setTimeout(() => { moreModelsOpen = false }, 250)">
                                    <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200" style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;">More models</span>
                                        <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    
                                    <!-- Sub-menu -->
                                    <div x-show="moreModelsOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full sm:bottom-0 mb-1 sm:mb-0 w-[200px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
                                        @foreach($moreModels as $mModel)
                                        <button wire:click="$set('selectedModel', '{{ $mModel->code }}')" @click="open = false; moreModelsOpen = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group {{ !$mModel->is_available ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$mModel->is_available ? 'disabled' : '' }}>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200" style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;">{{ $mModel->name }}</span>
                                            <svg x-show="selectedModel === '{{ $mModel->code }}'" class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        @elseif($currentView === 'routines')
            @include('livewire.routines-list')
        @elseif($currentView === 'new-routine')
            @include('livewire.new-routine')
        @endif
        </div>

    </div>
    
    <livewire:settings-modal />
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('message-added', () => {
                setTimeout(() => {
                    const container = document.getElementById('code-chat-container');
                    if (container) container.scrollTop = container.scrollHeight;
                }, 50);
                
                // Trigger generateResponse from the frontend to ensure UI updates first
                @this.call('generateResponse');
            });
        });
    </script>
</div>

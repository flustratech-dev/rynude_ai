<div
    x-data="{ open: true }"
    @sidebar-toggle.window="open = $event.detail.open"
    class="h-full w-full flex flex-col bg-[#F9F8F6] dark:bg-claude-bg-dark font-claude-response"
>
    {{-- ========== EXPANDED MODE ========== --}}
    <div x-show="open" x-cloak class="h-full flex flex-col" x-data="{ searchOpen: false, searchQuery: '' }">
        {{-- Top Header --}}
        <div class="flex items-center justify-between px-3 py-2.5 mt-0.5">
            <button @click="activePanel = null; window.dispatchEvent(new CustomEvent('close-artifact-panel')); window.location.href = '{{ route('chat') }}'" class="font-serif text-[20px] font-semibold text-[#2D2825] dark:text-stone-200 hover:opacity-80 transition-opacity text-left focus:outline-none">Rynude</button>
            <div class="flex items-center gap-1">
                <button
                    @click="searchOpen = !searchOpen; if(searchOpen) $nextTick(() => $refs.sidebarSearch?.focus())"
                    class="p-1 transition-colors"
                    :class="searchOpen ? 'text-[#D97757]' : 'text-gray-500 dark:text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200'"
                    title="Search chats"
                >
                    <svg class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </button>
                <button
                    @click="window.dispatchEvent(new CustomEvent('toggle-sidebar'))"
                    class="p-1 text-gray-500 dark:text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors group"
                >
                    <svg class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line class="sidebar-line-shift" x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Inline Search --}}
        <div x-show="searchOpen" x-cloak x-transition class="px-3 pb-1">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 dark:text-stone-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input
                    x-ref="sidebarSearch"
                    type="text"
                    x-model.debounce.300ms="searchQuery"
                    @keydown.escape="searchOpen = false; searchQuery = ''"
                    placeholder="Search chats..."
                    class="w-full bg-white dark:bg-[#2C2C2C] border border-[#E5E5E5] dark:border-stone-700 rounded-lg pl-8 pr-8 py-1.5 text-[13px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 dark:placeholder-stone-500 focus:outline-none focus:border-[#D97757] dark:focus:border-[#D97757] transition-colors"
                >
                <button x-show="searchQuery !== ''" @click="searchQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-stone-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="px-2 mt-1 space-y-0.5">
            @if($hasUpdate ?? false)
            <button
                @click="window.dispatchEvent(new CustomEvent('open-system-update'))"
                class="w-full flex items-center justify-between px-2 py-1.5 mb-1 rounded-lg text-[13px] font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-500/10 dark:hover:bg-blue-500/20 transition-colors border border-blue-200 dark:border-blue-500/20 shadow-sm"
            >
                <div class="flex items-center gap-2.5">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Update Available</span>
                </div>
                <span class="flex h-2 w-2 relative mr-1">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
            </button>
            @endif

            <button @click="activePanel = null; window.dispatchEvent(new CustomEvent('close-artifact-panel')); if (window.innerWidth < 768) { sidebarOpen = false; open = false; }; window.dispatchEvent(new CustomEvent('newChat')); window.history.pushState({}, '', '{{ route('chat') }}');" class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] text-[#2D2825] dark:text-stone-300 hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38] transition-colors group">
                <div class="flex items-center justify-center rounded-full size-[1.4rem] -mx-[0.2rem] border border-[#DCD7D2] dark:border-stone-600 bg-[#EAE6E1]/40 dark:bg-stone-700/30 transition-all ease-in-out group-hover:-rotate-3 group-hover:scale-110 group-active:rotate-6 group-active:scale-[0.98]">
                    <svg class="w-[14px] h-[14px] text-gray-600 dark:text-stone-300 group-hover:text-[#2D2825] dark:group-hover:text-white transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </div>
                <span>New chat</span>
            </button>

            <button
                @click="activePanel = activePanel === 'chats' ? null : 'chats'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 group"
                :class="activePanel === 'chats' ? 'bg-claude-200 dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-300 hover:bg-claude-200/60 dark:hover:bg-[#3A3A38]'"
            >
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path class="chat-bubble-1" stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
                <span>Chats</span>
            </button>

            <button
                @click="activePanel = activePanel === 'projects' ? null : 'projects'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 group"
                :class="activePanel === 'projects' ? 'bg-claude-200 dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-300 hover:bg-claude-200/60 dark:hover:bg-[#3A3A38]'"
            >
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    <path class="project-arrow" stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6.75m0 0l-3-3m3 3l3-3" />
                </svg>
                <span>Projects</span>
            </button>

            <button
                @click="activePanel = activePanel === 'artifacts' ? null : 'artifacts'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 group"
                :class="activePanel === 'artifacts' ? 'bg-claude-200 dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-300 hover:bg-claude-200/60 dark:hover:bg-[#3A3A38]'"
            >
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path class="artifact-triangle" d="M12 3l4 7H8z" />
                    <circle class="artifact-circle" cx="16" cy="16" r="3" />
                    <rect class="artifact-square" x="5" y="13" width="6" height="6" rx="1" />
                </svg>
                <span>Artifacts</span>
            </button>

            <button
                @click="activePanel = activePanel === 'customize' ? null : 'customize'; window.dispatchEvent(new CustomEvent('close-artifact-panel')); if(activePanel === 'customize') { sidebarOpen = false; open = false; }"
                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 group"
                :class="activePanel === 'customize' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-[#2C2C2C] dark:text-stone-200' : 'text-[#2D2825] dark:text-stone-300 hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38]'"
            >
                <svg class="w-[18px] h-[18px] flex-shrink-0 customize-gear" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                </svg>
                <span>Customize</span>
            </button>

            <a
                href="{{ route('api-keys') }}"
                class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 text-[#2D2825] dark:text-stone-300 hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38]"
            >
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
                <span>Add API</span>
            </a>
        </div>

        {{-- Products --}}
        <div class="mt-4 px-2">
            <div class="px-2 py-1">
                <span class="text-[12px] font-medium text-gray-500 dark:text-stone-400">Products</span>
            </div>
            
            <div class="space-y-0.5 mt-0.5">
                <a
                    href="{{ route('code') }}"
                    class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 text-[#2D2825] dark:text-stone-300 hover:bg-claude-200/60 dark:hover:bg-[#3A3A38] group"
                >
                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path class="code-bracket-right" stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25"/>
                        <path class="code-bracket-left" stroke-linecap="round" stroke-linejoin="round" d="M6.75 17.25L1.5 12l5.25-5.25"/>
                        <path class="code-slash" stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75l-4.5 16.5"/>
                    </svg>
                    <span>Code</span>
                </a>

                <button
                    @click="activePanel = activePanel === 'cowork' ? null : 'cowork'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
                    class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 group"
                    :class="activePanel === 'cowork' ? 'bg-claude-200 dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-300 hover:bg-claude-200/60 dark:hover:bg-[#3A3A38]'"
                >
                    <svg class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path class="cowork-line" d="M9 6h11M9 12h11M9 18h11"/>
                        <path class="cowork-check" d="M5 6l1 1 2-2M5 12l1 1 2-2M5 18l1 1 2-2"/>
                    </svg>
                    <span>Cowork</span>
                </button>

                <button
                    @click="activePanel = activePanel === 'design' ? null : 'design'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
                    class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-[13px] transition-all duration-200 group"
                    :class="activePanel === 'design' ? 'bg-claude-200 dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-300 hover:bg-claude-200/60 dark:hover:bg-[#3A3A38]'"
                >
                    <svg class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path class="design-palette" d="M12 2a10 10 0 0 0-10 10c0 5.52 4.48 10 10 10a2 2 0 0 0 2-2 2 2 0 0 0-2-2h-1a3 3 0 0 1-3-3 3 3 0 0 1 3-3h3a5 5 0 0 0 5-5c0-4.42-3.58-8-8-8z"/>
                        <circle class="design-dot-1" cx="7.5" cy="10.5" r="1.5"/>
                        <circle class="design-dot-2" cx="10.5" cy="6.5" r="1.5"/>
                        <circle class="design-dot-3" cx="14.5" cy="6.5" r="1.5"/>
                        <circle class="design-dot-4" cx="17.5" cy="10.5" r="1.5"/>
                    </svg>
                    <span>Design</span>
                </button>
            </div>
        </div>

        {{-- Recents --}}
        <div class="mt-4 px-2 flex-1 overflow-hidden flex flex-col" x-data="sidebarRecentsState()" x-init="init()">
            <div class="flex items-center justify-between px-2 py-1">
                <span class="text-[12px] font-medium text-gray-500 dark:text-stone-400">Recents</span>
                <button @click="activePanel = activePanel === 'chats' ? null : 'chats'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))" class="text-gray-400 dark:text-stone-500 hover:text-gray-600 dark:hover:text-stone-300 transition-colors" title="Manage chats">
                    <svg class="w-[14px] h-[14px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 13.5V3.75m0 9.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 9.75V10.5" />
                    </svg>
                </button>
            </div>
            
            <div id="sidebar-recents" class="flex-1 overflow-y-auto mt-0.5 space-y-0.5 pr-1">
                <template x-for="(items, groupName) in groupedRecents" :key="groupName">
                    <div>
                        <div class="text-[11px] font-medium text-stone-400 dark:text-stone-500 px-2 pt-2.5 pb-1 uppercase tracking-wider" x-text="groupName"></div>
                        <template x-for="c in items" :key="c.id">
                            <div class="relative group flex items-center w-full rounded-lg transition-colors"
                                 :class="conversationId === c.id ? 'bg-[#EAE9E5] dark:bg-[#2C2C2C] text-gray-900 dark:text-stone-200 font-medium' : 'text-stone-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38] hover:text-gray-900 dark:hover:text-stone-200'">
                                <button
                                    @click="activePanel = null; window.dispatchEvent(new CustomEvent('close-artifact-panel')); if (window.innerWidth < 768) { sidebarOpen = false; open = false; }; window.dispatchEvent(new CustomEvent('selectConversation', { detail: { conversationId: c.id } })); window.history.pushState({}, '', '/chat?conversation=' + c.id);"
                                    class="flex-1 text-left px-2 py-1.5 text-[13px] truncate"
                                    x-text="c.title"
                                ></button>
                                <div class="absolute right-1 hidden group-hover:flex items-center rounded-lg bg-[#EAE9E5] dark:bg-[#2C2C2C]">
                                    <button
                                        @click="toggleStar(c)"
                                        class="p-1 transition-colors"
                                        :class="c.is_starred ? 'text-[#D97757]' : 'text-gray-400 hover:text-[#D97757]'"
                                        :title="c.is_starred ? 'Unstar' : 'Star'"
                                    >
                                        <svg class="w-3.5 h-3.5" :fill="c.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    </button>
                                    <button
                                        @click="renameChat(c)"
                                        class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-stone-200 transition-colors"
                                        title="Rename"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    </button>
                                    <button
                                        @click="if(confirm('Are you sure you want to delete this chat?')) { deleteChat(c.id); }"
                                        class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                        title="Delete"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                <div x-show="recents.length === 0" class="px-2 py-2 text-[13px] text-gray-400 dark:text-stone-500">No recent chats</div>
            </div>
        </div>

        {{-- Bottom Profile --}}
        <div class="border-t border-claude-border-light dark:border-claude-border-dark px-2 py-2 relative bg-[#F9F8F6] dark:bg-claude-bg-dark">
            @auth
                <div x-data="{ profileMenuOpen: false }">
                    <div
                        x-show="profileMenuOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        @click.away="profileMenuOpen = false"
                        class="absolute bottom-full left-2 right-2 mb-2 w-auto max-h-[60vh] overflow-y-auto overflow-x-hidden custom-scrollbar bg-white dark:bg-[#2C2C2C] border border-gray-200 dark:border-stone-700 rounded-2xl shadow-lg z-50 py-2"
                        style="display: none;"
                    >
                        <div class="text-sm text-gray-500 dark:text-stone-400 font-medium px-4 py-2 mb-1">
                            {{ auth()->user()->email }}
                        </div>
                        <button
                            @click="profileMenuOpen = false; window.dispatchEvent(new CustomEvent('open-settings-ui', { detail: 'general' }));"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Settings</span>
                            </span>
                            <span class="text-xs text-gray-400 font-light">Ctrl ⇧ ,</span>
                        </button>
                        <div x-data="{ languageOpen: false, currentLang: localStorage.getItem('rynude_lang') || 'English' }" class="relative">
                            <button
                                @click="languageOpen = !languageOpen"
                                class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                            >
                                <span class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                                    </svg>
                                    <span>Language</span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-400 dark:text-stone-500" x-text="currentLang"></span>
                                    <svg class="w-4 h-4 text-gray-400 dark:text-stone-500 transition-transform duration-200" :class="languageOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </span>
                            </button>
                            <div
                                x-show="languageOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="pl-8 pr-4 pb-1 space-y-0.5"
                            >
                                <template x-for="lang in ['English', 'Bahasa Indonesia']" :key="lang">
                                    <button
                                        @click="currentLang = lang; localStorage.setItem('rynude_lang', lang); languageOpen = false"
                                        class="flex items-center justify-between w-full px-3 py-2 text-[13px] text-gray-600 dark:text-stone-300 hover:bg-[#F3F3F3] dark:hover:bg-[#3A3A38] rounded-lg transition-colors"
                                    >
                                        <span x-text="lang"></span>
                                        <svg x-show="currentLang === lang" class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <button
                            @click="profileMenuOpen = false; window.dispatchEvent(new CustomEvent('open-help-modal', { detail: { tab: 'help' } }))"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                                </svg>
                                <span>Get help</span>
                            </span>
                        </button>
                        <div class="border-t border-gray-100 dark:border-stone-800 my-1"></div>
                        <button
                            @click="profileMenuOpen = false; window.dispatchEvent(new CustomEvent('open-settings-ui', { detail: 'billing' }));"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/>
                                </svg>
                                <span>Upgrade plan</span>
                            </span>
                        </button>
                        <button
                            @click="profileMenuOpen = false; window.dispatchEvent(new CustomEvent('open-help-modal', { detail: { tab: 'apps' } }))"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                                <span>Get apps and extensions</span>
                            </span>
                        </button>
                        <button
                            @click="profileMenuOpen = false; window.dispatchEvent(new CustomEvent('open-system-update'))"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>Check for updates</span>
                            </span>
                        </button>

                        {{-- Learn More Submenu --}}
                        <div x-data="{ learnMoreOpen: false }" class="relative">
                            <button
                                @click="learnMoreOpen = !learnMoreOpen"
                                class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-stone-300 hover:bg-[#F9F8F6] dark:hover:bg-[#3A3A38] cursor-pointer transition-colors"
                            >
                                <span class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                    </svg>
                                    <span>Learn more</span>
                                </span>
                                <svg class="w-4 h-4 text-gray-400 dark:text-stone-500 transition-transform duration-200" :class="learnMoreOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                </svg>
                            </button>
                            <div
                                x-show="learnMoreOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="pl-8 pr-4 pb-1 space-y-0.5"
                            >
                                <a href="https://docs.anthropic.com" target="_blank" rel="noopener" class="flex items-center gap-2 px-3 py-2 text-[13px] text-gray-600 dark:text-stone-300 hover:bg-[#F3F3F3] dark:hover:bg-[#3A3A38] rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                    </svg>
                                    <span>Documentation</span>
                                    <svg class="w-3 h-3 text-gray-300 ml-auto flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                </a>
                                <a href="https://anthropic.com/blog" target="_blank" rel="noopener" class="flex items-center gap-2 px-3 py-2 text-[13px] text-gray-600 dark:text-stone-300 hover:bg-[#F3F3F3] dark:hover:bg-[#3A3A38] rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/>
                                    </svg>
                                    <span>Blog</span>
                                    <svg class="w-3 h-3 text-gray-300 ml-auto flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                </a>
                                <a href="https://status.anthropic.com" target="_blank" rel="noopener" class="flex items-center gap-2 px-3 py-2 text-[13px] text-gray-600 dark:text-stone-300 hover:bg-[#F3F3F3] dark:hover:bg-[#3A3A38] rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>
                                    </svg>
                                    <span>System Status</span>
                                    <svg class="w-3 h-3 text-gray-300 ml-auto flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-stone-800 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-[#F9F8F6] cursor-pointer transition-colors">
                                <span class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                    </svg>
                                    <span>Log out</span>
                                </span>
                            </button>
                        </form>
                    </div>

                    <button @click="profileMenuOpen = !profileMenuOpen" class="w-full flex items-center justify-between px-2 py-2 hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38] rounded-lg transition-colors">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-8 h-8 flex-shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                            <span class="text-[15px] text-[#2D2825] dark:text-stone-200 font-medium">
                                {{ auth()->user()->name }} <span class="text-stone-400 font-normal">· Max</span>
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-200" :class="profileMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                </div>
            @else
                <div class="flex flex-col items-center gap-1">
                    <a href="{{ route('login') }}" class="w-full text-center block px-4 py-2 bg-[#2D2825] text-white rounded-lg text-sm font-medium hover:bg-black transition-colors">Log in</a>
                    <a href="{{ route('register') }}" class="w-full text-center block mt-2 text-sm text-gray-500 hover:text-gray-800 transition-colors">Sign up</a>
                </div>
            @endauth
        </div>
    </div>

    {{-- ========== COLLAPSED MODE (icons only) ========== --}}
    <div x-show="!open" x-cloak class="h-full w-full flex flex-col items-center py-4 px-2 gap-1 bg-[#F9F8F6] dark:bg-claude-bg-dark">
        <button
            @click="window.dispatchEvent(new CustomEvent('toggle-sidebar'))"
            class="p-2 rounded-lg hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38] transition-colors text-gray-500 dark:text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 w-full flex items-center justify-center mb-4 group"
        >
            <svg class="w-[20px] h-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line class="sidebar-line-shift" x1="9" y1="3" x2="9" y2="21"></line>
            </svg>
        </button>

        @if($hasUpdate ?? false)
        <button
            @click="window.dispatchEvent(new CustomEvent('open-system-update'))"
            class="p-2 rounded-lg mb-1 transition-colors w-full flex items-center justify-center text-blue-600 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-500/10 dark:hover:bg-blue-500/20 relative"
            title="Update Available"
        >
            <span class="absolute top-1 right-1 flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
        </button>
        @endif

        <button @click="activePanel = null; window.dispatchEvent(new CustomEvent('close-artifact-panel')); if (window.innerWidth < 768) { sidebarOpen = false; open = false; }; window.dispatchEvent(new CustomEvent('newChat')); window.history.pushState({}, '', '{{ route('chat') }}');" class="p-2 rounded-lg hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38] transition-colors text-gray-500 dark:text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 w-full flex items-center justify-center group" title="New chat">
            <div class="flex items-center justify-center rounded-full size-[1.4rem] -mx-[0.2rem] border border-[#DCD7D2] dark:border-stone-600 bg-[#EAE6E1]/40 dark:bg-stone-700/30 transition-all ease-in-out group-hover:-rotate-3 group-hover:scale-110 group-active:rotate-6 group-active:scale-[0.98]">
                <svg class="w-[14px] h-[14px] text-gray-600 dark:text-stone-300 group-hover:text-[#2D2825] dark:group-hover:text-white transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </div>
        </button>

        <div class="w-6 border-t border-gray-200 dark:border-stone-800 my-1"></div>

        <button
            @click="activePanel = activePanel === 'chats' ? null : 'chats'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center group"
            :class="activePanel === 'chats' ? 'bg-[#EAE9E5] dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200' : 'text-gray-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-[#3A3A38] hover:text-[#2D2825] dark:hover:text-stone-200'"
            title="Chats"
        >
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path class="chat-bubble-1" stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
            </svg>
        </button>

        <button
            @click="activePanel = activePanel === 'projects' ? null : 'projects'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center group"
            :class="activePanel === 'projects' ? 'bg-[#EAE9E5] dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200' : 'text-gray-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200'"
            title="Projects"
        >
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                <path class="project-arrow" stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6.75m0 0l-3-3m3 3l3-3" />
            </svg>
        </button>

        <button
            @click="activePanel = activePanel === 'artifacts' ? null : 'artifacts'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center group"
            :class="activePanel === 'artifacts' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-[#2C2C2C] dark:text-stone-200' : 'text-gray-500 hover:bg-[#EAE9E5]/60 hover:text-[#2D2825] dark:text-stone-400 dark:hover:bg-stone-800/50 dark:hover:text-stone-200'"
            title="Artifacts"
        >
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path class="artifact-triangle" d="M12 3l4 7H8z" />
                <circle class="artifact-circle" cx="16" cy="16" r="3" />
                <rect class="artifact-square" x="5" y="13" width="6" height="6" rx="1" />
            </svg>
        </button>

        <button
            @click="activePanel = activePanel === 'customize' ? null : 'customize'; window.dispatchEvent(new CustomEvent('close-artifact-panel')); if(activePanel === 'customize') { sidebarOpen = false; open = false; }"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center group"
            :class="activePanel === 'customize' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-[#2C2C2C] dark:text-stone-200' : 'text-gray-500 hover:bg-[#EAE9E5]/60 hover:text-[#2D2825] dark:text-stone-400 dark:hover:bg-stone-800/50 dark:hover:text-stone-200'"
            title="Customize"
        >
            <svg class="w-[18px] h-[18px] customize-gear" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
            </svg>
        </button>

        <a
            href="{{ route('api-keys') }}"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center text-gray-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200"
            title="Add API"
        >
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
        </a>

        <div class="w-6 border-t border-gray-200 my-1"></div>

        <a
            href="{{ route('code') }}"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center text-gray-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200 group"
            title="Code"
        >
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path class="code-bracket-right" stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25"/>
                <path class="code-bracket-left" stroke-linecap="round" stroke-linejoin="round" d="M6.75 17.25L1.5 12l5.25-5.25"/>
                <path class="code-slash" stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75l-4.5 16.5"/>
            </svg>
        </a>

        <button
            @click="activePanel = activePanel === 'cowork' ? null : 'cowork'; window.dispatchEvent(new CustomEvent('close-artifact-panel'))"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center group"
            :class="activePanel === 'cowork' ? 'bg-[#EAE9E5] dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200' : 'text-gray-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200'"
            title="Cowork"
        >
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path class="cowork-line" d="M9 6h11M9 12h11M9 18h11"/>
                <path class="cowork-check" d="M5 6l1 1 2-2M5 12l1 1 2-2M5 18l1 1 2-2"/>
            </svg>
        </button>

        <button
            @click="activePanel = activePanel === 'design' ? null : 'design'"
            class="p-2 rounded-lg transition-colors w-full flex items-center justify-center group"
            :class="activePanel === 'design' ? 'bg-[#EAE9E5] dark:bg-[#2C2C2C] text-[#2D2825] dark:text-stone-200' : 'text-gray-500 dark:text-stone-400 hover:bg-[#EAE9E5]/60 dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200'"
            title="Design"
        >
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path class="design-palette" d="M12 2a10 10 0 0 0-10 10c0 5.52 4.48 10 10 10a2 2 0 0 0 2-2 2 2 0 0 0-2-2h-1a3 3 0 0 1-3-3 3 3 0 0 1 3-3h3a5 5 0 0 0 5-5c0-4.42-3.58-8-8-8z"/>
                <circle class="design-dot-1" cx="7.5" cy="10.5" r="1.5"/>
                <circle class="design-dot-2" cx="10.5" cy="6.5" r="1.5"/>
                <circle class="design-dot-3" cx="14.5" cy="6.5" r="1.5"/>
                <circle class="design-dot-4" cx="17.5" cy="10.5" r="1.5"/>
            </svg>
        </button>

        <div class="flex-1"></div>

        @auth
            <button class="p-1.5 hover:bg-[#EAE9E5]/60 rounded-xl transition-colors w-full flex items-center justify-center mb-2" title="{{ auth()->user()->name }}">
                <svg class="w-8 h-8 flex-shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            </button>
        @else
            <a href="{{ route('login') }}" class="p-2 rounded-lg hover:bg-[#EAE9E5]/60 dark:hover:bg-stone-800/50 transition-colors text-gray-500 dark:text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 w-full flex items-center justify-center mb-2" title="Log in">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
            </a>
        @endauth
    </div>
</div>

<script>
function sidebarRecentsState() {
    return {
        recents: [],
        conversationId: null,
        init() {
            var self = this;
            this.loadRecents();

            var urlParams = new URLSearchParams(window.location.search);
            var convId = urlParams.get('conversation');
            if (convId) this.conversationId = parseInt(convId);

            window.addEventListener('selectConversation', function(e) {
                if (e.detail && e.detail.conversationId) self.conversationId = e.detail.conversationId;
            });
            window.addEventListener('conversationCreated', function(e) {
                self.loadRecents();
                if (e.detail && e.detail.id) self.conversationId = e.detail.id;
            });
            window.addEventListener('conversationDeleted', function() {
                self.loadRecents();
            });
            window.addEventListener('newChat', function() {
                self.conversationId = null;
            });

            // The sidebar search box lives in the parent Alpine scope; wire it
            // to a server-side search (title + message contents).
            try {
                this.$watch('searchQuery', function(q) { self.loadRecents(q); });
            } catch(e) {}
        },
        loadRecents(q) {
            var self = this;
            var url = '/api/chats' + (q ? ('?search=' + encodeURIComponent(q)) : '');
            fetch(url, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        self.recents = resp.data;
                    }
                });
        },
        toggleStar(c) {
            var self = this;
            fetch('/api/chats/' + c.id, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ is_starred: !c.is_starred })
            }).then(function() { self.loadRecents(); });
        },
        renameChat(c) {
            var name = prompt('Rename chat', c.title);
            if (!name || !name.trim()) return;
            var self = this;
            fetch('/api/chats/' + c.id, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ title: name.trim() })
            }).then(function() { self.loadRecents(); });
        },
        deleteChat(id) {
            var self = this;
            fetch('/api/chats/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                }
            }).then(function() {
                self.loadRecents();
                window.dispatchEvent(new CustomEvent('conversationDeleted', { detail: { id: id } }));
                if (self.conversationId === id) {
                    window.dispatchEvent(new CustomEvent('newChat'));
                    window.history.pushState({}, '', '/chat');
                }
            });
        },
        get groupedRecents() {
            var groups = {};
            this.recents.forEach(function(item) {
                var g = item.group || 'Older';
                if (!groups[g]) groups[g] = [];
                groups[g].push(item);
            });
            // Fixed section order, Starred on top (object key insertion order)
            var ordered = {};
            ['Starred', 'Today', 'Yesterday', 'Previous 7 days', 'Older'].forEach(function(k) {
                if (groups[k]) ordered[k] = groups[k];
            });
            Object.keys(groups).forEach(function(k) {
                if (!ordered[k]) ordered[k] = groups[k];
            });
            return ordered;
        }
    }
}
</script>

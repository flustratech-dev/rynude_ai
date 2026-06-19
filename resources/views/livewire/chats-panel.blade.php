<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-stone-900 overflow-y-auto">
    <div class="max-w-[1000px] mx-auto w-full px-4 sm:px-8 py-6 sm:py-10 flex flex-col h-full">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <h2 class="font-serif text-[28px] sm:text-[32px] text-[#2D2825] dark:text-stone-200">Chats</h2>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                @if($isSelectMode)
                    @if(count($selectedChats) > 0)
                        <button wire:click="deleteSelectedChats()" class="flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl border border-red-200 text-[13px] sm:text-[14px] font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors bg-white dark:bg-stone-900 active:scale-95">
                            Delete ({{ count($selectedChats) }})
                        </button>
                    @endif
                    <button wire:click="toggleSelectMode()" class="px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors bg-white dark:bg-stone-900 active:scale-95">
                        Cancel
                    </button>
                @else
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] text-gray-500 dark:text-stone-400 hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors bg-white dark:bg-stone-900 active:scale-95">
                            <span class="hidden sm:inline">Filter by <strong class="font-medium text-[#2D2825] dark:text-stone-200">{{ $filterType === 'all' ? 'All' : ($filterType === 'today' ? 'Today' : 'Past 7 days') }}</strong></span>
                            <span class="sm:hidden font-medium text-[#2D2825] dark:text-stone-200">{{ $filterType === 'all' ? 'Filter' : ($filterType === 'today' ? 'Today' : '7 Days') }}</span>
                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-stone-500 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 sm:right-0 sm:left-auto mt-2 w-48 rounded-xl bg-white dark:bg-stone-800 border border-gray-200 dark:border-stone-700 shadow-lg py-1 z-50">
                            <button wire:click="setFilter('all')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm {{ $filterType === 'all' ? 'bg-gray-50 dark:bg-stone-700 text-black dark:text-white' : 'text-gray-700 dark:text-stone-300 hover:bg-gray-50 dark:hover:bg-stone-700' }}">All</button>
                            <button wire:click="setFilter('today')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm {{ $filterType === 'today' ? 'bg-gray-50 dark:bg-stone-700 text-black dark:text-white' : 'text-gray-700 dark:text-stone-300 hover:bg-gray-50 dark:hover:bg-stone-700' }}">Today</button>
                            <button wire:click="setFilter('week')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm {{ $filterType === 'week' ? 'bg-gray-50 dark:bg-stone-700 text-black dark:text-white' : 'text-gray-700 dark:text-stone-300 hover:bg-gray-50 dark:hover:bg-stone-700' }}">Past 7 days</button>
                        </div>
                    </div>
                    <button wire:click="toggleSelectMode()" class="px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors bg-white dark:bg-stone-900 active:scale-95">
                        <span class="hidden sm:inline">Select chats</span>
                        <span class="sm:hidden">Select</span>
                    </button>
                    <button wire:click="startNewChat()" class="px-3 sm:px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[13px] sm:text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95">
                        New chat
                    </button>
                @endif
            </div>
        </div>

        {{-- Search --}}
        <div class="relative mb-6">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input
                wire:model.live="searchQuery"
                type="text"
                placeholder="Search chats..."
                class="w-full pl-11 pr-4 py-3 rounded-xl border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 dark:placeholder-stone-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all"
            >
        </div>

        {{-- Chat List --}}
        <div class="flex-1">
            @php $grouped = $this->getGroupedConversations(); @endphp

            @if(empty($grouped))
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <p class="text-gray-500 text-[15px]">No chats found</p>
                </div>
            @else
                <div class="flex flex-col">
                    @foreach($grouped as $period => $items)
                        @foreach($items as $conversation)
                            <div
                                class="group flex items-center justify-between py-4 border-b border-[#E5E5E5] dark:border-stone-800 cursor-pointer hover:bg-gray-50/50 dark:hover:bg-stone-800/50 transition-colors"
                                wire:click="{{ $isSelectMode ? 'toggleChatSelection('.$conversation['id'].')' : 'selectConversation('.$conversation['id'].')' }}"
                            >
                                <div class="flex items-center flex-1 min-w-0 pr-4">
                                    @if($isSelectMode)
                                        <div class="mr-3 w-5 h-5 rounded border flex-shrink-0 {{ in_array($conversation['id'], $selectedChats) ? 'bg-[#2D2825] border-[#2D2825] text-white dark:bg-stone-200 dark:border-stone-200 dark:text-stone-900' : 'border-gray-300 dark:border-stone-600 bg-white dark:bg-stone-800' }} flex items-center justify-center transition-colors">
                                            @if(in_array($conversation['id'], $selectedChats))
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </div>
                                    @endif
                                    <span class="text-[14.5px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $conversation['title'] }}</span>
                                </div>
                                <span class="text-[13.5px] text-gray-400 dark:text-stone-500 flex-shrink-0">{{ $conversation['updated_at'] }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-stone-900 overflow-y-auto">
    <div class="max-w-[1000px] mx-auto w-full px-8 py-10 flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-serif text-[32px] text-[#2D2825] dark:text-stone-200">Chats</h2>
            <div class="flex items-center gap-3">
                <button class="flex items-center gap-1.5 px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[14px] text-gray-500 dark:text-stone-400 hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors bg-white dark:bg-stone-900">
                    <span>Filter by <strong class="font-medium text-[#2D2825] dark:text-stone-200">All</strong></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <button class="px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[14px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors bg-white dark:bg-stone-900">
                    Select chats
                </button>
                <button wire:click="$dispatch('start-new-chat')" class="px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors">
                    New chat
                </button>
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
                                wire:click="selectConversation({{ $conversation['id'] }})"
                            >
                                <span class="flex-1 text-[14.5px] font-medium text-[#2D2825] dark:text-stone-200 truncate pr-4">{{ $conversation['title'] }}</span>
                                <span class="text-[13.5px] text-gray-400 dark:text-stone-500 flex-shrink-0">{{ $conversation['updated_at'] }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

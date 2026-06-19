<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-stone-900 overflow-y-auto">
    <div class="max-w-[1000px] mx-auto w-full px-8 py-10 flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-serif text-[32px] text-[#2D2825] dark:text-stone-200">Projects</h2>
            <div class="flex items-center gap-3">
                <button class="flex items-center gap-1.5 px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[14px] text-gray-500 dark:text-stone-400 hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors bg-white dark:bg-stone-900">
                    <span>Sort by <strong class="font-medium text-[#2D2825] dark:text-stone-200">Last updated</strong></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <button wire:click="toggleCreateForm" class="px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors">
                    New project
                </button>
            </div>
        </div>

        {{-- Search --}}
        <div class="relative mb-12">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input
                type="text"
                placeholder="Search projects..."
                class="w-full pl-11 pr-4 py-3 rounded-xl border border-blue-400 dark:border-blue-500 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 dark:placeholder-stone-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all"
            >
        </div>

        {{-- Create Form --}}
        @if($showCreateForm)
            <div class="p-6 border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-white dark:bg-stone-800 mb-8">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[14px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Project Name</label>
                        <input
                            wire:model="newProjectName"
                            type="text"
                            placeholder="My awesome project"
                            class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all"
                        >
                        @error('newProjectName')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-[14px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Description <span class="text-gray-400 dark:text-stone-500 font-normal">(optional)</span></label>
                        <textarea
                            wire:model="newProjectDescription"
                            rows="2"
                            placeholder="Brief description of the project..."
                            class="w-full px-4 py-2.5 rounded-xl border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all resize-none"
                        ></textarea>
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <button wire:click="createProject" class="px-5 py-2.5 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors">
                            Create
                        </button>
                        <button wire:click="toggleCreateForm" class="px-5 py-2.5 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-gray-600 dark:text-stone-400 text-[14px] font-medium hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Projects List / Empty State --}}
        <div class="flex-1 flex flex-col">
            @if(empty($projects) && !$showCreateForm)
                <div class="flex flex-col items-center justify-center flex-1 text-center py-10">
                    <div class="w-16 h-16 text-[#2D2825] dark:text-stone-200 mb-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" />
                            <rect x="14" y="3" width="7" height="7" />
                            <rect x="3" y="14" width="7" height="7" />
                            <path d="M14.5 16.5l3.5 3.5 3.5-3.5" />
                            <path d="M18 11v9" />
                        </svg>
                    </div>
                    <h3 class="text-[17px] font-medium text-[#2D2825] dark:text-stone-200 mb-2">Looking to start a project?</h3>
                    <p class="text-[14px] text-gray-500 dark:text-stone-400 max-w-[320px] mb-6 leading-relaxed">
                        Upload materials, set custom instructions, and organize conversations in one space.
                    </p>
                    <button wire:click="toggleCreateForm" class="px-5 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 text-[14px] font-medium hover:bg-gray-50 dark:hover:bg-stone-700 transition-colors">
                        New project
                    </button>
                </div>
            @elseif(!empty($projects))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($projects as $project)
                        <div class="group relative p-5 rounded-2xl border border-[#E5E5E5] dark:border-stone-700 hover:border-[#D1D1D1] dark:hover:border-stone-500 bg-white dark:bg-stone-800 transition-all cursor-pointer">
                            <button
                                wire:click.stop="deleteProject({{ $project['id'] }})"
                                class="absolute top-3 right-3 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-red-50 dark:hover:bg-red-900/30 transition-all text-gray-400 hover:text-red-500 dark:text-stone-500 dark:hover:text-red-400"
                                title="Delete project"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </button>
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-[#F9F8F6] dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $project['name'] }}</h3>
                                    <p class="text-[12px] text-gray-400 dark:text-stone-500 mt-0.5">{{ $project['created_at'] }}</p>
                                </div>
                            </div>
                            <p class="text-[13.5px] text-gray-500 dark:text-stone-400 line-clamp-2 mb-3 leading-relaxed">{{ $project['description'] ?: 'No description' }}</p>
                            <div class="flex items-center gap-1.5 text-[12px] text-gray-400 dark:text-stone-500 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.566 2.707-3.227V6.741c0-1.602-1.123-2.935-2.707-3.168A48.334 48.334 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.806 2.25 5.14 2.25 6.741v6.018z"/>
                                </svg>
                                <span>{{ $project['chat_count'] }} {{ Str::plural('chat', $project['chat_count']) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

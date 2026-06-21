<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-[#1C1A1A] overflow-y-auto relative">
    
    @if($selectedProjectId && $selectedProject)
        {{-- ==========================================
             PROJECT DETAILS VIEW 
             ========================================== --}}
        <div class="max-w-[1200px] mx-auto w-full px-8 py-8 flex flex-col h-full animate-fade-in">
            
            {{-- Top Bar Navigation --}}
            <div class="flex items-center gap-3 mb-8">
                <button wire:click="backToList" class="flex items-center justify-center p-2 rounded-lg text-[#2D2825] hover:text-black dark:text-stone-400 dark:hover:text-stone-200 hover:bg-black/5 dark:hover:bg-white/5 transition-colors group">
                    <svg class="w-5 h-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-[20px] shrink-0" style="background-color: {{ ($selectedProject['color'] ?? '#D97757') }}1A;">
                    {{ $selectedProject['icon'] ?? '📁' }}
                </div>
                <h2 class="font-serif text-[28px] sm:text-[32px] text-[#2D2825] dark:text-stone-200">{{ $selectedProject['name'] }}</h2>
                <button wire:click="starProject({{ $selectedProject['id'] }})" class="p-1.5 rounded-lg transition-colors {{ ($selectedProject['is_starred'] ?? false) ? 'text-[#F5A623]' : 'text-stone-300 dark:text-stone-600 hover:text-[#F5A623]' }}" title="Star project">
                    <svg class="w-5 h-5" fill="{{ ($selectedProject['is_starred'] ?? false) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                </button>
            </div>

            {{-- Main Two-Column Layout --}}
            <div class="flex flex-col lg:flex-row gap-8 flex-1">
                
                {{-- Left Column: Project Setup --}}
                <div class="flex-1 max-w-[600px] space-y-8">
                    
                    {{-- Description --}}
                    <div>
                        <div class="text-[14px] text-stone-600 dark:text-stone-400 leading-relaxed bg-white dark:bg-[#2C2A29] p-4 rounded-xl border border-stone-200 dark:border-stone-700 shadow-sm">
                            {{ $selectedProject['description'] ?: 'No description provided.' }}
                        </div>
                    </div>

                    {{-- Custom Instructions --}}
                    <div>
                        <h3 class="text-[15px] font-medium text-[#1a1a1a] dark:text-stone-200 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                            Custom instructions
                        </h3>
                        <div class="p-4 rounded-xl bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 shadow-sm relative group">
                            <textarea 
                                wire:model="customInstructions"
                                class="w-full bg-transparent resize-none text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none min-h-[120px]" 
                                placeholder="How should Rynude respond in this project? E.g., 'Always output responses in JSON format' or 'Adopt the persona of a senior engineer'."
                            ></textarea>
                            <div class="absolute bottom-3 right-3 opacity-0 group-focus-within:opacity-100 transition-opacity">
                                <button wire:click="saveInstructions" class="px-3 py-1.5 bg-[#EAE9E5] hover:bg-stone-300 dark:bg-stone-700 dark:hover:bg-stone-600 rounded-lg text-[12px] font-medium text-stone-700 dark:text-stone-200 transition-colors">
                                    <span wire:loading.remove wire:target="saveInstructions">Save</span>
                                    <span wire:loading wire:target="saveInstructions">Saving...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Project Knowledge --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-[15px] font-medium text-[#1a1a1a] dark:text-stone-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                Project knowledge
                            </h3>
                            @if(count($projectFiles) > 0)
                                <span class="text-[12px] text-stone-500 font-medium">{{ count($projectFiles) }} files</span>
                            @endif
                        </div>
                        
                        {{-- Dropzone --}}
                        <div class="relative w-full border-2 border-dashed border-stone-200 dark:border-stone-700 rounded-xl bg-white/50 dark:bg-[#2C2A29]/50 hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors p-8 text-center cursor-pointer mb-4">
                            <input type="file" wire:model="newKnowledgeFiles" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            
                            <div wire:loading.remove wire:target="newKnowledgeFiles">
                                <div class="w-10 h-10 bg-white dark:bg-[#3A3837] border border-stone-200 dark:border-stone-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm">
                                    <svg class="w-5 h-5 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </div>
                                <p class="text-[14px] font-medium text-[#1a1a1a] dark:text-stone-200 mb-1">Add content</p>
                                <p class="text-[13px] text-stone-500">Upload documents, text files, or code to give Rynude context.</p>
                            </div>

                            <div wire:loading wire:target="newKnowledgeFiles">
                                <svg class="animate-spin h-8 w-8 text-stone-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-[14px] font-medium text-stone-600 dark:text-stone-300">Uploading files...</p>
                            </div>
                        </div>

                        {{-- File List --}}
                        @if(count($projectFiles) > 0)
                            <div class="space-y-2">
                                @foreach($projectFiles as $file)
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 rounded-lg shadow-sm group">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="p-2 bg-stone-100 dark:bg-stone-800 rounded text-stone-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 truncate">{{ $file['file_name'] }}</p>
                                                <p class="text-[11px] text-stone-500">{{ round($file['size'] / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <button wire:click="deleteKnowledgeFile({{ $file['id'] }})" class="p-1.5 text-stone-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right Column: Chats --}}
                <div class="lg:w-[360px] flex flex-col">
                    <button @click="activePanel = null; artifactPanelOpen = false" wire:click="startNewChatInProject" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95 shadow-sm mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        New chat in project
                    </button>

                    <div class="flex-1 bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 rounded-xl p-5 shadow-sm">
                        <h3 class="text-[14px] font-medium text-[#1a1a1a] dark:text-stone-200 mb-4">Recent chats</h3>
                        
                        @if(empty($projectChats))
                            <div class="flex flex-col items-center justify-center text-center py-10">
                                <div class="w-10 h-10 rounded-full bg-stone-100 dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
                                </div>
                                <p class="text-[13px] text-stone-500">No chats yet</p>
                            </div>
                        @else
                            <div class="space-y-1">
                                @foreach($projectChats as $chat)
                                    <button @click="activePanel = null; artifactPanelOpen = false" wire:click="openProjectChat({{ $chat['id'] }})" class="w-full flex items-center justify-between p-2 rounded-lg hover:bg-stone-50 dark:hover:bg-stone-800 text-left transition-colors group">
                                        <div class="flex items-center gap-2 truncate">
                                            <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.43 3 11.996c0 2.29.81 4.385 2.146 6.012.35.433.52 1.016.326 1.547l-.547 1.492c-.22.602.32 1.196.903.953l1.838-.76c.46-.191.98-.18 1.433.025.753.338 1.58.534 2.451.534z"/></svg>
                                            <span class="text-[13px] text-[#1a1a1a] dark:text-stone-200 truncate">{{ $chat['title'] ?? 'Untitled chat' }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    @else
        {{-- ==========================================
             PROJECTS LIST VIEW 
             ========================================== --}}
        <div class="max-w-[1000px] mx-auto w-full px-4 sm:px-8 py-6 sm:py-10 flex flex-col h-full">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h2 class="font-serif text-[28px] sm:text-[32px] text-[#2D2825] dark:text-stone-200">Projects</h2>
                <div class="flex items-center gap-3">
                    <div x-data="{ showSort: false }" class="relative">
                        <button @click="showSort = !showSort" @click.away="showSort = false" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 text-[13px] font-medium text-stone-600 dark:text-stone-400 hover:bg-black/5 dark:hover:bg-white/5 transition-colors bg-white dark:bg-transparent shadow-sm">
                            <span>Sort by <strong class="text-[#1a1a1a] dark:text-stone-200 font-semibold">
                                @if($sortBy === 'updated_at') Last updated
                                @elseif($sortBy === 'created_at') Date created
                                @else Name
                                @endif
                            </strong></span>
                            <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-200" :class="showSort ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        
                        <div x-show="showSort" x-transition.opacity.duration.200ms style="display: none;" class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 rounded-xl shadow-lg overflow-hidden z-20">
                            <button wire:click="setSortBy('updated_at')" @click="showSort = false" class="w-full text-left px-4 py-2.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 flex items-center justify-between transition-colors">
                                Last updated
                                @if($sortBy === 'updated_at') <svg class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> @endif
                            </button>
                            <button wire:click="setSortBy('created_at')" @click="showSort = false" class="w-full text-left px-4 py-2.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 flex items-center justify-between transition-colors">
                                Date created
                                @if($sortBy === 'created_at') <svg class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> @endif
                            </button>
                            <button wire:click="setSortBy('name')" @click="showSort = false" class="w-full text-left px-4 py-2.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 flex items-center justify-between transition-colors">
                                Name
                                @if($sortBy === 'name') <svg class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> @endif
                            </button>
                        </div>
                    </div>
                    <button wire:click="toggleCreateForm" class="flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[13px] sm:text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        New project
                    </button>
                </div>
            </div>

            {{-- Search --}}
            <div class="relative mb-8">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input
                    wire:model.live.debounce.300ms="searchQuery"
                    type="text"
                    placeholder="Search projects..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 dark:border-stone-700 bg-white dark:bg-[#2C2A29] text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#D97757]/30 focus:border-[#D97757] transition-all shadow-[0_1px_2px_rgba(0,0,0,0.02)]"
                >
            </div>

            {{-- Create Form --}}
            @if($showCreateForm)
                <div class="p-6 border border-stone-200 dark:border-stone-700 rounded-2xl bg-white dark:bg-[#2C2A29] mb-8 shadow-sm animate-fade-in">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Project Name</label>
                            <input
                                wire:model="newProjectName"
                                type="text"
                                placeholder="e.g. Website Redesign"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-transparent text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#D97757]/30 focus:border-[#D97757] transition-all"
                            >
                            @error('newProjectName')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Description <span class="text-stone-400 font-normal">(optional)</span></label>
                            <textarea
                                wire:model="newProjectDescription"
                                rows="2"
                                placeholder="What is this project about?"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-transparent text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#D97757]/30 focus:border-[#D97757] transition-all resize-none"
                            ></textarea>
                        </div>

                        {{-- Icon & Color picker --}}
                        <div class="flex flex-col sm:flex-row gap-6">
                            <div>
                                <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Icon</label>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($projectIcons as $icon)
                                        <button type="button" wire:click="$set('newProjectIcon', '{{ $icon }}')"
                                            class="w-9 h-9 rounded-lg flex items-center justify-center text-[18px] border transition-all {{ $newProjectIcon === $icon ? 'border-[#D97757] ring-2 ring-[#D97757]/30 bg-[#D97757]/5' : 'border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800' }}">
                                            {{ $icon }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Color</label>
                                <div class="flex flex-wrap gap-2 items-center h-9">
                                    @foreach($projectColors as $color)
                                        <button type="button" wire:click="$set('newProjectColor', '{{ $color }}')"
                                            class="w-7 h-7 rounded-full transition-all {{ $newProjectColor === $color ? 'ring-2 ring-offset-2 ring-stone-400 dark:ring-offset-[#2C2A29]' : 'hover:scale-110' }}"
                                            style="background-color: {{ $color }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <button wire:click="createProject" class="px-4 py-2 rounded-xl bg-[#2D2825] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white text-[13px] sm:text-[14px] font-medium transition-colors active:scale-95 shadow-sm">
                                Create project
                            </button>
                            <button wire:click="toggleCreateForm" class="px-4 py-2 rounded-lg border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-400 text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Projects List / Empty State --}}
            <div class="flex-1 flex flex-col">
                @if(empty($projects) && !$showCreateForm)
                    <div class="flex flex-col items-center justify-center flex-1 text-center py-12 animate-fade-in">
                        <div class="w-12 h-12 text-stone-300 dark:text-stone-600 mb-5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                                <path d="M14.5 16.5l3.5 3.5 3.5-3.5" />
                                <path d="M18 11v9" />
                            </svg>
                        </div>
                        <h3 class="text-[18px] font-medium text-[#1a1a1a] dark:text-stone-200 mb-2">Create a project</h3>
                        <p class="text-[14px] text-stone-500 max-w-[340px] mb-6 leading-relaxed">
                            Organize your conversations, upload documents, and define custom instructions for a specific task or team.
                        </p>
                        <button wire:click="toggleCreateForm" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            New project
                        </button>
                    </div>
                @elseif(!empty($projects))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 animate-fade-in">
                        @foreach($projects as $project)
                            <div wire:click="selectProject({{ $project['id'] }})" x-data="{ menuOpen: false }" class="group relative p-5 rounded-2xl bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 hover:border-stone-300 dark:hover:border-stone-500 shadow-[0_1px_2px_rgba(0,0,0,0.02)] hover:shadow-md transition-all cursor-pointer">
                                {{-- Star button --}}
                                <button
                                    wire:click.stop="starProject({{ $project['id'] }})"
                                    class="absolute top-3 right-10 p-1.5 rounded-lg transition-all {{ $project['is_starred'] ? 'text-[#F5A623]' : 'text-stone-300 dark:text-stone-600 opacity-0 group-hover:opacity-100 hover:text-[#F5A623]' }}"
                                    title="{{ $project['is_starred'] ? 'Unstar project' : 'Star project' }}"
                                >
                                    <svg class="w-4 h-4" fill="{{ $project['is_starred'] ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                </button>

                                {{-- Options menu --}}
                                <button @click.stop="menuOpen = !menuOpen" @click.away="menuOpen = false" class="absolute top-3 right-3 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-stone-100 dark:hover:bg-stone-700 transition-all text-stone-400 hover:text-stone-600 dark:hover:text-stone-300" title="Options">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                </button>
                                <div x-show="menuOpen" x-cloak style="display:none" class="absolute top-10 right-3 w-40 bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-30">
                                    <button @click.stop="menuOpen = false" wire:click.stop="duplicateProject({{ $project['id'] }})" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        Duplicate
                                    </button>
                                    <div class="h-px w-full bg-stone-200 dark:bg-stone-700 my-1"></div>
                                    <button @click.stop="menuOpen = false; if(confirm('Delete this project?')) { @this.deleteProject({{ $project['id'] }}) }" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        Delete
                                    </button>
                                </div>

                                <div class="mb-4 flex items-start gap-3 pr-14">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-[20px] shrink-0" style="background-color: {{ $project['color'] }}1A;">
                                        {{ $project['icon'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-[15px] font-semibold tracking-tight text-[#1a1a1a] dark:text-stone-200 truncate">{{ $project['name'] }}</h3>
                                        <p class="text-[12px] text-stone-500 mt-0.5">Updated {{ $project['created_at'] }}</p>
                                    </div>
                                </div>
                                <p class="text-[13px] text-stone-600 dark:text-stone-400 line-clamp-2 mb-5 leading-relaxed h-[38px]">{{ $project['description'] ?: 'No description provided.' }}</p>
                                <div class="flex items-center gap-1.5 text-[12px] text-stone-500 font-medium">
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
    @endif
</div>

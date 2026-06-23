<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-claude-bg-dark overflow-y-auto relative">
    
    @if($selectedProjectId && $selectedProject)
        {{-- ==========================================
             PROJECT DETAILS VIEW 
             ========================================== --}}
        <div class="max-w-[1200px] mx-auto w-full px-8 py-8 flex flex-col h-full animate-fade-in">
            
            {{-- Top Bar Navigation --}}
            <button wire:click="backToList" class="flex items-center gap-2 text-[14px] text-[#5e5c5a] hover:text-[#1a1a1a] dark:text-stone-400 dark:hover:text-stone-200 transition-colors mb-6 w-fit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                All projects
            </button>

            {{-- Title Section --}}
            <div class="flex items-center justify-between mb-8">
                <h2 class="font-serif text-[32px] text-[#1a1a1a] dark:text-stone-200">{{ $selectedProject['name'] }}</h2>
                <div class="flex items-center gap-1.5">
                    <button class="p-1.5 text-[#1a1a1a] dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 rounded-lg transition-colors" title="Options">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1.5"></circle><circle cx="12" cy="5" r="1.5"></circle><circle cx="12" cy="19" r="1.5"></circle></svg>
                    </button>
                    <button wire:click="starProject({{ $selectedProject['id'] }})" class="p-1.5 rounded-lg transition-colors hover:bg-black/5 dark:hover:bg-white/5 {{ ($selectedProject['is_starred'] ?? false) ? 'text-[#1a1a1a]' : 'text-[#1a1a1a] dark:text-stone-300' }}" title="Star project">
                        <svg class="w-5 h-5" fill="{{ ($selectedProject['is_starred'] ?? false) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                    </button>
                </div>
            </div>

            {{-- Main Two-Column Layout --}}
            <div class="flex flex-col lg:flex-row gap-12 flex-1">
                
                {{-- Left Column: Chat input & Recent chats --}}
                <div class="flex-1 max-w-[700px] flex flex-col">
                    
                    {{-- Chat input --}}
                    <div class="relative w-full mx-auto bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-2xl md:rounded-3xl shadow-sm flex flex-col focus-within:shadow-lg focus-within:border-claude-accent/50 dark:focus-within:border-claude-accent/50 animate-smooth transition-all duration-200 mb-8">
                        <textarea
                            x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                            x-init="$watch('$wire.projectChatPrompt', value => { if(!value) { $el.style.height = 'auto'; } else { resize(); } }); resize()"
                            @input="resize()"
                            wire:model="projectChatPrompt"
                            @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $wire.startNewChatInProject() }"
                            rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto"
                            placeholder="How can I help you today?"
                        ></textarea>
                        
                        <div x-data="{ webSearch: @entangle('webSearch') }" class="flex items-center justify-between w-full mt-4 pb-1">
                            {{-- Left: Plus Icon --}}
                            <div class="relative">
                                <button type="button" class="p-2 text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Right: Model Selector & Action Icons --}}
                            <div class="flex items-center gap-1 md:gap-1.5 text-stone-500">
                                {{-- Model Dropdown --}}
                                <div x-data="{ open: false, selectedModel: @entangle('selectedModel'), extendedMode: true, moreModelsOpen: false, closeTimer: null }" class="relative">
                                    <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[120px] truncate">{{ collect($models)->concat($moreModels)->firstWhere('code', $selectedModel)?->name ?? 'Select Model' }}</span>
                                        <span class="text-[13px] text-stone-500 hidden sm:inline" x-show="extendedMode">Extended</span>
                                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 9l6 6 6-6"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute bottom-full right-0 mb-2 w-[250px] bg-white border border-[#E5E5E5] rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
                                        @foreach($models as $model)
                                            <button wire:click="$set('selectedModel', '{{ $model->code }}')" @click="open = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group {{ !$model->is_available ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$model->is_available ? 'disabled' : '' }}>
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-[13px] text-stone-800 dark:text-stone-200" style="font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;">{{ $model->name }}</span>
                                                        @if(!$model->is_available)
                                                            <span class="inline-flex items-center gap-1 px-1 py-0.5 rounded text-[10px] font-medium bg-stone-100 dark:bg-stone-700 text-stone-500">
                                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                                                Currently unavailable
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-[12px] text-stone-400 dark:text-stone-500 font-medium mt-0.5">{{ $model->description }}</div>
                                                </div>
                                                <svg x-show="selectedModel === '{{ $model->code }}'" class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        @endforeach
                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                        <!-- Extended Toggle -->
                                        <div class="px-3 py-1.5 flex items-center justify-between cursor-pointer hover:bg-stone-50 transition-colors group" @click="extendedMode = !extendedMode">
                                            <div>
                                                <div class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Extended</div>
                                                <p class="text-[11.5px] text-stone-500 mt-0.5">Always uses deep reasoning</p>
                                            </div>
                                            <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out" :class="extendedMode ? 'bg-[#2563EB]' : 'bg-gray-200'">
                                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition duration-200 ease-in-out" :class="extendedMode ? 'translate-x-4' : 'translate-x-[3px]'"></span>
                                            </div>
                                        </div>

                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                        <!-- More Models -->
                                        <div class="relative" @mouseenter="clearTimeout(closeTimer); moreModelsOpen = true" @mouseleave="closeTimer = setTimeout(() => { moreModelsOpen = false }, 250)">
                                            <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                                <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">More models</span>
                                                <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                            </button>
                                            
                                            <!-- Sub-menu -->
                                            <div x-show="moreModelsOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full mb-1 sm:mb-0 sm:bottom-[-8px] sm:top-auto mt-2 sm:mt-0 w-[200px] bg-white border border-[#E5E5E5] rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
                                                @foreach($moreModels as $mModel)
                                                <button wire:click="$set('selectedModel', '{{ $mModel->code }}')" @click="open = false; moreModelsOpen = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 transition-colors flex items-center justify-between group {{ !$mModel->is_available ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$mModel->is_available ? 'disabled' : '' }}>
                                                    <span class="text-[13px] font-medium text-stone-800">{{ $mModel->name }}</span>
                                                    <svg x-show="selectedModel === '{{ $mModel->code }}'" class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                </button>
                                                @endforeach
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>

                                {{-- Mic Icon (speech-to-text dictation) --}}
                                <div x-data="voiceInput" x-show="supported" class="relative group flex items-center justify-center">
                                    <button type="button" @click="toggle()" :class="listening ? 'bg-red-50 dark:bg-red-500/10 text-red-500' : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700'" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="listening ? 'animate-pulse' : ''">
                                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                            <line x1="12" x2="12" y1="19" y2="22"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Send Button --}}
                                <button @click="activePanel = null; artifactPanelOpen = false" wire:click="startNewChatInProject" :disabled="!$wire.projectChatPrompt.trim()" :class="($wire.projectChatPrompt.trim()) ? 'bg-[#D97757] text-white hover:bg-[#c96646]' : 'bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-500'" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 19V5M5 12l7-7 7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Recent chats --}}
                    <div class="flex flex-col">
                        @if(empty($projectChats))
                            <div class="border-t border-[#E5E5E5] dark:border-stone-800 py-6 text-center">
                                <p class="text-[14px] text-stone-500">No chats yet</p>
                            </div>
                        @else
                            @foreach($projectChats as $chat)
                                <div class="py-4 border-t border-[#E5E5E5] dark:border-stone-800 flex flex-col cursor-pointer transition-colors group" @click="activePanel = null; artifactPanelOpen = false" wire:click="openProjectChat({{ $chat['id'] }})">
                                    <span class="text-[15px] text-[#1a1a1a] dark:text-stone-200 font-medium group-hover:underline decoration-[#1a1a1a]/30 underline-offset-2">{{ $chat['title'] ?? 'Untitled chat' }}</span>
                                    <span class="text-[13px] text-[#5e5c5a] mt-1">Last message {{ \Carbon\Carbon::parse($chat['updated_at'])->diffForHumans() }}</span>
                                </div>
                            @endforeach
                            <div class="border-t border-[#E5E5E5] dark:border-stone-800"></div>
                        @endif
                    </div>
                </div>

                {{-- Right Column: Context/Knowledge --}}
                <div class="lg:w-[380px] flex flex-col shrink-0">
                    <div class="bg-white dark:bg-[#2C2A29] border border-[#E5E5E5] dark:border-stone-700 rounded-2xl overflow-hidden">
                        
                        {{-- Memory block --}}
                        <div class="p-5 border-b border-[#E5E5E5] dark:border-stone-700 relative group">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-[15px] text-[#1a1a1a] dark:text-stone-200">Memory</h3>
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center gap-1 px-2 py-0.5 rounded-md border border-[#E5E5E5] dark:border-stone-700 text-[12px] text-[#5e5c5a]">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                        Only you
                                    </span>
                                    <button class="text-[#1a1a1a] hover:text-[#5e5c5a] dark:text-stone-400 dark:hover:text-stone-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[13px] text-[#5e5c5a] dark:text-stone-400 leading-relaxed line-clamp-2 mt-3">
                                {{ $selectedProject['description'] ?: 'Purpose & context ' . Auth::user()->name . ' is working on this project.' }}
                            </p>
                            <p class="text-[12px] text-[#A3A3A3] mt-2">Last updated {{ \Carbon\Carbon::parse($selectedProject['updated_at'])->diffForHumans() }}</p>
                        </div>

                        {{-- Instructions block --}}
                        <div class="p-5 border-b border-[#E5E5E5] dark:border-stone-700 relative group" x-data="{ expanded: false }">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-[15px] text-[#1a1a1a] dark:text-stone-200">Instructions</h3>
                                <button @click="expanded = !expanded" class="text-[#1a1a1a] dark:text-stone-200 transition-colors">
                                    <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <svg x-show="expanded" style="display:none" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                                </button>
                            </div>
                            
                            <div x-show="!expanded" @click="expanded = true" class="cursor-text mt-1">
                                <p class="text-[13px] text-[#A3A3A3] truncate">{{ $customInstructions ? Str::limit($customInstructions, 50) : 'Add instructions to tailor Claude\'s responses' }}</p>
                            </div>

                            <div x-show="expanded" style="display:none" class="mt-3">
                                <textarea 
                                    wire:model="customInstructions"
                                    class="w-full bg-transparent border border-stone-200 dark:border-stone-700 rounded-lg p-3 resize-none text-[13px] text-[#1a1a1a] dark:text-stone-300 placeholder-[#A3A3A3] focus:outline-none focus:ring-1 focus:ring-[#D97757] min-h-[120px] transition-all" 
                                    placeholder="Add instructions to tailor Claude's responses"
                                ></textarea>
                                <div class="mt-2 flex justify-end">
                                    <button wire:click="saveInstructions" @click="expanded = false" class="px-3 py-1.5 bg-[#EAE9E5] hover:bg-stone-300 dark:bg-stone-700 dark:hover:bg-stone-600 rounded-lg text-[12px] font-medium text-stone-700 dark:text-stone-200 transition-colors">
                                        <span wire:loading.remove wire:target="saveInstructions">Save</span>
                                        <span wire:loading wire:target="saveInstructions">Saving...</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Files block --}}
                        <div class="p-5" x-data="{ expanded: false }">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-[15px] text-[#1a1a1a] dark:text-stone-200">Files</h3>
                                <button type="button" onclick="document.getElementById('project-file-upload').click();" class="cursor-pointer text-[#1a1a1a] dark:text-stone-200 transition-colors hover:text-stone-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </button>
                            </div>
                            
                            <input type="file" wire:model="newKnowledgeFiles" id="project-file-upload" multiple class="hidden" />
                            
                            {{-- Loading indicator --}}
                            <div wire:loading wire:target="newKnowledgeFiles" class="w-full text-center py-4">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="animate-spin w-6 h-6 text-[#D97757] mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span class="text-[13px] font-medium text-stone-600 dark:text-stone-300">Uploading...</span>
                                    <span class="text-[11px] text-stone-400 mt-0.5">Large files may take a moment</span>
                                </div>
                            </div>

                            <div wire:loading.remove wire:target="newKnowledgeFiles">
                                @if(count($projectFiles) > 0)
                                    <div class="space-y-1 mt-2">
                                        @foreach($projectFiles as $file)
                                            <div class="flex items-center justify-between p-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 rounded-lg group transition-colors -mx-2 cursor-pointer">
                                                <div class="flex items-center gap-2.5 truncate">
                                                    <div class="w-6 h-6 rounded bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 flex items-center justify-center shrink-0">
                                                        <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                    </div>
                                                    <span class="text-[13px] text-stone-600 dark:text-stone-300 truncate">{{ $file['file_name'] }}</span>
                                                </div>
                                                <button wire:click="deleteKnowledgeFile({{ $file['id'] }})" class="p-1.5 text-stone-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity rounded-md hover:bg-stone-200 dark:hover:bg-stone-700" title="Remove file">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <label onclick="document.getElementById('project-file-upload').click();" class="block cursor-pointer bg-[#FAFAFA] dark:bg-[#2C2A29] rounded-2xl p-6 text-center group relative transition-all border border-transparent dark:border-stone-800 hover:bg-[#F2F1EF] mt-4">
                                        <div class="flex justify-center mb-4">
                                            <div class="relative flex items-end">
                                                <!-- Card 1 (Left) -->
                                                <div class="w-[30px] h-[34px] bg-white border border-[#E5E5E5] rounded-md shadow-sm flex flex-col gap-[3px] p-[5px] -mr-1 z-0">
                                                    <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    <div class="w-3/4 h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                </div>
                                                <!-- Card 2 (Center - Front) -->
                                                <div class="w-[34px] h-[40px] bg-white border border-[#E5E5E5] rounded-md shadow-md flex flex-col gap-[4px] p-[6px] z-10">
                                                    <div class="w-full h-[3px] bg-[#D97757] rounded-full mb-1"></div>
                                                    <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    <div class="w-1/2 h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                </div>
                                                <!-- Card 3 (Right) -->
                                                <div class="w-[30px] h-[34px] bg-white border border-[#E5E5E5] rounded-md shadow-sm flex flex-col gap-[3px] p-[5px] -ml-1 z-0">
                                                    <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    <div class="w-5/6 h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-[14px] text-[#1a1a1a] dark:text-stone-200 font-medium group-hover:underline decoration-[#1a1a1a]/30 underline-offset-2">Add project knowledge</p>
                                        <p class="text-[13px] text-[#5e5c5a] mt-1">Upload documents, code, or images</p>
                                    </label>
                                @endif
                            </div>
                        </div>

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
                            <div wire:key="project-{{ $project['id'] }}" wire:click="selectProject({{ $project['id'] }})" x-data="{ menuOpen: false }" class="group relative p-5 rounded-2xl bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 hover:border-stone-300 dark:hover:border-stone-500 shadow-[0_1px_2px_rgba(0,0,0,0.02)] hover:shadow-md transition-all cursor-pointer">
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

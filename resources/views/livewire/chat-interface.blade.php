<div class="flex flex-col h-full bg-[#F9F8F6] dark:bg-stone-900">
    <input type="file" wire:model="attachment" id="file-upload" class="hidden" accept="image/*,.pdf,.doc,.docx,.txt">

    {{-- Empty State: Greeting + Form centered vertically --}}
    @if(empty($messages))
        <div class="flex-1 flex flex-col justify-center items-center px-4 -mt-16 md:-mt-32">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3">
                    <svg class="w-8 h-8 md:w-10 md:h-10 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                    </svg>
                    @auth
                        <h1 class="font-serif text-3xl md:text-[2.75rem] bg-clip-text text-transparent bg-gradient-to-r from-[#2D2825] to-[#7a7a75] dark:from-stone-200 dark:to-stone-400 tracking-tight">{{ strtolower(auth()->user()->name) }} returns!</h1>
                    @else
                        <h1 class="font-serif text-3xl md:text-[2.75rem] bg-clip-text text-transparent bg-gradient-to-r from-[#2D2825] to-[#7a7a75] dark:from-stone-200 dark:to-stone-400 tracking-tight">Golden hour thinking</h1>
                    @endauth
                </div>
            </div>

            {{-- Centered Form --}}
            <div class="w-full max-w-full md:max-w-[42rem] mx-auto" wire:key="empty-state-form-container">
                <form wire:submit.prevent="sendMessage" wire:key="empty-state-form">
                    {{-- Prompt Box Container --}}
                    <div class="relative w-full mx-auto bg-white dark:bg-stone-800/80 border border-[#E5E5E5] dark:border-stone-700/80 rounded-[1.25rem] shadow-sm flex flex-col focus-within:shadow-glow focus-within:border-stone-300 dark:focus-within:border-stone-500 animate-smooth transition-all duration-200">
                        {{-- Uploading State --}}
                        <div wire:loading wire:target="attachment" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <div class="w-16 h-16 rounded-xl border border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                                <svg class="animate-spin w-6 h-6 text-[#D97757]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Uploading...</span>
                                <span class="text-[11px] text-stone-500">Please wait</span>
                            </div>
                        </div>

                        {{-- Attachment Preview Area --}}
                        @if($attachment)
                            <div wire:loading.remove wire:target="attachment" class="px-4 pt-4 pb-2 flex items-center gap-3">
                                <div class="relative group">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden border border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                                        @if(str_starts_with($attachment->getMimeType(), 'image/'))
                                            <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                        @else
                                            <svg class="w-8 h-8 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="removeAttachment" class="absolute -top-2 -right-2 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full p-1 text-stone-500 hover:text-red-500 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 truncate max-w-[200px]">{{ $attachment->getClientOriginalName() }}</span>
                                    <span class="text-[11px] text-stone-500">{{ round($attachment->getSize() / 1024) }} KB</span>
                                </div>
                            </div>
                        @endif

                        <textarea
                            x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                            x-init="$watch('$wire.prompt', value => { if(!value) { $el.style.height = 'auto'; } else { resize(); } }); resize()"
                            @input="resize()"
                        wire:model="prompt"
                            @keydown.enter.prevent="if(!$event.shiftKey) { $wire.sendMessage() }"
                            rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto"
                            placeholder="How can I help you today?"
                        ></textarea>

                        {{-- Bottom Action Bar --}}
                        <div class="flex items-center justify-between px-3 pb-3 pt-1">
                            {{-- Left: Plus Icon --}}
                            <div x-data="{ openPlus: false }" class="relative">
                                <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 dark:bg-stone-700 text-stone-800 dark:text-stone-200' : 'hover:bg-stone-100 dark:hover:bg-stone-700'">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>

                                <div x-show="openPlus" @click.away="openPlus = false" x-transition.opacity x-cloak class="absolute top-full left-0 mt-2 w-[240px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
                                    <button type="button" onclick="document.getElementById('file-upload').click();" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add files or photos</span>
                                        </div>
                                        <span class="text-[12px] text-stone-400 font-medium">Ctrl+U</span>
                                    </button>
                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center gap-2.5 group">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Take a screenshot</span>
                                    </button>
                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add to project</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 13l2 2 4-4"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Skills</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add connector</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center gap-2.5 group">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 22 5-5"/><path d="M19 19a2 2 0 0 1-2 2H7.5A2.5 2.5 0 0 1 5 18.5V7a2 2 0 0 1 2-2h4.5a2.5 2.5 0 0 1 2.5 2.5V11l5-5Z"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Add plugins...</span>
                                    </button>

                                    <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center gap-2.5 group">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/><path d="M11 8v6"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Research</span>
                                    </button>
                                    <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Web search</span>
                                        </div>
                                        <svg class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                </div>
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

                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full right-0 mt-2 w-[240px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
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
                                            <div x-show="moreModelsOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full mb-1 sm:mb-0 sm:bottom-[-8px] sm:top-auto mt-2 sm:mt-0 w-[200px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
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

                                {{-- Mic Icon --}}
                                <div class="relative group flex items-center justify-center">
                                    <button type="button" class="hover:bg-stone-100 dark:hover:bg-stone-700 rounded-lg text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                            <line x1="12" x2="12" y1="19" y2="22"/>
                                        </svg>
                                    </button>
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-1 hidden group-hover:block whitespace-nowrap bg-[#1E1E1E] text-white text-[13px] font-medium px-3 py-1.5 rounded-lg shadow-sm z-50">
                                        Press and hold to record
                                    </div>
                                </div>

                                {{-- Voice Mode Icon --}}
                                <div class="relative group flex items-center justify-center">
                                    <button type="button" class="hover:bg-stone-100 dark:hover:bg-stone-700 rounded-lg text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 4v16M8 8v8M16 8v8M4 11v2M20 11v2"/>
                                        </svg>
                                    </button>
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-1 hidden group-hover:block whitespace-nowrap bg-[#1E1E1E] text-white text-[13px] font-medium px-3 py-1.5 rounded-lg shadow-sm z-50">
                                        Use voice mode
                                    </div>
                                </div>
                                
                                {{-- Send Button --}}
                                <button type="submit" x-data :disabled="!$wire.prompt.trim()" wire:loading.attr="disabled" wire:target="sendMessage, generateResponse" :class="$wire.prompt.trim() ? 'bg-[#D97757] text-white hover:bg-[#c96646]' : 'bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-500'" class="rounded-lg transition-all duration-200 p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center hover:scale-105 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <svg wire:loading.remove wire:target="sendMessage, generateResponse" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 19V5M5 12l7-7 7 7"/>
                                    </svg>
                                    <svg wire:loading wire:target="sendMessage, generateResponse" class="animate-spin w-[18px] h-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Suggestion Chips --}}
                <div class="flex items-center justify-center gap-2 mt-4 overflow-x-auto md:overflow-visible px-2 md:px-0 -mx-2 md:mx-0 scrollbar-hide">
                    <button wire:click="$set('prompt', 'Write a ')" class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                            <path d="m15 5 4 4"/>
                        </svg>
                        Write
                    </button>

                    <button wire:click="$set('prompt', 'Explain to me ')" class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                        Learn
                    </button>

                    <button wire:click="$set('prompt', 'Write a code to ')" class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0 group">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline class="code-bracket-right" points="16 18 22 12 16 6"/>
                            <polyline class="code-bracket-left" points="8 6 2 12 8 18"/>
                        </svg>
                        Code
                    </button>

                    <button wire:click="$set('prompt', 'Give me advice on ')" class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                            <line x1="6" x2="6" y1="2" y2="4"/>
                            <line x1="10" x2="10" y1="2" y2="4"/>
                            <line x1="14" x2="14" y1="2" y2="4"/>
                        </svg>
                        Life stuff
                    </button>

                    <button wire:click="sendMessage" class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                        </svg>
                        Rynude's choice
                    </button>
                </div>
            </div>
        </div>

    {{-- Active Chat State --}}
    @else
        {{-- Scrollable Messages --}}
        <div class="flex-1 overflow-y-auto" id="chat-scroll-container">
            <div class="max-w-3xl mx-auto w-full py-4 md:py-6 px-3 md:px-4">
                <div class="space-y-1">
                    @foreach($messages as $msg)
                        <div class="w-full max-w-[49rem] mx-auto flex flex-col py-0.5 md:py-1 px-2 md:px-4 group/msg">
                            @if($msg['role'] === 'user')
                                <!-- User Message -->
                                <div class="flex justify-end w-full">
                                    <div class="flex flex-col items-end gap-2 max-w-[85%] md:max-w-[75%]">
                                        @if(isset($msg['attachment']) && $msg['attachment'])
                                            <div class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-2 shadow-sm flex items-center gap-3">
                                                @if(str_starts_with($msg['attachment']['file_type'], 'image/'))
                                                    <img src="{{ Storage::url($msg['attachment']['file_path']) }}" class="w-20 h-20 object-cover rounded-xl border border-[#E5E5E5] dark:border-stone-700" alt="Attachment">
                                                @else
                                                    <div class="w-12 h-12 bg-stone-100 dark:bg-stone-700 rounded-xl flex items-center justify-center shrink-0">
                                                        <svg class="w-6 h-6 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                                    </div>
                                                @endif
                                                <div class="pr-2">
                                                    <p class="text-[13px] font-medium text-stone-800 dark:text-stone-200 truncate max-w-[150px]">{{ $msg['attachment']['file_name'] }}</p>
                                                    <p class="text-[11px] text-stone-500 uppercase">{{ explode('/', $msg['attachment']['file_type'])[1] ?? 'FILE' }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty($msg['content']))
                                            <div class="bg-[#F3F2EE] dark:bg-stone-800 border border-transparent dark:border-stone-700/50 text-[#2D2825] dark:text-stone-200 px-4 md:px-[22px] py-2.5 md:py-3 rounded-[1.5rem] text-[15px] leading-relaxed shadow-sm break-words w-full">
                                                {{ $msg['content'] }}
                                            </div>
                                            {{-- User message actions --}}
                                            <div class="flex items-center gap-1 opacity-0 group-hover/msg:opacity-100 transition-opacity duration-150">
                                                <button
                                                    x-data="{ copied: false }"
                                                    @click="navigator.clipboard.writeText(@js($msg['content'])); copied = true; setTimeout(() => copied = false, 1500)"
                                                    class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors"
                                                    title="Copy"
                                                >
                                                    <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                    <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                                <button
                                                    wire:click="editMessage({{ $loop->index }})"
                                                    class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors"
                                                    title="Edit"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- Assistant Message -->
                                <div class="flex justify-start w-full gap-3 md:gap-4">
                                    <div class="flex-shrink-0 mt-1">
                                        <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                        </svg>
                                    </div>
                                    <div class="text-[#2D2825] dark:text-stone-200 text-[15px] leading-relaxed max-w-[90%] prose prose-stone dark:prose-invert max-w-none w-full prose-p:leading-relaxed prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-a:text-[#D97757] hover:prose-a:text-[#c96646] transition-colors">
                                        {!! Illuminate\Support\Str::markdown($msg['content']) !!}

                                        @if(isset($msg['artifact']) && $msg['artifact'])
                                            <div wire:click="openArtifact({{ $msg['artifact']['id'] }})" class="mt-3 inline-flex items-center gap-3 border border-[#E5E5E5] dark:border-stone-700 rounded-xl p-2 pr-4 bg-white dark:bg-stone-800 shadow-sm cursor-pointer hover:border-[#D97757] dark:hover:border-[#D97757] transition-colors max-w-full group">
                                                <div class="w-10 h-10 bg-[#F9F8F6] dark:bg-stone-700 rounded-lg flex items-center justify-center shrink-0 group-hover:bg-[#F3F2EE] dark:group-hover:bg-stone-600 transition-colors">
                                                    @if($msg['artifact']['language'] === 'php' || $msg['artifact']['type'] === 'code')
                                                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $msg['artifact']['title'] ?? 'Generated Code' }}</h4>
                                                    <p class="text-[12px] text-stone-500 mt-0.5 truncate group-hover:text-stone-700 dark:group-hover:text-stone-300 transition-colors">Click to open artifact</p>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Assistant message actions --}}
                                        <div class="flex items-center gap-1 mt-2 opacity-0 group-hover/msg:opacity-100 transition-opacity duration-150 not-prose">
                                            <button
                                                x-data="{ copied: false }"
                                                @click="navigator.clipboard.writeText(@js($msg['content'])); copied = true; setTimeout(() => copied = false, 1500)"
                                                class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors"
                                                title="Copy"
                                            >
                                                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <button
                                                wire:click="rateMessage({{ $loop->index }}, 'up')"
                                                class="p-1.5 rounded-lg transition-colors {{ ($msg['rating'] ?? null) === 'up' ? 'text-green-600 bg-green-50 dark:bg-green-500/10' : 'text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800' }}"
                                                title="Good response"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="{{ ($msg['rating'] ?? null) === 'up' ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14 9.5V5.25"/></svg>
                                            </button>
                                            <button
                                                wire:click="rateMessage({{ $loop->index }}, 'down')"
                                                class="p-1.5 rounded-lg transition-colors {{ ($msg['rating'] ?? null) === 'down' ? 'text-red-500 bg-red-50 dark:bg-red-500/10' : 'text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800' }}"
                                                title="Bad response"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="{{ ($msg['rating'] ?? null) === 'down' ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398C20.613 14.547 19.833 15 19 15h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 00.303-.54m.023-8.25H16.48a4.5 4.5 0 01-1.423-.23l-3.114-1.04a4.5 4.5 0 00-1.423-.23H6.504c-.618 0-1.217.247-1.605.729A11.95 11.95 0 002.25 12c0 .434.023.863.068 1.285C2.427 14.306 3.346 15 4.372 15h3.126c.618 0 .991.724.725 1.282A7.471 7.471 0 007.5 19.5a2.25 2.25 0 002.25 2.25.75.75 0 00.75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 002.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384"/></svg>
                                            </button>
                                            @if($loop->last)
                                            <button
                                                wire:click="$dispatch('regenerateResponse')"
                                                class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors"
                                                title="Regenerate"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach



                    {{-- Loading Indicator / Streaming Target --}}
                    <div wire:loading wire:target="sendMessage, generateResponse" class="w-full max-w-[49rem] mx-auto flex flex-col py-0.5 md:py-1 px-2 md:px-4 mt-1">
                        <div class="flex justify-start w-full gap-3 md:gap-4">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-7 h-7 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                                </svg>
                            </div>
                            <div class="text-[#2D2825] dark:text-stone-200 text-[15px] leading-relaxed max-w-[90%] prose prose-stone dark:prose-invert max-w-none w-full prose-p:leading-relaxed prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-a:text-[#D97757] hover:prose-a:text-[#c96646] transition-colors" wire:stream="message-stream">
                                <div class="text-stone-400 text-sm flex items-center gap-2 mt-1">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Rynude is thinking...</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-center w-full">
                            <button
                                type="button"
                                x-data="{ stopping: false }"
                                @click="stopping = true; fetch('{{ route('chat.stop') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'ngrok-skip-browser-warning': 'true' }, body: JSON.stringify({ conversation_id: $wire.get('conversationId') }) })"
                                class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors shadow-sm disabled:opacity-60"
                                :disabled="stopping"
                            >
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                    <rect x="4" y="4" width="16" height="16" rx="2" ry="2"/>
                                </svg>
                                <span x-text="stopping ? 'Stopping...' : 'Stop generating'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom-anchored Form --}}
        <div class="shrink-0 h-fit bg-[#F9F8F6] dark:bg-stone-900" wire:key="active-state-form-container">
            <form wire:submit.prevent="sendMessage" class="w-full max-w-[42rem] mx-auto pb-4 md:pb-6 px-3 md:px-4 pt-3 md:pt-4" wire:key="active-state-form">
                {{-- Prompt Box Container --}}
                <div class="relative w-full mx-auto bg-white dark:bg-stone-800/80 border border-[#E5E5E5] dark:border-stone-700/80 rounded-[1.25rem] shadow-sm flex flex-col focus-within:shadow-glow focus-within:border-stone-300 dark:focus-within:border-stone-500 animate-smooth transition-all duration-200">
                    
                    {{-- Uploading State --}}
                    <div wire:loading wire:target="attachment" class="px-4 pt-4 pb-2 flex items-center gap-3">
                        <div class="w-16 h-16 rounded-xl border border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                            <svg class="animate-spin w-6 h-6 text-[#D97757]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Uploading...</span>
                            <span class="text-[11px] text-stone-500">Please wait</span>
                        </div>
                    </div>

                    {{-- Attachment Preview Area --}}
                    @if($attachment)
                        <div wire:loading.remove wire:target="attachment" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <div class="relative group">
                                <div class="w-16 h-16 rounded-xl overflow-hidden border border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                                        <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                    @else
                                        <svg class="w-8 h-8 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    @endif
                                </div>
                                <button type="button" wire:click="removeAttachment" class="absolute -top-2 -right-2 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-full p-1 text-stone-500 hover:text-red-500 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 truncate max-w-[200px]">{{ $attachment->getClientOriginalName() }}</span>
                                <span class="text-[11px] text-stone-500">{{ round($attachment->getSize() / 1024) }} KB</span>
                            </div>
                        </div>
                    @endif

                    <textarea 
                        x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                        x-init="$watch('$wire.prompt', value => { if(!value) { $el.style.height = 'auto'; } else { resize(); } }); resize()"
                        @input="resize()"
                        wire:model="prompt" 
                        @keydown.enter.prevent="if(!$event.shiftKey) { $wire.sendMessage() }" 
                        rows="1" 
                        class="w-full bg-transparent border-0 focus:ring-0 px-3 md:px-4 pt-3 md:pt-4 pb-2 resize-none text-[#2D2825] dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto" 
                        placeholder="How can I help you today?"></textarea>

                    {{-- Bottom Action Bar --}}
                    <div class="flex items-center justify-between px-3 pb-3 pt-1">
                        {{-- Left: Plus Icon --}}
                        <div x-data="{ openPlus: false }" class="relative">
                            <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 hover:text-stone-800 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 text-stone-800' : 'hover:bg-stone-100'">
                            <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 hover:text-stone-800 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 text-stone-800' : 'hover:bg-stone-100'">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                            </button>

                            <div x-show="openPlus" @click.away="openPlus = false" x-transition.opacity x-cloak class="absolute bottom-full left-0 mb-2 w-[240px] bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
                                <button type="button" onclick="document.getElementById('file-upload').click();" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Add files or photos</span>
                                    </div>
                                    <span class="text-[12px] text-stone-400 font-medium">Ctrl+U</span>
                                </button>
                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center gap-2.5 group">
                                    <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                    <span class="text-[13px] text-stone-800 dark:text-stone-200">Take a screenshot</span>
                                </button>
                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Add to project</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>

                                <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 13l2 2 4-4"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Skills</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Add connector</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center gap-2.5 group">
                                    <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 22 5-5"/><path d="M19 19a2 2 0 0 1-2 2H7.5A2.5 2.5 0 0 1 5 18.5V7a2 2 0 0 1 2-2h4.5a2.5 2.5 0 0 1 2.5 2.5V11l5-5Z"/></svg>
                                    <span class="text-[13px] text-stone-800 dark:text-stone-200">Add plugins...</span>
                                </button>

                                <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center gap-2.5 group">
                                    <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/><path d="M11 8v6"/></svg>
                                    <span class="text-[13px] text-stone-800 dark:text-stone-200">Research</span>
                                </button>
                                <button class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Web search</span>
                                    </div>
                                    <svg class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
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

                            {{-- Mic Icon --}}
                            <div class="relative group flex items-center justify-center">
                                <button type="button" class="hover:bg-stone-100 dark:hover:bg-stone-700 rounded-lg text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                        <line x1="12" x2="12" y1="19" y2="22"/>
                                    </svg>
                                </button>
                                <!-- Tooltip -->
                                <div class="absolute bottom-full mb-1 hidden group-hover:block whitespace-nowrap bg-[#1E1E1E] text-white text-[13px] font-medium px-3 py-1.5 rounded-lg shadow-sm z-50">
                                    Press and hold to record
                                </div>
                            </div>

                            {{-- Voice Mode Icon --}}
                            <div class="relative group flex items-center justify-center">
                                <button type="button" class="hover:bg-stone-100 dark:hover:bg-stone-700 rounded-lg text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 4v16M8 8v8M16 8v8M4 11v2M20 11v2"/>
                                    </svg>
                                </button>
                                <!-- Tooltip -->
                                <div class="absolute bottom-full mb-1 hidden group-hover:block whitespace-nowrap bg-[#1E1E1E] text-white text-[13px] font-medium px-3 py-1.5 rounded-lg shadow-sm z-50">
                                    Use voice mode
                                </div>
                            </div>

                            {{-- Send Button --}}
                            <button type="submit" x-data :disabled="!$wire.prompt.trim() && !$wire.attachment" wire:loading.attr="disabled" wire:target="sendMessage, generateResponse, attachment" :class="($wire.prompt.trim() || $wire.attachment) ? 'bg-[#D97757] text-white hover:bg-[#c96646]' : 'bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-500'" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                                <svg wire:loading.remove wire:target="sendMessage, generateResponse, attachment" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 19V5M5 12l7-7 7 7"/>
                                </svg>
                                <svg wire:loading wire:target="sendMessage, generateResponse, attachment" class="animate-spin w-[18px] h-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('messageAdded', () => {
            scrollToBottom();
            @this.generateResponse();
        });
        
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                queueMicrotask(() => {
                    scrollToBottom();
                    enhanceCodeBlocks();
                });
            })
        });

        // Focus the prompt input after editing a message
        Livewire.on('focusPromptInput', () => {
            queueMicrotask(() => {
                const ta = document.querySelector('textarea[wire\\:model="prompt"]');
                if (ta) {
                    ta.focus();
                    const len = ta.value.length;
                    ta.setSelectionRange(len, len);
                    ta.dispatchEvent(new Event('input'));
                }
            });
        });

        // Initial setup
        enhanceCodeBlocks();
    });
    
    function scrollToBottom() {
        const container = document.getElementById('chat-scroll-container');
        if(container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function enhanceCodeBlocks() {
        document.querySelectorAll('.prose pre').forEach((pre) => {
            // Apply syntax highlighting to the inner <code> element
            const codeEl = pre.querySelector('code');
            if (codeEl && !codeEl.hasAttribute('data-highlighted') && window.hljs) {
                try {
                    hljs.highlightElement(codeEl);
                } catch (e) { /* hljs sets data-highlighted itself */ }
            }

            if(pre.hasAttribute('data-enhanced')) return;
            pre.setAttribute('data-enhanced', 'true');

            pre.classList.add('code-block-enter', 'relative', 'group/code');
            
            const copyBtn = document.createElement('button');
            copyBtn.className = 'absolute top-2 right-2 p-1.5 rounded-lg bg-stone-700/80 text-stone-300 opacity-0 group-hover/code:opacity-100 transition-all hover:bg-stone-600 flex items-center gap-1.5 text-xs font-medium border border-stone-600/50 shadow-sm backdrop-blur-sm z-10';
            copyBtn.innerHTML = `
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                <span>Copy</span>
            `;
            
            copyBtn.addEventListener('click', async () => {
                // Ensure we don't copy the copy button's own text
                const codeNode = pre.querySelector('code');
                const code = codeNode ? codeNode.innerText : pre.innerText.replace('Copy', '').trim();
                
                try {
                    await navigator.clipboard.writeText(code);
                    copyBtn.classList.add('copy-pop', '!text-green-400', '!border-green-500/50', '!bg-stone-800');
                    copyBtn.classList.remove('text-stone-300', 'bg-stone-700/80', 'border-stone-600/50');
                    copyBtn.innerHTML = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Copied!</span>
                    `;
                    
                    setTimeout(() => {
                        copyBtn.classList.remove('copy-pop', '!text-green-400', '!border-green-500/50', '!bg-stone-800');
                        copyBtn.classList.add('text-stone-300', 'bg-stone-700/80', 'border-stone-600/50');
                        copyBtn.innerHTML = `
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            <span>Copy</span>
                        `;
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy', err);
                }
            });
            
            pre.appendChild(copyBtn);
        });
    }
</script>

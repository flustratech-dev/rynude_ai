<div class="flex flex-col h-full bg-transparent dark:bg-claude-bg-dark relative"
    x-data="{ isDropping: false }"
    x-on:dragover.prevent="isDropping = true"
    x-on:dragleave.prevent="isDropping = false"
    x-on:drop.prevent="isDropping = false; if($event.dataTransfer.files.length > 0) { const fileInput = document.getElementById('file-upload'); fileInput.files = $event.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); }"
>
    {{-- Drag & Drop Overlay --}}
    <div x-show="isDropping" x-transition x-cloak class="absolute inset-0 z-[100] flex items-center justify-center bg-white/80 dark:bg-stone-900/80 backdrop-blur-sm border-2 border-dashed border-[#D97757] rounded-xl m-2">
        <div class="flex flex-col items-center pointer-events-none">
            <svg class="w-12 h-12 text-[#D97757] mb-3 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <h3 class="text-xl font-medium text-stone-800 dark:text-stone-200">Drop files here to attach</h3>
        </div>
    </div>

    {{-- Full-screen Loading Overlay --}}
    <div wire:loading wire:target="loadSelectedConversation, startProjectChat" class="absolute inset-0 z-[60] flex flex-col items-center justify-center bg-background/90 dark:bg-background/90 backdrop-blur-sm">
        <div class="w-12 h-12 rounded-2xl bg-claude-bg-light dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark flex items-center justify-center mb-4 shadow-sm">
            <svg class="w-6 h-6 text-[#D97757] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="2" x2="12" y2="6"></line>
                <line x1="12" y1="18" x2="12" y2="22"></line>
                <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                <line x1="2" y1="12" x2="6" y2="12"></line>
                <line x1="18" y1="12" x2="22" y2="12"></line>
                <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
            </svg>
        </div>
        <p class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Loading conversation...</p>
    </div>

    <input type="file" wire:model="attachments" id="file-upload" class="hidden" multiple accept="image/*,.pdf,.doc,.docx,.txt">

    {{-- Conversation Memory viewer / editor --}}
    @if($showMemory)
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showMemory', false)"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-stone-900 border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-xl flex flex-col max-h-[80vh]">
                {{-- Header --}}
                <div class="flex items-start gap-3 px-5 pt-5 pb-3 border-b border-claude-border-light dark:border-claude-border-dark">
                    <div class="w-9 h-9 rounded-xl bg-[#D97757]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a9 9 0 0 0-9 9c0 3.6 2.1 6.7 5.2 8.2.3 1.1 1.4 1.8 2.6 1.8h2.4c1.2 0 2.3-.7 2.6-1.8C18.9 17.7 21 14.6 21 11a9 9 0 0 0-9-9Z"/><path d="M9 21h6"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-100">Conversation memory</h3>
                        <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5">Durable facts the assistant keeps in mind — even across long chats and after you switch models.</p>
                    </div>
                    <button type="button" wire:click="$set('showMemory', false)" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                {{-- Body --}}
                <div class="px-5 py-4 overflow-y-auto">
                    <textarea
                        wire:model="memoryDraft"
                        rows="10"
                        placeholder="No memory recorded yet. Notable facts will appear here automatically as the chat grows — or you can write your own (e.g. “User's name is Budi. Prefers Laravel + Livewire. Building a Claude clone.”)."
                        class="w-full bg-stone-50 dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-xl px-3.5 py-3 text-[13.5px] leading-relaxed text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-1 focus:ring-[#D97757]/40 focus:border-[#D97757]/40 resize-y font-mono"></textarea>
                    @if($memoryUpdatedAt)
                        <p class="text-[11.5px] text-stone-400 dark:text-stone-500 mt-2">Last updated {{ $memoryUpdatedAt }}.</p>
                    @endif
                </div>
                {{-- Footer --}}
                <div class="flex items-center justify-between gap-2 px-5 py-3.5 border-t border-claude-border-light dark:border-claude-border-dark">
                    <button type="button" wire:click="clearMemory" class="text-[13px] font-medium text-stone-500 hover:text-red-500 transition-colors px-2 py-1.5">Clear</button>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('showMemory', false)" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg px-3 py-1.5 transition-colors">Cancel</button>
                        <button type="button" wire:click="saveMemory" class="text-[13px] font-medium text-white bg-[#D97757] hover:bg-[#c96646] rounded-lg px-3.5 py-1.5 transition-colors shadow-sm">Save memory</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Empty State: Greeting + Form centered vertically --}}
    @if(empty($messages))
        <div class="flex-1 flex flex-col justify-center items-center px-4 -mt-16 md:-mt-32">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3 md:gap-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="w-8 h-8 md:w-10 md:h-10 text-[#D97757] fill-current shrink-0">
                        <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path>
                    </svg>
                    @auth
                        <h1 class="font-serif text-[#2D2825] dark:text-[#E8E8E6] tracking-tight" style="font-size: clamp(1.875rem, 1.2rem + 2vw, 2.5rem); line-height: 1.5;">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h1>
                    @else
                        <h1 class="font-serif text-[#2D2825] dark:text-[#E8E8E6] tracking-tight" style="font-size: clamp(1.875rem, 1.2rem + 2vw, 2.5rem); line-height: 1.5;">Welcome back</h1>
                    @endauth
                </div>
            </div>

            {{-- Centered Form --}}
            <div class="w-full max-w-full md:max-w-[42rem] mx-auto" wire:key="empty-state-form-container">
                <form wire:submit.prevent="sendMessage" wire:key="empty-state-form">
                    {{-- Prompt Box Container --}}
                    <div class="relative w-full mx-auto bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-2xl md:rounded-3xl shadow-md flex flex-col focus-within:shadow-lg focus-within:border-claude-accent/50 dark:focus-within:border-claude-accent/50 animate-smooth transition-all duration-200">
                        {{-- Uploading State --}}
                        {{-- Uploading State --}}
                        <div wire:loading wire:target="attachments" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <div class="w-16 h-16 rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                                <svg class="animate-spin w-6 h-6 text-[#D97757]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Uploading...</span>
                                <span class="text-[11px] text-stone-500">Please wait</span>
                            </div>
                        </div>

                        {{-- Attachment Preview Area --}}
                        @if(!empty($attachments))
                            <div wire:loading.remove wire:target="attachments" class="px-4 pt-4 pb-2 flex flex-wrap items-center gap-3" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 12px;">
                                @foreach($attachments as $index => $attachment)
                                <div class="relative group bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl shrink-0 overflow-hidden shadow-sm" style="width: 112px; height: 128px; min-width: 112px; flex-shrink: 0;">
                                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                                        <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                        <div class="absolute bottom-2 left-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold bg-white/90 dark:bg-stone-800/90 text-stone-600 dark:text-stone-300 uppercase border border-stone-200 dark:border-stone-600 backdrop-blur-sm shadow-sm" style="font-size: 10px;">
                                                {{ strtoupper($attachment->getClientOriginalExtension()) }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex flex-col h-full justify-between bg-white dark:bg-stone-800" style="padding: 10px;">
                                            <p class="text-xs text-stone-800 dark:text-stone-200 font-medium leading-snug line-clamp-4 break-words">
                                                {{ $attachment->getClientOriginalName() }}
                                            </p>
                                            <div class="mt-auto">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold bg-stone-100 dark:bg-stone-700 text-stone-600 dark:text-stone-300 uppercase border border-stone-200 dark:border-stone-600" style="font-size: 10px;">
                                                    {{ strtoupper($attachment->getClientOriginalExtension()) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <button type="button" wire:click="removeAttachment({{ $index }})" class="absolute top-1.5 right-1.5 bg-black/40 hover:bg-black/60 dark:bg-black/60 dark:hover:bg-black/80 rounded-full p-1 text-white opacity-0 group-hover:opacity-100 transition-opacity z-10 backdrop-blur-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        @endif

                        <textarea
                            x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                            x-init="$watch('$wire.prompt', value => { if(!value) { $el.style.height = 'auto'; } else { resize(); } }); resize()"
                            @input="resize()"
                        wire:model.live.debounce.2000ms="prompt"
                            @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $wire.sendMessage() }"
                            rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto"
                            placeholder="How can I help you today?"
                        ></textarea>

                        {{-- Bottom Action Bar --}}
                        <div x-data="{ webSearch: @entangle('webSearch') }" class="flex items-center justify-between px-3 pb-3 pt-1">
                            {{-- Left: Plus Icon --}}
                            <div x-data="{ openPlus: false }" class="relative">
                                <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 dark:bg-stone-700 text-stone-800 dark:text-stone-200' : 'hover:bg-stone-100 dark:hover:bg-stone-700'">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>

                                <div x-show="openPlus" @click.away="openPlus = false" x-transition.opacity x-cloak class="absolute top-full left-0 mt-2 w-[240px] bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
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
                                    <button type="button" @click="webSearch = !webSearch" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4" :class="webSearch ? 'text-[#D97757]' : 'text-stone-500 group-hover:text-stone-700'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Web search</span>
                                        </div>
                                        <svg x-show="webSearch" x-cloak class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Right: Model Selector & Action Icons --}}
                            <div class="flex items-center gap-1 md:gap-1.5 text-stone-500">
                                <span x-show="webSearch" x-cloak class="hidden sm:inline-flex items-center gap-1 px-2 py-1 rounded-full bg-[#D97757]/10 text-[#D97757] text-[12px] font-medium">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>
                                    Web
                                </span>
                                {{-- Model Dropdown --}}
                                <div x-data="{ open: false, selectedModel: @entangle('selectedModel'), extendedMode: true, moreModelsOpen: false, closeTimer: null }" class="relative">
                                    <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[120px] truncate">{{ collect($models)->concat($moreModels)->firstWhere('code', $selectedModel)?->name ?? 'Select Model' }}</span>
                                        <span class="text-[13px] text-stone-500 hidden sm:inline" x-show="extendedMode">Extended</span>
                                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 9l6 6 6-6"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full right-0 mt-2 w-[240px] bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
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
                                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-claude-bg-light dark:bg-claude-bg-dark shadow transition duration-200 ease-in-out" :class="extendedMode ? 'translate-x-4' : 'translate-x-[3px]'"></span>
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
                                            <div x-show="moreModelsOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full mb-1 sm:mb-0 sm:bottom-[-8px] sm:top-auto mt-2 sm:mt-0 w-[200px] bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
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

                                {{-- Mic Icon (speech-to-text dictation) --}}
                                <div x-data="voiceInput" x-show="supported" class="relative group flex items-center justify-center">
                                    <button type="button" @click="toggle()" :class="listening ? 'bg-red-50 dark:bg-red-500/10 text-red-500' : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700'" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="listening ? 'animate-pulse' : ''">
                                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                            <line x1="12" x2="12" y1="19" y2="22"/>
                                        </svg>
                                    </button>
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-1 hidden group-hover:block whitespace-nowrap bg-[#1E1E1E] text-white text-[13px] font-medium px-3 py-1.5 rounded-lg shadow-sm z-50" x-text="listening ? 'Listening… click to stop' : 'Dictate with your voice'"></div>
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
                    <button wire:click="$set('prompt', 'Write a ')" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                            <path d="m15 5 4 4"/>
                        </svg>
                        Write
                    </button>

                    <button wire:click="$set('prompt', 'Explain to me ')" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                        Learn
                    </button>

                    <button wire:click="$set('prompt', 'Write a code to ')" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0 group">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline class="code-bracket-right" points="16 18 22 12 16 6"/>
                            <polyline class="code-bracket-left" points="8 6 2 12 8 18"/>
                        </svg>
                        Code
                    </button>

                    <button wire:click="$set('prompt', 'Give me advice on ')" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                            <line x1="6" x2="6" y1="2" y2="4"/>
                            <line x1="10" x2="10" y1="2" y2="4"/>
                            <line x1="14" x2="14" y1="2" y2="4"/>
                        </svg>
                        Life stuff
                    </button>

                    <button wire:click="sendMessage" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M4.93 19.07L19.07 4.93"/>
                        </svg>
                        Surprise me
                    </button>
                </div>
            </div>
        </div>

    {{-- Active Chat State --}}
    @else
        {{-- Memory button --}}
        @if($conversationId)
            <div class="absolute top-3 right-3 z-40 group">
                <button wire:click="openMemory" type="button"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/80 dark:bg-stone-800/80 backdrop-blur-sm border border-claude-border-light dark:border-claude-border-dark rounded-full text-[12.5px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 shadow-sm transition-colors">
                    <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a9 9 0 0 0-9 9c0 3.6 2.1 6.7 5.2 8.2.3 1.1 1.4 1.8 2.6 1.8h2.4c1.2 0 2.3-.7 2.6-1.8C18.9 17.7 21 14.6 21 11a9 9 0 0 0-9-9Z"/><path d="M9 21h6"/></svg>
                    <span class="hidden sm:inline">Memory</span>
                </button>
            </div>
        @endif

        {{-- Scrollable Messages --}}
        <div class="flex-1 overflow-y-auto" id="chat-scroll-container">
            <div class="max-w-[42rem] mx-auto w-full py-4 md:py-6 px-3 md:px-4">
                <div class="space-y-1">
                    @foreach($messages as $msg)
                        <div class="w-full mx-auto flex flex-col group/msg">
                            @if($msg['role'] === 'user')
                                <!-- User Message -->
                                <div class="flex justify-end w-full">
                                    <div class="flex flex-col items-end gap-2 max-w-[85%] md:max-w-[75%]">
                                        @if(isset($msg['attachments']) && !empty($msg['attachments']))
                                            <div class="flex flex-wrap gap-2 justify-end w-full" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 8px; justify-content: flex-end; width: 100%;">
                                                @foreach($msg['attachments'] as $att)
                                                    <div class="relative group bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl shrink-0 overflow-hidden shadow-sm" style="width: 112px; height: 128px; min-width: 112px; flex-shrink: 0;">
                                                        @if(str_starts_with($att['file_type'], 'image/'))
                                                            <img src="{{ Storage::url($att['file_path']) }}" class="w-full h-full object-cover" alt="Attachment">
                                                            <div class="absolute bottom-2 left-2">
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold bg-white/90 dark:bg-stone-800/90 text-stone-600 dark:text-stone-300 uppercase border border-stone-200 dark:border-stone-600 backdrop-blur-sm shadow-sm" style="font-size: 10px;">
                                                                    {{ strtoupper(pathinfo($att['file_name'], PATHINFO_EXTENSION)) }}
                                                                </span>
                                                            </div>
                                                        @else
                                                            <div class="flex flex-col h-full justify-between bg-white dark:bg-stone-800" style="padding: 10px;">
                                                                <p class="text-xs text-stone-800 dark:text-stone-200 font-medium leading-snug line-clamp-4 break-words">
                                                                    {{ $att['file_name'] }}
                                                                </p>
                                                                <div class="mt-auto">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold bg-stone-100 dark:bg-stone-700 text-stone-600 dark:text-stone-300 uppercase border border-stone-200 dark:border-stone-600" style="font-size: 10px;">
                                                                        {{ strtoupper(pathinfo($att['file_name'], PATHINFO_EXTENSION)) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(!empty($msg['content']))
                                            <div class="bg-stone-100 dark:bg-stone-800 border border-transparent text-stone-900 dark:text-stone-100 px-5 md:px-6 py-3 md:py-4 rounded-2xl md:rounded-3xl text-[15px] leading-relaxed break-words w-full">
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
                                        <svg class="w-6 h-6 md:w-7 md:h-7 text-[#D97757]" viewBox="0 0 100 100" fill="currentColor">
                                            <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-[#0B0B0B] dark:text-stone-200 text-[16px] leading-[1.6] max-w-[90%] prose prose-stone dark:prose-invert max-w-none w-full font-claude-response prose-p:mt-0 prose-p:mb-3 prose-p:pl-2 prose-p:pr-8 [&_li>p]:my-0 [&_ul]:mt-0 [&_ol]:mt-0 [&_ul]:mb-3 [&_ol]:mb-3 prose-headings:font-sans prose-headings:font-semibold prose-headings:text-[#0B0B0B] dark:prose-headings:text-stone-100 prose-headings:mt-6 prose-headings:mb-3 prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-ul:list-disc prose-ol:list-decimal prose-li:my-0 prose-li:pl-2 prose-ul:pl-5 prose-ol:pl-5 prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-pre:p-4 prose-pre:my-4 prose-pre:overflow-x-auto prose-code:px-1.5 prose-code:py-0.5 prose-code:bg-stone-100 dark:prose-code:bg-stone-800 prose-code:text-[#0B0B0B] dark:prose-code:text-stone-200 prose-code:rounded-md prose-code:font-mono prose-code:text-[14px] prose-code:font-medium prose-code:before:content-none prose-code:after:content-none prose-a:text-[#D97757] hover:prose-a:text-[#c96646] prose-a:no-underline hover:prose-a:underline transition-colors prose-strong:font-semibold prose-strong:text-[#0B0B0B] dark:prose-strong:text-stone-100 prose-blockquote:border-l-4 prose-blockquote:border-stone-300 dark:prose-blockquote:border-stone-700 prose-blockquote:pl-4 prose-blockquote:italic prose-blockquote:text-stone-600 dark:prose-blockquote:text-stone-400 prose-table:w-full prose-table:border-collapse prose-table:my-4 prose-th:border prose-th:border-stone-300 dark:prose-th:border-stone-700 prose-th:px-4 prose-th:py-2 prose-th:bg-stone-100 dark:prose-th:bg-stone-800 prose-th:font-semibold prose-td:border prose-td:border-stone-300 dark:prose-td:border-stone-700 prose-td:px-4 prose-td:py-2" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;">
                                        {!! Illuminate\Support\Str::markdown($msg['content'] ?? '', ['html_input' => 'strip']) !!}

                                        @if(isset($msg['artifact']) && $msg['artifact'])
                                            <div wire:click="openArtifact({{ $msg['artifact']['id'] }})" class="mt-3 inline-flex items-center gap-3 border border-claude-border-light dark:border-claude-border-dark rounded-xl p-2 pr-4 bg-claude-bg-light dark:bg-claude-bg-dark shadow-sm cursor-pointer hover:border-[#D97757] dark:hover:border-[#D97757] transition-colors max-w-full group">
                                                <div class="w-10 h-10 bg-claude-bg-light dark:bg-stone-700 rounded-lg flex items-center justify-center shrink-0 group-hover:bg-[#F3F2EE] dark:group-hover:bg-stone-600 transition-colors">
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
                                            {{-- Read aloud (text-to-speech) --}}
                                            <button
                                                x-data="{ speaking: false }"
                                                x-show="'speechSynthesis' in window"
                                                @click="
                                                    if (speaking) { window.speechSynthesis.cancel(); speaking = false; }
                                                    else {
                                                        window.speechSynthesis.cancel();
                                                        const u = new SpeechSynthesisUtterance(@js($msg['content']));
                                                        u.onend = () => speaking = false;
                                                        u.onerror = () => speaking = false;
                                                        speaking = true;
                                                        window.speechSynthesis.speak(u);
                                                    }
                                                "
                                                class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors"
                                                :title="speaking ? 'Stop' : 'Read aloud'"
                                            >
                                                <svg x-show="!speaking" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H2v6h4l5 4V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.54 8.46a5 5 0 010 7.07M19.07 4.93a10 10 0 010 14.14"/></svg>
                                                <svg x-show="speaking" x-cloak class="w-3.5 h-3.5 text-[#D97757]" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
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
                    <div wire:loading wire:target="sendMessage, generateResponse" class="w-full max-w-[42rem] mx-auto flex flex-col py-0.5 md:py-1 px-2 md:px-4 mt-1">
                        <div class="flex justify-start w-full gap-3 md:gap-4">
                            <div class="flex-shrink-0 mt-1">
                                        <svg class="w-6 h-6 md:w-7 md:h-7 text-[#D97757] animate-spin" viewBox="0 0 100 100" fill="currentColor">
                                            <path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path>
                                        </svg>
                            </div>
                            <div class="flex flex-col gap-1.5 max-w-[90%] w-full">
                                {{-- Live activity status: what the assistant is doing right now --}}
                                <div wire:stream="activity-status" class="empty:hidden text-[13px] text-[#D97757] font-medium"></div>
                                <div class="text-[#0B0B0B] dark:text-stone-200 text-[16px] leading-[1.6] prose prose-stone dark:prose-invert max-w-none w-full font-claude-response prose-p:mt-0 prose-p:mb-3 prose-p:pl-2 prose-p:pr-8 [&_li>p]:my-0 [&_ul]:mt-0 [&_ol]:mt-0 [&_ul]:mb-3 [&_ol]:mb-3 prose-headings:font-sans prose-headings:font-semibold prose-headings:text-[#0B0B0B] dark:prose-headings:text-stone-100 prose-headings:mt-6 prose-headings:mb-3 prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-ul:list-disc prose-ol:list-decimal prose-li:my-0 prose-li:pl-2 prose-ul:pl-5 prose-ol:pl-5 prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-pre:p-4 prose-pre:my-4 prose-pre:overflow-x-auto prose-code:px-1.5 prose-code:py-0.5 prose-code:bg-stone-100 dark:prose-code:bg-stone-800 prose-code:text-[#0B0B0B] dark:prose-code:text-stone-200 prose-code:rounded-md prose-code:font-mono prose-code:text-[14px] prose-code:font-medium prose-code:before:content-none prose-code:after:content-none prose-a:text-[#D97757] hover:prose-a:text-[#c96646] prose-a:no-underline hover:prose-a:underline transition-colors prose-strong:font-semibold prose-strong:text-[#0B0B0B] dark:prose-strong:text-stone-100 prose-blockquote:border-l-4 prose-blockquote:border-stone-300 dark:prose-blockquote:border-stone-700 prose-blockquote:pl-4 prose-blockquote:italic prose-blockquote:text-stone-600 dark:prose-blockquote:text-stone-400 prose-table:w-full prose-table:border-collapse prose-table:my-4 prose-th:border prose-th:border-stone-300 dark:prose-th:border-stone-700 prose-th:px-4 prose-th:py-2 prose-th:bg-stone-100 dark:prose-th:bg-stone-800 prose-th:font-semibold prose-td:border prose-td:border-stone-300 dark:prose-td:border-stone-700 prose-td:px-4 prose-td:py-2 [&::after]:hidden" wire:stream="message-stream" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;">
                                    <div class="text-stone-400 text-[15px] flex items-center gap-3 mt-1 font-medium">
                                        <span>Rynude is thinking...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-center w-full">
                            <button
                                type="button"
                                x-data="{ stopping: false }"
                                @click="stopping = true; fetch('{{ route('chat.stop') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'ngrok-skip-browser-warning': 'true' }, body: JSON.stringify({ conversation_id: $wire.get('conversationId') }) })"
                                class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors shadow-sm disabled:opacity-60"
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
        <div class="shrink-0 h-fit bg-background dark:bg-background" wire:key="active-state-form-container">
            <form wire:submit.prevent="sendMessage" class="w-full max-w-[42rem] mx-auto pb-4 md:pb-6 px-3 md:px-4 pt-3 md:pt-4" wire:key="active-state-form">
                {{-- Prompt Box Container --}}
                <div class="relative w-full mx-auto bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark/80 rounded-[1.25rem] shadow-sm flex flex-col focus-within:shadow-glow focus-within:border-stone-300 dark:focus-within:border-stone-500 animate-smooth transition-all duration-200">
                    
                    {{-- Uploading State --}}
                    <div wire:loading wire:target="attachments" class="px-4 pt-4 pb-2 flex items-center gap-3">
                        <div class="w-16 h-16 rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                            <svg class="animate-spin w-6 h-6 text-[#D97757]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Uploading...</span>
                            <span class="text-[11px] text-stone-500">Please wait</span>
                        </div>
                    </div>

                    {{-- Attachment Preview Area --}}
                    @if(!empty($attachments))
                        <div wire:loading.remove wire:target="attachments" class="px-4 pt-4 pb-2 flex flex-wrap items-center gap-3" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 12px;">
                            @foreach($attachments as $index => $attachment)
                            <div class="relative group bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl shrink-0 overflow-hidden shadow-sm" style="width: 112px; height: 128px; min-width: 112px; flex-shrink: 0;">
                                @if(str_starts_with($attachment->getMimeType(), 'image/'))
                                    <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                    <div class="absolute bottom-2 left-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold bg-white/90 dark:bg-stone-800/90 text-stone-600 dark:text-stone-300 uppercase border border-stone-200 dark:border-stone-600 backdrop-blur-sm shadow-sm" style="font-size: 10px;">
                                            {{ strtoupper($attachment->getClientOriginalExtension()) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="flex flex-col h-full justify-between bg-white dark:bg-stone-800" style="padding: 10px;">
                                        <p class="text-xs text-stone-800 dark:text-stone-200 font-medium leading-snug line-clamp-4 break-words">
                                            {{ $attachment->getClientOriginalName() }}
                                        </p>
                                        <div class="mt-auto">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold bg-stone-100 dark:bg-stone-700 text-stone-600 dark:text-stone-300 uppercase border border-stone-200 dark:border-stone-600" style="font-size: 10px;">
                                                {{ strtoupper($attachment->getClientOriginalExtension()) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                
                                <button type="button" wire:click="removeAttachment({{ $index }})" class="absolute top-1.5 right-1.5 bg-black/40 hover:bg-black/60 dark:bg-black/60 dark:hover:bg-black/80 rounded-full p-1 text-white opacity-0 group-hover:opacity-100 transition-opacity z-10 backdrop-blur-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    @endif

                    <textarea 
                        x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                        x-init="$watch('$wire.prompt', value => { if(!value) { $el.style.height = 'auto'; } else { resize(); } }); resize()"
                        @input="resize()"
                        wire:model.live.debounce.2000ms="prompt" 
                        @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $wire.sendMessage() }" 
                        rows="1" 
                        class="w-full bg-transparent border-0 focus:ring-0 px-3 md:px-4 pt-3 md:pt-4 pb-2 resize-none text-[#2D2825] dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto" 
                        placeholder="How can I help you today?"></textarea>

                    {{-- Bottom Action Bar --}}
                    <div x-data="{ webSearch: @entangle('webSearch') }" class="flex items-center justify-between px-3 pb-3 pt-1">
                        {{-- Left: Plus Icon --}}
                        <div x-data="{ openPlus: false }" class="relative">
                            <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 hover:text-stone-800 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 text-stone-800' : 'hover:bg-stone-100'">
                            <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 hover:text-stone-800 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 text-stone-800' : 'hover:bg-stone-100'">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                            </button>

                            <div x-show="openPlus" @click.away="openPlus = false" x-transition.opacity x-cloak class="absolute bottom-full left-0 mb-2 w-[240px] bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="display: none;">
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
                                <button type="button" @click="webSearch = !webSearch" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4" :class="webSearch ? 'text-[#D97757]' : 'text-stone-500 group-hover:text-stone-700'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Web search</span>
                                    </div>
                                    <svg x-show="webSearch" x-cloak class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
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

                            {{-- Mic Icon (speech-to-text dictation) --}}
                            <div x-data="voiceInput" x-show="supported" class="relative group flex items-center justify-center">
                                <button type="button" @click="toggle()" :class="listening ? 'bg-red-50 dark:bg-red-500/10 text-red-500' : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700'" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="listening ? 'animate-pulse' : ''">
                                        <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                        <line x1="12" x2="12" y1="19" y2="22"/>
                                    </svg>
                                </button>
                                <!-- Tooltip -->
                                <div class="absolute bottom-full mb-1 hidden group-hover:block whitespace-nowrap bg-[#1E1E1E] text-white text-[13px] font-medium px-3 py-1.5 rounded-lg shadow-sm z-50" x-text="listening ? 'Listening… click to stop' : 'Dictate with your voice'"></div>
                            </div>

                            {{-- Send Button --}}
                            <button type="submit" x-data :disabled="!$wire.prompt.trim() && $wire.attachments.length === 0" wire:loading.attr="disabled" wire:target="sendMessage, generateResponse, attachments" :class="($wire.prompt.trim() || $wire.attachments.length > 0) ? 'bg-[#D97757] text-white hover:bg-[#c96646]' : 'bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-500'" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                                <svg wire:loading.remove wire:target="sendMessage, generateResponse, attachments" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 19V5M5 12l7-7 7 7"/>
                                </svg>
                                <svg wire:loading wire:target="sendMessage, generateResponse, attachments" class="animate-spin w-[18px] h-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
            if(pre.hasAttribute('data-enhanced')) return;
            pre.setAttribute('data-enhanced', 'true');

            const codeEl = pre.querySelector('code');
            let lang = '';
            if (codeEl) {
                const langClass = Array.from(codeEl.classList).find(c => c.startsWith('language-'));
                if (langClass) {
                    lang = langClass.replace('language-', '');
                }
                if (!codeEl.hasAttribute('data-highlighted') && window.hljs) {
                    try {
                        hljs.highlightElement(codeEl);
                        if (!lang && codeEl.result && codeEl.result.language) {
                            lang = codeEl.result.language;
                        }
                    } catch (e) {}
                }
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'my-5 rounded-xl overflow-hidden border border-[#E5E5E5] dark:border-stone-700 shadow-sm bg-[#1E1E1E] flex flex-col font-sans';
            
            pre.parentNode.insertBefore(wrapper, pre);
            
            const topBar = document.createElement('div');
            topBar.className = 'flex items-center justify-between px-4 py-2 bg-stone-100 dark:bg-[#2B2D31] border-b border-[#E5E5E5] dark:border-stone-700 text-xs font-mono text-stone-500 dark:text-stone-400';
            
            const langLabel = document.createElement('span');
            langLabel.textContent = lang || 'text';
            topBar.appendChild(langLabel);

            const copyBtn = document.createElement('button');
            copyBtn.className = 'flex items-center gap-1.5 hover:text-stone-800 dark:hover:text-stone-200 transition-colors';
            copyBtn.innerHTML = `
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                <span>Copy code</span>
            `;
            
            copyBtn.addEventListener('click', async () => {
                const codeNode = pre.querySelector('code');
                const code = codeNode ? codeNode.innerText : pre.innerText;
                try {
                    await navigator.clipboard.writeText(code);
                    copyBtn.innerHTML = `
                        <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-green-600 dark:text-green-500">Copied!</span>
                    `;
                    setTimeout(() => {
                        copyBtn.innerHTML = `
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            <span>Copy code</span>
                        `;
                    }, 2000);
                } catch (err) {}
            });
            
            topBar.appendChild(copyBtn);
            wrapper.appendChild(topBar);
            
            pre.classList.remove('my-4', 'rounded-xl', 'border', 'shadow-sm', 'border-stone-700/50');
            pre.classList.add('!my-0', '!border-0', '!bg-transparent', '!rounded-none');
            wrapper.appendChild(pre);
        });
    }
</script>

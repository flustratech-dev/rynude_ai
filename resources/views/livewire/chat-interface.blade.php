<div id="chat-interface-root" class="flex flex-col h-full bg-transparent dark:bg-claude-bg-dark relative"
     x-data="chatInterfaceState()"
     x-init="init()"
     x-on:dragover.prevent="isDropping = true"
     x-on:dragleave.prevent="isDropping = false"
     x-on:drop.prevent="isDropping = false; handleDrop($event)">

    {{-- Drag & Drop Overlay --}}
    <div x-show="isDropping" x-transition x-cloak class="absolute inset-0 z-[100] flex items-center justify-center bg-white/80 dark:bg-stone-900/80 backdrop-blur-sm border-2 border-dashed border-[#D97757] rounded-xl m-2">
        <div class="flex flex-col items-center pointer-events-none">
            <svg class="w-12 h-12 text-[#D97757] mb-3 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <h3 class="text-xl font-medium text-stone-800 dark:text-stone-200">Drop files here to attach</h3>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div x-show="loading" class="absolute inset-0 z-[60] flex flex-col items-center justify-center bg-background/90 backdrop-blur-sm">
        <div class="w-12 h-12 rounded-2xl bg-claude-bg-light dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark flex items-center justify-center mb-4 shadow-sm">
            <svg class="w-6 h-6 text-[#D97757] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
        </div>
        <p class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Loading conversation...</p>
    </div>

    <input type="file" x-ref="fileInput" id="file-upload" class="hidden" multiple accept="image/*,.pdf,.doc,.docx,.txt" @change="handleFileUpload($event)">

    {{-- Memory modal --}}
    <template x-if="showMemory">
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showMemory = false"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-stone-900 border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-xl flex flex-col max-h-[80vh]">
                <div class="flex items-start gap-3 px-5 pt-5 pb-3 border-b border-claude-border-light dark:border-claude-border-dark">
                    <div class="w-9 h-9 rounded-xl bg-[#D97757]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a9 9 0 0 0-9 9c0 3.6 2.1 6.7 5.2 8.2.3 1.1 1.4 1.8 2.6 1.8h2.4c1.2 0 2.3-.7 2.6-1.8C18.9 17.7 21 14.6 21 11a9 9 0 0 0-9-9Z"/><path d="M9 21h6"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-100">Conversation memory</h3>
                        <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5">Durable facts the assistant keeps in mind.</p>
                    </div>
                    <button type="button" @click="showMemory = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4 overflow-y-auto">
                    <textarea x-model="memoryDraft" rows="10"
                        placeholder="No memory recorded yet. Notable facts will appear here automatically."
                        class="w-full bg-stone-50 dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-xl px-3.5 py-3 text-[13.5px] leading-relaxed text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-1 focus:ring-[#D97757]/40 focus:border-[#D97757]/40 resize-y font-mono"></textarea>
                    <p x-show="memoryUpdatedAt" class="text-[11.5px] text-stone-400 dark:text-stone-500 mt-2" x-text="'Last updated ' + memoryUpdatedAt"></p>
                </div>
                <div class="flex items-center justify-between gap-2 px-5 py-3.5 border-t border-claude-border-light dark:border-claude-border-dark">
                    <button type="button" @click="clearMemory()" class="text-[13px] font-medium text-stone-500 hover:text-red-500 transition-colors px-2 py-1.5">Clear</button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showMemory = false" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg px-3 py-1.5 transition-colors">Cancel</button>
                        <button type="button" @click="saveMemory()" class="text-[13px] font-medium text-white bg-[#D97757] hover:bg-[#c96646] rounded-lg px-3.5 py-1.5 transition-colors shadow-sm">Save memory</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty State --}}
    <template x-if="!conversationId && messages.length === 0">
        <div class="flex-1 flex flex-col justify-center items-center px-4 -mt-16 md:-mt-32">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3 md:gap-4">
                    <svg viewBox="0 0 100 100" class="w-8 h-8 md:w-10 md:h-10 text-[#D97757] fill-current shrink-0" xmlns="http://www.w3.org/2000/svg"><path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path></svg>
                    <h1 class="font-serif text-[#2D2825] dark:text-[#E8E8E6] tracking-tight" style="font-size:clamp(1.875rem,1.2rem+2vw,2.5rem);line-height:1.5;" x-text="'Welcome back, ' + userName"></h1>
                </div>
            </div>

            <div class="w-full max-w-full md:max-w-[42rem] mx-auto">
                <form @submit.prevent="sendMessage()">
                    <div class="relative w-full mx-auto bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-2xl md:rounded-3xl shadow-md flex flex-col focus-within:shadow-lg focus-within:border-claude-accent/50 dark:focus-within:border-claude-accent/50 animate-smooth transition-all duration-200">
                        <div x-show="uploading" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <div class="w-16 h-16 rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-stone-900 flex items-center justify-center">
                                <svg class="animate-spin w-6 h-6 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                            </div>
                            <div><p class="text-[14px] font-medium text-stone-700 dark:text-stone-300">Uploading...</p><p class="text-[12px] text-stone-400 dark:text-stone-500">Processing your files</p></div>
                        </div>
                        <div x-show="!uploading && attachments.length > 0" class="px-4 pt-4 pb-2 flex flex-wrap items-center gap-3">
                            <template x-for="(att, idx) in attachments" :key="idx">
                                <div class="relative group rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-stone-900 p-2 pr-8 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-stone-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span class="text-[13px] text-stone-700 dark:text-stone-300 truncate max-w-[120px]" x-text="att.name"></span>
                                    <button type="button" @click="removeAttachment(idx)" class="absolute top-1.5 right-1.5 bg-black/40 hover:bg-black/60 dark:bg-black/60 dark:hover:bg-black/80 rounded-full p-1 text-white opacity-0 group-hover:opacity-100 transition-opacity z-10 backdrop-blur-sm">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <textarea x-ref="chatInput" x-model="prompt"
                            @input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                            @keydown.enter="if(!$event.shiftKey) {$event.preventDefault(); sendMessage()}"
                            rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto"
                            placeholder="How can I help you today?"></textarea>

                        <div class="flex items-center justify-between w-full mt-4 pb-1">
                            <div class="relative">
                                <button type="button" @click="$refs.fileInput.click()" class="p-2 text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-1 md:gap-1.5 text-stone-500">
                                <div x-data="{ open: false, ext: true, subOpen: false, closeTimer: null }" class="relative">
                                    <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[120px] truncate" x-text="selectedModelName"></span>
                                        <span class="text-[13px] text-stone-500 hidden sm:inline" x-show="ext">Extended</span>
                                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute bottom-full right-0 mb-2 w-[250px] bg-white border border-[#E5E5E5] rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5">
                                        <template x-for="m in models" :key="m.code">
                                            <button @click="selectedModel=m.code; open=false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 transition-colors flex items-center justify-between group" :class="!m.is_available?'opacity-50 cursor-not-allowed':''" :disabled="!m.is_available">
                                                <div><div class="text-[13px] text-stone-800" x-text="m.name"></div><div class="text-[12px] text-stone-400" x-text="m.description"></div></div>
                                                <svg x-show="selectedModel===m.code" class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </template>
                                        <div class="h-px bg-[#E5E5E5] mx-3 my-1.5"></div>
                                        <div class="px-3 py-1.5 flex items-center justify-between cursor-pointer" @click="ext=!ext">
                                            <div><div class="text-[13px] text-stone-800">Extended</div><p class="text-[11.5px] text-stone-500 mt-0.5">Always uses deep reasoning</p></div>
                                            <div class="relative inline-flex h-5 w-9 items-center rounded-full" :class="ext?'bg-[#2563EB]':'bg-gray-200'"><span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow" :class="ext?'translate-x-4':'translate-x-[3px]'"></span></div>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="webSearchSupported">
                                    <button @click="webSearch=!webSearch" type="button" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center" :class="webSearch?'bg-[#D97757]/10 text-[#D97757]':'text-stone-500'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </button>
                                </template>
                                <div x-data="voiceInput" x-show="supported" class="relative group flex items-center justify-center">
                                    <button type="button" @click="toggle()" :class="listening?'bg-red-50 text-red-500':'text-stone-500 hover:text-stone-800'" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="listening?'animate-pulse':''"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                                    </button>
                                </div>
                                <button type="submit" :disabled="sending||!prompt.trim()" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center" :class="(sending||!prompt.trim())?'bg-stone-100 text-stone-400':'bg-[#D97757] text-white hover:bg-[#c96646]'">
                                    <svg x-show="!sending" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                    <svg x-show="sending" class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="flex items-center flex-wrap justify-center gap-2 mt-6">
                    <button @click="prompt='Write a '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light border border-claude-border-light rounded-full text-[13px] font-medium text-stone-600 hover:bg-stone-50 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Write
                    </button>
                    <button @click="prompt='Explain '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light border border-claude-border-light rounded-full text-[13px] font-medium text-stone-600 hover:bg-stone-50 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>Explain
                    </button>
                    <button @click="prompt='Write code to '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light border border-claude-border-light rounded-full text-[13px] font-medium text-stone-600 hover:bg-stone-50 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>Code
                    </button>
                    <button @click="prompt='Give me advice on '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light border border-claude-border-light rounded-full text-[13px] font-medium text-stone-600 hover:bg-stone-50 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>Advice
                    </button>
                    <button @click="sendSurpriseMessage()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light border border-claude-border-light rounded-full text-[13px] font-medium text-stone-600 hover:bg-stone-50 shadow-sm hover:shadow hover:-translate-y-0.5 active:translate-y-0 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>Surprise me
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Active Chat --}}
    <template x-if="conversationId || messages.length > 0">
        <div class="flex flex-col flex-1 overflow-hidden">
            <div class="shrink-0 px-4 py-2 md:py-3 flex items-center border-b border-claude-border-light dark:border-claude-border-dark">
                <button @click="openMemory()" type="button" class="flex items-center gap-1.5 px-3 py-1.5 text-[13px] text-[#D97757] hover:bg-[#D97757]/5 rounded-lg transition-colors" title="Conversation memory">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a9 9 0 0 0-9 9c0 3.6 2.1 6.7 5.2 8.2.3 1.1 1.4 1.8 2.6 1.8h2.4c1.2 0 2.3-.7 2.6-1.8C18.9 17.7 21 14.6 21 11a9 9 0 0 0-9-9Z"/><path d="M9 21h6"/></svg>
                    <span>Memory</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 md:px-6 py-4 md:py-6 space-y-4 md:space-y-6" x-ref="messagesContainer">
                <template x-for="(msg, idx) in messages" :key="'msg-' + idx">
                    <div class="w-full mx-auto flex flex-col group/msg" :class="msg.role==='user'?'items-end':'items-start'">
                        <div class="flex gap-3 max-w-[85%] md:max-w-[70%]" :class="msg.role==='user'?'flex-row-reverse':'flex-row'">
                            <div :class="msg.role==='user'?'w-7 h-7 bg-[#D97757] text-white':'w-7 h-7 bg-stone-100 text-stone-500'" class="rounded-full flex items-center justify-center shrink-0 text-xs font-medium" x-text="msg.role==='user'?'U':'R'"></div>
                            <div class="min-w-0">
                                <template x-if="msg.role==='user'">
                                    <div class="px-4 py-2.5 rounded-2xl bg-[#D97757] text-white text-[15px] leading-relaxed whitespace-pre-wrap break-words" x-text="msg.content"></div>
                                </template>
                                <template x-if="msg.role!=='user'">
                                    <div class="px-4 py-2.5 rounded-2xl bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 text-[15px] leading-relaxed text-stone-800 dark:text-stone-200 break-words prose prose-stone dark:prose-invert max-w-none" x-html="renderContent(msg.content)"></div>
                                </template>
                                <template x-if="msg.artifact">
                                    <div @click="openArtifact(msg.artifact.id)" class="mt-3 inline-flex items-center gap-3 border border-claude-border-light dark:border-claude-border-dark rounded-xl p-2 pr-4 bg-claude-bg-light dark:bg-claude-bg-dark shadow-sm cursor-pointer hover:border-[#D97757] dark:hover:border-[#D97757] transition-colors max-w-full group">
                                        <div class="w-8 h-8 rounded-lg bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-500 group-hover:text-[#D97757] transition-colors shrink-0">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-[13px] font-medium text-stone-800 dark:text-stone-200 truncate" x-text="msg.artifact.title"></div>
                                            <div class="text-[11px] text-stone-400" x-text="msg.artifact.type"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="msg.role!=='user'">
                                    <div class="flex items-center gap-1 mt-1.5 px-2 opacity-0 group-hover/msg:opacity-100 transition-opacity">
                                        <button @click="rateMessage(idx, 'up')" class="p-1 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-400 hover:text-green-600 transition-colors" :class="msg.rating==='up'?'text-green-600':''">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                                        </button>
                                        <button @click="rateMessage(idx, 'down')" class="p-1 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-400 hover:text-red-600 transition-colors" :class="msg.rating==='down'?'text-red-600':''">
                                            <svg class="w-3.5 h-3.5 scale-y-[-1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
                                        </button>
                                        <button @click="editMessage(idx)" class="p-1 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-400 hover:text-stone-600 transition-colors">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Streaming indicator --}}
                <div x-show="streaming" class="w-full max-w-[42rem] mx-auto flex flex-col py-0.5 md:py-1 px-2 md:px-4 mt-1">
                    <div class="flex items-center gap-2 text-[13px] text-stone-500">
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                        <span>Rynude is thinking...</span>
                    </div>
                    <div x-html="streamContent" class="prose prose-stone dark:prose-invert max-w-none mt-2"></div>
                </div>

                {{-- Stop button --}}
                <div x-show="streaming" class="flex justify-center">
                    <button @click="stopGeneration()" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-sm hover:shadow-md text-[13px] font-medium text-stone-700 dark:text-stone-300 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                        Stop generating
                    </button>
                </div>
            </div>

            <div class="shrink-0 h-fit bg-background dark:bg-background">
                <form @submit.prevent="sendMessage()" class="w-full max-w-[42rem] mx-auto pb-4 md:pb-6 px-3 md:px-4 pt-3 md:pt-4">
                    <div class="relative bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-sm flex flex-col focus-within:shadow-lg focus-within:border-[#D97757]/50 transition-all duration-200">
                        <div x-show="uploading" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <svg class="animate-spin w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                            <span class="text-[13px] text-stone-500">Uploading...</span>
                        </div>
                        <div x-show="!uploading && attachments.length > 0" class="px-4 pt-4 pb-2 flex flex-wrap items-center gap-2"></div>
                        <textarea x-model="prompt" @input="autoResize($event)" @keydown.enter="if(!$event.shiftKey){$event.preventDefault();sendMessage()}" rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto"
                            placeholder="Message Rynude..."></textarea>
                        <div class="flex items-center justify-between w-full mt-4 pb-1">
                            <button type="button" @click="$refs.fileInput2.click()" class="p-2 text-stone-500 hover:text-stone-800 rounded-xl hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                            <input type="file" x-ref="fileInput2" class="hidden" multiple @change="handleFileUpload($event)">
                            <div class="flex items-center gap-2">
                                <button type="submit" :disabled="sending||!prompt.trim()" class="rounded-lg transition-colors p-1.5" :class="sending||!prompt.trim()?'text-stone-400':'bg-[#D97757] text-white hover:bg-[#c96646]'">
                                    <svg x-show="!sending" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                    <svg x-show="sending" class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
function chatInterfaceState() {
    return {
        prompt: '',
        messages: [],
        conversationId: null,
        showMemory: false,
        memoryDraft: '',
        memoryUpdatedAt: null,
        selectedModel: 'claude-haiku-4-5',
        models: [],
        sending: false,
        streaming: false,
        streamContent: '',
        loading: false,
        isDropping: false,
        attachments: [],
        uploading: false,
        webSearch: false,
        webSearchSupported: true,
        userName: '',

        get selectedModelName() {
            var m = this.models.find(function(m) { return m.code === this.selectedModel; }.bind(this));
            return m ? m.name : 'Select Model';
        },

        init: function() {
            var self = this;
            this.userName = document.querySelector('meta[name=user-name]')?.content || '';
            this.loadModels();
            this.loadConversations();

            window.addEventListener('selectConversation', function(e) {
                if (e.detail && e.detail.conversationId) self.loadConversation(e.detail.conversationId);
            });
            window.addEventListener('newChat', function() {
                self.conversationId = null;
                self.messages = [];
                self.prompt = '';
            });
            window.addEventListener('startProjectChat', function(e) {
                if (e.detail) {
                    self.conversationId = null;
                    self.messages = [];
                    if (e.detail.initialModel) self.selectedModel = e.detail.initialModel;
                    if (e.detail.initialPrompt) self.prompt = e.detail.initialPrompt;
                }
            });
            window.addEventListener('openChat', function(e) {
                if (e.detail && e.detail.chatId) self.loadConversation(e.detail.chatId);
            });
        },

        loadModels: function() {
            var self = this;
            this.models = [
                {code:'claude-opus-4-8',name:'Opus 4.8',description:'For complex tasks',is_available:false},
                {code:'claude-sonnet-4-6',name:'Sonnet 4.6',description:'Most efficient',is_available:false},
                {code:'claude-haiku-4-5',name:'Haiku 4.5',description:'Fastest',is_available:false},
            ];
            fetch('/api/settings', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    var u = resp.profile || {};
                    var keys = resp.api_keys || {};
                    var hasKey = keys.anthropic || keys.use_proxy || keys.nine_router;
                    self.models.forEach(function(m){ m.is_available = hasKey; });
                });
        },

        loadConversation: function(id) {
            this.loading = true;
            var self = this;
            fetch('/api/chats/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        self.conversationId = resp.data.id;
                        self.messages = resp.data.messages || [];
                        self.memoryDraft = resp.data.memory || '';
                    }
                    self.loading = false;
                })
                .catch(function(){self.loading=false;});
        },

        loadConversations: function() {
            var self = this;
            fetch('/api/chats', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()});
        },

        sendMessage: function() {
            if (!this.prompt.trim() || this.sending) return;
            this.sending = true;
            var self = this;

            var body = {
                prompt: this.prompt.trim(),
                model: this.selectedModel,
                web_search: this.webSearch,
            };
            if (this.conversationId) body.conversation_id = this.conversationId;

            self.messages.push({role:'user',content:self.prompt});
            self.prompt = '';
            self.streaming = true;
            self.streamContent = '';

            fetch('/api/chats/send', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'text/event-stream'},
                body: JSON.stringify(body)
            })
            .then(function(response) {
                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';

                function read() {
                    reader.read().then(function(result) {
                        if (result.done) {
                            self.streaming = false;
                            self.sending = false;
                            self.loadConversation(self.conversationId);
                            return;
                        }
                        buffer += decoder.decode(result.value, {stream:true});
                        var lines = buffer.split('\n');
                        buffer = lines.pop() || '';
                        lines.forEach(function(line) {
                            if (line.startsWith('data: ')) {
                                try {
                                    var data = JSON.parse(line.slice(6));
                                    if (data.type === 'content') {
                                        self.streamContent += data.data;
                                    }
                                } catch(e) {}
                            }
                        });
                        read();
                    });
                }
                read();
            })
            .catch(function() {
                self.streaming = false;
                self.sending = false;
            });
        },

        stopGeneration: function() {
            var self = this;
            if (!this.conversationId) return;
            fetch('/api/chats/stop', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({conversation_id: this.conversationId})
            })
            .then(function(){self.streaming=false;self.sending=false;});
        },

        handleFileUpload: function(event) {
            var files = event.target.files;
            if (!files) return;
            for (var i=0; i<files.length; i++) {
                this.attachments.push({name:files[i].name,file:files[i]});
            }
        },

        handleDrop: function(event) {
            var files = event.dataTransfer.files;
            if (files.length > 0) {
                for (var i=0; i<files.length; i++) {
                    this.attachments.push({name:files[i].name,file:files[i]});
                }
            }
        },

        removeAttachment: function(idx) {
            this.attachments.splice(idx, 1);
        },

        autoResize: function(event) {
            var el = event.target;
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        },

        openMemory: function() {
            this.showMemory = true;
        },

        saveMemory: function() {
            this.showMemory = false;
            this.memoryUpdatedAt = 'just now';
        },

        clearMemory: function() {
            this.memoryDraft = '';
        },

        openArtifact: function(id) {
            window.dispatchEvent(new CustomEvent('openArtifact', {detail:{id:id}}));
        },

        rateMessage: function(idx, rating) {
            if (this.messages[idx]) this.messages[idx].rating = rating;
        },

        editMessage: function(idx) {
            if (this.messages[idx] && this.messages[idx].role === 'user') {
                this.prompt = this.messages[idx].content;
            }
        },

        sendSurpriseMessage: function() {
            this.prompt = 'Surprise me! Tell me something interesting or create something fun.';
            this.sendMessage();
        },

        renderContent: function(content) {
            return content ? content.replace(/\n/g, '<br>') : '';
        },
    };
}
</script>
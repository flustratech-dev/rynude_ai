<div id="chat-interface-root" class="flex flex-col h-full min-h-0 w-full bg-transparent dark:bg-claude-bg-dark relative overflow-hidden"
     x-data="chatInterfaceState()"
     x-init="init()"
     x-on:dragover.prevent="isDropping = true"
     x-on:dragleave.prevent="isDropping = false"
     x-on:drop.prevent="isDropping = false; handleDrop($event)">

    <style>
        /* Artifact card: cream at rest, white on hover; the paper sheet sits
           tilted and straightens on hover */
        .artifact-card { transition: background-color .2s ease, box-shadow .2s ease; }
        .artifact-card:hover { background-color: #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .dark .artifact-card:hover { background-color: #3A3A38; }
        .artifact-paper { transform: rotate(-8deg); transition: transform .25s ease; }
        .artifact-card:hover .artifact-paper { transform: rotate(0deg) translateY(-2px); }
        /* Rotating placeholder: fades out, swaps text, fades back in */
        .placeholder-fade::placeholder { opacity: var(--ph-opacity, 1); transition: opacity .9s ease-in-out; }
    </style>

    {{-- Quote-reply: select text in a reply → ask about it --}}
    <button x-show="quoteSel" x-cloak @mousedown.prevent="applyQuote()" class="fixed z-[90] flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark shadow-md text-[12px] font-medium text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors" :style="'left:' + quoteX + 'px; top:' + Math.max(8, quoteY - 42) + 'px; transform: translateX(-50%);'">
        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
        Tanyakan
    </button>

    {{-- Drag & Drop Overlay --}}
    <div x-show="isDropping" x-transition x-cloak class="absolute inset-0 z-[100] flex items-center justify-center bg-white/80 dark:bg-[#2C2C2A]/80 backdrop-blur-sm border-2 border-dashed border-[#D97757] rounded-xl m-2">
        <div class="flex flex-col items-center pointer-events-none">
            <svg class="w-12 h-12 text-[#D97757] mb-3 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <h3 class="text-xl font-medium text-stone-800 dark:text-stone-200">Drop files here to attach</h3>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div x-show="loading" class="absolute inset-0 z-[60] flex items-center justify-center bg-background/90 backdrop-blur-sm">
        <img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="w-8 h-8 animate-spin object-contain">
    </div>

    <input type="file" x-ref="fileInput" id="file-upload" class="hidden" multiple accept="image/*,.pdf,.doc,.docx,.txt" @change="handleFileUpload($event)">

    {{-- Memory modal --}}
    <template x-if="showMemory">
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showMemory = false"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-xl flex flex-col max-h-[80vh]">
                <div class="flex items-start gap-3 px-5 pt-5 pb-3 border-b border-claude-border-light dark:border-claude-border-dark">
                    <div class="w-9 h-9 rounded-xl bg-[#D97757]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a9 9 0 0 0-9 9c0 3.6 2.1 6.7 5.2 8.2.3 1.1 1.4 1.8 2.6 1.8h2.4c1.2 0 2.3-.7 2.6-1.8C18.9 17.7 21 14.6 21 11a9 9 0 0 0-9-9Z"/><path d="M9 21h6"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-200">Conversation memory</h3>
                        <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5">Durable facts the assistant keeps in mind.</p>
                    </div>
                    <button type="button" @click="showMemory = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4 overflow-y-auto">
                    <textarea x-model="memoryDraft" rows="10"
                        placeholder="No memory recorded yet. Notable facts will appear here automatically."
                        class="w-full bg-stone-50 dark:bg-[#3A3A38] border border-claude-border-light dark:border-claude-border-dark rounded-xl px-3.5 py-3 text-[13.5px] leading-relaxed text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-1 focus:ring-[#D97757]/40 focus:border-[#D97757]/40 resize-y font-mono"></textarea>
                    <p x-show="memoryUpdatedAt" class="text-[11.5px] text-stone-400 dark:text-stone-500 mt-2" x-text="'Last updated ' + memoryUpdatedAt"></p>
                </div>
                <div class="flex items-center justify-between gap-2 px-5 py-3.5 border-t border-claude-border-light dark:border-claude-border-dark">
                    <button type="button" @click="clearMemory()" class="text-[13px] font-medium text-stone-500 hover:text-red-500 transition-colors px-2 py-1.5">Clear</button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showMemory = false" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-[#3A3A38] rounded-lg px-3 py-1.5 transition-colors">Cancel</button>
                        <button type="button" @click="saveMemory()" class="text-[13px] font-medium text-white bg-[#D97757] hover:bg-[#c96646] rounded-lg px-3.5 py-1.5 transition-colors shadow-sm">Save memory</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Rename Modal --}}
    <template x-if="showRenameModal">
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showRenameModal = false"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-xl p-5">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-[#D97757]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-200">Rename chat</h3>
                        <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5">Enter a new name for this conversation</p>
                    </div>
                    <button type="button" @click="showRenameModal = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <input type="text" x-model="renameDraft" @keydown.enter="submitRename()" @keydown.escape="showRenameModal = false" placeholder="Chat name" class="w-full bg-stone-50 dark:bg-[#3A3A38] border border-claude-border-light dark:border-claude-border-dark rounded-xl px-3.5 py-2.5 text-[14px] text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-1 focus:ring-[#D97757]/40 focus:border-[#D97757]/40" x-ref="renameInput">
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button type="button" @click="showRenameModal = false" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-[#3A3A38] rounded-lg px-3 py-1.5 transition-colors">Cancel</button>
                    <button type="button" @click="submitRename()" class="text-[13px] font-medium text-white bg-[#D97757] hover:bg-[#c96646] rounded-lg px-3.5 py-1.5 transition-colors shadow-sm">Rename</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Add to Project Modal --}}
    <template x-if="showAddProjectModal">
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showAddProjectModal = false"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-xl p-5">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-[#D97757]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-200">Add to project</h3>
                        <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5">Select a project for this conversation</p>
                    </div>
                    <button type="button" @click="showAddProjectModal = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[300px] overflow-y-auto space-y-1">
                    <template x-if="availableProjects.length === 0">
                        <div class="text-center py-8 text-stone-500 dark:text-stone-400 text-[13px]">
                            <p>No projects available</p>
                            <p class="mt-1">Create a project first</p>
                        </div>
                    </template>
                    <template x-for="proj in availableProjects" :key="proj.id">
                        <button type="button" @click="selectedProjectForAdd = proj.id" class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-3" :class="selectedProjectForAdd === proj.id ? 'bg-stone-100 dark:bg-[#3A3A38]' : ''">
                            <div class="flex-1 min-w-0">
                                <div class="text-[13.5px] font-medium text-stone-800 dark:text-stone-200 truncate" x-text="proj.name"></div>
                                <div class="text-[12px] text-stone-500 dark:text-stone-400 truncate" x-text="proj.description || 'No description'"></div>
                            </div>
                            <svg x-show="selectedProjectForAdd === proj.id" class="w-4 h-4 text-[#D97757] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                        </button>
                    </template>
                </div>
                <div class="flex items-center justify-between gap-2 mt-4 pt-4 border-t border-claude-border-light dark:border-claude-border-dark">
                    <button type="button" @click="removeFromProject()" x-show="chatProject" class="text-[13px] font-medium text-stone-500 hover:text-red-500 transition-colors px-2 py-1.5">Remove from project</button>
                    <div class="flex items-center gap-2" :class="!chatProject ? 'ml-auto' : ''">
                        <button type="button" @click="showAddProjectModal = false" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-[#3A3A38] rounded-lg px-3 py-1.5 transition-colors">Cancel</button>
                        <button type="button" @click="submitAddToProject()" :disabled="!selectedProjectForAdd" class="text-[13px] font-medium text-white bg-[#D97757] hover:bg-[#c96646] rounded-lg px-3.5 py-1.5 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Delete Confirmation Modal --}}
    <template x-if="showDeleteModal">
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-lg shadow-xl p-5">
                <div class="flex items-start gap-3 mb-4">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-200">Delete chat?</h3>
                        <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5">This will permanently delete this conversation and all its messages. This action cannot be undone.</p>
                    </div>
                    <button type="button" @click="showDeleteModal = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button type="button" @click="showDeleteModal = false" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-[#3A3A38] rounded-lg px-3 py-1.5 transition-colors">Cancel</button>
                    <button type="button" @click="submitDelete()" class="text-[13px] font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg px-3.5 py-1.5 transition-colors shadow-sm">Delete</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Empty State --}}
    <template x-if="!conversationId && messages.length === 0">
        <div class="flex-1 flex flex-col justify-center -mt-6 md:-mt-16">
            <div class="w-full mx-auto px-4" style="max-width: 650px;">
            <div class="text-center mb-10">
                <div class="flex items-center justify-center gap-3">
                    <svg viewBox="0 0 100 100" class="w-8 h-8 md:w-9 md:h-9 text-[#D97757] fill-current shrink-0" xmlns="http://www.w3.org/2000/svg"><path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path></svg>
                    <h1 class="font-claude-response text-[#2D2825] dark:text-[#E8E8E6]" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif; font-size: clamp(1.5rem, 1.25rem + 1.5vw, 1.875rem); font-weight: 300; line-height: 1.2; letter-spacing: -0.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="userName ? 'Welcome back, ' + userName : 'Welcome back'"></h1>
                </div>
            </div>

            <div class="w-full">
                <form @submit.prevent="sendMessage()">
                    <div class="relative w-full mx-auto bg-white dark:bg-[#3A3A38] border border-claude-border-light dark:border-claude-border-dark rounded-2xl md:rounded-3xl shadow-sm flex flex-col focus-within:shadow-md focus-within:border-stone-300 dark:focus-within:border-stone-600 animate-smooth transition-all duration-200">
                        <div x-show="uploading" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <div class="w-16 h-16 rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-[#2C2C2A] flex items-center justify-center">
                                <img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-6 h-6 object-contain">
                            </div>
                            <div><p class="text-[14px] font-medium text-stone-700 dark:text-stone-300">Uploading...</p><p class="text-[12px] text-stone-400 dark:text-stone-550">Processing your files</p></div>
                        </div>
                        <div x-show="!uploading && attachments.length > 0" class="px-4 pt-4 pb-2 flex flex-wrap items-center gap-3">
                            <template x-for="(att, idx) in attachments" :key="idx">
                                <div class="relative group rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-[#2C2C2A] p-2 pr-8 flex items-center gap-2">
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
                            rows="2"
                            class="placeholder-fade w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[72px] max-h-48 overflow-y-auto"
                            x-init="(() => {
                                const phrases = ['How can I help you today?', 'Type / for skills', 'Rynude AI'];
                                let i = 0;
                                setInterval(() => {
                                    $el.style.setProperty('--ph-opacity', '0');
                                    setTimeout(() => {
                                        i = (i + 1) % phrases.length;
                                        $el.placeholder = phrases[i];
                                        $el.style.setProperty('--ph-opacity', '1');
                                    }, 950);
                                }, 4000);
                            })()"
                            placeholder="How can I help you today?"></textarea>
 
                        <div class="flex items-center justify-between w-full mt-4 pb-1">
                            <div x-data="{ openPlus: false }" class="relative">
                                <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 dark:bg-[#3A3A38] text-stone-800 dark:text-stone-200' : 'hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38]'">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>

                                <div x-show="openPlus" @click.away="openPlus = false" x-transition.opacity x-cloak class="absolute bottom-full left-0 mb-2 w-[240px] bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5">
                                    {{-- Add files --}}
                                    <button type="button" @click="openPlus=false; $refs.fileInput.click()" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add files or photos</span>
                                        </div>
                                        <span class="text-[12px] text-stone-400 font-medium">Ctrl+U</span>
                                    </button>
                                    {{-- Screenshot --}}
                                    <button type="button" @click="takeScreenshot(); openPlus=false" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-2.5 group">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Take a screenshot</span>
                                    </button>
                                    {{-- Add to project --}}
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'projects' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add to project</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                    {{-- Skills --}}
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'customize' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 13l2 2 4-4"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Skills</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    {{-- Add connector --}}
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'customize' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add connector</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    {{-- Add plugins --}}
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'customize' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-2.5 group">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 22 5-5"/><path d="M19 19a2 2 0 0 1-2 2H7.5A2.5 2.5 0 0 1 5 18.5V7a2 2 0 0 1 2-2h4.5a2.5 2.5 0 0 1 2.5 2.5V11l5-5Z"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Add plugins...</span>
                                    </button>

                                    <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                    {{-- Research --}}
                                    <button type="button" @click="researchMode = !researchMode; openPlus=false" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4" :class="researchMode ? 'text-[#D97757]' : 'text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/><path d="M11 8v6"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Research</span>
                                        </div>
                                        <svg x-show="researchMode" x-cloak class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                    {{-- Web search toggle --}}
                                    <button type="button" @click="webSearch = !webSearch; openPlus=false" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4" :class="webSearch ? 'text-[#D97757]' : 'text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Web search</span>
                                        </div>
                                        <svg x-show="webSearch" x-cloak class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 md:gap-1.5 text-stone-500">
                                <div x-data="{ open: false, ext: true, subOpen: false, closeTimer: null }" class="relative">
                                    <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-[#3A3A38] hover:bg-stone-200 dark:hover:bg-[#45423f] px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[120px] truncate" x-text="selectedModelName"></span>
                                        <span class="text-[13px] text-stone-500 hidden sm:inline" x-show="ext">Extended</span>
                                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute bottom-full right-0 mb-2 w-[250px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5">
                                        <template x-for="m in models" :key="m.code">
                                            <button @click="selectedModel=m.code; open=false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group" :class="!m.is_available?'opacity-50 cursor-not-allowed':''" :disabled="!m.is_available">
                                                <div><div class="text-[13px] text-stone-800 dark:text-stone-200" x-text="m.name"></div><div class="text-[12px] text-stone-400 dark:text-stone-550" x-text="m.description"></div></div>
                                                <svg x-show="selectedModel===m.code" class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </template>
                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>
                                        <div class="px-3 py-1.5 flex items-center justify-between cursor-pointer hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors" @click="ext=!ext">
                                            <div><div class="text-[13px] text-stone-800 dark:text-stone-200">Extended</div><p class="text-[11.5px] text-stone-500 mt-0.5">Always uses deep reasoning</p></div>
                                            <div class="relative inline-flex h-5 w-9 items-center rounded-full" :class="ext?'bg-[#D97757]':'bg-gray-200'"><span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow" :class="ext?'translate-x-4':'translate-x-[3px]'"></span></div>
                                        </div>
                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>
                                        
                                        <!-- More Models -->
                                        <div class="relative" @mouseenter="clearTimeout(closeTimer); subOpen = true" @mouseleave="closeTimer = setTimeout(() => { subOpen = false }, 250)">
                                            <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                                <span class="text-[13px] text-stone-800 dark:text-stone-200">More models</span>
                                                <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                            </button>
                                            
                                            <!-- Sub-menu -->
                                            <div x-show="subOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full mb-1 sm:mb-0 sm:bottom-[-8px] sm:top-auto mt-2 sm:mt-0 w-[200px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.12)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
                                                <template x-for="m in moreModels" :key="m.code">
                                                    <button @click="selectedModel=m.code; open=false; subOpen=false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group" :class="!m.is_available?'opacity-50 cursor-not-allowed':''" :disabled="!m.is_available">
                                                        <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="m.name"></span>
                                                        <svg x-show="selectedModel === m.code" class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="webSearchSupported">
                                    <button @click="webSearch=!webSearch" type="button" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center" :class="webSearch?'bg-[#D97757]/10 text-[#D97757]':'text-stone-500'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </button>
                                </template>
                                <div x-data="voiceInput" x-show="supported" class="relative group flex items-center justify-center">
                                    <button type="button" @click="toggle()" :class="listening?'bg-red-50 text-red-500':'text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 dark:hover:bg-[#3A3A38]'" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="listening?'animate-pulse':''"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                                    </button>
                                </div>
                                <button type="submit" :disabled="sending||!prompt.trim()" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center" :class="(sending||!prompt.trim())?'bg-stone-100 dark:bg-[#3A3A38] text-stone-400':'bg-[#D97757] hover:bg-[#c96646] text-white'">
                                    <svg x-show="!sending" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                    <img x-show="sending" src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-[18px] h-[18px] object-contain">
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
 
                <div class="flex items-center flex-wrap justify-center gap-2 mt-6">
                    <button @click="prompt='Write a '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3.5 py-1.5 bg-transparent border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-500 dark:text-stone-400 hover:bg-[#F3F2F1] dark:hover:bg-[#3A3A38] hover:text-stone-850 dark:hover:text-stone-200 hover:border-stone-400 dark:hover:border-stone-600 transition-colors duration-150">
                        <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Write
                    </button>
                    <button @click="prompt='Explain '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3.5 py-1.5 bg-transparent border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-500 dark:text-stone-400 hover:bg-[#F3F2F1] dark:hover:bg-[#3A3A38] hover:text-stone-850 dark:hover:text-stone-200 hover:border-stone-400 dark:hover:border-stone-600 transition-colors duration-150">
                        <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>Explain
                    </button>
                    <button @click="prompt='Write code to '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3.5 py-1.5 bg-transparent border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-500 dark:text-stone-400 hover:bg-[#F3F2F1] dark:hover:bg-[#3A3A38] hover:text-stone-850 dark:hover:text-stone-200 hover:border-stone-400 dark:hover:border-stone-600 transition-colors duration-150">
                        <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>Code
                    </button>
                    <button @click="prompt='Give me advice on '; $refs.chatInput.focus()" class="flex items-center gap-2 px-3.5 py-1.5 bg-transparent border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-500 dark:text-stone-400 hover:bg-[#F3F2F1] dark:hover:bg-[#3A3A38] hover:text-stone-850 dark:hover:text-stone-200 hover:border-stone-400 dark:hover:border-stone-600 transition-colors duration-150">
                        <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>Advice
                    </button>
                    <button @click="sendSurpriseMessage()" class="flex items-center gap-2 px-3.5 py-1.5 bg-transparent border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-500 dark:text-stone-400 hover:bg-[#F3F2F1] dark:hover:bg-[#3A3A38] hover:text-stone-850 dark:hover:text-stone-200 hover:border-stone-400 dark:hover:border-stone-600 transition-colors duration-150">
                        <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>Surprise me
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Active Chat --}}
    <template x-if="conversationId || messages.length > 0">
        <div class="flex flex-col flex-1 min-h-0 w-full relative overflow-hidden">
            {{-- Floating conversation-memory button (Claude-style, top-right) --}}
            <div class="absolute top-3 right-3 z-40">
                <button @click="openMemory()" type="button" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/80 dark:bg-[#3A3A38]/80 backdrop-blur-sm border border-claude-border-light dark:border-claude-border-dark rounded-full text-[12.5px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-[#3A3A38] shadow-sm transition-colors" title="Conversation memory">
                    <svg class="w-3.5 h-3.5 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a9 9 0 0 0-9 9c0 3.6 2.1 6.7 5.2 8.2.3 1.1 1.4 1.8 2.6 1.8h2.4c1.2 0 2.3-.7 2.6-1.8C18.9 17.7 21 14.6 21 11a9 9 0 0 0-9-9Z"/><path d="M9 21h6"/></svg>
                    <span class="hidden sm:inline">Memory</span>
                </button>
            </div>

            {{-- Chat Header with Title and Actions --}}
            <div x-show="conversationId" class="shrink-0 px-4 py-2.5">
                <div class="flex items-center gap-1.5">
                    <h2 class="text-[13px] font-medium text-stone-600 dark:text-stone-400 truncate">
                        <template x-if="chatProjectName">
                            <span class="text-stone-500 dark:text-stone-500" x-text="chatProjectName + ' / '"></span>
                        </template>
                        <span x-text="chatTitle || 'New Chat'"></span>
                    </h2>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="p-1.5 rounded-lg text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                        </button>

                        {{-- Dropdown menu --}}
                        <div x-show="open" @click.away="open = false" x-transition.opacity x-cloak class="absolute left-0 mt-1 w-[200px] bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.12)] z-50 py-1.5 overflow-hidden">
                            {{-- Star / Unstar --}}
                            <button type="button" @click="open = false; toggleStar()" class="w-full text-left px-3 py-2 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-2.5 group">
                                <svg class="w-4 h-4" :class="isStarred ? 'text-[#D97757] fill-current' : 'text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="isStarred ? 'Unstar' : 'Star'"></span>
                            </button>

                            {{-- Rename --}}
                            <button type="button" @click="open = false; openRenameModal()" class="w-full text-left px-3 py-2 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-2.5 group">
                                <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                <span class="text-[13px] text-stone-800 dark:text-stone-200">Rename</span>
                                <span class="ml-auto text-[11px] text-stone-400">R</span>
                            </button>

                            {{-- Add to project --}}
                            <button type="button" @click="open = false; openAddProjectModal()" class="w-full text-left px-3 py-2 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-2.5 group">
                                <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                                <span class="text-[13px] text-stone-800 dark:text-stone-200">Add to project</span>
                                <svg class="w-3.5 h-3.5 ml-auto text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>

                            <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-2 my-1.5"></div>

                            {{-- Delete --}}
                            <button type="button" @click="open = false; openDeleteModal()" class="w-full text-left px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center gap-2.5 group">
                                <svg class="w-4 h-4 text-stone-500 group-hover:text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                <span class="text-[13px] text-stone-800 dark:text-stone-200 group-hover:text-red-500">Delete</span>
                                <span class="ml-auto text-[11px] text-stone-400 group-hover:text-red-400">D</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto custom-scrollbar" x-ref="messagesContainer" id="chat-scroll-container">
                <div class="mx-auto w-full py-4 md:py-6 px-3 md:px-4" style="max-width: 880px;">
                    <div class="space-y-1">
                        <template x-for="(msg, idx) in messages" :key="'msg-' + idx">
                            <div class="w-full mx-auto flex flex-col group/msg">
                                {{-- User Message --}}
                                <template x-if="msg.role === 'user'">
                                    <div class="flex justify-end w-full">
                                        <div class="flex flex-col items-end gap-2 max-w-[85%] md:max-w-[75%]">
                                            <template x-if="msg.attachments && msg.attachments.length">
                                                <div class="flex flex-wrap gap-2 justify-end w-full">
                                                    <template x-for="(att, ai) in msg.attachments" :key="ai">
                                                        <div class="shrink-0">
                                                            <template x-if="att.url && (att.file_type || '').indexOf('image/') === 0">
                                                                <img :src="att.url" :alt="att.file_name || att.name" class="border border-[#E5E5E5] dark:border-stone-700 rounded-2xl shadow-sm" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                                            </template>
                                                            <template x-if="!(att.url && (att.file_type || '').indexOf('image/') === 0)">
                                                                <div class="relative bg-white dark:bg-[#3A3A38] border border-[#E5E5E5] dark:border-stone-700 rounded-2xl overflow-hidden shadow-sm flex items-center gap-2 p-2.5" style="max-width: 200px;">
                                                                    <svg class="w-4 h-4 text-stone-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                                    <span class="text-xs text-stone-700 dark:text-stone-300 truncate" x-text="att.file_name || att.name"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="msg.content && editingIdx !== idx">
                                                <div class="bg-stone-100 dark:bg-[#3A3A38] border border-transparent text-stone-900 dark:text-stone-200 px-4 py-2.5 rounded-xl text-[15px] leading-relaxed break-words whitespace-pre-wrap w-full" x-text="msg.content"></div>
                                             </template>
                                             <template x-if="editingIdx === idx">
                                                 <div class="bg-stone-100 dark:bg-[#3A3A38] border border-transparent rounded-xl px-4 py-2.5 w-full">
                                                    <textarea x-model="editDraft" rows="3" class="w-full text-[15px] leading-relaxed text-stone-900 dark:text-stone-200" style="background: transparent; outline: none; border: none; resize: vertical; min-height: 60px;" @keydown.escape="editingIdx = null"></textarea>
                                                    <div class="flex items-center justify-end gap-2 mt-2">
                                                        <button @click="editingIdx = null" class="px-3 py-1.5 text-[13px] rounded-lg text-stone-500 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-700 transition-colors">Batal</button>
                                                        <button @click="submitEdit(idx)" class="px-3 py-1.5 text-[13px] font-medium rounded-lg text-white bg-[#D97757] hover:bg-[#c96646] transition-colors">Simpan</button>
                                                    </div>
                                                </div>
                                            </template>
                                            <div class="flex items-center gap-1 opacity-0 group-hover/msg:opacity-100 transition-opacity duration-150">
                                                <button @click="navigator.clipboard.writeText(msg.content)" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" title="Copy">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                </button>
                                                <button @click="editMessage(idx)" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors" title="Edit">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                                </button>
                                                <template x-if="msg.sibling_count > 1">
                                                    <div class="flex items-center text-[11px] text-stone-400 select-none">
                                                        <button @click="switchBranch(idx, -1)" class="p-1.5 rounded-lg hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" :style="msg.sibling_index === 0 ? 'opacity: 0.3; pointer-events: none;' : ''" title="Previous version">
                                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                                                        </button>
                                                        <span x-text="(msg.sibling_index + 1) + '/' + msg.sibling_count"></span>
                                                        <button @click="switchBranch(idx, 1)" class="p-1.5 rounded-lg hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" :style="msg.sibling_index >= msg.sibling_count - 1 ? 'opacity: 0.3; pointer-events: none;' : ''" title="Next version">
                                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Assistant Message --}}
                                <template x-if="msg.role !== 'user'">
                                    <div class="flex justify-start w-full gap-3 md:gap-4">
                                        <div class="flex-shrink-0 mt-1">
                                            <svg class="w-6 h-6 md:w-7 md:h-7 text-[#D97757]" viewBox="0 0 100 100" fill="currentColor"><path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <template x-if="msg.thinking">
                                                <div x-data="{open:false}" class="mb-2 not-prose">
                                                    <button type="button" @click="open=!open" class="flex items-center gap-2 text-[13px] font-medium text-stone-500 dark:text-stone-400">
                                                        <span>Proses berpikir</span>
                                                        <svg class="w-3.5 h-3.5" :style="open ? 'transform: rotate(180deg)' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                    </button>
                                                    <div x-show="open" x-cloak class="mt-2 text-[13px] text-stone-500 dark:text-stone-400 max-h-64 overflow-y-auto custom-scrollbar prose prose-stone dark:prose-invert max-w-none prose-p:my-1 prose-li:my-0 prose-table:my-2" style="font-style: italic; border-left: 2px solid rgba(168,162,158,0.35); padding-left: 12px; font-size: 13px;" x-html="renderContent(msg.thinking)"></div>
                                                </div>
                                            </template>
                                            <div class="text-[#0B0B0B] dark:text-stone-200 text-[16px] leading-[1.6] prose prose-stone dark:prose-invert max-w-none w-full font-claude-response prose-p:mt-0 prose-p:mb-3 [&_li>p]:my-0 [&_ul]:mt-0 [&_ol]:mt-0 [&_ul]:mb-3 [&_ol]:mb-3 prose-headings:font-sans prose-headings:font-semibold prose-headings:text-[#0B0B0B] dark:prose-headings:text-stone-200 prose-headings:mt-6 prose-headings:mb-3 prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-ul:list-disc prose-ol:list-decimal prose-li:my-0 prose-li:pl-2 prose-ul:pl-5 prose-ol:pl-5 prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-pre:p-4 prose-pre:my-4 prose-pre:overflow-x-auto prose-code:px-1.5 prose-code:py-0.5 prose-code:bg-stone-100 dark:prose-code:bg-[#3A3A38] prose-code:text-[#0B0B0B] dark:prose-code:text-stone-200 prose-code:rounded-md prose-code:font-mono prose-code:text-[14px] prose-code:font-medium prose-code:before:content-none prose-code:after:content-none prose-a:text-[#D97757] hover:prose-a:text-[#c96646] prose-a:no-underline hover:prose-a:underline prose-strong:font-semibold prose-strong:text-stone-800 dark:prose-strong:text-stone-400 prose-blockquote:border-l-4 prose-blockquote:border-stone-300 dark:prose-blockquote:border-stone-700 prose-blockquote:pl-4 prose-blockquote:italic prose-blockquote:text-stone-600 dark:prose-blockquote:text-stone-400 prose-table:w-full prose-table:border-collapse prose-table:my-4 prose-th:border prose-th:border-stone-300 dark:prose-th:border-stone-700 prose-th:px-4 prose-th:py-2 prose-th:bg-stone-100 dark:prose-th:bg-[#3A3A38] prose-th:font-semibold prose-td:border prose-td:border-stone-300 dark:prose-td:border-stone-700 prose-td:px-4 prose-td:py-2" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;" x-html="renderContent(msg.content)"></div>
                                            <template x-for="(art, artIdx) in ((msg.artifacts && msg.artifacts.length) ? msg.artifacts : (msg.artifact ? [msg.artifact] : []))" :key="'art-' + idx + '-' + artIdx">
                                                <div @click="openArtifact(art.id)" class="artifact-card mt-3 w-full flex items-center gap-4 border border-claude-border-light dark:border-claude-border-dark rounded-2xl p-3 bg-claude-bg-light dark:bg-claude-bg-dark shadow-sm cursor-pointer not-prose">
                                                    {{-- Tilted paper sheet, straightens on hover (see .artifact-paper) --}}
                                                    <div class="relative w-12 h-12 rounded-lg bg-[#F3F2F1] dark:bg-stone-700 overflow-hidden shrink-0">
                                                        <div class="artifact-paper absolute bg-white dark:bg-stone-200 border border-stone-200 dark:border-stone-400 shadow-sm flex items-center justify-center" style="width: 30px; height: 38px; left: 9px; top: 10px; border-radius: 3px;">
                                                            <svg class="w-3.5 h-3.5 text-stone-400 dark:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-[14px] font-medium text-stone-800 dark:text-stone-200 truncate" x-text="art.title"></div>
                                                        <div class="text-[13px] text-stone-400" x-text="art.type === 'code' ? 'Code' : 'Document'"></div>
                                                    </div>
                                                    <button type="button" @click.stop="window.open('/api/artifacts/' + art.id + '/download/' + (art.type === 'code' ? 'file' : 'docx'), '_blank')" class="shrink-0 px-4 py-1.5 border border-claude-border-light dark:border-claude-border-dark rounded-lg text-[13px] font-medium text-stone-700 dark:text-stone-300 bg-white dark:bg-[#3A3A38] hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors shadow-sm">Download</button>
                                                </div>
                                            </template>
                                            {{-- Web sources cited by this reply --}}
                                            <template x-if="msg.citations && msg.citations.length">
                                                <div class="flex flex-wrap gap-1.5 mt-2 not-prose">
                                                    <template x-for="c in msg.citations" :key="'cit-' + c.n">
                                                        <a :href="c.url" target="_blank" rel="noopener" :title="c.title" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-claude-bg-light dark:bg-claude-bg-dark text-[11px] text-stone-600 dark:text-stone-300 hover:bg-white dark:hover:bg-[#3A3A38] transition-colors" style="max-width: 220px;">
                                                            <span class="text-stone-400 shrink-0" x-text="'[' + c.n + ']'"></span>
                                                            <span class="truncate" x-text="(function(){ try { return new URL(c.url).hostname.replace('www.', ''); } catch(e) { return c.title; } })()"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                            <div class="flex items-center gap-1 mt-2 opacity-0 group-hover/msg:opacity-100 transition-opacity duration-150 not-prose">
                                                <button @click="navigator.clipboard.writeText(msg.content)" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" title="Copy">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                </button>
                                                <button @click="rateMessage(idx, 'up')" class="p-1.5 rounded-lg transition-colors hover:bg-stone-100 dark:hover:bg-stone-800" :class="msg.rating==='up'?'text-green-600':'text-stone-400 hover:text-stone-700 dark:hover:text-stone-200'" title="Good response">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14 9.5V5.25"/></svg>
                                                </button>
                                                <button @click="rateMessage(idx, 'down')" class="p-1.5 rounded-lg transition-colors hover:bg-stone-100 dark:hover:bg-stone-800" :class="msg.rating==='down'?'text-red-500':'text-stone-400 hover:text-stone-700 dark:hover:text-stone-200'" title="Bad response">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398C20.613 14.547 19.833 15 19 15h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 00.303-.54m.023-8.25H16.48a4.5 4.5 0 01-1.423-.23l-3.114-1.04a4.5 4.5 0 00-1.423-.23H6.504c-.618 0-1.217.247-1.605.729A11.95 11.95 0 002.25 12c0 .434.023.863.068 1.285C2.427 14.306 3.346 15 4.372 15h3.126c.618 0 .991.724.725 1.282A7.471 7.471 0 007.5 19.5a2.25 2.25 0 002.25 2.25.75.75 0 00.75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 002.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384"/></svg>
                                                </button>
                                                <div x-data="{ openRegen: false }" class="relative">
                                                    <button @click="openRegen = !openRegen" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" title="Regenerate">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
                                                    </button>
                                                    <div x-show="openRegen" @click.away="openRegen = false" x-transition.opacity x-cloak class="absolute left-0 mb-2 w-[240px] bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5" style="bottom: 100%; max-height: 280px; overflow-y: auto;">
                                                        <button type="button" @click="openRegen = false; regenerate(idx, null)" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors">
                                                            <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Coba lagi</span>
                                                        </button>
                                                        <div class="px-3 py-1 text-[11px] text-stone-400">Coba dengan model lain</div>
                                                        <template x-for="m in models.concat(moreModels)" :key="'regen-' + m.code">
                                                            <button type="button" @click="openRegen = false; regenerate(idx, m.code)" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors">
                                                                <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="m.name"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                                <template x-if="msg.sibling_count > 1">
                                                    <div class="flex items-center text-[11px] text-stone-400">
                                                        <button @click="switchBranch(idx, -1)" class="p-1.5 rounded-lg hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" :style="msg.sibling_index === 0 ? 'opacity: 0.3; pointer-events: none;' : ''" title="Previous version">
                                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                                                        </button>
                                                        <span x-text="(msg.sibling_index + 1) + '/' + msg.sibling_count"></span>
                                                        <button @click="switchBranch(idx, 1)" class="p-1.5 rounded-lg hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors" :style="msg.sibling_index >= msg.sibling_count - 1 ? 'opacity: 0.3; pointer-events: none;' : ''" title="Next version">
                                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                                <span x-show="msg.model" x-cloak class="text-[11px] text-stone-400 px-1.5" x-text="msg.model"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Streaming indicator --}}
                        <div x-show="streaming" x-cloak class="flex justify-start w-full gap-3 md:gap-4 mt-1">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-6 h-6 md:w-7 md:h-7 text-[#D97757] fill-current" :class="finalizing ? '' : 'animate-spin'" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4-4.7.6-2-2 .2-3 1-1 8-5.5Z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div x-show="!streamContent && !thinkingContent" class="mb-1">
                                    <div class="text-[13px] text-[#D97757] font-medium">Rynude is thinking…</div>
                                    <div x-show="waitStatus" class="text-[13px] text-stone-500 dark:text-stone-400 whitespace-pre-wrap" style="font-style: italic;" x-text="waitStatus"></div>
                                </div>
                                {{-- Live thinking / reasoning panel --}}
                                <template x-if="thinkingContent">
                                    <div class="mb-2 not-prose">
                                        <button type="button" @click="thinkingOpen=!thinkingOpen" class="text-left flex items-center gap-2 text-[13px] font-medium text-stone-500 dark:text-stone-400">
                                            <svg class="w-3.5 h-3.5 animate-pulse text-[#D97757]" x-show="!streamContent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.2 2.2M16.9 16.9l2.2 2.2M4.9 19.1l2.2-2.2M16.9 7.1l2.2-2.2"/></svg>
                                            <span x-text="streamContent ? 'Proses berpikir' : 'Sedang berpikir…'"></span>
                                            <svg class="w-3.5 h-3.5" :style="thinkingOpen ? 'transform: rotate(180deg)' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div x-show="thinkingOpen" x-ref="thinkingBox" class="mt-2 text-[13px] text-stone-500 dark:text-stone-400 max-h-64 overflow-y-auto custom-scrollbar prose prose-stone dark:prose-invert max-w-none prose-p:my-1 prose-li:my-0 prose-table:my-2" style="font-style: italic; border-left: 2px solid rgba(168,162,158,0.35); padding-left: 12px; font-size: 13px;" x-html="renderContent(thinkingContent)"></div>
                                    </div>
                                </template>
                                {{-- Class list must stay identical to the final assistant message div so the layout doesn't shift when the stream finalizes --}}
                                <div x-html="parseStreamContent(streamContent)" class="text-[#0B0B0B] dark:text-stone-200 text-[16px] leading-[1.6] prose prose-stone dark:prose-invert max-w-none w-full font-claude-response prose-p:mt-0 prose-p:mb-3 [&_li>p]:my-0 [&_ul]:mt-0 [&_ol]:mt-0 [&_ul]:mb-3 [&_ol]:mb-3 prose-headings:font-sans prose-headings:font-semibold prose-headings:text-[#0B0B0B] dark:prose-headings:text-stone-200 prose-headings:mt-6 prose-headings:mb-3 prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-ul:list-disc prose-ol:list-decimal prose-li:my-0 prose-li:pl-2 prose-ul:pl-5 prose-ol:pl-5 prose-pre:bg-[#1E1E1E] prose-pre:text-stone-200 prose-pre:rounded-xl prose-pre:shadow-sm prose-pre:border prose-pre:border-stone-700/50 prose-pre:p-4 prose-pre:my-4 prose-pre:overflow-x-auto prose-code:px-1.5 prose-code:py-0.5 prose-code:bg-stone-100 dark:prose-code:bg-[#3A3A38] prose-code:text-[#0B0B0B] dark:prose-code:text-stone-200 prose-code:rounded-md prose-code:font-mono prose-code:text-[14px] prose-code:font-medium prose-code:before:content-none prose-code:after:content-none prose-a:text-[#D97757] hover:prose-a:text-[#c96646] prose-a:no-underline hover:prose-a:underline prose-strong:font-semibold prose-strong:text-stone-800 dark:prose-strong:text-stone-400 prose-blockquote:border-l-4 prose-blockquote:border-stone-300 dark:prose-blockquote:border-stone-700 prose-blockquote:pl-4 prose-blockquote:italic prose-blockquote:text-stone-600 dark:prose-blockquote:text-stone-400 prose-table:w-full prose-table:border-collapse prose-table:my-4 prose-th:border prose-th:border-stone-300 dark:prose-th:border-stone-700 prose-th:px-4 prose-th:py-2 prose-th:bg-stone-100 dark:prose-th:bg-[#3A3A38] prose-th:font-semibold prose-td:border prose-td:border-stone-300 dark:prose-td:border-stone-700 prose-td:px-4 prose-td:py-2" style="font-family: 'Anthropic Serif', 'Lora', Georgia, serif;"></div>
                            </div>
                        </div>

                        {{-- Stop button --}}
                        <div x-show="streaming && !finalizing" x-cloak class="mt-4 flex justify-center w-full">
                            <button @click="stopGeneration()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors shadow-sm">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"/></svg>
                                Stop generating
                            </button>
                        </div>

                        {{-- Continue button: the reply hit the model's output limit --}}
                        <div x-show="canContinue && !streaming" x-cloak class="mt-4 flex justify-center w-full">
                            <button @click="continueGeneration()" class="flex items-center gap-2 px-3 py-1.5 bg-claude-bg-light dark:bg-claude-bg-dark border border-claude-border-light dark:border-claude-border-dark rounded-full text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors shadow-sm">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                Jawaban terpotong — Lanjutkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Jump to bottom (appears when the user scrolled up) --}}
            <button x-show="userScrolledUp" x-cloak @click="scrollToBottom()" class="absolute z-40 p-2 rounded-full bg-white dark:bg-[#3A3A38] border border-claude-border-light dark:border-claude-border-dark shadow-md text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 transition-colors" style="bottom: 140px; left: 50%; transform: translateX(-50%);" title="Ke pesan terbaru">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
            </button>

            <div class="shrink-0 h-fit bg-transparent">
                <form @submit.prevent="sendMessage()" class="w-full mx-auto pb-2 md:pb-3 px-3 md:px-4 pt-2 md:pt-3" style="max-width: 800px;">
                    <div class="relative bg-white dark:bg-[#3A3A38] border border-claude-border-light dark:border-claude-border-dark rounded-2xl shadow-sm flex flex-col focus-within:shadow-lg focus-within:border-stone-300 dark:focus-within:border-stone-600 transition-all duration-200">
                        <div x-show="uploading" class="px-4 pt-4 pb-2 flex items-center gap-3">
                            <img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-5 h-5 object-contain">
                            <span class="text-[13px] text-stone-500">Uploading...</span>
                        </div>
                        <div x-show="!uploading && attachments.length > 0" class="px-4 pt-4 pb-2 flex flex-wrap items-center gap-2">
                            <template x-for="(att, idx) in attachments" :key="idx">
                                <div class="relative group rounded-xl border border-claude-border-light dark:border-claude-border-dark bg-stone-50 dark:bg-[#2C2C2A] p-2 pr-8 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-stone-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span class="text-[13px] text-stone-700 dark:text-stone-300 truncate max-w-[120px]" x-text="att.name"></span>
                                    <button type="button" @click="removeAttachment(idx)" class="absolute top-1.5 right-1.5 bg-black/40 hover:bg-black/60 rounded-full p-1 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <textarea x-model="prompt" @input="autoResize($event)" @keydown.enter="if(!$event.shiftKey){$event.preventDefault();sendMessage()}" rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-3 pb-1 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] max-h-48 overflow-y-auto"
                            style="min-height: 30px;"
                            placeholder="Message Rynude..."></textarea>
                        <div class="flex items-center justify-between w-full mt-1 pb-1 px-1">
                            <button type="button" @click="$refs.fileInput2.click()" class="p-2 text-stone-500 hover:text-stone-800 rounded-xl hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                            <input type="file" x-ref="fileInput2" class="hidden" multiple @change="handleFileUpload($event)">
                            <div class="flex items-center gap-1 md:gap-1.5 text-stone-500">
                                {{-- Response style (per-conversation, like Claude's Styles) --}}
                                <div x-data="{ openStyle: false }" class="relative hidden md:block">
                                    <button @click="openStyle = !openStyle" type="button" title="Response style" class="flex items-center gap-1 cursor-pointer focus:outline-none hover:bg-stone-100 dark:hover:bg-[#3A3A38] px-2 py-1.5 rounded-lg transition-colors" :class="chatStyle !== 'normal' ? 'text-[#D97757]' : ''">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                                        <span class="text-[13px]" x-text="chatStyle === 'normal' ? 'Style' : chatStyle.charAt(0).toUpperCase() + chatStyle.slice(1)"></span>
                                    </button>
                                    <div x-show="openStyle" @click.away="openStyle = false" x-cloak class="absolute bottom-full right-0 mb-2 w-[180px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.12)] z-50 py-1.5">
                                        <template x-for="s in ['normal','concise','explanatory','formal']" :key="'style-' + s">
                                            <button @click="chatStyle = s; openStyle = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between">
                                                <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="s.charAt(0).toUpperCase() + s.slice(1)"></span>
                                                <svg x-show="chatStyle === s" class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                {{-- Model Selector --}}
                                <div x-data="{ open: false, subOpen: false, closeTimer: null }" class="relative">
                                    <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-[#3A3A38] hover:bg-stone-200 dark:hover:bg-[#45423f] px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[100px] truncate" x-text="selectedModelName"></span>
                                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false; subOpen = false" x-cloak class="absolute bottom-full right-0 mb-2 w-[240px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.12)] z-50 py-1.5">
                                        <template x-for="m in models" :key="m.code">
                                            <button @click="selectedModel=m.code; open=false" type="button"
                                                class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group"
                                                :class="!m.is_available?'opacity-50 cursor-not-allowed':''"
                                                :disabled="!m.is_available">
                                                <div>
                                                    <div class="text-[13px] text-stone-800 dark:text-stone-200" x-text="m.name"></div>
                                                    <div class="text-[12px] text-stone-400 dark:text-stone-500" x-text="m.description"></div>
                                                </div>
                                                <svg x-show="selectedModel===m.code" class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </template>
                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>
                                        
                                        <!-- More Models -->
                                        <div class="relative" @mouseenter="clearTimeout(closeTimer); subOpen = true" @mouseleave="closeTimer = setTimeout(() => { subOpen = false }, 250)">
                                            <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                                <span class="text-[13px] text-stone-800 dark:text-stone-200">More models</span>
                                                <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                            </button>
                                            
                                            <!-- Sub-menu -->
                                            <div x-show="subOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full mb-1 sm:mb-0 sm:bottom-[-8px] sm:top-auto mt-2 sm:mt-0 w-[200px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.12)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
                                                <template x-for="m in moreModels" :key="m.code">
                                                    <button @click="selectedModel=m.code; open=false; subOpen=false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group" :class="!m.is_available?'opacity-50 cursor-not-allowed':''" :disabled="!m.is_available">
                                                        <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="m.name"></span>
                                                        <svg x-show="selectedModel === m.code" class="w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Web Search --}}
                                <template x-if="webSearchSupported">
                                    <button @click="webSearch=!webSearch" type="button" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center" :class="webSearch?'bg-[#D97757]/10 text-[#D97757]':'text-stone-400 hover:text-stone-600'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </button>
                                </template>
                                {{-- Extended thinking --}}
                                <button @click="thinkingMode=!thinkingMode" type="button" title="Extended thinking" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center" :class="thinkingMode?'bg-[#D97757]/10 text-[#D97757]':'text-stone-400 hover:text-stone-600'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 1 7 7c0 2.5-1.3 4.6-3.2 5.9-.5.4-.8.9-.8 1.5V17h-6v-.6c0-.6-.3-1.1-.8-1.5A7 7 0 0 1 5 9a7 7 0 0 1 7-7z"/></svg>
                                </button>
                                {{-- Send --}}
                                <button type="submit" :disabled="sending||!prompt.trim()" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center" :class="(sending||!prompt.trim())?'bg-stone-100 dark:bg-[#3A3A38] text-stone-400':'bg-[#D97757] text-white hover:bg-[#c96646]'">
                                    <svg x-show="!sending" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                    <img x-show="sending" src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-[18px] h-[18px] object-contain">
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-[11px] text-stone-400 dark:text-stone-500 mt-1.5">Rynude all model is AI and can make mistakes. Please double-check responses.</p>
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
        selectedModel: localStorage.getItem('rynude_selected_model') || 'claude-haiku-4-5',
        models: JSON.parse(localStorage.getItem('rynude_models_cache') || '[]'),
        moreModels: JSON.parse(localStorage.getItem('rynude_more_models_cache') || '[]'),
        sending: false,
        streaming: false,
        streamContent: '',
        thinkingContent: '',
        thinkingOpen: true,
        contentQueue: '',
        thinkQueue: '',
        streamEnded: false,
        finalizing: false,
        pumpTimer: null,
        lastThinking: '',
        waitStatus: '',
        waitTimer: null,
        loading: false,
        isDropping: false,
        attachments: [],
        uploading: false,
        webSearch: false,
        researchMode: false,
        webSearchSupported: true,
        userName: '',
        selectedProject: null,
        editingIdx: null,
        editDraft: '',
        doneMeta: null,
        pendingArtifacts: [],
        lastUsedModel: '',
        canContinue: false,
        resumeAttempts: 0,
        // Thinking defaults ON: every model shows a reasoning pass (native
        // reasoning models use their own; the rest get prompted sim-thinking).
        // Persisted so turning it off sticks.
        thinkingMode: localStorage.getItem('rynude_thinking_mode') !== '0',
        chatStyle: 'normal',
        pendingCitations: null,
        userScrolledUp: false,
        quoteSel: '',
        quoteX: 0,
        quoteY: 0,
        // Chat header states
        chatTitle: '',
        chatProject: null,
        chatProjectName: '',
        isStarred: false,
        showChatActions: false,
        showRenameModal: false,
        showDeleteModal: false,
        showAddProjectModal: false,
        renameDraft: '',
        availableProjects: [],
        selectedProjectForAdd: null,

        get selectedModelName() {
            var all = this.models.concat(this.moreModels);
            var m = all.find(function(m) { return m.code === this.selectedModel; }.bind(this));
            return m ? m.name : 'Select Model';
        },

        get greeting() {
            var hour = new Date().getHours();
            var greet = 'Good evening';
            if (hour >= 5 && hour < 12) {
                greet = 'Good morning';
            } else if (hour >= 12 && hour < 17) {
                greet = 'Good afternoon';
            }
            var name = this.userName ? this.userName.trim().split(' ')[0] : '';
            return name ? (greet + ', ' + name) : greet;
        },

        init: function() {
            var self = this;
            this.userName = document.querySelector('meta[name=user-name]')?.content || '';

            // Persist model selection to localStorage
            this.$watch('selectedModel', function(value) {
                if (value) {
                    localStorage.setItem('rynude_selected_model', value);
                }
            });
            this.$watch('thinkingMode', function(value) {
                localStorage.setItem('rynude_thinking_mode', value ? '1' : '0');
            });

            this.loadModels();

            // Load conversation from URL on initial page load
            var urlParams = new URLSearchParams(window.location.search);
            var convId = urlParams.get('conversation');
            if (convId) {
                self.loadConversation(parseInt(convId));

                // A generation was streaming when this tab was refreshed —
                // the server kept it running, so reattach and replay it.
                var activeStream = null;
                try { activeStream = sessionStorage.getItem('rynude_active_stream'); } catch(e) {}
                if (activeStream && activeStream === convId) {
                    self.conversationId = parseInt(convId);
                    self.sending = true;
                    self.beginStreamingState(self.selectedModel);
                    self.resumeStream();
                }
            }

            window.addEventListener('selectConversation', function(e) {
                if (e.detail && e.detail.conversationId) self.loadConversation(e.detail.conversationId);
            });
            window.addEventListener('newChat', function() {
                self.conversationId = null;
                self.messages = [];
                self.prompt = '';
                self.attachments = [];
                self.streamContent = '';
                self.streaming = false;
                self.sending = false;
            });
            window.addEventListener('startProjectChat', function(e) {
                if (e.detail) {
                    self.conversationId = null;
                    self.messages = [];
                    if (e.detail.projectId) self.selectedProject = e.detail.projectId;
                    if (e.detail.initialModel) self.selectedModel = e.detail.initialModel;
                    if (e.detail.initialPrompt) {
                        self.prompt = e.detail.initialPrompt;
                        self.sendMessage();
                    }
                }
            });
            window.addEventListener('openChat', function(e) {
                if (e.detail && e.detail.chatId) self.loadConversation(e.detail.chatId);
            });

            // Smart auto-scroll: once the user scrolls up to read, streaming
            // stops yanking the view down until they return to the bottom.
            // Delegated (capture phase) because the scroll container lives
            // inside an x-if template — it doesn't exist yet at init() time
            // and is re-created whenever the layout switches, so a direct
            // addEventListener on the element never sticks.
            document.addEventListener('scroll', function(e) {
                var t = e.target;
                if (t && t.id === 'chat-scroll-container') {
                    self.userScrolledUp = (t.scrollHeight - t.scrollTop - t.clientHeight) > 80;
                }
            }, true);

            // Quote-reply: selecting text inside the message list offers an
            // "ask about this" button near the selection.
            document.addEventListener('mouseup', function() {
                setTimeout(function() {
                    var sel = window.getSelection();
                    var text = sel ? sel.toString().trim() : '';
                    var container = document.getElementById('chat-scroll-container');
                    if (text && text.length > 2 && container && sel.rangeCount && container.contains(sel.anchorNode)) {
                        var rect = sel.getRangeAt(0).getBoundingClientRect();
                        self.quoteSel = text;
                        self.quoteX = rect.left + rect.width / 2;
                        self.quoteY = rect.top;
                    } else {
                        self.quoteSel = '';
                    }
                }, 10);
            });
        },

        scrollToBottom: function() {
            var c = document.getElementById('chat-scroll-container');
            if (c) c.scrollTop = c.scrollHeight;
            this.userScrolledUp = false;
        },

        applyQuote: function() {
            if (!this.quoteSel) return;
            var q = this.quoteSel.split('\n').map(function(l) { return '> ' + l; }).join('\n');
            this.prompt = q + '\n\n' + (this.prompt || '');
            this.quoteSel = '';
            try { window.getSelection().removeAllRanges(); } catch(e) {}
            var ta = document.querySelector('textarea[x-model="prompt"]');
            if (ta) ta.focus();
        },

        loadModels: function() {
            var self = this;
            // Restore from cache immediately (already done in state init),
            // then fetch fresh data in background without blocking render.
            fetch('/api/settings', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.models) {
                        self.models = resp.models;
                        localStorage.setItem('rynude_models_cache', JSON.stringify(resp.models));
                    }
                    if (resp.more_models) {
                        self.moreModels = resp.more_models;
                        localStorage.setItem('rynude_more_models_cache', JSON.stringify(resp.more_models));
                    }
                })
                .catch(function() { /* silent fail, cached data still shown */ });
        },

        loadConversation: function(id, silent) {
            if (!silent) {
                this.loading = true;
            }
            var self = this;
            fetch('/api/chats/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    // A late silent reload must never clobber a stream the user
                    // has started in the meantime.
                    if (silent && (self.streaming || self.sending)) {
                        return;
                    }
                    if (resp.data) {
                        self.conversationId = resp.data.id;
                        var msgs = resp.data.messages || [];
                        // Re-attach the just-streamed thinking text to the saved
                        // assistant message (client-side only, until page reload)
                        if (self.lastThinking) {
                            for (var i = msgs.length - 1; i >= 0; i--) {
                                if (msgs[i].role !== 'user') {
                                    msgs[i].thinking = self.lastThinking;
                                    break;
                                }
                            }
                            self.lastThinking = '';
                        }
                        self.messages = msgs;
                        self.memoryDraft = resp.data.memory || '';
                        self.chatStyle = resp.data.style || 'normal';

                        // Populate chat header data
                        self.chatTitle = resp.data.title || 'New Chat';
                        self.isStarred = resp.data.is_starred || false;
                        self.chatProject = resp.data.project_id || null;
                        self.chatProjectName = resp.data.project_name || '';

                        // Don't clear live-stream state while a resume/send is
                        // in flight (e.g. the initial page load racing with a
                        // reconnect to a stream that survived the refresh).
                        if (!self.sending) {
                            self.streamContent = '';
                            self.thinkingContent = '';
                            self.streaming = false;
                            self.finalizing = false;
                            self.canContinue = false;
                        }
                    }
                    if (!silent) {
                        self.loading = false;
                        // Scroll to bottom after render — only when the user
                        // opened the chat. A silent refresh after a finished
                        // stream must not yank the view while they're reading.
                        setTimeout(function() {
                            var container = document.querySelector('[x-ref="messagesContainer"]');
                            if (container) container.scrollTop = container.scrollHeight;
                        }, 50);
                    }
                })
                .catch(function(){
                    if (!silent) {
                        self.loading = false;
                    }
                    // finishStream() already materialized the streamed reply as
                    // a local message, so a failed reload loses nothing. Just
                    // make sure not to disturb a stream that's now in flight.
                    if (self.sending || self.streaming) {
                        return;
                    }
                    self.streamContent = '';
                    self.thinkingContent = '';
                    self.streaming = false;
                    self.finalizing = false;
                });
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

            var headers = {
                'Accept': 'text/event-stream'
            };
            var body;

            if (this.attachments.length === 0) {
                headers['Content-Type'] = 'application/json';
                var payload = {
                    prompt: this.prompt.trim(),
                    model: this.selectedModel,
                    web_search: this.webSearch ? 1 : 0,
                    research_mode: this.researchMode ? 1 : 0,
                    thinking: this.thinkingMode ? 1 : 0,
                    style: this.chatStyle
                };
                if (this.conversationId) payload.conversation_id = this.conversationId;
                if (this.selectedProject) payload.project_id = this.selectedProject;
                body = JSON.stringify(payload);
            } else {
                var fd = new FormData();
                fd.append('prompt', this.prompt.trim());
                fd.append('model', this.selectedModel);
                fd.append('web_search', this.webSearch ? '1' : '0');
                fd.append('research_mode', this.researchMode ? '1' : '0');
                fd.append('thinking', this.thinkingMode ? '1' : '0');
                fd.append('style', this.chatStyle);
                if (this.conversationId) fd.append('conversation_id', this.conversationId);
                if (this.selectedProject) fd.append('project_id', this.selectedProject);
                for (var i = 0; i < this.attachments.length; i++) {
                    fd.append('attachments[]', this.attachments[i].file);
                }
                body = fd;
            }

            self.messages.push({
                role: 'user',
                content: self.prompt,
                attachments: self.attachments.map(a => ({
                    file_name: a.name,
                    file_type: (a.file && a.file.type) || '',
                    url: (a.file && a.file.type && a.file.type.indexOf('image/') === 0) ? URL.createObjectURL(a.file) : null
                }))
            });
            self.prompt = '';
            self.attachments = [];
            // autoResize leaves the composer at its last typed height — snap
            // it back to a single line for the next message
            document.querySelectorAll('textarea[x-model="prompt"]').forEach(function(el) {
                el.style.height = '';
            });
            self.beginStreamingState(self.selectedModel);

            // The server decides how to run this turn. For web-account (Claude
            // via claude.ai) models it saves the user message and replies with a
            // `need_extension` event (handled in the stream reader) instead of
            // generating server-side.
            fetch('/api/chats/send', { method: 'POST', headers: headers, body: body })
                .then(function(response) { self.handleStreamResponse(response); })
                .catch(function(err) { self.handleStreamNetworkError(err); });
        },

        // Server asked us to produce this turn via the browser extension. The
        // user message is already saved; get the answer from a real claude.ai
        // tab, then POST it to complete-extension (which streams + saves it).
        handleNeedExtension: function(info) {
            var self = this;
            self.awaitingExtension = true;
            self._fromExtensionLive = true;
            self._receivedLiveChunks = 0;
            self._inThinkingTag = false;
            self._tagBuffer = '';

            var provider = info.provider || 'claude';
            var ext = window.rynudeExtension;

            var callStream = null;
            if (ext && ext.webCompleteStream) {
                callStream = function(p, onChunk) { return ext.webCompleteStream(provider, p, onChunk); };
            } else if (ext && ext.webComplete) {
                callStream = function(p, onChunk) { return ext.webComplete(provider, p); };
            } else if (ext && ext.claudeComplete && provider === 'claude') {
                callStream = function(p, onChunk) { return ext.claudeComplete(p); };
            }

            console.log('[Rynude] need_extension provider=', provider,
                'hasStream=', !!(ext && ext.webCompleteStream),
                'canRun=', !!callStream,
                'extVersion=', ext && ext.version);

            if (!callStream) {
                self.showStreamError(provider === 'claude'
                    ? 'Butuh Rynude Extension aktif. Reload extension di chrome://extensions lalu refresh halaman.'
                    : 'Untuk ' + provider.toUpperCase() + ' butuh Rynude Extension versi terbaru: buka chrome://extensions → klik Reload pada "Rynude Connector" → refresh halaman ini.');
                return;
            }

            var prompt = self.flattenForClaude(info.messages || []);

            var settled = false;
            var timer = setTimeout(function () {
                if (settled) return;
                settled = true;
                self.showStreamError('Timeout menunggu extension. Buka tab claude.ai (login) lalu kirim lagi.');
            }, 90000);

            var onChunk = function(chunk) {
                if (settled) return;
                if (self.awaitingExtension) {
                    self.awaitingExtension = false;
                }
                self._receivedLiveChunks = (self._receivedLiveChunks || 0) + 1;

                var text = self._tagBuffer + chunk;
                self._tagBuffer = '';

                while (text.length > 0) {
                    if (!self._inThinkingTag) {
                        var openIdx = text.indexOf('<thinking>');
                        if (openIdx !== -1) {
                            if (openIdx > 0) {
                                self.contentQueue += text.slice(0, openIdx);
                            }
                            self._inThinkingTag = true;
                            text = text.slice(openIdx + 10);
                        } else {
                            var partialMatch = false;
                            for (var len = Math.min(text.length, 9); len >= 1; len--) {
                                if ('<thinking>'.slice(0, len) === text.slice(text.length - len)) {
                                    self.contentQueue += text.slice(0, text.length - len);
                                    self._tagBuffer = text.slice(text.length - len);
                                    text = '';
                                    partialMatch = true;
                                    break;
                                }
                            }
                            if (!partialMatch) {
                                self.contentQueue += text;
                                text = '';
                            }
                        }
                    } else {
                        var closeIdx = text.indexOf('</thinking>');
                        if (closeIdx !== -1) {
                            if (closeIdx > 0) {
                                self.thinkQueue += text.slice(0, closeIdx);
                            }
                            self._inThinkingTag = false;
                            text = text.slice(closeIdx + 11);
                        } else {
                            var partialMatchClose = false;
                            for (var len = Math.min(text.length, 10); len >= 1; len--) {
                                if ('</thinking>'.slice(0, len) === text.slice(text.length - len)) {
                                    self.thinkQueue += text.slice(0, text.length - len);
                                    self._tagBuffer = text.slice(text.length - len);
                                    text = '';
                                    partialMatchClose = true;
                                    break;
                                }
                            }
                            if (!partialMatchClose) {
                                self.thinkQueue += text;
                                text = '';
                            }
                        }
                    }
                }
                self.pumpStream();
            };

            callStream(prompt, onChunk).then(function(res) {
                if (settled) return;
                settled = true;
                clearTimeout(timer);

                if (!res || !res.success) {
                    self.showStreamError((res && res.error) || 'Extension gagal menghubungi provider.');
                    return;
                }

                if (self._tagBuffer) {
                    if (self._inThinkingTag) self.thinkQueue += self._tagBuffer;
                    else self.contentQueue += self._tagBuffer;
                    self._tagBuffer = '';
                }
                self.awaitingExtension = false;

                fetch('/api/chats/' + info.conversation_id + '/complete-extension', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream' },
                    body: JSON.stringify({
                        model: info.model,
                        content: res.content || '',
                        parent_message_id: info.parent_message_id
                    })
                })
                .then(function(response) { self.handleStreamResponse(response); })
                .catch(function(err) { self.handleStreamNetworkError(err); });
            }).catch(function(err) {
                if (settled) return;
                settled = true;
                clearTimeout(timer);
                self.showStreamError(String(err && err.message || err));
            });
        },

        // Flatten the active thread into one prompt so context survives
        // (claude.ai opens a fresh conversation per call).
        flattenForClaude: function(messages) {
            var parts = [];
            messages.forEach(function(m) {
                var c = (typeof m.content === 'string') ? m.content.trim() : '';
                if (!c) return;
                var label = m.role === 'assistant' ? 'Assistant' : (m.role === 'system' ? 'System' : 'Human');
                parts.push(label + ': ' + c);
            });
            var promptText = parts.length <= 1
                ? (parts[0] || '').replace(/^(Human|System|Assistant):\s*/, '')
                : parts.join('\n\n');
            var thinkingInstruction = "Instruksi: Sebelum menjawab pertanyaan, kamu WAJIB memikirkan langkah-langkah dan analisis jawabanmu terlebih dahulu secara terperinci di dalam tag <thinking>...</thinking>. Setelah tag penutup </thinking>, baru tuliskan jawaban akhirmu secara jelas dan rapi.";
            return thinkingInstruction + "\n\n" + promptText;
        },

        // Surface an error in the streaming bubble and reset the send state.
        showStreamError: function(msg) {
            this._fromExtensionLive = false;
            this.stopWaitFeed();
            this.contentQueue = '';
            this.thinkQueue = '';
            this.streamContent = '<div class="text-red-500 font-medium">Error: ' + msg + '</div>';
            this.streaming = false;
            this.sending = false;
            this.streamEnded = true;
            try { sessionStorage.removeItem('rynude_active_stream'); } catch (e) {}
        },

        // Reset all streaming state before a send/edit/regenerate kicks off.
        beginStreamingState: function(model) {
            this._fromExtensionLive = false;
            this._receivedLiveChunks = 0;
            this._inThinkingTag = false;
            this._tagBuffer = '';
            this.editingIdx = null;
            this.streaming = true;
            this.streamContent = '';
            this.thinkingContent = '';
            this.thinkingOpen = true;
            this.contentQueue = '';
            this.thinkQueue = '';
            this.streamEnded = false;
            this.finalizing = false;
            this.awaitingExtension = false;
            this.lastThinking = '';
            this.doneMeta = null;
            this.pendingArtifacts = [];
            this.pendingCitations = null;
            this.lastUsedModel = model || this.selectedModel;
            this.canContinue = false;
            this.resumeAttempts = 0;
            // Mark the stream as active so a refreshed tab can reconnect to it
            if (this.conversationId) {
                try { sessionStorage.setItem('rynude_active_stream', String(this.conversationId)); } catch(e) {}
            }
            // Sending a message means the user wants to follow the reply
            this.scrollToBottom();
            this.startWaitFeed();
        },

        // Shared SSE reader for send/edit/regenerate responses.
        handleStreamResponse: function(response) {
            var self = this;
                if (!response.ok) {
                    self.stopWaitFeed();
                    self.streaming = false;
                    self.sending = false;
                    response.json().then(function(errData) {
                        showAlert("Error: " + (errData.message || JSON.stringify(errData)), 'error');
                    }).catch(function() {
                        response.text().then(function(t) {
                            showAlert("Server error: " + t, 'error');
                        });
                    });
                    return;
                }
                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';

                function read() {
                    reader.read().then(function(result) {
                        if (result.done) {
                            // Let the typewriter pump drain the remaining queued
                            // text before finalizing (finishStream does the rest).
                            // Skip when already finalized or when an error/gone
                            // event ended the stream (streaming is false then).
                            // Also skip when we're waiting on the browser extension:
                            // the send stream ends right after `need_extension`, but
                            // the real answer arrives on the follow-up request.
                            if (!self.finalizing && self.streaming && !self.awaitingExtension) {
                                self.streamEnded = true;
                                self.pumpStream();
                            }
                            return;
                        }
                        buffer += decoder.decode(result.value, {stream:true});
                        var lines = buffer.split('\n');
                        buffer = lines.pop() || '';
                        lines.forEach(function(line) {
                            var trimmedLine = line.trim();
                            if (trimmedLine.startsWith('data: ')) {
                                try {
                                    var data = JSON.parse(trimmedLine.slice(6).trim());
                                    if (data.type === 'init') {
                                        // Capture conversation_id from init event (new conversation)
                                        if (data.data && data.data.conversation_id) {
                                            var isNew = !self.conversationId;
                                            self.conversationId = data.data.conversation_id;
                                            try { sessionStorage.setItem('rynude_active_stream', String(self.conversationId)); } catch(e) {}
                                            window.history.replaceState({}, '', '/chat?conversation=' + self.conversationId);
                                            if (isNew) {
                                                window.dispatchEvent(new CustomEvent('conversationCreated', { detail: { id: self.conversationId } }));
                                            }
                                        }
                                    } else if (data.type === 'content') {
                                        if (self._fromExtensionLive && self._receivedLiveChunks > 0) return;
                                        // Queue tokens and reveal them gradually so the
                                        // answer types out even if chunks arrive in bursts
                                        self.contentQueue += data.data;
                                        self.pumpStream();
                                    } else if (data.type === 'thinking') {
                                        if (self._fromExtensionLive && self._receivedLiveChunks > 0) return;
                                        self.thinkQueue += data.data;
                                        self.pumpStream();
                                    } else if (data.type === 'error') {
                                        self._fromExtensionLive = false;
                                        self.stopWaitFeed();
                                        self.contentQueue = '';
                                        self.thinkQueue = '';
                                        self.streamContent = '<div class="text-red-500 font-medium">Error: ' + data.data + '</div>';
                                        self.streaming = false;
                                        self.sending = false;
                                        try { sessionStorage.removeItem('rynude_active_stream'); } catch(e) {}
                                    } else if (data.type === 'gone') {
                                        // No stream buffer on the server: the generation
                                        // finished (and was saved) while we were away
                                        self.abandonResume();
                                    } else if (data.type === 'done') {
                                        // Carries message_id/artifact_id so the finalized
                                        // local message is actionable before the reload
                                        self.doneMeta = data.data || null;
                                        // Capture conversation ID from SSE done event
                                        if (data.data && data.data.conversation_id) {
                                            self.conversationId = data.data.conversation_id;
                                            window.history.replaceState({}, '', '/chat?conversation=' + self.conversationId);
                                            // Notify sidebar to reload its recents
                                            window.dispatchEvent(new CustomEvent('conversationCreated', { detail: { id: self.conversationId } }));
                                        }
                                        // The answer is complete — finalize as soon as the
                                        // typewriter drains, don't wait for the connection
                                        // to close (server may still run housekeeping jobs)
                                        self._fromExtensionLive = false;
                                        self.streamEnded = true;
                                        self.pumpStream();
                                    } else if (data.type === 'artifact') {
                                        if (data.data) self.pendingArtifacts.push(data.data);
                                    } else if (data.type === 'citations') {
                                        self.pendingCitations = data.data || null;
                                    } else if (data.type === 'need_extension') {
                                        // Web-account turn: hand off to the browser
                                        // extension (claude.ai tab), then it saves
                                        // the reply via complete-extension.
                                        self.handleNeedExtension(data.data || {});
                                    }
                                } catch(e) {}
                            }
                        });
                        // Auto-scroll (unless the user scrolled up to read)
                        if (!self.userScrolledUp) {
                            var container = document.querySelector('[x-ref="messagesContainer"]');
                            if (container) container.scrollTop = container.scrollHeight;
                        }
                        read();
                    }).catch(function(err) {
                        // Connection dropped mid-stream: the server keeps
                        // generating (ignore_user_abort) — reattach instead of
                        // giving up.
                        console.error("Stream read error:", err);
                        self.resumeStream();
                    });
                }
                read();
        },

        handleStreamNetworkError: function(err) {
            console.error("Fetch network error:", err);
            showAlert("Network error sending message. Please check connection.", 'error');
            this.stopWaitFeed();
            this.streaming = false;
            this.sending = false;
            try { sessionStorage.removeItem('rynude_active_stream'); } catch(e) {}
        },

        // Reattach to a generation the server kept running after the SSE
        // connection dropped (network hiccup or page refresh). The server
        // replays only the tail we haven't seen; byte lengths are used because
        // they match PHP's strlen exactly (JS .length counts UTF-16 units).
        resumeStream: function() {
            var self = this;
            if (!this.conversationId) { this.failStream(); return; }
            if (this.resumeAttempts >= 3) { this.failStream(); return; }
            this.resumeAttempts++;
            var enc = new TextEncoder();
            var contentLen = enc.encode(this.streamContent + this.contentQueue).length;
            var thinkingLen = enc.encode(this.thinkingContent + this.thinkQueue).length;
            fetch('/api/chats/' + this.conversationId + '/stream-resume?content_len=' + contentLen + '&thinking_len=' + thinkingLen, {
                headers: {'Accept': 'text/event-stream'}
            })
            .then(function(response) { self.handleStreamResponse(response); })
            .catch(function() { setTimeout(function() { self.resumeStream(); }, 1000); });
        },

        // Resume gave up: keep whatever partial answer we have as a local
        // message instead of losing it.
        failStream: function() {
            this.stopWaitFeed();
            this.streamContent += this.contentQueue;
            this.thinkingContent += this.thinkQueue;
            this.contentQueue = '';
            this.thinkQueue = '';
            var content = (this.streamContent || '').replace(/<antArtifact[\s\S]*?(?:<\/antArtifact>|$)/i, '').trim();
            if (content || this.thinkingContent) {
                this.messages.push({
                    id: null,
                    role: 'assistant',
                    content: content,
                    thinking: this.thinkingContent || null,
                    model: this.lastUsedModel || this.selectedModel,
                    artifact: null,
                    attachments: []
                });
            }
            this.streamContent = '';
            this.thinkingContent = '';
            this.streamEnded = false;
            this.streaming = false;
            this.sending = false;
            try { sessionStorage.removeItem('rynude_active_stream'); } catch(e) {}
        },

        // The server has no buffer for this conversation anymore: the answer
        // finished (and was saved) while we were away. Show the saved thread.
        abandonResume: function() {
            this.stopWaitFeed();
            this.contentQueue = '';
            this.thinkQueue = '';
            this.streamContent = '';
            this.thinkingContent = '';
            this.streamEnded = false;
            this.streaming = false;
            this.sending = false;
            try { sessionStorage.removeItem('rynude_active_stream'); } catch(e) {}
            if (this.conversationId) {
                this.loadConversation(this.conversationId, true);
            }
        },

        continueGeneration: function() {
            if (this.sending || this.streaming) return;
            this.canContinue = false;
            this.prompt = 'Lanjutkan persis dari titik terakhir jawabanmu terpotong. Jangan mengulang bagian yang sudah ditulis dan jangan menambahkan kalimat pembuka.';
            this.sendMessage();
        },

        // Typewriter pump: drains the queued thinking/content tokens a few
        // characters per frame so the reply always types out gradually, even
        // when the network delivers big bursts at once. Drain speed adapts to
        // the backlog so a long queue never lags far behind the stream.
        pumpStream: function() {
            if (this.pumpTimer) return;
            var self = this;

            function take(queue, minChars, divisor, maxChars) {
                var n = Math.max(minChars, Math.ceil(queue.length / divisor));
                if (maxChars && n > maxChars) n = maxChars;
                // Don't split a surrogate pair (emoji) across frames
                var code = queue.charCodeAt(n - 1);
                if (code >= 0xD800 && code <= 0xDBFF && n < queue.length) n++;
                return n;
            }

            this.pumpTimer = setInterval(function() {
                var moved = false;
                if (self.thinkQueue.length > 0) {
                    if (self.streamEnded) {
                        // Answer is finished — dump the remaining reasoning at once
                        self.thinkingContent += self.thinkQueue;
                        self.thinkQueue = '';
                    } else {
                        // Reasoning reads best at a calm pace: hard-capped at
                        // ~4 chars per frame (≈250 chars/s) no matter how big
                        // the backlog gets — leftovers are flushed at finish
                        var n = take(self.thinkQueue, 1, 200, 4);
                        self.thinkingContent += self.thinkQueue.slice(0, n);
                        self.thinkQueue = self.thinkQueue.slice(n);
                    }
                    moved = true;
                }
                if (self.contentQueue.length > 0) {
                    // Fast sweep: drain 1/5 of the backlog per frame (min 10
                    // chars) so the spinner never lingers once the full answer
                    // has arrived, while short answers still visibly type out.
                    // After the server says done, sweep near-instantly.
                    var m = self.streamEnded
                        ? take(self.contentQueue, 40, 3)
                        : take(self.contentQueue, 10, 5);
                    self.streamContent += self.contentQueue.slice(0, m);
                    self.contentQueue = self.contentQueue.slice(m);
                    moved = true;
                }
                if (moved && !self.userScrolledUp) {
                    var container = document.querySelector('[x-ref="messagesContainer"]');
                    if (container) container.scrollTop = container.scrollHeight;
                    var think = document.querySelector('[x-ref="thinkingBox"]');
                    if (think) think.scrollTop = think.scrollHeight;
                }
                // Finalize as soon as the visible ANSWER is fully typed — never
                // wait for a reasoning backlog (finishStream flushes it at once)
                if (self.contentQueue.length === 0 && (self.streamEnded || !self.streaming)) {
                    clearInterval(self.pumpTimer);
                    self.pumpTimer = null;
                    if (self.streamEnded) self.finishStream();
                }
            }, 16);
        },

        startWaitFeed: function() {
            this.stopWaitFeed();
            var self = this;
            var steps = [
                'Membaca pertanyaan…',
                'Menganalisis konteks percakapan…',
                'Mengumpulkan poin-poin penting…',
                'Menyusun kerangka jawaban…',
                'Menulis jawaban…',
                'Masih menyusun jawaban, mohon tunggu…'
            ];
            var idx = 0;
            this.waitStatus = steps[idx++];
            // Single line swapped in place — stacking the past steps made the
            // wait look longer than it is.
            this.waitTimer = setInterval(function() {
                if (self.streamContent || self.thinkingContent || !self.streaming) {
                    self.stopWaitFeed();
                    return;
                }
                self.waitStatus = steps[Math.min(idx++, steps.length - 1)];
            }, 1800);
        },

        stopWaitFeed: function() {
            if (this.waitTimer) {
                clearInterval(this.waitTimer);
                this.waitTimer = null;
            }
            this.waitStatus = '';
        },

        finishStream: function() {
            this.streamEnded = false;
            this.sending = false;
            this.finalizing = false;
            this.stopWaitFeed();
            // Reveal any reasoning still queued in one go — it's secondary to
            // the finished answer and must not delay it
            if (this.thinkQueue) {
                this.thinkingContent += this.thinkQueue;
                this.thinkQueue = '';
            }
            // Materialize the streamed reply as a real message NOW and end the
            // loading state — the send connection may stay open for seconds
            // running housekeeping (title generation on a sync queue), and the
            // silent reload below can't be served before that finishes on a
            // single-worker `artisan serve`. Waiting on it left the spinner up
            // and the streaming block + swapped-in message briefly doubled the
            // scroll area.
            var content = (this.streamContent || '').replace(/<antArtifact[\s\S]*?(?:<\/antArtifact>|$)/i, '').trim();
            if (content || this.thinkingContent) {
                this.messages.push({
                    id: this.doneMeta ? (this.doneMeta.message_id || null) : null,
                    role: 'assistant',
                    content: content,
                    thinking: this.thinkingContent || null,
                    model: this.lastUsedModel || this.selectedModel,
                    artifact: this.pendingArtifacts[0] || null,
                    artifacts: this.pendingArtifacts.slice(),
                    citations: this.pendingCitations || null,
                    attachments: []
                });
            }
            // Answer hit the model's output limit — offer to continue it
            this.canContinue = !!(this.doneMeta && this.doneMeta.truncated);
            try { sessionStorage.removeItem('rynude_active_stream'); } catch(e) {}
            // Keep the thinking text so it can be re-attached to the saved message
            this.lastThinking = this.thinkingContent;
            this.streamContent = '';
            this.thinkingContent = '';
            this.streaming = false;
            this.doneMeta = null;
            this.pendingArtifacts = [];
            if (this.conversationId) {
                // Swap in the authoritative thread (ids, artifacts, branch info)
                // whenever the server is ready — the UI is already settled.
                this.loadConversation(this.conversationId, true);
            }
        },

        stopGeneration: function() {
            var self = this;
            if (!this.conversationId) return;
            fetch('/api/chats/stop', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({conversation_id: this.conversationId})
            })
            .then(function(){
                // Show whatever is still queued right away, then let the
                // reader's done-event finalize via finishStream()
                self.stopWaitFeed();
                self.streamContent += self.contentQueue;
                self.thinkingContent += self.thinkQueue;
                self.contentQueue = '';
                self.thinkQueue = '';
                self.sending = false;
            });
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

        takeScreenshot: function() {
            var self = this;
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                showAlert('Screen capture is not supported in this browser or context.', 'error');
                return;
            }
            navigator.mediaDevices.getDisplayMedia({ video: true })
                .then(function(stream) {
                    var video = document.createElement('video');
                    video.srcObject = stream;
                    video.autoplay = true;
                    video.onloadedmetadata = function() {
                        setTimeout(function() {
                            var canvas = document.createElement('canvas');
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            var ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            
                            stream.getTracks().forEach(function(track) { track.stop(); });
                            
                            canvas.toBlob(function(blob) {
                                if (blob) {
                                    var filename = 'Screenshot_' + new Date().toISOString().slice(0,19).replace(/[:T]/g, '_') + '.png';
                                    var fileObj = new File([blob], filename, { type: 'image/png' });
                                    self.attachments.push({
                                        name: filename,
                                        file: fileObj
                                    });
                                }
                            }, 'image/png');
                        }, 300);
                    };
                })
                .catch(function(err) {
                    console.error('Screenshot failed:', err);
                });
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
            window.dispatchEvent(new CustomEvent('open-artifact', {detail:{id:id}}));
        },

        rateMessage: function(idx, rating) {
            var msg = this.messages[idx];
            if (!msg) return;
            // Same thumb again clears the rating (toggle), like Claude
            var next = msg.rating === rating ? null : rating;
            msg.rating = next;
            if (!msg.id) return;
            fetch('/api/chats/messages/' + msg.id + '/rating', {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ rating: next })
            }).catch(function(){});
        },

        editMessage: function(idx) {
            var msg = this.messages[idx];
            if (!msg || msg.role !== 'user') return;
            this.editingIdx = idx;
            this.editDraft = msg.content;
        },

        submitEdit: function(idx) {
            var msg = this.messages[idx];
            var text = (this.editDraft || '').trim();
            this.editingIdx = null;
            if (!msg || !text || this.sending) return;
            // Unsaved local message (no id yet): fall back to the composer
            if (!msg.id || !this.conversationId) { this.prompt = text; return; }
            var self = this;
            this.sending = true;
            // Fork the thread: hide the old message + its tail, show the new text
            this.messages = this.messages.slice(0, idx);
            this.messages.push({ role: 'user', content: text, attachments: [] });
            this.beginStreamingState(this.selectedModel);
            fetch('/api/chats/send', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'text/event-stream'},
                body: JSON.stringify({
                    prompt: text,
                    model: this.selectedModel,
                    conversation_id: this.conversationId,
                    edit_of: msg.id,
                    web_search: this.webSearch ? 1 : 0,
                    research_mode: this.researchMode ? 1 : 0,
                    thinking: this.thinkingMode ? 1 : 0,
                    style: this.chatStyle
                })
            })
            .then(function(response) { self.handleStreamResponse(response); })
            .catch(function(err) { self.handleStreamNetworkError(err); });
        },

        regenerate: function(idx, model) {
            var msg = this.messages[idx];
            if (!msg || msg.role === 'user' || !msg.id || this.sending || !this.conversationId) return;
            var self = this;
            var useModel = model || msg.model || this.selectedModel;
            this.sending = true;
            // The old reply becomes an inactive branch server-side; drop it from view
            this.messages = this.messages.slice(0, idx);
            this.beginStreamingState(useModel);
            fetch('/api/chats/' + this.conversationId + '/regenerate', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'text/event-stream'},
                body: JSON.stringify({ message_id: msg.id, model: useModel, thinking: this.thinkingMode ? 1 : 0 })
            })
            .then(function(response) { self.handleStreamResponse(response); })
            .catch(function(err) { self.handleStreamNetworkError(err); });
        },

        switchBranch: function(idx, dir) {
            var msg = this.messages[idx];
            if (!msg || !msg.sibling_ids || this.sending || this.streaming) return;
            var target = msg.sibling_ids[msg.sibling_index + dir];
            if (!target) return;
            var self = this;
            fetch('/api/chats/' + this.conversationId + '/switch-branch', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ message_id: target })
            })
            .then(function(r) { return r.json(); })
            .then(function() { self.loadConversation(self.conversationId, true); })
            .catch(function(){});
        },

        // Chat header actions
        toggleStar: function() {
            if (!this.conversationId) return;
            var self = this;
            this.isStarred = !this.isStarred;
            fetch('/api/chats/' + this.conversationId, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ is_starred: this.isStarred })
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                window.dispatchEvent(new CustomEvent('conversationUpdated'));
            })
            .catch(function() { self.isStarred = !self.isStarred; });
        },

        openRenameModal: function() {
            this.renameDraft = this.chatTitle || '';
            this.showRenameModal = true;
            var self = this;
            setTimeout(function() {
                var input = document.querySelector('[x-ref="renameInput"]');
                if (input) input.focus();
            }, 50);
        },

        submitRename: function() {
            if (!this.conversationId || !this.renameDraft.trim()) return;
            var self = this;
            var newTitle = this.renameDraft.trim();
            fetch('/api/chats/' + this.conversationId, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ title: newTitle })
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                self.chatTitle = newTitle;
                self.showRenameModal = false;
                window.dispatchEvent(new CustomEvent('conversationUpdated'));
            })
            .catch(function() { showAlert('Failed to rename chat', 'error'); });
        },

        openAddProjectModal: function() {
            this.loadProjects();
            this.selectedProjectForAdd = this.chatProject;
            this.showAddProjectModal = true;
        },

        loadProjects: function() {
            var self = this;
            fetch('/api/projects', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    self.availableProjects = resp.data || [];
                })
                .catch(function(){});
        },

        submitAddToProject: function() {
            if (!this.conversationId || !this.selectedProjectForAdd) return;
            var self = this;
            fetch('/api/chats/' + this.conversationId, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ project_id: this.selectedProjectForAdd })
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                self.chatProject = self.selectedProjectForAdd;
                var proj = self.availableProjects.find(function(p) { return p.id === self.selectedProjectForAdd; });
                self.chatProjectName = proj ? proj.name : '';
                self.showAddProjectModal = false;
                window.dispatchEvent(new CustomEvent('conversationUpdated'));
            })
            .catch(function() { showAlert('Failed to add chat to project', 'error'); });
        },

        removeFromProject: function() {
            if (!this.conversationId) return;
            var self = this;
            fetch('/api/chats/' + this.conversationId, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({ project_id: null })
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                self.chatProject = null;
                self.chatProjectName = '';
                self.showAddProjectModal = false;
                window.dispatchEvent(new CustomEvent('conversationUpdated'));
            })
            .catch(function() { showAlert('Failed to remove chat from project', 'error'); });
        },

        openDeleteModal: function() {
            this.showDeleteModal = true;
        },

        submitDelete: function() {
            if (!this.conversationId) return;
            var self = this;
            fetch('/api/chats/' + this.conversationId, {
                method: 'DELETE',
                headers: {'Accept':'application/json'}
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                self.showDeleteModal = false;
                self.conversationId = null;
                self.messages = [];
                self.chatTitle = '';
                self.chatProject = null;
                self.chatProjectName = '';
                window.dispatchEvent(new CustomEvent('conversationDeleted'));
                window.history.replaceState({}, '', '/chat');
            })
            .catch(function() { showAlert('Failed to delete chat', 'error'); });
        },

        sendSurpriseMessage: function() {
            this.prompt = 'Surprise me! Tell me something interesting or create something fun.';
            this.sendMessage();
        },

        renderContent: function(content) {
            if (!content) return '';

            // Detect ```mermaid blocks before markdown parsing. Kode diagram
            // DISIMPAN di memori, bukan disuntik mentah sebagai HTML — kode
            // yang mengandung baris kosong atau karakter < akan dipotong oleh
            // parser markdown/HTML sehingga mermaid menerima diagram setengah.
            var hasMermaid = false;
            var self = this;
            if (!this._mermaidCodes) this._mermaidCodes = {};
            content = content.replace(/```mermaid\r?\n([\s\S]*?)```/g, function(match, code) {
                hasMermaid = true;
                // Generate unique ID for this diagram
                var id = 'mermaid-' + Math.random().toString(36).substr(2, 9);
                self._mermaidCodes[id] = code;
                return '\n<div class="mermaid-diagram my-4 p-4 bg-white dark:bg-stone-900 rounded-xl border border-stone-200 dark:border-stone-700 overflow-x-auto" id="' + id + '" data-mermaid-pending="true"></div>\n';
            });

            // Runnable analysis snippets (```js-run): rendered as a code box
            // with a Run button, executed in a sandboxed iframe. Same
            // placeholder pattern as mermaid so markdown never mangles code.
            var hasJsRun = false;
            window._rynudeJsRun = window._rynudeJsRun || {};
            content = content.replace(/```js-run\r?\n([\s\S]*?)```/g, function(match, code) {
                hasJsRun = true;
                var id = 'jsrun-' + Math.random().toString(36).substr(2, 9);
                window._rynudeJsRun[id] = code;
                return '\n<div class="jsrun-block not-prose" id="' + id + '" data-jsrun-pending="true"></div>\n';
            });

            // Protect LaTeX math from the markdown parser (same placeholder
            // trick as mermaid); rendered with KaTeX after parsing.
            var mathChunks = [];
            if (typeof katex !== 'undefined') {
                var stashMath = function(tex, display) {
                    mathChunks.push({ tex: tex, display: display });
                    return '%%KATEX' + (mathChunks.length - 1) + '%%';
                };
                content = content.replace(/\$\$([\s\S]+?)\$\$/g, function(m, tex) { return stashMath(tex, true); });
                content = content.replace(/\\\[([\s\S]+?)\\\]/g, function(m, tex) { return stashMath(tex, true); });
                content = content.replace(/\\\((.+?)\\\)/g, function(m, tex) { return stashMath(tex, false); });
                // Single-$ inline math only when the content looks like math
                // (\ ^ _ = or braces), so currency like $100 stays untouched
                content = content.replace(/(^|[^\\$\w])\$([^\n$]+?)\$(?![\w$])/g, function(m, pre, tex) {
                    if (!/[\\^_={}]/.test(tex)) return m;
                    return pre + stashMath(tex, false);
                });
            }

            // Parse markdown
            var html = '';
            if (typeof marked !== 'undefined') {
                try {
                    html = marked.parse(content);
                } catch(e) {
                    html = content.replace(/\n/g, '<br>');
                }
            } else {
                html = content.replace(/\n/g, '<br>');
            }

            // Swap the math placeholders back in as rendered KaTeX
            if (mathChunks.length) {
                html = html.replace(/%%KATEX(\d+)%%/g, function(m, i) {
                    var c = mathChunks[+i];
                    try {
                        return katex.renderToString(c.tex, { displayMode: c.display, throwOnError: false });
                    } catch(e) {
                        return c.display ? '$$' + c.tex + '$$' : c.tex;
                    }
                });
            }

            // Populate runnable-code boxes after DOM insertion
            if (hasJsRun) {
                setTimeout(function() {
                    document.querySelectorAll('.jsrun-block[data-jsrun-pending="true"]').forEach(function(el) {
                        el.removeAttribute('data-jsrun-pending');
                        var code = window._rynudeJsRun[el.id];
                        if (!code) return;
                        var esc = code.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        el.innerHTML = '<div style="background:#1E1E1E;border-radius:12px;overflow:hidden;margin:12px 0;border:1px solid rgba(120,113,108,0.3);">'
                            + '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 12px;background:#2A2A28;">'
                            + '<span style="font-size:11px;color:#a8a29e;font-family:monospace;">javascript &middot; analysis</span>'
                            + '<button onclick="window.rynudeRunJs(\'' + el.id + '\')" style="font-size:12px;color:#fff;background:#D97757;border:none;border-radius:6px;padding:3px 12px;cursor:pointer;">&#9654; Jalankan</button>'
                            + '</div>'
                            + '<pre style="margin:0;padding:12px;overflow-x:auto;"><code style="background:transparent;color:#e7e5e4;font-size:13px;font-family:monospace;white-space:pre;">' + esc + '</code></pre>'
                            + '<pre id="' + el.id + '-out" style="display:none;margin:0;padding:10px 12px;border-top:1px solid #3f3f3c;background:#171716;color:#86efac;font-size:12.5px;white-space:pre-wrap;font-family:monospace;"></pre>'
                            + '</div>';
                    });
                }, 50);
            }

            // Render mermaid diagrams after DOM insertion
            if (hasMermaid && typeof window.mermaid !== 'undefined') {
                setTimeout(function() {
                    document.querySelectorAll('.mermaid-diagram[data-mermaid-pending="true"]').forEach(function(el) {
                        el.removeAttribute('data-mermaid-pending');
                        var rawCode = self._mermaidCodes[el.id];
                        if (rawCode) self.renderMermaidDiagram(el, rawCode);
                    });
                }, 50);
            }

            return html;
        },

        // Render kode ASLI dari model lebih dulu; fixMermaidSyntax hanya
        // dipakai sebagai percobaan kedua bila kode asli ditolak parser,
        // karena fixer dapat merusak kode yang sebenarnya valid (mis.
        // mencabut tanda kutip pelindung label). Bila keduanya gagal,
        // tampilkan kode mentah dalam kotak error yang rapi.
        renderMermaidDiagram: function(el, rawCode) {
            var self = this;
            var attempts = [rawCode.trim(), this.fixMermaidSyntax(rawCode)];
            var attemptIndex = 0;

            function tryNext() {
                if (attemptIndex >= attempts.length) {
                    self.showMermaidError(el, rawCode);
                    return;
                }
                var code = attempts[attemptIndex];
                attemptIndex++;
                if (!code) { tryNext(); return; }
                var renderId = el.id + '-svg-' + attemptIndex;
                try {
                    window.mermaid.render(renderId, code).then(function(result) {
                        el.innerHTML = result.svg;
                        el.setAttribute('data-processed', 'true');
                    }).catch(function(error) {
                        console.error('Mermaid parse error (attempt ' + attemptIndex + '):', error);
                        tryNext();
                    });
                } catch(e) {
                    console.error('Mermaid rendering error (attempt ' + attemptIndex + '):', e);
                    tryNext();
                }
            }
            tryNext();
        },

        showMermaidError: function(el, rawCode) {
            el.innerHTML = '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">' +
                '<div class="flex items-start gap-2 mb-2">' +
                '<svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' +
                '<div class="flex-1"><p class="text-sm font-medium text-red-800 dark:text-red-300">Mermaid Syntax Error</p>' +
                '<p class="text-xs text-red-600 dark:text-red-400 mt-1">The diagram code has syntax errors. Raw code shown below:</p></div>' +
                '</div>' +
                '<pre class="mt-2 p-3 bg-stone-900 text-stone-200 rounded text-xs overflow-x-auto"><code>' + rawCode.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</code></pre>' +
                '</div>';
            el.setAttribute('data-processed', 'true');
        },

        /**
         * Universal Mermaid Syntax Fixer
         * Cleans up common errors from ALL AI models (weak & strong)
         */
        fixMermaidSyntax: function(code) {
            if (!code) return '';

            // 1. Remove common garbage text patterns
            code = code.replace(/text\s+(Copy\s+)?code/gi, ''); // "text Copy code", "text code"
            code = code.replace(/^(Copy\s+code|code)\s*/gim, ''); // Line starting with "Copy code" or "code"

            // 2. Remove code block headers/footers that models sometimes add
            code = code.replace(/^```\w*\s*/gm, ''); // Remove ```language at start of lines
            code = code.replace(/```\s*$/gm, ''); // Remove closing ```

            // 3. Fix special characters in node labels that break Mermaid parser

            // Fix parentheses inside square brackets: [Text (A, B, C)] -> [Text A, B, C]
            code = code.replace(/\[([^\]]*?)\(([^)]*?)\)([^\]]*?)\]/g, function(match, before, inside, after) {
                return '[' + before.trim() + ' ' + inside.trim() + ' ' + after.trim() + ']';
            });

            // Fix colons in labels: [System: Action] -> [System - Action]
            code = code.replace(/\[([^\]:]+):([^\]]+)\]/g, '[$1 -$2]');

            // Replace ampersand with "dan" or "and" in labels
            code = code.replace(/\[([^\]]*?)&([^\]]*?)\]/g, function(match, before, after) {
                // Detect language - use "dan" for Indonesian, "and" for English
                var useIndonesian = /[A-Z][a-z]*an|Sistem|Proses|Data|Validasi/i.test(code);
                var connector = useIndonesian ? 'dan' : 'and';
                return '[' + before.trim() + ' ' + connector + ' ' + after.trim() + ']';
            });

            // 4. Fix quote issues - remove unmatched quotes
            code = code.replace(/["""]/g, '"'); // Normalize quote types
            code = code.replace(/\[([^\]]*?)"([^\]]*?)\]/g, '[$1$2]'); // Remove quotes inside labels

            // 5. Fix edge label syntax - normalize arrow labels
            // Convert different arrow label formats to standard format
            code = code.replace(/--\s*\[([^\]]+)\]\s*-->/g, '-->|$1|'); // --[Label]--> to -->|Label|
            code = code.replace(/--\s*"([^"]+)"\s*-->/g, '-->|$1|'); // --"Label"--> to -->|Label|

            // 6. Remove empty lines and clean up whitespace
            code = code.split('\n').map(function(line) {
                return line.trim();
            }).filter(function(line) {
                return line.length > 0 && line !== '```' && line !== '```mermaid';
            }).join('\n');

            // 7. Fix common node shape syntax errors
            // Ensure start/end nodes use proper syntax
            code = code.replace(/\(\[([^\]]+)\]\)/g, '([($1)])'); // Fix double brackets in stadium shape

            // 8. Remove trailing semicolons (some models add them, Mermaid doesn't need them)
            code = code.replace(/;+$/gm, '');

            // 9. Fix multiple spaces on same line (preserve newlines)
            code = code.replace(/  +/g, ' ');  // 2+ spaces → 1 space (but keep newlines)

            return code.trim();
        },
        parseStreamContent: function(content) {
            if (!content) return '';
            
            // Extract everything before the artifact, inside the artifact tag, and after.
            // Attributes are parsed separately because models emit them in any order
            // (the system prompt example puts type before title).
            var match = content.match(/([\s\S]*?)<antArtifact\b([^>]*)>([\s\S]*?)(?:<\/antArtifact>|$)([\s\S]*)/i);
            if (!match) {
                // The opening tag is still arriving (no '>' yet): hide the
                // partial raw tag and just show what came before it
                var openIdx = content.search(/<antArtifact\b/i);
                if (openIdx !== -1) {
                    return this.renderContent(content.slice(0, openIdx));
                }
            }
            if (match) {
                var pre = match[1];
                var attrs = match[2];
                var artifactContent = match[3];
                var post = match[4] || '';
                var title = (attrs.match(/title="([^"]*)"/i) || [])[1] || '';
                var type = (attrs.match(/type="([^"]*)"/i) || [])[1] || '';
                // No closing tag yet = the model is still writing the artifact
                var isWriting = !/<\/antArtifact>/i.test(content);

                var html = this.renderContent(pre);

                html += `
                    <div class="mt-3 inline-flex items-center gap-3 border border-claude-border-light dark:border-claude-border-dark rounded-xl p-2 pr-4 bg-claude-bg-light dark:bg-claude-bg-dark shadow-sm max-w-full">
                        <div class="w-8 h-8 rounded-lg bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-500 shrink-0">
                            <svg class="w-4 h-4 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[13px] font-medium text-stone-800 dark:text-stone-200 truncate">` + (title || 'Generating...') + `</div>
                            <div class="text-[11px] text-stone-400">` + type + (isWriting ? ' — ' + artifactContent.length + ' karakter…' : '') + `</div>
                        </div>
                    </div>
                `;

                // Live preview of the artifact text while it is being written:
                // show the tail of the content in a compact dark box, so the
                // user can watch the document grow in real time.
                if (isWriting && artifactContent) {
                    var tail = artifactContent.slice(-500);
                    if (artifactContent.length > 500) {
                        // Start the excerpt at a clean line boundary when possible
                        var nl = tail.indexOf('\n');
                        if (nl > -1 && nl < 200) tail = tail.slice(nl + 1);
                    }
                    var esc = tail.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    html += '<div class="mt-2 rounded-xl border border-claude-border-light dark:border-claude-border-dark not-prose" style="background:#1E1E1E;padding:12px;">'
                        + '<pre style="margin:0;white-space:pre-wrap;word-break:break-word;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.5;color:#D6D3D1;">'
                        + esc + '<span class="animate-pulse">▌</span></pre></div>';
                }

                if (post) {
                    html += this.renderContent(post);
                }

                return html;
            }
            return this.renderContent(content);
        },
    };
}

// Analysis tool runner: executes a ```js-run snippet inside a sandboxed
// iframe (allow-scripts only — no cookies, no DOM, no same-origin access).
// console.* output and errors are collected and posted back for display.
window.rynudeRunJs = function(id) {
    var code = (window._rynudeJsRun || {})[id];
    var out = document.getElementById(id + '-out');
    if (!code || !out) return;
    out.style.display = 'block';
    out.textContent = 'Menjalankan…';

    // A literal closing script-tag inside the code would terminate the srcdoc
    // script block, so escape it (never write that tag literally in here!)
    var safe = code.replace(new RegExp('</scr' + 'ipt', 'gi'), '<\\/script');
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.setAttribute('sandbox', 'allow-scripts');

    var done = false;
    function handler(e) {
        if (!e.data || e.data.rynudeJsRun !== id) return;
        done = true;
        window.removeEventListener('message', handler);
        out.textContent = (e.data.logs && e.data.logs.length)
            ? e.data.logs.join('\n')
            : '(selesai — tidak ada output console.log)';
        if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
    }
    window.addEventListener('message', handler);

    iframe.srcdoc = '<scr' + 'ipt>var logs=[];function put(){logs.push(Array.prototype.map.call(arguments,function(a){try{return (typeof a==="object"&&a!==null)?JSON.stringify(a,null,2):String(a)}catch(e){return String(a)}}).join(" "))}console.log=put;console.info=put;console.warn=put;console.error=put;window.onerror=function(m){put("Error: "+m);return true};try{(function(){' + safe + '\n})()}catch(e){put("Error: "+e.message)}parent.postMessage({rynudeJsRun:"' + id + '",logs:logs},"*");</scr' + 'ipt>';
    document.body.appendChild(iframe);

    setTimeout(function() {
        if (done) return;
        window.removeEventListener('message', handler);
        out.textContent = 'Timeout: kode berjalan lebih dari 8 detik (kemungkinan loop tak berujung).';
        if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
    }, 8000);
};
</script>

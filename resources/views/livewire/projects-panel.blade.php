<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-claude-bg-dark overflow-hidden relative"
     x-data="projectsPanelState()"
     x-init="init()">

    {{-- ============ PROJECT DETAILS VIEW ============ --}}
    <template x-if="selectedProject && selectedProject.id">
        <div class="max-w-[1200px] mx-auto w-full px-8 py-8 flex flex-col h-full animate-fade-in">
            <button @click="backToList()" class="flex items-center gap-2 text-[14px] text-[#5e5c5a] hover:text-[#1a1a1a] dark:text-stone-400 dark:hover:text-stone-200 transition-colors mb-6 w-fit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>All projects
            </button>

            <div class="flex items-center justify-between mb-8">
                <h2 class="font-serif text-[32px] text-[#1a1a1a] dark:text-stone-200" x-text="selectedProject.name"></h2>
                <div class="flex items-center gap-1.5">
                    <button class="p-1.5 text-[#1a1a1a] dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 rounded-lg transition-colors" title="Options">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1.5"></circle><circle cx="12" cy="5" r="1.5"></circle><circle cx="12" cy="19" r="1.5"></circle></svg>
                    </button>
                    <button @click="starProject(selectedProject.id)" class="p-1.5 rounded-lg transition-colors hover:bg-black/5 dark:hover:bg-white/5" :class="selectedProject.is_starred ? 'text-[#1a1a1a]' : 'text-[#1a1a1a] dark:text-stone-300'" title="Star project">
                        <svg class="w-5 h-5" :fill="selectedProject.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-12 flex-1 min-h-0">
                <div class="flex-1 max-w-[700px] flex flex-col min-h-0">
                    {{-- Chat input --}}
                    <div class="relative w-full mx-auto bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-2xl md:rounded-3xl shadow-sm flex flex-col focus-within:shadow-lg focus-within:border-claude-accent/50 dark:focus-within:border-claude-accent/50 animate-smooth transition-all duration-200 mb-8">
                        <textarea x-ref="projectChatInput"
                            x-init="$watch('projectChatPrompt', function(v) { if(!v) { $refs.projectChatInput.style.height = 'auto'; } })"
                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                            x-model="projectChatPrompt"
                            @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); startNewChatInProject(); }"
                            rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 md:px-5 pt-4 pb-2 resize-none text-stone-800 dark:text-stone-200 placeholder-[#8E8B87] dark:placeholder-stone-500 text-[15px] min-h-[52px] max-h-48 overflow-y-auto"
                            placeholder="How can I help you today?"
                        ></textarea>

                        <div class="flex items-center justify-between w-full mt-4 pb-1">
                            <div x-data="{ openPlus: false }" class="relative">
                                <button @click="openPlus = !openPlus" type="button" class="p-2 text-stone-500 rounded-xl transition-colors min-w-[36px] min-h-[36px] flex items-center justify-center" :class="openPlus ? 'bg-stone-100 dark:bg-[#3A3A38] text-stone-800 dark:text-stone-200' : 'hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38]'">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>

                                <div x-show="openPlus" @click.away="openPlus = false" x-transition.opacity x-cloak class="absolute top-full left-0 mt-2 w-[240px] bg-white dark:bg-[#2C2C2A] border border-claude-border-light dark:border-claude-border-dark rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5">
                                    <button type="button" @click="openPlus=false; $refs.projectFileInput.click()" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add files or photos</span>
                                        </div>
                                        <span class="text-[12px] text-stone-400 font-medium">Ctrl+U</span>
                                    </button>
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'projects' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add to project</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>

                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'customize' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 13l2 2 4-4"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Skills</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'customize' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                            <span class="text-[13px] text-stone-800 dark:text-stone-200">Add connector</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <button type="button" @click="openPlus=false; window.dispatchEvent(new CustomEvent('open-panel', { detail: 'customize' }))" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center gap-2.5 group">
                                        <svg class="w-4 h-4 text-stone-500 group-hover:text-stone-700 dark:group-hover:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 22 5-5"/><path d="M19 19a2 2 0 0 1-2 2H7.5A2.5 2.5 0 0 1 5 18.5V7a2 2 0 0 1 2-2h4.5a2.5 2.5 0 0 1 2.5 2.5V11l5-5Z"/></svg>
                                        <span class="text-[13px] text-stone-800 dark:text-stone-200">Add plugins...</span>
                                    </button>
                                </div>
                                <input type="file" x-ref="projectFileInput" class="hidden" multiple>
                            </div>
                            <div class="flex items-center gap-1 md:gap-1.5 text-stone-500">
                                <div x-data="{ open: false, ext: true, subOpen: false, closeTimer: null }" class="relative">
                                    <button @click="open = !open" type="button" class="flex items-center gap-1.5 cursor-pointer focus:outline-none bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200 max-w-[120px] truncate" x-text="selectedModelName()"></span>
                                        <span class="text-[13px] text-stone-500 hidden sm:inline" x-show="ext">Extended</span>
                                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full right-0 mt-2 w-[250px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] z-50 py-1.5">
                                        <template x-for="m in models" :key="m.code">
                                            <button @click="selectedModel = m.code; open = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group" :class="!m.is_available ? 'opacity-50 cursor-not-allowed' : ''" :disabled="!m.is_available">
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-[13px] text-stone-800 dark:text-stone-200" x-text="m.name"></span>
                                                        <span x-show="!m.is_available" class="inline-flex items-center gap-1 px-1 py-0.5 rounded text-[10px] font-medium bg-stone-100 dark:bg-stone-700 text-stone-500">
                                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>Unavailable
                                                        </span>
                                                    </div>
                                                    <div class="text-[12px] text-stone-400 dark:text-stone-500 font-medium mt-0.5" x-text="m.description"></div>
                                                </div>
                                                <svg x-show="selectedModel === m.code" class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </template>
                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>
                                        <div class="px-3 py-1.5 flex items-center justify-between cursor-pointer hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors" @click="ext = !ext">
                                            <div>
                                                <div class="text-[13px] font-medium text-stone-800 dark:text-stone-200">Extended</div>
                                                <p class="text-[11.5px] text-stone-500 mt-0.5">Always uses deep reasoning</p>
                                            </div>
                                            <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out" :class="ext ? 'bg-[#2563EB]' : 'bg-gray-200'">
                                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition duration-200 ease-in-out" :class="ext ? 'translate-x-4' : 'translate-x-[3px]'"></span>
                                            </div>
                                        </div>
                                        <div class="h-px bg-[#E5E5E5] dark:bg-stone-700 mx-3 my-1.5"></div>
                                        <div class="relative" @mouseenter="clearTimeout(closeTimer); subOpen = true" @mouseleave="closeTimer = setTimeout(function(){ subOpen = false }, 250)">
                                            <button type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group">
                                                <span class="text-[13px] font-medium text-stone-800 dark:text-stone-200">More models</span>
                                                <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                            </button>
                                            <div x-show="subOpen" x-cloak class="absolute left-0 sm:left-auto sm:right-full sm:-mr-1 bottom-full mb-1 sm:mb-0 sm:bottom-[-8px] sm:top-auto mt-2 sm:mt-0 w-[200px] bg-white dark:bg-[#2C2C2A] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.12)] py-1.5 z-50 max-h-[300px] overflow-y-auto custom-scrollbar">
                                                <template x-for="m in moreModels" :key="m.code">
                                                    <button @click="selectedModel = m.code; open = false; subOpen = false" type="button" class="w-full text-left px-3 py-1.5 hover:bg-stone-50 dark:hover:bg-[#3A3A38] transition-colors flex items-center justify-between group" :class="!m.is_available ? 'opacity-50 cursor-not-allowed' : ''" :disabled="!m.is_available">
                                                        <span class="text-[13px] font-medium text-stone-800" x-text="m.name"></span>
                                                        <svg x-show="selectedModel === m.code" class="w-4 h-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div x-data="voiceInput" x-show="supported" class="relative group flex items-center justify-center">
                                    <button type="button" @click="toggle()" :class="listening ? 'bg-red-50 dark:bg-red-500/10 text-red-500' : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700'" class="rounded-lg transition-colors p-1 min-w-[36px] min-h-[36px] flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="listening ? 'animate-pulse' : ''"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                                    </button>
                                </div>
                                <button @click="startNewChatInProject()" :disabled="!projectChatPrompt.trim()" class="rounded-lg transition-colors p-1.5 min-w-[32px] min-h-[32px] flex items-center justify-center" :class="projectChatPrompt.trim() ? 'bg-[#D97757] text-white hover:bg-[#c96646]' : 'bg-stone-100 dark:bg-stone-700 text-stone-400 dark:text-stone-500'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                        <template x-if="!selectedProject.chats || selectedProject.chats.length === 0">
                            <div class="border-t border-[#E5E5E5] dark:border-stone-800 py-6 text-center"><p class="text-[14px] text-stone-500">No chats yet</p></div>
                        </template>
                        <template x-if="selectedProject.chats && selectedProject.chats.length > 0">
                            <div>
                                <template x-for="chat in selectedProject.chats" :key="chat.id">
                                    <div class="py-4 border-t border-[#E5E5E5] dark:border-stone-800 flex flex-col cursor-pointer transition-colors group" @click="openProjectChat(chat.id)">
                                        <span class="text-[15px] text-[#1a1a1a] dark:text-stone-200 font-medium group-hover:underline decoration-[#1a1a1a]/30 underline-offset-2" x-text="chat.title"></span>
                                        <span class="text-[13px] text-[#5e5c5a] mt-1">Last message <span x-text="timeAgo(chat.updated_at)"></span></span>
                                    </div>
                                </template>
                                <div class="border-t border-[#E5E5E5] dark:border-stone-800"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="lg:w-[380px] flex flex-col shrink-0 min-h-0 overflow-y-auto custom-scrollbar">
                    <div class="bg-white dark:bg-[#2C2A29] border border-[#E5E5E5] dark:border-stone-700 rounded-2xl overflow-hidden">
                        <div class="p-5 border-b border-[#E5E5E5] dark:border-stone-700 relative group">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-[15px] text-[#1a1a1a] dark:text-stone-200">Memory</h3>
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center gap-1 px-2 py-0.5 rounded-md border border-[#E5E5E5] dark:border-stone-700 text-[12px] text-[#5e5c5a]">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>Only you
                                    </span>
                                    <button class="text-[#1a1a1a] hover:text-[#5e5c5a] dark:text-stone-400 dark:hover:text-stone-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[13px] text-[#5e5c5a] dark:text-stone-400 leading-relaxed line-clamp-2 mt-3" x-text="selectedProject.description || 'Purpose & context for this project.'"></p>
                            <p class="text-[12px] text-[#A3A3A3] mt-2" x-text="'Last updated ' + timeAgo(selectedProject.updated_at)"></p>
                        </div>

                        <div class="p-5 border-b border-[#E5E5E5] dark:border-stone-700 relative group" x-data="{ expanded: false }">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-[15px] text-[#1a1a1a] dark:text-stone-200">Instructions</h3>
                                <button @click="expanded = !expanded" class="text-[#1a1a1a] dark:text-stone-200 transition-colors">
                                    <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <svg x-show="expanded" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                                </button>
                            </div>
                            <div x-show="!expanded" @click="expanded = true" class="cursor-text mt-1">
                                <p class="text-[13px] text-[#A3A3A3] truncate" x-text="customInstructions ? customInstructions.substring(0,50) + '...' : 'Add instructions to tailor responses'"></p>
                            </div>
                            <div x-show="expanded" x-cloak class="mt-3">
                                <textarea x-model="customInstructions" class="w-full bg-transparent border border-stone-200 dark:border-stone-700 rounded-lg p-3 resize-none text-[13px] text-[#1a1a1a] dark:text-stone-300 placeholder-[#A3A3A3] focus:outline-none focus:ring-1 focus:ring-[#D97757] min-h-[120px] transition-all" placeholder="Add instructions to tailor responses"></textarea>
                                <div class="mt-2 flex justify-end">
                                    <button @click="saveInstructions(); expanded = false" class="px-3 py-1.5 bg-[#EAE9E5] hover:bg-stone-300 dark:bg-stone-700 dark:hover:bg-stone-600 rounded-lg text-[12px] font-medium text-stone-700 dark:text-stone-200 transition-colors">Save</button>
                                </div>
                            </div>
                        </div>

                        <div class="p-5" x-data="{ expanded: false }">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-[15px] text-[#1a1a1a] dark:text-stone-200">Files</h3>
                                <button type="button" @click="$refs.fileInput.click()" class="cursor-pointer text-[#1a1a1a] dark:text-stone-200 transition-colors hover:text-stone-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </button>
                            </div>
                            <input type="file" x-ref="fileInput" multiple class="hidden" @change="uploadFiles($event)">

                            <div x-show="uploading" class="w-full text-center py-4">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="animate-spin w-6 h-6 text-[#D97757] mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span class="text-[13px] font-medium text-stone-600 dark:text-stone-300">Uploading...</span>
                                    <span class="text-[11px] text-stone-400 mt-0.5">Large files may take a moment</span>
                                </div>
                            </div>

                            <template x-if="!uploading">
                                <div>
                                    <template x-if="selectedProject.files && selectedProject.files.length > 0">
                                        <div class="space-y-1 mt-2">
                                            <template x-for="f in selectedProject.files" :key="f.id">
                                                <div class="flex items-center justify-between p-2 hover:bg-stone-50 dark:hover:bg-stone-800/50 rounded-lg group transition-colors -mx-2 cursor-pointer">
                                                    <div class="flex items-center gap-2.5 truncate">
                                                        <div class="w-6 h-6 rounded bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 flex items-center justify-center shrink-0">
                                                            <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                        </div>
                                                        <span class="text-[13px] text-stone-600 dark:text-stone-300 truncate" x-text="f.file_name"></span>
                                                    </div>
                                                    <button @click="deleteFile(f.id)" class="p-1.5 text-stone-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity rounded-md hover:bg-stone-200 dark:hover:bg-stone-700" title="Remove file">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!selectedProject.files || selectedProject.files.length === 0">
                                        <label @click="$refs.fileInput.click()" class="block cursor-pointer bg-[#FAFAFA] dark:bg-[#2C2A29] rounded-2xl p-6 text-center group relative transition-all border border-transparent dark:border-stone-800 hover:bg-[#F2F1EF] mt-4">
                                            <div class="flex justify-center mb-4">
                                                <div class="relative flex items-end">
                                                    <div class="w-[30px] h-[34px] bg-white border border-[#E5E5E5] rounded-md shadow-sm flex flex-col gap-[3px] p-[5px] -mr-1 z-0">
                                                        <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div><div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div><div class="w-3/4 h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    </div>
                                                    <div class="w-[34px] h-[40px] bg-white border border-[#E5E5E5] rounded-md shadow-md flex flex-col gap-[4px] p-[6px] z-10">
                                                        <div class="w-full h-[3px] bg-[#D97757] rounded-full mb-1"></div><div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div><div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div><div class="w-1/2 h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    </div>
                                                    <div class="w-[30px] h-[34px] bg-white border border-[#E5E5E5] rounded-md shadow-sm flex flex-col gap-[3px] p-[5px] -ml-1 z-0">
                                                        <div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div><div class="w-full h-[2px] bg-[#E5E5E5] rounded-full"></div><div class="w-5/6 h-[2px] bg-[#E5E5E5] rounded-full"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-[14px] text-[#1a1a1a] dark:text-stone-200 font-medium group-hover:underline decoration-[#1a1a1a]/30 underline-offset-2">Add project knowledge</p>
                                            <p class="text-[13px] text-[#5e5c5a] mt-1">Upload documents, code, or images</p>
                                        </label>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- ============ PROJECTS LIST VIEW ============ --}}
    <template x-if="!selectedProject || !selectedProject.id">
        <div class="max-w-[1000px] mx-auto w-full px-4 sm:px-8 py-6 sm:py-10 flex flex-col h-full overflow-y-auto custom-scrollbar">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h2 class="font-serif text-[28px] sm:text-[32px] text-[#2D2825] dark:text-stone-200">Projects</h2>
                <div class="flex items-center gap-3">
                    <div x-data="{ showSort: false }" class="relative">
                        <button @click="showSort = !showSort" @click.away="showSort = false" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 text-[13px] font-medium text-stone-600 dark:text-stone-400 hover:bg-black/5 dark:hover:bg-white/5 transition-colors bg-white dark:bg-transparent shadow-sm">
                            <span>Sort by <strong class="text-[#1a1a1a] dark:text-stone-200 font-semibold" x-text="sortLabel()"></strong></span>
                            <svg class="w-3.5 h-3.5 text-stone-400 transition-transform duration-200" :class="showSort ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="showSort" x-transition.opacity.duration.200ms x-cloak class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 rounded-xl shadow-lg overflow-hidden z-20">
                            <button @click="setSortBy('updated_at'); showSort = false" class="w-full text-left px-4 py-2.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 flex items-center justify-between transition-colors">
                                Last updated
                                <svg x-show="sortBy === 'updated_at'" class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </button>
                            <button @click="setSortBy('created_at'); showSort = false" class="w-full text-left px-4 py-2.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 flex items-center justify-between transition-colors">
                                Date created
                                <svg x-show="sortBy === 'created_at'" class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </button>
                            <button @click="setSortBy('name'); showSort = false" class="w-full text-left px-4 py-2.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800 flex items-center justify-between transition-colors">
                                Name
                                <svg x-show="sortBy === 'name'" class="w-4 h-4 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </button>
                        </div>
                    </div>
                    <button @click="showCreateForm = !showCreateForm" class="flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[13px] sm:text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> New project
                    </button>
                </div>
            </div>

            <div class="relative mb-8">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input x-model="searchQuery" @input.debounce.300ms="loadProjects()" type="text" placeholder="Search projects..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 dark:border-stone-700 bg-white dark:bg-[#2C2A29] text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#D97757]/30 focus:border-[#D97757] transition-all shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            </div>

            <template x-if="showCreateForm">
                <div class="p-6 border border-stone-200 dark:border-stone-700 rounded-2xl bg-white dark:bg-[#2C2A29] mb-8 shadow-sm animate-fade-in">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Project Name</label>
                            <input x-model="newProjectName" type="text" placeholder="e.g. Website Redesign"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-transparent text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#D97757]/30 focus:border-[#D97757] transition-all">
                            <template x-if="formErrors.name"><p class="mt-1 text-xs text-red-500" x-text="formErrors.name"></p></template>
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Description <span class="text-stone-400 font-normal">(optional)</span></label>
                            <textarea x-model="newProjectDescription" rows="2" placeholder="What is this project about?"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-transparent text-[14px] text-[#1a1a1a] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#D97757]/30 focus:border-[#D97757] transition-all resize-none"></textarea>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-6">
                            <div>
                                <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Icon</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="icon in projectIcons" :key="icon">
                                        <button type="button" @click="newProjectIcon = icon" class="w-9 h-9 rounded-lg flex items-center justify-center text-[18px] border transition-all" :class="newProjectIcon === icon ? 'border-[#D97757] ring-2 ring-[#D97757]/30 bg-[#D97757]/5' : 'border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800'" x-text="icon"></button>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-[#1a1a1a] dark:text-stone-300 mb-1.5">Color</label>
                                <div class="flex flex-wrap gap-2 items-center h-9">
                                    <template x-for="color in projectColors" :key="color">
                                        <button type="button" @click="newProjectColor = color" class="w-7 h-7 rounded-full transition-all" :class="newProjectColor === color ? 'ring-2 ring-offset-2 ring-stone-400 dark:ring-offset-[#2C2A29]' : 'hover:scale-110'" :style="'background-color: ' + color"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <button @click="createProject()" class="px-4 py-2 rounded-xl bg-[#2D2825] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white text-[13px] sm:text-[14px] font-medium transition-colors active:scale-95 shadow-sm">Create project</button>
                            <button @click="showCreateForm = false" class="px-4 py-2 rounded-lg border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-400 text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors">Cancel</button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="loading">
                <div class="flex items-center justify-center py-12"><svg class="animate-spin h-8 w-8 text-[#D97757]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></path></svg></div>
            </template>
            <template x-if="!loading && !showCreateForm && projects.length === 0">
                <div class="flex flex-col items-center justify-center flex-1 text-center py-12 animate-fade-in">
                    <div class="w-12 h-12 text-stone-300 dark:text-stone-600 mb-5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14.5 16.5l3.5 3.5 3.5-3.5"/><path d="M18 11v9"/></svg>
                    </div>
                    <h3 class="text-[18px] font-medium text-[#1a1a1a] dark:text-stone-200 mb-2">Create a project</h3>
                    <p class="text-[14px] text-stone-500 max-w-[340px] mb-6 leading-relaxed">Organize your conversations, upload documents, and define custom instructions for a specific task or team.</p>
                    <button @click="showCreateForm = true" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> New project
                    </button>
                </div>
            </template>
            <template x-if="!loading && projects.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 animate-fade-in">
                    <template x-for="project in projects" :key="project.id">
                        <div @click="selectProject(project.id)" x-data="{ menuOpen: false }" class="group relative p-5 rounded-2xl bg-white dark:bg-[#2C2A29] border border-stone-200 dark:border-stone-700 hover:border-stone-300 dark:hover:border-stone-500 shadow-[0_1px_2px_rgba(0,0,0,0.02)] hover:shadow-md transition-all cursor-pointer">

                            <button @click.stop="starProject(project.id)" class="absolute top-3 right-10 p-1.5 rounded-lg transition-all" :class="project.is_starred ? 'text-[#F5A623]' : 'text-stone-300 dark:text-stone-600 opacity-0 group-hover:opacity-100 hover:text-[#F5A623]'" title="Star">
                                <svg class="w-4 h-4" :fill="project.is_starred ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                            </button>

                            <button @click.stop="menuOpen = !menuOpen" @click.away="menuOpen = false" class="absolute top-3 right-3 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-stone-100 dark:hover:bg-stone-700 transition-all text-stone-400 hover:text-stone-600 dark:hover:text-stone-300" title="Options">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                            </button>
                            <div x-show="menuOpen" x-cloak class="absolute top-10 right-3 w-40 bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-30">
                                <button @click.stop="menuOpen = false; duplicateProject(project.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#1a1a1a] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Duplicate
                                </button>
                                <div class="h-px w-full bg-stone-200 dark:bg-stone-700 my-1"></div>
                                <button @click.stop="menuOpen = false; deleteProject(project.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
                                </button>
                            </div>

                            <div class="mb-4 flex items-start gap-3 pr-14">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-[20px] shrink-0" :style="'background-color: ' + project.color + '1A'">
                                    <span x-text="project.icon"></span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-[15px] font-semibold tracking-tight text-[#1a1a1a] dark:text-stone-200 truncate" x-text="project.name"></h3>
                                    <p class="text-[12px] text-stone-500 mt-0.5" x-text="'Updated ' + project.created_at"></p>
                                </div>
                            </div>
                            <p class="text-[13px] text-stone-600 dark:text-stone-400 line-clamp-2 mb-5 leading-relaxed h-[38px]" x-text="project.description || 'No description provided.'"></p>
                            <div class="flex items-center gap-1.5 text-[12px] text-stone-500 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.566 2.707-3.227V6.741c0-1.602-1.123-2.935-2.707-3.168A48.334 48.334 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.806 2.25 5.14 2.25 6.741v6.018z"/></svg>
                                <span x-text="project.chat_count + ' ' + (project.chat_count === 1 ? 'chat' : 'chats')"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function projectsPanelState() {
    return {
        projects: [],
        showCreateForm: false,
        newProjectName: '',
        newProjectDescription: '',
        newProjectColor: '#D97757',
        newProjectIcon: '📁',
        selectedProject: null,
        customInstructions: '',
        projectChatPrompt: '',
        sortBy: 'updated_at',
        searchQuery: '',
        loading: false,
        uploading: false,
        formErrors: {},
        selectedModel: 'claude-haiku-4-5',
        models: [],
        moreModels: [],
        projectColors: ['#D97757','#5E72E4','#11998E','#E0529C','#F5A623','#8B5CF6','#0EA5E9','#64748B'],
        projectIcons: ['📁','🚀','💡','📊','✍️','🎨','🔬','💻','📚','🎯'],

        init: function() {
            this.loadProjects();
            this.loadModels();
        },

        sortLabel: function() {
            return {updated_at:'Last updated',created_at:'Date created',name:'Name'}[this.sortBy] || 'Last updated';
        },

        selectedModelName: function() {
            var all = this.models.concat(this.moreModels);
            var m = all.find(function(m) { return m.code === this.selectedModel; }.bind(this));
            return m ? m.name : 'Select Model';
        },

        loadModels: function() {
            var self = this;
            this.models = [
                {code:'claude-opus-4-8',name:'Opus 4.8',description:'For complex tasks',is_available:false},
                {code:'claude-sonnet-4-6',name:'Sonnet 4.6',description:'Most efficient for everyday tasks',is_available:false},
                {code:'claude-haiku-4-5',name:'Haiku 4.5',description:'Fastest for quick answers',is_available:false},
            ];
            fetch('/api/settings', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        var u = resp.data.user || resp.data;
                        var hasAnthropic = u.anthropic_api_key || u.anthropicApiKey;
                        var hasOpenAI = u.openai_api_key || u.openaiApiKey;
                        var hasProxy = u.use_proxy && u.proxy_base_url;
                        var hasRouter = u.nine_router_api_key || u.nineRouterApiKey;
                        var hasHF = u.huggingface_api_key || u.huggingfaceApiKey;
                        var hasGoogle = u.google_api_key || u.googleApiKey;
                        var hasMistral = u.mistral_api_key || u.mistralApiKey;
                        var available = hasAnthropic || hasProxy || hasRouter || hasHF || hasGoogle || hasMistral;
                        self.models.forEach(function(m){
                            if (m.code === 'fable-5') m.is_available = false;
                            else m.is_available = available;
                        });
                        if (available) self.selectedModel = 'claude-haiku-4-5';
                    }
                    if (resp.more_models) {
                        self.moreModels = resp.more_models;
                    }
                });
        },

        loadProjects: function() {
            this.loading = true;
            var params = new URLSearchParams();
            params.append('sort', this.sortBy);
            if (this.searchQuery.trim()) params.append('search', this.searchQuery.trim());
            fetch('/api/projects?' + params.toString(), {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    this.projects = resp.data || [];
                    this.loading = false;
                }.bind(this))
                .catch(function(){this.loading=false;}.bind(this));
        },

        setSortBy: function(s) {
            this.sortBy = s;
            this.loadProjects();
        },

        selectProject: function(id) {
            this.loading = true;
            fetch('/api/projects/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    this.selectedProject = resp.data;
                    this.customInstructions = resp.data.custom_instructions || '';
                    this.loading = false;
                }.bind(this))
                .catch(function(){this.loading=false;}.bind(this));
        },

        backToList: function() {
            this.selectedProject = null;
            this.customInstructions = '';
            this.projectChatPrompt = '';
            this.loadProjects();
        },

        createProject: function() {
            this.formErrors = {};
            if (!this.newProjectName.trim()) { this.formErrors.name = 'Project name is required.'; return; }
            fetch('/api/projects', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({
                    name: this.newProjectName,
                    description: this.newProjectDescription,
                    color: this.newProjectColor,
                    icon: this.newProjectIcon
                })
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                this.newProjectName = '';
                this.newProjectDescription = '';
                this.newProjectColor = '#D97757';
                this.newProjectIcon = '📁';
                this.showCreateForm = false;
                if (resp.data) this.selectProject(resp.data.id);
                this.loadProjects();
            }.bind(this));
        },

        starProject: function(id) {
            var self = this;
            fetch('/api/projects/' + id, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({is_starred: true})
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                if (resp.data) {
                    if (self.selectedProject && self.selectedProject.id === id) {
                        self.selectedProject.is_starred = resp.data.is_starred;
                    }
                    self.loadProjects();
                }
            });
        },

        duplicateProject: function(id) {
            fetch('/api/projects/' + id + '/duplicate', {
                method: 'POST',
                headers: {'Accept':'application/json'}
            })
            .then(function(r){if(r.ok) this.loadProjects();}.bind(this));
        },

        deleteProject: function(id) {
            if (!confirm('Delete this project?')) return;
            fetch('/api/projects/' + id, {
                method: 'DELETE',
                headers: {'Accept':'application/json'}
            })
            .then(function(r){
                if (r.ok) {
                    if (this.selectedProject && this.selectedProject.id === id) this.backToList();
                    else this.loadProjects();
                }
            }.bind(this));
        },

        saveInstructions: function() {
            if (!this.selectedProject || !this.selectedProject.id) return;
            fetch('/api/projects/' + this.selectedProject.id, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({custom_instructions: this.customInstructions})
            });
        },

        uploadFiles: function(event) {
            var files = event.target.files;
            if (!files || files.length === 0 || !this.selectedProject) return;
            this.uploading = true;
            var promises = [];
            for (var i = 0; i < files.length; i++) {
                var fd = new FormData();
                fd.append('file', files[i]);
                promises.push(
                    fetch('/api/projects/' + this.selectedProject.id + '/files', {
                        method: 'POST',
                        headers: {'Accept':'application/json'},
                        body: fd
                    }).then(function(r){return r.json()})
                );
            }
            Promise.all(promises).then(function(){
                this.uploading = false;
                event.target.value = '';
                return this.refreshProject();
            }.bind(this));
        },

        deleteFile: function(fileId) {
            if (!this.selectedProject) return;
            fetch('/api/projects/' + this.selectedProject.id + '/files/' + fileId, {
                method: 'DELETE',
                headers: {'Accept':'application/json'}
            })
            .then(function(r){if(r.ok) this.refreshProject();}.bind(this));
        },

        refreshProject: function() {
            if (!this.selectedProject) return;
            fetch('/api/projects/' + this.selectedProject.id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) this.selectedProject = resp.data;
                }.bind(this));
        },

        startNewChatInProject: function() {
            if (!this.selectedProject || !this.projectChatPrompt.trim()) return;
            var prompt = this.projectChatPrompt;
            this.projectChatPrompt = '';
            window.dispatchEvent(new CustomEvent('close-panel'));
            window.dispatchEvent(new CustomEvent('startProjectChat', {
                detail: {
                    projectId: this.selectedProject.id,
                    initialPrompt: prompt,
                    initialModel: this.selectedModel,
                }
            }));
        },

        openProjectChat: function(chatId) {
            window.dispatchEvent(new CustomEvent('close-panel'));
            window.dispatchEvent(new CustomEvent('openChat', {detail: {chatId: chatId}}));
            window.history.pushState({}, '', '/chat?conversation=' + chatId);
        },

        timeAgo: function(iso) {
            if (!iso) return '';
            var d = new Date(iso);
            var now = new Date();
            var sec = Math.floor((now - d) / 1000);
            if (sec < 60) return 'just now';
            var min = Math.floor(sec / 60);
            if (min < 60) return min + 'm ago';
            var hr = Math.floor(min / 60);
            if (hr < 24) return hr + 'h ago';
            var days = Math.floor(hr / 24);
            if (days < 7) return days + 'd ago';
            return d.toLocaleDateString();
        },
    };
}
</script>
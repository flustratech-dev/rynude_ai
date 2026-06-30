<div
    x-data="settingsState()"
    x-init="init(); $watch('open', val => { if (val) document.body.classList.add('overflow-hidden'); else document.body.classList.remove('overflow-hidden'); })"
    @keydown.escape.window="open = false"
    @open-settings-ui.window="open = true; activeTab = $event.detail || 'general'"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50"
>
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-stone-900/40 backdrop-blur-sm" @click="open = false"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             @click.away="if (!isModelModalOpen) open = false"
             class="bg-claude-bg-light dark:bg-claude-bg-dark w-full max-w-[900px] h-[95vh] md:h-[85vh] max-h-[700px] rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden border border-claude-border-light dark:border-claude-border-dark relative">

            {{-- Sidebar tabs --}}
            <div class="w-full md:w-[260px] bg-claude-bg-light dark:bg-claude-bg-dark border-b md:border-b-0 md:border-r border-claude-border-light dark:border-claude-border-dark p-3 md:p-4 flex md:flex-col gap-2 md:gap-1 flex-shrink-0 overflow-x-auto scrollbar-hide">
                {{-- Search --}}
                <div class="relative hidden md:block mb-4">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="searchQuery" placeholder="Search" class="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-lg text-sm focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400 dark:placeholder-stone-500 text-gray-800 dark:text-stone-200">
                </div>

                <div class="px-3 py-1 hidden md:block mb-1">
                    <span class="text-xs font-medium text-gray-500 dark:text-stone-400">Settings</span>
                </div>

                <div class="flex md:flex-col gap-2 md:gap-1 overflow-x-auto md:overflow-visible">
                    <template x-for="item in filteredNavItems" :key="item.id">
                        <button @click="activeTab = item.id" class="w-auto md:w-full flex items-center gap-2 md:gap-3 px-3 md:px-3 py-2 md:py-2.5 rounded-xl text-[13px] md:text-[14px] transition-all duration-150 whitespace-nowrap"
                                :class="activeTab === item.id ? 'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#6B6B6B] dark:text-stone-400 hover:bg-claude-bg-light dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200'">
                            <svg class="w-[18px] h-[18px] flex-shrink-0" :class="activeTab === item.id ? 'text-[#2D2825] dark:text-stone-200' : 'text-[#6B6B6B] dark:text-stone-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-html="item.icon"></svg>
                            <span x-text="item.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Main content --}}
            <div class="flex-1 bg-claude-bg-light dark:bg-claude-bg-dark p-6 md:p-10 overflow-y-auto relative">
                <button @click="open = false" class="absolute top-6 right-6 z-10 p-1.5 rounded-lg text-gray-500 dark:text-stone-400 hover:text-gray-800 dark:hover:text-stone-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div x-show="flashMessage" x-cloak x-effect="if (flashMessage) { clearTimeout($data._flashTimer); $data._flashTimer = setTimeout(() => flashMessage = null, 3000); }" class="mb-4 p-3 text-sm rounded-lg border" :class="flashType === 'success' ? 'text-green-800 bg-green-50 dark:bg-stone-800 dark:text-green-400 border-green-200 dark:border-stone-700' : 'text-red-800 bg-red-50 dark:bg-stone-800 dark:text-red-400 border-red-200 dark:border-stone-700'" x-text="flashMessage"></div>

                {{-- General tab --}}
                <div x-show="activeTab === 'general'" x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Profile</h2>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Avatar</label>
                            <div class="w-10 h-10 rounded-full bg-[#D97757] flex items-center justify-center text-sm font-medium text-white shadow-sm" x-text="initials"></div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Full name</label>
                            <input x-model="name" @input.debounce.500ms="save('name')" type="text" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">What should Rynude call you?</label>
                            <input x-model="nickname" @input.debounce.500ms="save('nickname')" type="text" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">What best describes your work?</label>
                            <select x-model="profession" @change="save('profession')" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                                <option value="">Select...</option><option value="developer">Software Developer</option><option value="designer">Designer</option><option value="data_scientist">Data Scientist</option><option value="product_manager">Product Manager</option><option value="student">Student</option><option value="researcher">Researcher</option><option value="writer">Writer / Content Creator</option><option value="marketer">Marketer</option><option value="business">Business / Entrepreneur</option><option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-[15px] text-[#2D2825] dark:text-stone-200 mb-1">Instructions for Rynude</h3>
                        <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-4">
                            Rynude will keep these in mind across chats and Cowork within <a href="#" class="underline hover:text-gray-800 dark:hover:text-stone-200">Anthropic's guidelines</a>. <a href="#" class="underline hover:text-gray-800 dark:hover:text-stone-200">Learn more</a>
                        </p>
                        <textarea x-model="customInstructions" @input.debounce.1000ms="save('custom_instructions')" class="w-full h-24 p-3 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 resize-none" placeholder="e.g. keep explanations brief and to the point"></textarea>
                    </div>

                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mt-12 mb-6">Preferences</h2>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Appearance</label>
                            <div class="flex items-center border border-claude-border-light dark:border-claude-border-dark rounded-lg overflow-hidden bg-white dark:bg-stone-800">
                                <button @click="theme='system'; saveAppearance()" class="p-1.5 px-3 border-r border-claude-border-light dark:border-claude-border-dark transition-colors" :class="theme==='system'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'" title="System Theme">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </button>
                                <button @click="theme='light'; saveAppearance()" class="p-1.5 px-3 border-r border-claude-border-light dark:border-claude-border-dark transition-colors" :class="theme==='light'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'" title="Light Theme">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path></svg>
                                </button>
                                <button @click="theme='dark'; saveAppearance()" class="p-1.5 px-3 transition-colors" :class="theme==='dark'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'" title="Dark Theme">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 block">Response language</label><p class="text-[13px] text-gray-500 dark:text-stone-400">The language Rynude will reply in.</p></div>
                            <select x-model="language" @change="save('language')" class="w-[200px] px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                                <option value="en">English</option><option value="id">Bahasa Indonesia</option><option value="es">Español</option><option value="fr">Français</option><option value="de">Deutsch</option><option value="ja">日本語</option><option value="zh">中文</option><option value="ar">العربية</option>
                            </select>
                        </div>
                        <div class="flex items-center justify-between pb-8">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Chat font</label>
                            <select x-model="chatFont" @change="save('chat_font')" class="w-[200px] px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                                <option value="default">Default (System)</option><option value="serif">Serif</option><option value="mono">Monospace</option><option value="inter">Inter</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Appearance tab --}}
                <div x-show="activeTab === 'appearance'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Appearance</h2>
                    <div class="space-y-8">
                        <div class="flex items-center justify-between">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block">Theme</label><p class="text-[13px] text-gray-500 dark:text-stone-400">Choose how Rynude looks to you.</p></div>
                            <div class="flex items-center border border-claude-border-light dark:border-claude-border-dark rounded-lg overflow-hidden bg-white dark:bg-stone-800">
                                <button @click="theme='light'; saveAppearance()" class="px-3 py-1.5 text-sm border-r border-claude-border-light dark:border-claude-border-dark transition-colors" :class="theme==='light'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'">Light</button>
                                <button @click="theme='dark'; saveAppearance()" class="px-3 py-1.5 text-sm border-r border-claude-border-light dark:border-claude-border-dark transition-colors" :class="theme==='dark'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'">Dark</button>
                                <button @click="theme='system'; saveAppearance()" class="px-3 py-1.5 text-sm transition-colors" :class="theme==='system'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'">System</button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block">Font size</label><p class="text-[13px] text-gray-500 dark:text-stone-400">Adjust the text size in chats.</p></div>
                            <div class="flex items-center border border-claude-border-light dark:border-claude-border-dark rounded-lg overflow-hidden bg-white dark:bg-stone-800">
                                <button @click="fontSize='small'; saveAppearance()" class="px-3 py-1.5 transition-colors border-r border-claude-border-light dark:border-claude-border-dark" style="font-size:12px" :class="fontSize==='small'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'">A</button>
                                <button @click="fontSize='medium'; saveAppearance()" class="px-3 py-1.5 transition-colors border-r border-claude-border-light dark:border-claude-border-dark" style="font-size:15px" :class="fontSize==='medium'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'">A</button>
                                <button @click="fontSize='large'; saveAppearance()" class="px-3 py-1.5 transition-colors" style="font-size:18px" :class="fontSize==='large'?'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200':'text-gray-600 dark:text-stone-400'">A</button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block">Accent color</label><p class="text-[13px] text-gray-500 dark:text-stone-400">Used for highlights and buttons.</p></div>
                            <div class="flex items-center gap-2">
                                <template x-for="color in accentColors" :key="color">
                                    <button @click="accentColor=color; saveAppearance()" class="w-7 h-7 rounded-full transition-all" :class="accentColor===color?'ring-2 ring-offset-2 ring-stone-400 dark:ring-offset-stone-900':'hover:scale-110'" :style="'background-color:'+color"></button>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block">Compact mode</label><p class="text-[13px] text-gray-500 dark:text-stone-400">Reduce spacing to fit more on screen.</p></div>
                            <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 cursor-pointer" :class="compactMode?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="compactMode=!compactMode; saveAppearance()">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="compactMode?'translate-x-5':'translate-x-[2px]'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account tab --}}
                <div x-show="activeTab === 'account'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Account</h2>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Email address</label>
                            <div class="text-[15px] text-gray-500 dark:text-stone-400" x-text="email"></div>
                        </div>
                        <div class="flex items-center justify-between border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Full name</label>
                            <div class="text-[15px] text-gray-500 dark:text-stone-400" x-text="name"></div>
                        </div>
                        <div class="flex items-start justify-between border-t border-red-100 dark:border-red-900/40 pt-6">
                            <div>
                                <label class="text-[15px] text-red-600 dark:text-red-400 font-medium block mb-1">Delete account</label>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Permanently delete your account and all of its contents from Rynude. This action cannot be undone.</p>
                            </div>
                            <button @click="deleteAccount()" class="px-4 py-2 bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-100">Delete account</button>
                        </div>
                    </div>
                </div>

                {{-- Data & Privacy tab --}}
                <div x-show="activeTab === 'data'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Data & Privacy</h2>
                    <div class="space-y-8">
                        <div class="flex items-start justify-between">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Export all chats</label><p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Download a complete copy of all your conversations as a JSON file.</p></div>
                            <button @click="exportAllChats('json')" class="px-4 py-2 border border-claude-border-light dark:border-claude-border-dark text-[#2D2825] dark:text-stone-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-stone-800">Export JSON</button>
                        </div>
                        <div class="flex items-start justify-between border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Train on conversations</label><p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Allow Rynude to use your conversations to improve the models.</p></div>
                            <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 cursor-pointer flex-shrink-0 mt-1" :class="allowTraining?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="allowTraining=!allowTraining; save('allow_training')">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="allowTraining?'translate-x-5':'translate-x-[2px]'"></span>
                            </div>
                        </div>
                        <div class="flex items-start justify-between border-t border-red-100 dark:border-red-900/40 pt-6">
                            <div><label class="text-[15px] text-red-600 dark:text-red-400 font-medium block mb-1">Delete all chats</label><p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Permanently delete all of your conversations. This action cannot be undone.</p></div>
                            <button @click="deleteAllChats()" class="px-4 py-2 bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-100">Delete all</button>
                        </div>
                    </div>
                </div>

                {{-- Shortcuts tab --}}
                <div x-show="activeTab === 'shortcuts'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Keyboard Shortcuts</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                        <template x-for="sc in [
                            ['New chat', ['Ctrl', 'K']],
                            ['Send message', ['Ctrl', 'Enter']],
                            ['New line', ['Shift', 'Enter']],
                            ['Toggle sidebar', ['Ctrl', 'Shift', 'S']],
                            ['Open settings', ['Ctrl', 'Shift', ',']],
                            ['Show shortcuts', ['Ctrl', '/']],
                            ['Search chats', ['Ctrl', 'F']],
                            ['Close panel / modal', ['Esc']],
                        ]">
                            <div class="flex items-center justify-between py-2 border-b border-claude-border-light dark:border-claude-border-dark">
                                <span class="text-[14px] text-[#2D2825] dark:text-stone-300" x-text="sc[0]"></span>
                                <div class="flex items-center gap-1">
                                    <template x-for="k in sc[1]">
                                        <kbd class="px-2 py-1 bg-[#F3F2EE] dark:bg-stone-800 border border-claude-border-light dark:border-claude-border-dark rounded-md text-[12px] font-mono text-gray-600 dark:text-stone-300 shadow-sm" x-text="k"></kbd>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Privacy tab --}}
                <div x-show="activeTab === 'privacy'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Privacy</h2>
                    <div class="space-y-8">
                        <div>
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Train on your conversations</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Allow Rynude to use your conversations to train our models. This helps us improve Rynude for everyone.</p>
                                </div>
                                <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 cursor-pointer mt-1" :class="allowTraining?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="allowTraining=!allowTraining; save('allow_training')">
                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="allowTraining?'translate-x-5':'translate-x-[2px]'"></span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Export data</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Download a complete copy of all your conversations as a JSON file.</p>
                                </div>
                                <button @click="exportAllChats('json')" class="px-4 py-2 border border-claude-border-light dark:border-claude-border-dark text-[#2D2825] dark:text-stone-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-stone-800">Export data</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing tab --}}
                <div x-show="activeTab === 'billing'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Quota & Usage</h2>
                    <div class="p-5 bg-[#FBFBFA] dark:bg-stone-800/50 border border-claude-border-light dark:border-claude-border-dark rounded-xl mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[15px] font-medium">Token usage</span>
                            <span class="text-[13px] text-gray-500" x-text="tokensUsed.toLocaleString()+' used · '+tokensLimit.toLocaleString()+' remaining'"></span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-100 dark:bg-stone-700 rounded-full overflow-hidden">
                            <div class="h-full bg-[#D97757] rounded-full" :style="'width:'+Math.min(100,Math.round(tokensUsed/Math.max(1,tokensUsed+tokensLimit)*100))+'%'"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <template x-for="row in tokenBreakdown" :key="row.model">
                            <div class="flex items-center justify-between text-[13px] py-1.5 border-b border-stone-100 dark:border-stone-800">
                                <span class="font-medium text-stone-800 dark:text-stone-200" x-text="row.model"></span>
                                <span class="text-stone-500" x-text="row.total.toLocaleString()"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Capabilities tab --}}
                <div x-show="activeTab === 'capabilities'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Capabilities</h2>
                    <div class="space-y-8">
                        <div class="flex items-start justify-between">
                            <div><label class="text-[15px] font-medium text-stone-800 dark:text-stone-200">Web Search</label><p class="text-[13.5px] text-gray-500">Allow searching the web for up-to-date information.</p></div>
                            <div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer mt-1" :class="capWebSearch?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="capWebSearch=!capWebSearch; save('cap_web_search')">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow" :class="capWebSearch?'translate-x-5':'translate-x-[2px]'"></span>
                            </div>
                        </div>
                        <div class="border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div class="flex items-start justify-between">
                                <div><label class="text-[15px] font-medium text-stone-800 dark:text-stone-200">Artifacts</label><p class="text-[13.5px] text-gray-500">Generate standalone artifacts like code and documents.</p></div>
                                <div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer mt-1" :class="capArtifacts?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="capArtifacts=!capArtifacts; save('cap_artifacts')">
                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow" :class="capArtifacts?'translate-x-5':'translate-x-[2px]'"></span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div class="flex items-start justify-between">
                                <div><label class="text-[15px] font-medium text-stone-800 dark:text-stone-200">Code Execution</label><p class="text-[13.5px] text-gray-500">Run code in a secure sandbox.</p></div>
                                <div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer mt-1" :class="capCodeExecution?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="capCodeExecution=!capCodeExecution; save('cap_code_execution')">
                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow" :class="capCodeExecution?'translate-x-5':'translate-x-[2px]'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Connectors tab --}}
                <div x-show="activeTab === 'connectors'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Connectors</h2>
                    <p class="text-[14px] text-gray-500 dark:text-stone-400 mb-6">Connect Rynude to your tools to let it read context and perform actions on your behalf.</p>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-claude-border-light dark:border-claude-border-dark rounded-xl bg-white dark:bg-stone-800/50">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-[#F3F2EE] dark:bg-stone-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-700 dark:text-stone-300" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.012 21.314l-8.914-5.145V5.88l8.914-5.145 8.914 5.145v10.289l-8.914 5.145zM4.698 15.34l7.314 4.22 7.314-4.22V6.898l-7.314-4.22-7.314 4.22v8.441z" />
                                        <path d="M12.012 17.514l-5.614-3.245V7.78l5.614-3.245 5.614 3.245v6.489l-5.614 3.245zM8.098 13.54l3.914 2.22 3.914-2.22V9.098l-3.914-2.22-3.914 2.22v4.441z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Google Drive</h4>
                                    <p class="text-[13px] text-gray-500 dark:text-stone-400 mt-0.5">Access docs, sheets, and presentations.</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-[#F3F2EE] dark:bg-stone-700 text-[#2D2825] dark:text-stone-200 rounded-lg text-sm font-medium hover:bg-[#EAE9E5] dark:hover:bg-stone-600 transition-colors">Connect</button>
                        </div>
                        <div class="flex items-center justify-between p-4 border border-claude-border-light dark:border-claude-border-dark rounded-xl bg-white dark:bg-stone-800/50">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-[#F3F2EE] dark:bg-stone-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-700 dark:text-stone-300" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.686c.223-.198-.054-.31-.346-.11l-6.4 4.024-2.76-.86c-.6-.185-.61-.6.125-.89l10.736-4.136c.498-.19.958.115.828.913z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Telegram</h4>
                                    <p class="text-[13px] text-gray-500 dark:text-stone-400 mt-0.5">Read messages and send replies.</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-[#F3F2EE] dark:bg-stone-700 text-[#2D2825] dark:text-stone-200 rounded-lg text-sm font-medium hover:bg-[#EAE9E5] dark:hover:bg-stone-600 transition-colors">Connect</button>
                        </div>
                    </div>
                </div>

                {{-- Rynude Code tab --}}
                <div x-show="activeTab === 'claude-code'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Rynude Code</h2>
                    <p class="text-[14px] text-gray-500 dark:text-stone-400 mb-6">Rynude Code is an AI coding assistant that lives in your terminal. It understands your codebase and helps you write code faster.</p>
                    
                    <div class="p-6 border border-claude-border-light dark:border-claude-border-dark rounded-xl bg-gray-50 dark:bg-stone-800/30 mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-[#2D2825] dark:bg-stone-900 text-white flex items-center justify-center font-mono text-sm">$&gt;</div>
                            <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Install via npm</h3>
                        </div>
                        <div class="bg-gray-900 text-gray-300 font-mono text-[13px] p-4 rounded-lg flex items-center justify-between">
                            <span>npm install -g @anthropic-ai/rynude-code</span>
                            <button class="text-gray-400 hover:text-white transition-colors" title="Copy to clipboard" onclick="navigator.clipboard.writeText('npm install -g @anthropic-ai/rynude-code')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-3">Authentication</h3>
                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-4">Run the following command in your terminal to authenticate with your Anthropic account:</p>
                    <div class="bg-gray-900 text-gray-300 font-mono text-[13px] p-4 rounded-lg flex items-center justify-between mb-6">
                        <span>rynude auth login</span>
                        <button class="text-gray-400 hover:text-white transition-colors" title="Copy to clipboard" onclick="navigator.clipboard.writeText('rynude auth login')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Add/Edit Model Dialog --}}
    <div x-show="isModelModalOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm" @click="isModelModalOpen = false"></div>
        <div class="bg-white dark:bg-stone-900 border border-claude-border-light dark:border-claude-border-dark w-full max-w-md rounded-xl p-6 shadow-2xl relative z-10">
            <h3 class="text-lg font-bold text-stone-800 dark:text-stone-100 mb-4" x-text="editModelId ? 'Edit AI Model' : 'Add AI Model'"></h3>
            <div x-show="modelError" x-cloak class="mb-4 p-3 text-sm rounded-lg border text-red-800 bg-red-50 dark:bg-red-900/20 dark:text-red-400 border-red-200 dark:border-red-900/40" x-text="modelError"></div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Model Code</label>
                    <input type="text" x-model="modelCode" placeholder="e.g. meta-llama/Llama-3" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Model Name</label>
                    <input type="text" x-model="modelName" placeholder="e.g. Llama 3" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Provider</label>
                    <select x-model="modelProvider" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                        <option value="huggingface">Hugging Face</option>
                        <option value="openai">OpenAI</option>
                        <option value="anthropic">Anthropic</option>
                        <option value="google">Google</option>
                        <option value="mistral">Mistral</option>
                        <option value="ollama">Ollama (Local)</option>
                        <option value="proxy">9Router / Proxy</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="modelIsActiveChk" x-model="modelIsActive" class="w-4 h-4 text-[#D97757] rounded border-stone-300 focus:ring-[#D97757]">
                    <label for="modelIsActiveChk" class="ml-2 text-sm text-stone-600 dark:text-stone-400">Set as Active</label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-stone-100 dark:border-stone-800">
                <button @click="isModelModalOpen = false" class="px-3 py-1.5 text-sm text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg">Cancel</button>
                <button @click="storeModel()" :disabled="modelSaving" class="px-4 py-1.5 text-sm text-white bg-[#D97757] hover:bg-[#c66547] rounded-lg disabled:opacity-60 disabled:cursor-not-allowed" x-text="modelSaving ? 'Saving...' : 'Save'">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function settingsState() {
    return {
        open: false, activeTab: 'general', searchQuery: '', flashMessage: null, flashType: 'success',
        name: '', email: '', nickname: '', profession: '', customInstructions: '',
        language: 'en', chatFont: 'default', theme: 'light', fontSize: 'medium', accentColor: '#D97757', compactMode: false,
        allowTraining: false, capWebSearch: true, capArtifacts: true, capCodeExecution: false,
        anthropicApiKey: '', openaiApiKey: '', nineRouterApiKey: '', googleApiKey: '', mistralApiKey: '',
        useProxy: false, proxyBaseUrl: '', proxyApiKey: '', huggingfaceApiKey: '',
        tokensUsed: 0, tokensLimit: 0, tokenBreakdown: [], aiModels: [],
        accentColors: ['#D97757','#5E72E4','#11998E','#E0529C','#F5A623','#8B5CF6'],

        // Custom models dialog states
        isModelModalOpen: false, editModelId: null, modelCode: '', modelName: '', modelIsActive: true, modelProvider: 'huggingface', modelError: null, modelSaving: false,

        navItems: [
            {id:'general',label:'General',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\"></path>'},
            {id:'appearance',label:'Appearance',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z\"></path>'},
            {id:'account',label:'Account',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z\"></path>'},
            {id:'data',label:'Data & Privacy',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75\"></path>'},
            {id:'shortcuts',label:'Shortcuts',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122\"></path>'},
            {id:'privacy',label:'Privacy',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z\"></path>'},
            {id:'billing',label:'Billing',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z\"></path>'},
            {id:'capabilities',label:'Capabilities',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z\"></path>'},
            {id:'connectors',label:'Connectors',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z\"></path>'},
            {id:'claude-code',label:'Rynude Code',icon:'<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5\"></path>'}
        ],

        get initials() { return this.name ? this.name.split(' ').map(s => s[0]).join('').toUpperCase().slice(0,2) : '?'; },

        get filteredNavItems() {
            var q = this.searchQuery.trim().toLowerCase();
            if (!q) return this.navItems;
            return this.navItems.filter(item => item.label.toLowerCase().includes(q));
        },

        init: function() { this.loadSettings(); },

        loadSettings: function() {
            fetch('/api/settings', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    var profile = resp.profile || {};
                    var prefs = resp.preferences || {};
                    var billing = resp.billing || {};
                    var keys = resp.api_keys || {};
                    this.name = profile.name || '';
                    this.email = profile.email || '';
                    this.nickname = profile.nickname || '';
                    this.profession = profile.profession || '';
                    this.customInstructions = profile.custom_instructions || '';
                    this.theme = prefs.theme || 'light';
                    this.fontSize = prefs.font_size || 'medium';
                    this.accentColor = prefs.accent_color || '#D97757';
                    this.compactMode = prefs.compact_mode || false;
                    this.allowTraining = prefs.allow_training || false;
                    this.capWebSearch = prefs.cap_web_search !== false;
                    this.capArtifacts = prefs.cap_artifacts !== false;
                    this.capCodeExecution = prefs.cap_code_execution || false;
                    this.tokensUsed = billing.tokens_used || 0;
                    this.tokensLimit = billing.tokens_limit || 0;
                    this.tokenBreakdown = billing.token_breakdown || [];
                    this.aiModels = resp.ai_models || [];

                    // API Keys
                    this.anthropicApiKey = keys.anthropic ? '••••••••••••••••' : '';
                    this.openaiApiKey = keys.openai ? '••••••••••••••••' : '';
                    this.googleApiKey = keys.google ? '••••••••••••••••' : '';
                    this.mistralApiKey = keys.mistral ? '••••••••••••••••' : '';
                    this.nineRouterApiKey = keys.nine_router ? '••••••••••••••••' : '';
                    this.useProxy = keys.use_proxy || false;
                    this.proxyBaseUrl = keys.proxy_base_url || '';
                    this.proxyApiKey = keys.proxy_api_key_set ? '••••••••••••••••' : '';
                    this.huggingfaceApiKey = keys.huggingface_api_key_set ? '••••••••••••••••' : '';
                    this.huggingfaceBaseUrl = keys.huggingface_base_url || 'https://api-inference.huggingface.co/v1';
                }.bind(this));
        },

        save: function(field) {
            var payload = {};
            var keyMap = {name:'name',nickname:'nickname',profession:'profession',custom_instructions:'customInstructions',
                allow_training:'allowTraining',cap_web_search:'capWebSearch',cap_artifacts:'capArtifacts',cap_code_execution:'capCodeExecution'};
            var fieldKey = keyMap[field] || field;
            payload[field] = this[fieldKey] !== undefined ? this[fieldKey] : this[field];
            this._patch(payload);
        },

        saveAppearance: function() {
            // Apply theme IMMEDIATELY by directly manipulating DOM
            var theme = this.theme;
            var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Save to localStorage
            localStorage.setItem('theme', theme);

            // Log for debugging
            console.log('Theme changed to:', theme, 'Dark class applied:', isDark);

            // Dispatch event to sync with other components
            document.dispatchEvent(new CustomEvent('theme-changed', {detail:{theme:theme}}));

            // Save to API in background
            this._patch({theme:theme,font_size:this.fontSize,accent_color:this.accentColor,compact_mode:this.compactMode});
        },

        saveApiKeys: function() {
            var payload = {
                use_proxy: this.useProxy,
                proxy_base_url: this.proxyBaseUrl,
            };
            if (this.anthropicApiKey && this.anthropicApiKey !== '••••••••••••••••') payload.anthropic_api_key = this.anthropicApiKey;
            if (this.openaiApiKey && this.openaiApiKey !== '••••••••••••••••') payload.openai_api_key = this.openaiApiKey;
            if (this.googleApiKey && this.googleApiKey !== '••••••••••••••••') payload.google_api_key = this.googleApiKey;
            if (this.mistralApiKey && this.mistralApiKey !== '••••••••••••••••') payload.mistral_api_key = this.mistralApiKey;
            if (this.nineRouterApiKey && this.nineRouterApiKey !== '••••••••••••••••') payload.nine_router_api_key = this.nineRouterApiKey;
            if (this.proxyApiKey && this.proxyApiKey !== '••••••••••••••••') payload.proxy_api_key = this.proxyApiKey;

            this._patch(payload).then(function() {
                this.flashMessage = 'API Keys saved successfully!';
                this.flashType = 'success';
                this.loadSettings();
            }.bind(this));
        },

        saveHuggingface: function() {
            var payload = {
                huggingface_base_url: this.huggingfaceBaseUrl
            };
            if (this.huggingfaceApiKey && this.huggingfaceApiKey !== '••••••••••••••••') {
                payload.huggingface_api_key = this.huggingfaceApiKey;
            }
            this._patch(payload).then(function() {
                this.flashMessage = 'Hugging Face settings saved successfully!';
                this.flashType = 'success';
                this.loadSettings();
            }.bind(this));
        },

        _patch: function(data) {
            return fetch('/api/settings', {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify(data)
            }).then(function(r){return r.json()});
        },

        deleteAllChats: function() {
            if (!confirm('Are you sure? This will permanently delete ALL your chats.')) return;
            var self = this;
            this._patch({_action: 'delete_chats'}).then(function() {
                self.flashMessage = 'All chats have been deleted.';
                self.flashType = 'success';
                self.loadSettings();
                document.dispatchEvent(new CustomEvent('chatCreated'));
            });
        },

        deleteAccount: function() {
            if (!confirm('Are you absolutely sure? This will permanently delete your account, all conversations, projects, and data. This cannot be undone.')) return;
            this._patch({_action: 'delete_account'}).then(function(resp) {
                if (resp && resp.redirect) {
                    window.location.href = resp.redirect;
                }
            });
        },

        // Models CRUD
        createModel: function() {
            this.editModelId = null;
            this.modelCode = '';
            this.modelName = '';
            this.modelIsActive = true;
            this.modelProvider = 'huggingface';
            this.modelError = null;
            this.modelSaving = false;
            this.isModelModalOpen = true;
        },

        createModelHF: function() {
            this.editModelId = null;
            this.modelCode = '';
            this.modelName = '';
            this.modelIsActive = true;
            this.modelProvider = 'huggingface';
            this.modelError = null;
            this.modelSaving = false;
            this.isModelModalOpen = true;
        },

        editModel: function(model) {
            this.editModelId = model.id;
            this.modelCode = model.code;
            this.modelName = model.name;
            this.modelIsActive = model.is_active;
            this.modelProvider = model.provider || 'huggingface';
            this.modelError = null;
            this.modelSaving = false;
            this.isModelModalOpen = true;
        },

        storeModel: function() {
            var self = this;
            this.modelError = null;

            // Client-side guard so the user gets immediate feedback instead of a
            // silent server-side validation rejection.
            if (!this.modelCode.trim()) { this.modelError = 'Model Code is required.'; return; }
            if (!this.modelName.trim()) { this.modelError = 'Model Name is required.'; return; }

            this.modelSaving = true;
            fetch('/api/settings', {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({
                    _action: 'store_model',
                    model_id: this.editModelId,
                    model_code: this.modelCode.trim(),
                    model_name: this.modelName.trim(),
                    model_provider: this.modelProvider,
                    model_is_active: this.modelIsActive
                })
            }).then(function(r) {
                return r.json().catch(function(){ return {}; }).then(function(data) {
                    return { status: r.status, ok: r.ok, data: data };
                });
            }).then(function(res) {
                self.modelSaving = false;
                if (res.ok) {
                    // Success: refresh the list from the fresh payload and close.
                    if (res.data && res.data.ai_models) self.aiModels = res.data.ai_models;
                    self.isModelModalOpen = false;
                    self.flashMessage = self.editModelId ? 'Model updated successfully!' : 'Model added successfully!';
                    self.flashType = 'success';
                    self.loadSettings();
                    return;
                }
                // Surface the server error and keep the modal open.
                if (res.status === 422 && res.data && res.data.errors) {
                    var firstKey = Object.keys(res.data.errors)[0];
                    self.modelError = res.data.errors[firstKey][0];
                } else if (res.status === 419) {
                    self.modelError = 'Your session expired. Please refresh the page and try again.';
                } else {
                    self.modelError = (res.data && res.data.message) ? res.data.message : 'Failed to save model. Please try again.';
                }
            }).catch(function() {
                self.modelSaving = false;
                self.modelError = 'Network error. Please check your connection and try again.';
            });
        },

        toggleModelActive: function(model) {
            var self = this;
            this._patch({
                _action: 'toggle_model',
                model_id: model.id
            }).then(function() {
                self.loadSettings();
            });
        },

        deleteModel: function(model) {
            if (!confirm('Delete this AI model?')) return;
            var self = this;
            this._patch({
                _action: 'delete_model',
                model_id: model.id
            }).then(function() {
                self.loadSettings();
            });
        },

        validateKey: function(provider, key) {
            if (!key || key === '••••••••••••••••') return;
            fetch('/api/settings/validate-api-key', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({provider: provider, key: key})
            }).then(function(r) { return r.json(); }).then(function(resp) {
                alert(resp.message);
            });
        }
    };
}
</script>
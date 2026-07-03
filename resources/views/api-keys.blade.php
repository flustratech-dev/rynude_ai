<x-app-layout>
<div x-data="apiKeysPage()" x-init="init()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[#2D2825] dark:text-stone-200">Add API</h1>
            <p class="text-sm text-gray-500 dark:text-stone-400 mt-1">Configure your API keys, AI models, and Hugging Face integration.</p>
        </div>

        {{-- Flash Message --}}
        <div x-show="flashMessage" x-cloak x-effect="if(flashMessage){clearTimeout(ft);ft=setTimeout(()=>flashMessage=null,3000)}" class="mb-4 p-3 text-sm rounded-lg border" :class="flashType==='success'?'text-green-800 bg-green-50 dark:bg-[#1E1E20] dark:text-green-400 border-green-200 dark:border-stone-700':'text-red-800 bg-red-50 dark:bg-[#1E1E20] dark:text-red-400 border-red-200 dark:border-stone-700'" x-text="flashMessage"></div>

        {{-- Tab Buttons --}}
        <div class="flex gap-2 mb-6 border-b border-claude-border-light dark:border-claude-border-dark pb-3">
            <button @click="tab='hf'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='hf'?'bg-[#EAE9E5] dark:bg-[#1E1E20] text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-[#2E2E32]'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>
                Hugging Face
            </button>
            <button @click="tab='models'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='models'?'bg-[#EAE9E5] dark:bg-[#1E1E20] text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-[#2E2E32]'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                AI Models
            </button>
            <button @click="tab='keys'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='keys'?'bg-[#EAE9E5] dark:bg-[#1E1E20] text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-[#2E2E32]'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1221.75 8.25z"/></svg>
                API Keys
            </button>
            <button @click="tab='connect'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='connect'?'bg-[#EAE9E5] dark:bg-[#1E1E20] text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-[#2E2E32]'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                Connect Account
            </button>
        </div>

        {{-- ==================== HUGGING FACE TAB ==================== --}}
        <div x-show="tab==='hf'" x-transition>
            <div class="bg-white dark:bg-[#1E1E1E] rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
                <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-1">Hugging Face</h2>
                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-6">Connect your Hugging Face account to use open-source models.</p>

                <div class="space-y-5">
                    <div>
                        <label class="block text-[14px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">API Key</label>
                        <p class="text-[12.5px] text-gray-500 dark:text-stone-400 mb-2">Get your API key from <a href="https://huggingface.co/settings/tokens" target="_blank" class="underline hover:text-gray-800 dark:hover:text-stone-200">huggingface.co/settings/tokens</a>. Keys start with <code class="bg-gray-100 dark:bg-[#1E1E20] px-1 rounded text-[12px]">hf_</code>.</p>
                        <input type="password" x-model="hfKey" placeholder="hf_xxxxxxxxxxxxxxxxxxxxxxxx" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400 dark:placeholder-stone-500">
                    </div>
                    <div>
                        <label class="block text-[14px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Base URL</label>
                        <p class="text-[12.5px] text-gray-500 dark:text-stone-400 mb-2">Default: <code class="bg-gray-100 dark:bg-[#1E1E20] px-1 rounded text-[12px]">https://api-inference.huggingface.co/v1</code></p>
                        <input type="text" x-model="hfUrl" placeholder="https://api-inference.huggingface.co/v1" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400 dark:placeholder-stone-500">
                    </div>
                    <div class="pt-2">
                        <button @click="saveHF()" :disabled="saving" class="px-5 py-2 bg-[#D97757] hover:bg-[#c66547] text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-60" x-text="saving?'Saving...':'Save'">Save</button>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-claude-border-light dark:border-claude-border-dark">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Hugging Face Models</h3>
                        <button @click="openAddModel('huggingface')" class="px-3 py-1.5 bg-[#2D2825] dark:bg-stone-700 text-white rounded-lg text-[13px] font-medium hover:bg-black dark:hover:bg-[#2E2E32] transition-colors">+ Add Model</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="m in hfModels" :key="m.id">
                            <div class="flex items-center justify-between p-3 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E20]/50">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0" :class="m.is_active?'bg-green-500':'bg-gray-300 dark:bg-stone-600'"></div>
                                    <div class="min-w-0">
                                        <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate" x-text="m.name"></div>
                                        <div class="text-[12px] text-gray-500 dark:text-stone-400 truncate" x-text="m.code"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button @click="toggleModel(m)" class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-stone-300 hover:bg-gray-100 dark:hover:bg-[#2E2E32]" title="Toggle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                    </button>
                                    <button @click="editModel(m)" class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-stone-300 hover:bg-gray-100 dark:hover:bg-stone-700" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button @click="delModel(m)" class="p-1.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="hfModels.length===0" class="text-center py-6 text-[13px] text-gray-400 dark:text-stone-500">No Hugging Face models yet.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== AI MODELS TAB ==================== --}}
        <div x-show="tab==='models'" x-transition>
            <div class="bg-white dark:bg-[#1E1E1E] rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200">AI Models</h2>
                    <button @click="openAddModel('openai')" class="px-3 py-1.5 bg-[#2D2825] dark:bg-stone-700 text-white rounded-lg text-[13px] font-medium hover:bg-black dark:hover:bg-stone-600 transition-colors">+ Add Model</button>
                </div>

                <div class="flex gap-1.5 mb-4 flex-wrap">
                    <template x-for="f in ['all','anthropic','openai','google','huggingface','mistral','ollama','proxy']" :key="f">
                        <button @click="filter=f" class="px-3 py-1 text-[12px] rounded-lg transition-colors capitalize" :class="filter===f?'bg-[#EAE9E5] dark:bg-[#1E1E20] text-[#2D2825] dark:text-stone-200 font-medium':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-[#2E2E32]/50'" x-text="f==='all'?'All':f==='huggingface'?'Hugging Face':f"></button>
                    </template>
                </div>

                <div class="space-y-2">
                    <template x-for="m in filteredModels" :key="m.id">
                        <div class="flex items-center justify-between p-3 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E20]/50">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" :class="m.is_active?'bg-green-500':'bg-gray-300 dark:bg-stone-600'"></div>
                                <div class="min-w-0">
                                    <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate" x-text="m.name"></div>
                                    <div class="text-[12px] text-gray-500 dark:text-stone-400 truncate flex items-center gap-2">
                                        <span x-text="m.code"></span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-stone-700 text-gray-600 dark:text-stone-300" x-text="m.provider||'?'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button @click="toggleModel(m)" class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-stone-300 hover:bg-gray-100 dark:hover:bg-stone-700" title="Toggle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                </button>
                                <button @click="editModel(m)" class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-stone-300 hover:bg-gray-100 dark:hover:bg-stone-700" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                </button>
                                <button @click="delModel(m)" class="p-1.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredModels.length===0" class="text-center py-6 text-[13px] text-gray-400 dark:text-stone-500">No models found.</div>
                </div>
            </div>
        </div>

        {{-- ==================== API KEYS TAB ==================== --}}
        <div x-show="tab==='keys'" x-transition>
            <div class="bg-white dark:bg-[#1E1E1E] rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
                <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-1">API Keys</h2>
                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-6">Manage your API keys for various AI providers.</p>

                <div class="space-y-4">
                    {{-- Anthropic --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold text-sm">A</div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Anthropic</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">Claude models</p></div>
                            </div>
                            <a href="https://console.anthropic.com/settings/keys" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-[#D97757] hover:bg-[#D97757]/10 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                Get API
                            </a>
                        </div>
                        <input type="password" x-model="kAnthropic" placeholder="sk-ant-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                    </div>
                    {{-- OpenAI --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-stone-700 flex items-center justify-center text-gray-600 dark:text-stone-300 font-bold text-sm">O</div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">OpenAI</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">GPT-4, GPT-4o, o1</p></div>
                            </div>
                            <a href="https://platform.openai.com/api-keys" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-[#D97757] hover:bg-[#D97757]/10 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                Get API
                            </a>
                        </div>
                        <input type="password" x-model="kOpenai" placeholder="sk-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                    </div>
                    {{-- Google --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 font-bold text-sm">G</div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Google</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">Gemini models</p></div>
                            </div>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-[#D97757] hover:bg-[#D97757]/10 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                Get API
                            </a>
                        </div>
                        <input type="password" x-model="kGoogle" placeholder="AIza..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                    </div>
                    {{-- Mistral --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-500 font-bold text-sm">M</div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Mistral</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">Mistral Large, Medium, Small</p></div>
                            </div>
                            <a href="https://console.mistral.ai/api-keys/" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-medium text-[#D97757] hover:bg-[#D97757]/10 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                Get API
                            </a>
                        </div>
                        <input type="password" x-model="kMistral" placeholder="mist-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                    </div>
                    {{-- 9Router --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 font-bold text-sm">9</div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">9Router</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">9Router multi-provider proxy</p></div>
                            </div>
                        </div>
                        <input type="password" x-model="kNineRouter" placeholder="Your 9Router API key" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                    </div>
                    {{-- Custom Proxy --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-stone-700 flex items-center justify-center text-gray-500 dark:text-stone-400 font-bold text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z"/></svg>
                                </div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Custom Proxy</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">Use your own OpenAI-compatible proxy</p></div>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="useProxy" class="w-4 h-4 text-[#D97757] rounded border-stone-300 focus:ring-[#D97757]">
                                <span class="text-[13px] text-[#2D2825] dark:text-stone-300">Enable</span>
                            </label>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[12px] text-gray-500 dark:text-stone-400 mb-1">Base URL</label>
                                <input type="text" x-model="proxyUrl" placeholder="https://your-proxy-url.com/v1" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                            </div>
                            <div>
                                <label class="block text-[12px] text-gray-500 dark:text-stone-400 mb-1">API Key</label>
                                <input type="password" x-model="kProxy" placeholder="Your proxy API key" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button @click="saveKeys()" :disabled="saving" class="px-5 py-2 bg-[#D97757] hover:bg-[#c66547] text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-60" x-text="saving?'Saving...':'Save All API Keys'">Save All API Keys</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== CONNECT ACCOUNT TAB ==================== --}}
        <div x-show="tab==='connect'" x-transition x-data="{
            extensionInstalled: false,
            providers: {chatgpt: false, gemini: false, claude: false},
            providerStatus: {},
            flashMessage: null,
            flashType: 'success',
            ft: null,

            init() {
                // Check if extension is installed
                this.checkExtension();
                this.loadProviderStatus();

                // Listen for extension events
                window.addEventListener('rynude-extension-ready', () => {
                    this.extensionInstalled = true;
                    this.checkAllProviders();
                });

                window.addEventListener('rynude-provider-connected', (e) => {
                    this.providers[e.detail.provider] = true;
                    this.loadProviderStatus();
                });

                window.addEventListener('rynude-provider-disconnected', (e) => {
                    this.providers[e.detail.provider] = false;
                    this.providerStatus[e.detail.provider] = {};
                });
            },

            async loadProviderStatus() {
                try {
                    const r = await fetch('/api/provider-tokens', { headers: { 'Accept': 'application/json' } });
                    const data = await r.json();
                    if (data) this.providerStatus = data;
                } catch (e) {
                    console.error('Failed to load provider status:', e);
                }
            },

            checkExtension() {
                if (window.rynudeExtension) {
                    this.extensionInstalled = true;
                    this.checkAllProviders();
                }
            },

            async checkAllProviders() {
                if (!window.rynudeExtension) return;
                try {
                    const status = await window.rynudeExtension.getStatus();
                    this.providers.chatgpt = status.chatgpt?.connected || false;
                    this.providers.gemini = status.gemini?.connected || false;
                    this.providers.claude = status.claude?.connected || false;
                } catch (error) {
                    console.error('Failed to check providers:', error);
                }
            },

            async refreshSession(provider) {
                if (!window.rynudeExtension) return;
                try {
                    const status = await window.rynudeExtension.getStatus();
                    this.providers[provider] = status[provider]?.connected || false;
                    this.loadProviderStatus();
                    this.flashMessage = `${provider.charAt(0).toUpperCase() + provider.slice(1)} session refreshed`;
                    this.flashType = 'success';
                } catch (error) {
                    console.error('Failed to refresh session:', error);
                }
            },

            async connectChatGPT() {
                if (!window.rynudeExtension) {
                    alert('Extension not installed. Please install Rynude Extension first.');
                    return;
                }

                const success = await window.rynudeExtension.connectProvider('chatgpt');
                if (success) {
                    this.providers.chatgpt = true;
                    this.loadProviderStatus();
                    this.flashMessage = 'ChatGPT connected successfully!';
                    this.flashType = 'success';
                }
            },

            async connectGemini() {
                if (!window.rynudeExtension) {
                    alert('Extension not installed. Please install Rynude Extension first.');
                    return;
                }

                const success = await window.rynudeExtension.connectProvider('gemini');
                if (success) {
                    this.providers.gemini = true;
                    this.loadProviderStatus();
                    this.flashMessage = 'Gemini connected successfully!';
                    this.flashType = 'success';
                }
            },

            async connectClaude() {
                if (!window.rynudeExtension) {
                    alert('Extension not installed. Please install Rynude Extension first.');
                    return;
                }

                const success = await window.rynudeExtension.connectProvider('claude');
                if (success) {
                    this.providers.claude = true;
                    this.loadProviderStatus();
                    this.flashMessage = 'Claude connected successfully!';
                    this.flashType = 'success';
                }
            },

            async disconnectProvider(provider) {
                if (!window.rynudeExtension) return;

                const success = await window.rynudeExtension.disconnectProvider(provider);
                if (success) {
                    this.providers[provider] = false;
                    this.flashMessage = `${provider.charAt(0).toUpperCase() + provider.slice(1)} disconnected`;
                    this.flashType = 'success';
                }
            }
        }">
            <div class="bg-white dark:bg-[#1E1E1E] rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
                {{-- Flash Message --}}
                <div x-show="flashMessage" x-cloak x-effect="if(flashMessage){clearTimeout(ft);ft=setTimeout(()=>flashMessage=null,3000)}" class="mb-4 p-3 text-sm rounded-lg border" :class="flashType==='success'?'text-green-800 bg-green-50 dark:bg-[#1E1E20] dark:text-green-400 border-green-200 dark:border-stone-700':'text-red-800 bg-red-50 dark:bg-[#1E1E20] dark:text-red-400 border-red-200 dark:border-stone-700'" x-text="flashMessage"></div>

                <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-1">Connected Accounts</h2>
                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-6">Connect your free tier accounts from ChatGPT, Gemini, and Claude to use Rynude without API keys.</p>

                {{-- Extension Setup Section --}}
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50/30 dark:from-blue-900/20 dark:to-indigo-900/10 border-2 border-blue-200 dark:border-blue-900/30">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-[15px] font-bold text-[#2D2825] dark:text-stone-200 mb-2">Step 1: Install Rynude Extension</h3>
                            <p class="text-[13px] text-gray-600 dark:text-stone-400 mb-4">
                                The browser extension is required to connect your provider accounts securely. It will extract authentication tokens from your browser sessions.
                            </p>

                            <template x-if="!extensionInstalled">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('extension.download', ['browser' => 'chrome']) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[13px] font-medium transition-colors shadow-md">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                            </svg>
                                            Download Extension (Chrome/Edge)
                                        </a>
                                        <a href="{{ route('extension.download', ['browser' => 'firefox']) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-[13px] font-medium transition-colors shadow-md">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                            </svg>
                                            Download Extension (Firefox)
                                        </a>
                                    </div>
                                    <button @click="extensionInstalled = true" class="text-[12px] text-blue-600 dark:text-blue-400 hover:underline">
                                        I've installed the extension →
                                    </button>
                                </div>
                            </template>

                            <template x-if="extensionInstalled">
                                <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/30 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-[13px] font-medium text-green-800 dark:text-green-300">Extension installed! You can now connect your accounts below.</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Provider Cards Section --}}
                <div class="space-y-4" x-show="extensionInstalled">
                    <h3 class="text-[14px] font-semibold text-[#2D2825] dark:text-stone-200 mb-3">Step 2: Connect Your Accounts</h3>

                    {{-- ChatGPT / OpenAI --}}
                    <div class="p-5 rounded-xl border-2 transition-all" :class="providers.chatgpt ? 'border-green-200 dark:border-green-900/30 bg-gradient-to-br from-green-50/50 to-transparent dark:from-green-900/10' : 'border-claude-border-light dark:border-claude-border-dark hover:border-gray-300 dark:hover:border-stone-600'">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-stone-700 flex items-center justify-center text-gray-600 dark:text-stone-300 font-bold text-lg shadow-sm">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364 15.1192 7.2a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6067-1.4997Z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200">ChatGPT / OpenAI</h3>
                                    <p class="text-[12px] text-gray-500 dark:text-stone-400 mt-0.5">GPT-4o, GPT-4, o1, o3</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium" :class="providers.chatgpt ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-[#1E1E20] text-gray-600 dark:text-stone-400'">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="4"/></svg>
                                    <span x-text="providers.chatgpt ? 'Connected' : 'Not Connected'"></span>
                                </span>
                            </div>
                        </div>

                        <div class="pl-[52px]">
                            <template x-if="!providers.chatgpt">
                                <div class="space-y-3">
                                    <div class="text-[12.5px] text-gray-600 dark:text-stone-400 space-y-2">
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                            <span>Login to <a href="https://chatgpt.com" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">chatgpt.com</a> in a new tab</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                            <span>The Rynude Extension will automatically detect your session</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                            <span>Click the button below to connect</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 pt-2">
                                        <button @click="connectChatGPT()" class="inline-flex items-center gap-2 px-4 py-2 bg-[#2D2825] hover:bg-black dark:bg-stone-700 dark:hover:bg-stone-600 text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                            Connect ChatGPT Account
                                        </button>
                                        <a href="https://chatgpt.com" target="_blank" class="text-[12px] text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                            Open ChatGPT
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="providers.chatgpt">
                                <div class="space-y-3">
                                    <div class="space-y-1.5">
                                        <div class="text-[12.5px] text-gray-600 dark:text-stone-400 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" :class="(providerStatus.chatgpt?.status||'active')==='active'?'text-green-600 dark:text-green-400':'text-yellow-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Status: <strong class="font-medium" :class="(providerStatus.chatgpt?.status||'active')==='active'?'text-green-700 dark:text-green-400':'text-yellow-600 dark:text-yellow-400'" x-text="(providerStatus.chatgpt?.status||'active').charAt(0).toUpperCase()+(providerStatus.chatgpt?.status||'active').slice(1)"></strong></span>
                                        </div>
                                        <div class="text-[12.5px] text-gray-600 dark:text-stone-400 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Last sync: <strong class="font-medium" x-text="providerStatus.chatgpt?.last_validated||'Never'"></strong></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <button @click="refreshSession('chatgpt')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E20] hover:bg-gray-50 dark:hover:bg-[#2E2E32] text-[#2D2825] dark:text-stone-200 border border-claude-border-light dark:border-claude-border-dark rounded-lg text-[12px] font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            Refresh Session
                                        </button>
                                        <button @click="disconnectProvider('chatgpt')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E20] hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 border border-claude-border-light dark:border-claude-border-dark hover:border-red-300 dark:hover:border-red-900/50 rounded-lg text-[12px] font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Disconnect
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Google Gemini --}}
                    <div class="p-5 rounded-xl border-2 transition-all" :class="providers.gemini ? 'border-green-200 dark:border-green-900/30 bg-gradient-to-br from-green-50/50 to-transparent dark:from-green-900/10' : 'border-claude-border-light dark:border-claude-border-dark hover:border-gray-300 dark:hover:border-stone-600'">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 font-bold text-lg shadow-sm">G</div>
                                <div>
                                    <h3 class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200">Google Gemini</h3>
                                    <p class="text-[12px] text-gray-500 dark:text-stone-400 mt-0.5">Gemini 2.0 Flash, Pro, Ultra</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium" :class="providers.gemini ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-[#1E1E20] text-gray-600 dark:text-stone-400'">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="4"/></svg>
                                    <span x-text="providers.gemini ? 'Connected' : 'Not Connected'"></span>
                                </span>
                            </div>
                        </div>

                        <div class="pl-[52px]">
                            <template x-if="!providers.gemini">
                                <div class="space-y-3">
                                    <div class="text-[12.5px] text-gray-600 dark:text-stone-400 space-y-2">
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                            <span>Login to <a href="https://gemini.google.com" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">gemini.google.com</a> with your Google account</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                            <span>Extension will extract your session tokens automatically</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                            <span>Click connect to use Gemini free tier in Rynude</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 pt-2">
                                        <button @click="connectGemini()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                            Connect Gemini Account
                                        </button>
                                        <a href="https://gemini.google.com" target="_blank" class="text-[12px] text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                            Open Gemini
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="providers.gemini">
                                <div class="space-y-3">
                                    <div class="space-y-1.5">
                                        <div class="text-[12.5px] text-gray-600 dark:text-stone-400 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" :class="(providerStatus.gemini?.status||'active')==='active'?'text-green-600 dark:text-green-400':'text-yellow-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Status: <strong class="font-medium" :class="(providerStatus.gemini?.status||'active')==='active'?'text-green-700 dark:text-green-400':'text-yellow-600 dark:text-yellow-400'" x-text="(providerStatus.gemini?.status||'active').charAt(0).toUpperCase()+(providerStatus.gemini?.status||'active').slice(1)"></strong></span>
                                        </div>
                                        <div class="text-[12.5px] text-gray-600 dark:text-stone-400 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Last sync: <strong class="font-medium" x-text="providerStatus.gemini?.last_validated||'Never'"></strong></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <button @click="refreshSession('gemini')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E20] hover:bg-gray-50 dark:hover:bg-[#2E2E32] text-[#2D2825] dark:text-stone-200 border border-claude-border-light dark:border-claude-border-dark rounded-lg text-[12px] font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            Refresh Session
                                        </button>
                                        <button @click="disconnectProvider('gemini')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E20] hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 border border-claude-border-light dark:border-claude-border-dark hover:border-red-300 dark:hover:border-red-900/50 rounded-lg text-[12px] font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Disconnect
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Claude / Anthropic --}}
                    <div class="p-5 rounded-xl border-2 transition-all" :class="providers.claude ? 'border-green-200 dark:border-green-900/30 bg-gradient-to-br from-green-50/50 to-transparent dark:from-green-900/10' : 'border-claude-border-light dark:border-claude-border-dark hover:border-gray-300 dark:hover:border-stone-600'">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold text-lg shadow-sm">C</div>
                                <div>
                                    <h3 class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200">Anthropic Claude</h3>
                                    <p class="text-[12px] text-gray-500 dark:text-stone-400 mt-0.5">Claude Sonnet, Opus, Haiku</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium" :class="providers.claude ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-[#1E1E20] text-gray-600 dark:text-stone-400'">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="4"/></svg>
                                    <span x-text="providers.claude ? 'Connected' : 'Not Connected'"></span>
                                </span>
                            </div>
                        </div>

                        <div class="pl-[52px]">
                            <template x-if="!providers.claude">
                                <div class="space-y-3">
                                    <div class="text-[12.5px] text-gray-600 dark:text-stone-400 space-y-2">
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                            <span>Login to <a href="https://claude.ai" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">claude.ai</a> with your account</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                            <span>Extension captures your session cookies securely</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="inline-block w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                            <span>Use Claude free tier directly in Rynude</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 pt-2">
                                        <button @click="connectClaude()" class="inline-flex items-center gap-2 px-4 py-2 bg-[#D97757] hover:bg-[#c66547] text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                            Connect Claude Account
                                        </button>
                                        <a href="https://claude.ai" target="_blank" class="text-[12px] text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                            Open Claude.ai
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="providers.claude">
                                <div class="space-y-3">
                                    <div class="space-y-1.5">
                                        <div class="text-[12.5px] text-gray-600 dark:text-stone-400 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" :class="(providerStatus.claude?.status||'active')==='active'?'text-green-600 dark:text-green-400':'text-yellow-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Status: <strong class="font-medium" :class="(providerStatus.claude?.status||'active')==='active'?'text-green-700 dark:text-green-400':'text-yellow-600 dark:text-yellow-400'" x-text="(providerStatus.claude?.status||'active').charAt(0).toUpperCase()+(providerStatus.claude?.status||'active').slice(1)"></strong></span>
                                        </div>
                                        <div class="text-[12.5px] text-gray-600 dark:text-stone-400 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Last sync: <strong class="font-medium" x-text="providerStatus.claude?.last_validated||'Never'"></strong></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <button @click="refreshSession('claude')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E20] hover:bg-gray-50 dark:hover:bg-[#2E2E32] text-[#2D2825] dark:text-stone-200 border border-claude-border-light dark:border-claude-border-dark rounded-lg text-[12px] font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            Refresh Session
                                        </button>
                                        <button @click="disconnectProvider('claude')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E20] hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 border border-claude-border-light dark:border-claude-border-dark hover:border-red-300 dark:hover:border-red-900/50 rounded-lg text-[12px] font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Disconnect
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- How It Works & Security Info --}}
                <div class="mt-6 space-y-4" x-show="extensionInstalled">
                    {{-- How it works --}}
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-[#1E1E20]/50 border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-gray-600 dark:text-stone-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="text-[13px] text-gray-700 dark:text-stone-300">
                                <p class="font-medium mb-1">How It Works</p>
                                <p class="text-gray-600 dark:text-stone-400">The Rynude Extension extracts authentication tokens from your browser sessions when you login to ChatGPT, Gemini, or Claude. These tokens are stored locally and used to access the same APIs that the web apps use, allowing you to chat with your free tier accounts directly in Rynude.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Security & Privacy --}}
                    <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-900/30">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-2.25 0H5.25A2.25 2.25 0 003 12m0 0v6.75m0-6.75a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 12m0 0v6.75m0-6.75a2.25 2.25 0 00-2.25-2.25H5.25m15 0a2.25 2.25 0 012.25 2.25M21 12H9"/></svg>
                            <div class="text-[13px] text-green-900 dark:text-green-300">
                                <p class="font-medium mb-1">Security & Privacy</p>
                                <p class="text-green-800 dark:text-green-400">Your session tokens are encrypted and stored locally on your machine. They are never sent to any third-party servers. Rynude only forwards your requests directly to the provider APIs using your own credentials.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Important Notice --}}
                    <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-900/30">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            <div class="text-[13px] text-amber-900 dark:text-amber-300">
                                <p class="font-medium mb-1">Important Notice</p>
                                <ul class="list-disc list-inside text-amber-800 dark:text-amber-400 space-y-1">
                                    <li>This feature uses reverse-engineered APIs and may violate provider Terms of Service</li>
                                    <li>Your account could be banned if detected by providers</li>
                                    <li>Sessions may expire and require re-login</li>
                                    <li>Free tier rate limits and quotas still apply</li>
                                    <li>Use at your own risk - recommended for local/personal use only</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== MODEL DIALOG ==================== --}}
    <div x-show="dlgOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm" @click="dlgOpen=false"></div>
        <div class="bg-white dark:bg-[#1E1E1E] border border-claude-border-light dark:border-claude-border-dark w-full max-w-md rounded-xl p-6 shadow-2xl relative z-10">
            <h3 class="text-lg font-bold text-stone-800 dark:text-stone-100 mb-4" x-text="dlgEditId?'Edit AI Model':'Add AI Model'"></h3>
            <div x-show="dlgErr" x-cloak class="mb-4 p-3 text-sm rounded-lg border text-red-800 bg-red-50 dark:bg-red-900/20 dark:text-red-400 border-red-200" x-text="dlgErr"></div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Model Code</label>
                    <input type="text" x-model="dlgCode" placeholder="e.g. meta-llama/Llama-3" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Model Name</label>
                    <input type="text" x-model="dlgName" placeholder="e.g. Llama 3" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Provider</label>
                    <select x-model="dlgProv" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-[#1E1E1E] text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
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
                    <input type="checkbox" id="dlgActive" x-model="dlgActive" class="w-4 h-4 text-[#D97757] rounded border-stone-300 focus:ring-[#D97757]">
                    <label for="dlgActive" class="ml-2 text-sm text-stone-600 dark:text-stone-400">Active</label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-stone-100 dark:border-stone-800">
                <button @click="dlgOpen=false" class="px-3 py-1.5 text-sm text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 rounded-lg">Cancel</button>
                <button @click="saveModel()" :disabled="dlgSaving" class="px-4 py-1.5 text-sm text-white bg-[#D97757] hover:bg-[#c66547] rounded-lg disabled:opacity-60" x-text="dlgSaving?'Saving...':'Save'">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function apiKeysPage(){
    return {
        tab:'hf',flashMessage:null,flashType:'success',ft:null,saving:false,
        hfKey:'',hfUrl:'https://api-inference.huggingface.co/v1',
        kAnthropic:'',kOpenai:'',kGoogle:'',kMistral:'',kNineRouter:'',kProxy:'',
        useProxy:false,proxyUrl:'',
        models:[],filter:'all',
        dlgOpen:false,dlgEditId:null,dlgCode:'',dlgName:'',dlgActive:true,dlgProv:'huggingface',dlgErr:null,dlgSaving:false,

        get hfModels(){return this.models.filter(m=>m.provider==='huggingface')},
        get filteredModels(){return this.filter==='all'?this.models:this.models.filter(m=>m.provider===this.filter)},

        init(){this.load()},

        load(){
            fetch('/api/settings',{headers:{'Accept':'application/json'}})
            .then(r=>r.json())
            .then(r=>{
                var k=r.api_keys||{};
                this.kAnthropic=k.anthropic?'••••••••••••••••':'';
                this.kOpenai=k.openai?'••••••••••••••••':'';
                this.kGoogle=k.google?'••••••••••••••••':'';
                this.kMistral=k.mistral?'••••••••••••••••':'';
                this.kNineRouter=k.nine_router?'•••••••••9•••••••':'';
                this.useProxy=k.use_proxy||false;
                this.proxyUrl=k.proxy_base_url||'';
                this.kProxy=k.proxy_api_key_set?'••••••••••••••••':'';
                this.hfKey=k.huggingface_api_key_set?'••••••••••••••••':'';
                this.hfUrl=k.huggingface_base_url||'https://api-inference.huggingface.co/v1';
                this.models=r.ai_models||[];
            });
        },

        _patch(d){
            return fetch('/api/settings',{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(d)}).then(r=>r.json());
        },

        saveHF(){
            this.saving=true;
            var p={huggingface_base_url:this.hfUrl};
            if(this.hfKey&&this.hfKey!=='••••••••••••••••')p.huggingface_api_key=this.hfKey;
            this._patch(p).then(()=>{this.saving=false;this.flashMessage='Saved!';this.flashType='success';this.load()});
        },

        saveKeys(){
            this.saving=true;
            var p={use_proxy:this.useProxy,proxy_base_url:this.proxyUrl};
            if(this.kAnthropic&&this.kAnthropic!=='••••••••••••••••')p.anthropic_api_key=this.kAnthropic;
            if(this.kOpenai&&this.kOpenai!=='••••••••••••••••')p.openai_api_key=this.kOpenai;
            if(this.kGoogle&&this.kGoogle!=='••••••••••••••••')p.google_api_key=this.kGoogle;
            if(this.kMistral&&this.kMistral!=='••••••••••••••••')p.mistral_api_key=this.kMistral;
            if(this.kNineRouter&&this.kNineRouter!=='•••••••••9•••••••')p.nine_router_api_key=this.kNineRouter;
            if(this.kProxy&&this.kProxy!=='••••••••••••••••')p.proxy_api_key=this.kProxy;
            this._patch(p).then(()=>{this.saving=false;this.flashMessage='API Keys saved!';this.flashType='success';this.load()});
        },

        openAddModel(prov){
            this.dlgEditId=null;this.dlgCode='';this.dlgName='';this.dlgActive=true;this.dlgProv=prov||'openai';this.dlgErr=null;this.dlgSaving=false;this.dlgOpen=true;
        },
        editModel(m){
            this.dlgEditId=m.id;this.dlgCode=m.code;this.dlgName=m.name;this.dlgActive=m.is_active;this.dlgProv=m.provider||'huggingface';this.dlgErr=null;this.dlgSaving=false;this.dlgOpen=true;
        },
        saveModel(){
            this.dlgErr=null;
            if(!this.dlgCode.trim()){this.dlgErr='Model Code required.';return}
            if(!this.dlgName.trim()){this.dlgErr='Model Name required.';return}
            this.dlgSaving=true;
            fetch('/api/settings',{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({_action:'store_model',model_id:this.dlgEditId,model_code:this.dlgCode.trim(),model_name:this.dlgName.trim(),model_provider:this.dlgProv,model_is_active:this.dlgActive})})
            .then(r=>r.json().catch(()=>({})).then(d=>({s:r.status,ok:r.ok,d})))
            .then(r=>{
                this.dlgSaving=false;
                if(r.ok){if(r.d&&r.d.ai_models)this.models=r.d.ai_models;this.dlgOpen=false;this.flashMessage='Model saved!';this.flashType='success';this.load();return}
                this.dlgErr=r.d&&r.d.message?r.d.message:'Failed to save.';
            }).catch(()=>{this.dlgSaving=false;this.dlgErr='Network error.'});
        },
        toggleModel(m){this._patch({_action:'toggle_model',model_id:m.id}).then(()=>this.load())},
        delModel(m){if(!confirm('Delete this model?'))return;this._patch({_action:'delete_model',model_id:m.id}).then(()=>this.load())}
    };
}
</script>
</x-app-layout>

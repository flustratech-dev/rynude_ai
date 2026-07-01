<x-app-layout>
<div x-data="apiKeysPage()" x-init="init()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[#2D2825] dark:text-stone-200">Add API</h1>
            <p class="text-sm text-gray-500 dark:text-stone-400 mt-1">Configure your API keys, AI models, and Hugging Face integration.</p>
        </div>

        {{-- Flash Message --}}
        <div x-show="flashMessage" x-cloak x-effect="if(flashMessage){clearTimeout(ft);ft=setTimeout(()=>flashMessage=null,3000)}" class="mb-4 p-3 text-sm rounded-lg border" :class="flashType==='success'?'text-green-800 bg-green-50 dark:bg-stone-800 dark:text-green-400 border-green-200 dark:border-stone-700':'text-red-800 bg-red-50 dark:bg-stone-800 dark:text-red-400 border-red-200 dark:border-stone-700'" x-text="flashMessage"></div>

        {{-- Tab Buttons --}}
        <div class="flex gap-2 mb-6 border-b border-claude-border-light dark:border-claude-border-dark pb-3">
            <button @click="tab='hf'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='hf'?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-stone-800/50'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>
                Hugging Face
            </button>
            <button @click="tab='models'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='models'?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-stone-800/50'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                AI Models
            </button>
            <button @click="tab='keys'" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-medium transition-colors" :class="tab==='keys'?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-stone-800/50'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                API Keys
            </button>
        </div>

        {{-- ==================== HUGGING FACE TAB ==================== --}}
        <div x-show="tab==='hf'" x-transition>
            <div class="bg-white dark:bg-stone-900 rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
                <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-1">Hugging Face</h2>
                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-6">Connect your Hugging Face account to use open-source models.</p>

                <div class="space-y-5">
                    <div>
                        <label class="block text-[14px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">API Key</label>
                        <p class="text-[12.5px] text-gray-500 dark:text-stone-400 mb-2">Get your API key from <a href="https://huggingface.co/settings/tokens" target="_blank" class="underline hover:text-gray-800 dark:hover:text-stone-200">huggingface.co/settings/tokens</a>. Keys start with <code class="bg-gray-100 dark:bg-stone-800 px-1 rounded text-[12px]">hf_</code>.</p>
                        <input type="password" x-model="hfKey" placeholder="hf_xxxxxxxxxxxxxxxxxxxxxxxx" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400 dark:placeholder-stone-500">
                    </div>
                    <div>
                        <label class="block text-[14px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Base URL</label>
                        <p class="text-[12.5px] text-gray-500 dark:text-stone-400 mb-2">Default: <code class="bg-gray-100 dark:bg-stone-800 px-1 rounded text-[12px]">https://api-inference.huggingface.co/v1</code></p>
                        <input type="text" x-model="hfUrl" placeholder="https://api-inference.huggingface.co/v1" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400 dark:placeholder-stone-500">
                    </div>
                    <div class="pt-2">
                        <button @click="saveHF()" :disabled="saving" class="px-5 py-2 bg-[#D97757] hover:bg-[#c66547] text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-60" x-text="saving?'Saving...':'Save'">Save</button>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-claude-border-light dark:border-claude-border-dark">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Hugging Face Models</h3>
                        <button @click="openAddModel('huggingface')" class="px-3 py-1.5 bg-[#2D2825] dark:bg-stone-700 text-white rounded-lg text-[13px] font-medium hover:bg-black dark:hover:bg-stone-600 transition-colors">+ Add Model</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="m in hfModels" :key="m.id">
                            <div class="flex items-center justify-between p-3 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800/50">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0" :class="m.is_active?'bg-green-500':'bg-gray-300 dark:bg-stone-600'"></div>
                                    <div class="min-w-0">
                                        <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate" x-text="m.name"></div>
                                        <div class="text-[12px] text-gray-500 dark:text-stone-400 truncate" x-text="m.code"></div>
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
                        <div x-show="hfModels.length===0" class="text-center py-6 text-[13px] text-gray-400 dark:text-stone-500">No Hugging Face models yet.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== AI MODELS TAB ==================== --}}
        <div x-show="tab==='models'" x-transition>
            <div class="bg-white dark:bg-stone-900 rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200">AI Models</h2>
                    <button @click="openAddModel('openai')" class="px-3 py-1.5 bg-[#2D2825] dark:bg-stone-700 text-white rounded-lg text-[13px] font-medium hover:bg-black dark:hover:bg-stone-600 transition-colors">+ Add Model</button>
                </div>

                <div class="flex gap-1.5 mb-4 flex-wrap">
                    <template x-for="f in ['all','anthropic','openai','google','huggingface','mistral','ollama','proxy']" :key="f">
                        <button @click="filter=f" class="px-3 py-1 text-[12px] rounded-lg transition-colors capitalize" :class="filter===f?'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 font-medium':'text-gray-500 dark:text-stone-400 hover:bg-gray-100 dark:hover:bg-stone-800/50'" x-text="f==='all'?'All':f==='huggingface'?'Hugging Face':f"></button>
                    </template>
                </div>

                <div class="space-y-2">
                    <template x-for="m in filteredModels" :key="m.id">
                        <div class="flex items-center justify-between p-3 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800/50">
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
            <div class="bg-white dark:bg-stone-900 rounded-xl border border-claude-border-light dark:border-claude-border-dark p-6">
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
                        <input type="password" x-model="kAnthropic" placeholder="sk-ant-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
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
                        <input type="password" x-model="kOpenai" placeholder="sk-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
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
                        <input type="password" x-model="kGoogle" placeholder="AIza..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
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
                        <input type="password" x-model="kMistral" placeholder="mist-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                    </div>
                    {{-- 9Router --}}
                    <div class="p-4 rounded-lg border border-claude-border-light dark:border-claude-border-dark">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 font-bold text-sm">9</div>
                                <div><h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">9Router</h3><p class="text-[12px] text-gray-500 dark:text-stone-400">9Router multi-provider proxy</p></div>
                            </div>
                        </div>
                        <input type="password" x-model="kNineRouter" placeholder="Your 9Router API key" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
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
                                <input type="text" x-model="proxyUrl" placeholder="https://your-proxy-url.com/v1" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                            </div>
                            <div>
                                <label class="block text-[12px] text-gray-500 dark:text-stone-400 mb-1">API Key</label>
                                <input type="password" x-model="kProxy" placeholder="Your proxy API key" class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button @click="saveKeys()" :disabled="saving" class="px-5 py-2 bg-[#D97757] hover:bg-[#c66547] text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-60" x-text="saving?'Saving...':'Save All API Keys'">Save All API Keys</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== MODEL DIALOG ==================== --}}
    <div x-show="dlgOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm" @click="dlgOpen=false"></div>
        <div class="bg-white dark:bg-stone-900 border border-claude-border-light dark:border-claude-border-dark w-full max-w-md rounded-xl p-6 shadow-2xl relative z-10">
            <h3 class="text-lg font-bold text-stone-800 dark:text-stone-100 mb-4" x-text="dlgEditId?'Edit AI Model':'Add AI Model'"></h3>
            <div x-show="dlgErr" x-cloak class="mb-4 p-3 text-sm rounded-lg border text-red-800 bg-red-50 dark:bg-red-900/20 dark:text-red-400 border-red-200" x-text="dlgErr"></div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Model Code</label>
                    <input type="text" x-model="dlgCode" placeholder="e.g. meta-llama/Llama-3" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Model Name</label>
                    <input type="text" x-model="dlgName" placeholder="e.g. Llama 3" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Provider</label>
                    <select x-model="dlgProv" class="w-full px-3 py-2 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none">
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

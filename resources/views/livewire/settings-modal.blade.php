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
             @click.away="open = false"
             class="bg-claude-bg-light dark:bg-claude-bg-dark w-full max-w-[900px] h-[95vh] md:h-[85vh] max-h-[700px] rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden border border-claude-border-light dark:border-claude-border-dark relative">

            {{-- Sidebar tabs --}}
            <div class="w-full md:w-[260px] bg-claude-bg-light dark:bg-claude-bg-dark border-b md:border-b-0 md:border-r border-claude-border-light dark:border-claude-border-dark p-3 md:p-4 flex md:flex-col gap-2 md:gap-1 flex-shrink-0 overflow-x-auto scrollbar-hide">
                <template x-for="item in navItems" :key="item.id">
                    <button @click="activeTab = item.id" class="w-auto md:w-full flex items-center gap-2 md:gap-3 px-3 md:px-3 py-2 md:py-2.5 rounded-xl text-[13px] md:text-[14px] transition-all duration-150 whitespace-nowrap"
                            :class="activeTab === item.id ? 'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#6B6B6B] dark:text-stone-400 hover:bg-claude-bg-light dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200'">
                        <span x-text="item.label"></span>
                    </button>
                </template>
            </div>

            {{-- Main content --}}
            <div class="flex-1 bg-claude-bg-light dark:bg-claude-bg-dark p-6 md:p-10 overflow-y-auto relative">
                <button @click="open = false" class="absolute top-6 right-6 z-10 p-1.5 rounded-lg text-gray-500 dark:text-stone-400 hover:text-gray-800 dark:hover:text-stone-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div x-show="flashMessage" x-init="setTimeout(() => flashMessage = null, 3000)" class="mb-4 p-3 text-sm rounded-lg border" :class="flashType === 'success' ? 'text-green-800 bg-green-50 dark:bg-stone-800 dark:text-green-400 border-green-200 dark:border-stone-700' : 'text-red-800 bg-red-50 dark:bg-stone-800 dark:text-red-400 border-red-200 dark:border-stone-700'" x-text="flashMessage"></div>

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
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Nickname</label>
                            <input x-model="nickname" @input.debounce.500ms="save('nickname')" type="text" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Profession</label>
                            <select x-model="profession" @change="save('profession')" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                                <option value="">Select...</option><option value="developer">Software Developer</option><option value="designer">Designer</option><option value="data_scientist">Data Scientist</option><option value="product_manager">Product Manager</option><option value="student">Student</option><option value="researcher">Researcher</option><option value="writer">Writer</option><option value="marketer">Marketer</option><option value="business">Business</option><option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-[15px] text-[#2D2825] dark:text-stone-200 mb-1">Custom instructions</h3>
                        <textarea x-model="customInstructions" @input.debounce.1000ms="save('custom_instructions')" class="w-full h-24 p-3 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 resize-none"></textarea>
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
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block">Font size</label><p class="text-[13px] text-gray-500 dark:text-stone-400">Adjust text size.</p></div>
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
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block">Compact mode</label><p class="text-[13px] text-gray-500 dark:text-stone-400">Reduce spacing.</p></div>
                            <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 cursor-pointer" :class="compactMode?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="compactMode=!compactMode; saveAppearance()">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="compactMode?'translate-x-5':'translate-x-[2px]'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rest of tabs --}}
                <div x-show="activeTab === 'data'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Data & Privacy</h2>
                    <div class="space-y-8">
                        <div class="flex items-start justify-between">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Export all chats</label><p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Download a JSON copy of all conversations.</p></div>
                            <button @click="exportAllChats('json')" class="px-4 py-2 border border-claude-border-light dark:border-claude-border-dark text-[#2D2825] dark:text-stone-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-stone-800">Export JSON</button>
                        </div>
                        <div class="flex items-start justify-between border-t border-claude-border-light dark:border-claude-border-dark pt-6">
                            <div><label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Train on conversations</label><p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Allow using conversations to improve models.</p></div>
                            <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 cursor-pointer flex-shrink-0 mt-1" :class="allowTraining?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="allowTraining=!allowTraining; save('allow_training')">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="allowTraining?'translate-x-5':'translate-x-[2px]'"></span>
                            </div>
                        </div>
                        <div class="flex items-start justify-between border-t border-red-100 dark:border-red-900/40 pt-6">
                            <div><label class="text-[15px] text-red-600 dark:text-red-400 font-medium block mb-1">Delete all chats</label><p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[460px]">Permanently delete all conversations.</p></div>
                            <button @click="deleteAllChats()" class="px-4 py-2 bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-100">Delete all</button>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'api-keys'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">API Keys</h2>
                    <div class="space-y-4">
                        <div><label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">Anthropic</label><input type="password" x-model="anthropicApiKey" placeholder="sk-ant-..." class="w-full px-3 py-2.5 rounded-lg border border-claude-border-light dark:border-claude-border-dark bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 mb-4"></div>
                        <div><label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">OpenAI</label><input type="password" x-model="openaiApiKey" placeholder="sk-proj-..." class="w-full px-3 py-2.5 rounded-lg border ... mb-4"></div>
                        <div><label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">Google AI</label><input type="password" x-model="googleApiKey" placeholder="AIza..." class="w-full px-3 py-2.5 rounded-lg border ... mb-4"></div>
                        <div><label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">Mistral</label><input type="password" x-model="mistralApiKey" placeholder="..." class="w-full px-3 py-2.5 rounded-lg border ... mb-6"></div>
                        <div><label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">9Router</label><input type="password" x-model="nineRouterApiKey" placeholder="sk-..." class="w-full px-3 py-2.5 rounded-lg border ... mb-6"></div>
                        <div><label class="flex items-center gap-3 cursor-pointer mb-4"><div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors" :class="useProxy?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="useProxy=!useProxy"><span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition" :class="useProxy?'translate-x-4':'translate-x-[3px]'"></span></div><div><span class="text-[15px] text-[#2D2825] dark:text-stone-200 font-medium">Custom Proxy</span></div></label></div>
                        <div x-show="useProxy" class="space-y-4"><input type="url" x-model="proxyBaseUrl" placeholder="https://openrouter.ai/api/v1" class="w-full px-3 py-2.5 rounded-lg border ..."><input type="password" x-model="proxyApiKey" placeholder="sk-or-..." class="w-full px-3 py-2.5 rounded-lg border ..."></div>
                        <div class="flex justify-end"><button @click="saveApiKeys()" class="px-4 py-2 bg-[#D97757] text-white rounded-lg text-sm font-medium hover:bg-[#c66547]">Save Keys</button></div>
                    </div>
                </div>

                <div x-show="activeTab === 'billing'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Usage</h2>
                    <div class="p-5 bg-[#FBFBFA] dark:bg-stone-800/50 border border-claude-border-light dark:border-claude-border-dark rounded-xl mb-6">
                        <div class="flex items-center justify-between mb-2"><span class="text-[15px] font-medium">Token usage</span><span class="text-[13px] text-gray-500" x-text="tokensUsed.toLocaleString()+' used · '+tokensLimit.toLocaleString()+' remaining'"></span></div>
                        <div class="w-full h-2.5 bg-gray-100 dark:bg-stone-700 rounded-full overflow-hidden"><div class="h-full bg-[#D97757] rounded-full" :style="'width:'+Math.min(100,Math.round(tokensUsed/Math.max(1,tokensUsed+tokensLimit)*100))+'%'"></div></div>
                    </div>
                    <template x-for="row in tokenBreakdown" :key="row.model">
                        <div class="flex items-center justify-between text-[13px] py-1"><span x-text="row.model"></span><span x-text="row.total.toLocaleString()"></span></div>
                    </template>
                </div>

                {{-- Capabilities tab --}}
                <div x-show="activeTab === 'capabilities'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Capabilities</h2>
                    <div class="space-y-8">
                        <div class="flex items-start justify-between"><div><label class="text-[15px] font-medium">Web Search</label><p class="text-[13.5px] text-gray-500">Allow searching the web for up-to-date information.</p></div><div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer mt-1" :class="capWebSearch?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="capWebSearch=!capWebSearch; save('cap_web_search')"><span class="inline-block h-5 w-5 transform rounded-full bg-white shadow" :class="capWebSearch?'translate-x-5':'translate-x-[2px]'"></span></div></div>
                        <div class="border-t border-claude-border-light dark:border-claude-border-dark pt-6"><div class="flex items-start justify-between"><div><label class="text-[15px] font-medium">Artifacts</label><p class="text-[13.5px] text-gray-500">Generate standalone artifacts like code and documents.</p></div><div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer mt-1" :class="capArtifacts?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="capArtifacts=!capArtifacts; save('cap_artifacts')"><span class="inline-block h-5 w-5 transform rounded-full bg-white shadow" :class="capArtifacts?'translate-x-5':'translate-x-[2px]'"></span></div></div></div>
                        <div class="border-t border-claude-border-light dark:border-claude-border-dark pt-6"><div class="flex items-start justify-between"><div><label class="text-[15px] font-medium">Code Execution</label><p class="text-[13.5px] text-gray-500">Run code in a secure sandbox.</p></div><div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer mt-1" :class="capCodeExecution?'bg-[#D97757]':'bg-gray-200 dark:bg-stone-600'" @click="capCodeExecution=!capCodeExecution; save('cap_code_execution')"><span class="inline-block h-5 w-5 transform rounded-full bg-white shadow" :class="capCodeExecution?'translate-x-5':'translate-x-[2px]'"></span></div></div></div>
                    </div>
                </div>

                {{-- AI Models tab --}}
                <div x-show="activeTab === 'models'" x-cloak x-transition>
                    <div class="flex items-center justify-between"><h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200">AI Models</h2></div>
                    <template x-if="aiModels.length === 0"><p class="text-gray-500 text-sm mt-4">No custom models yet.</p></template>
                    <template x-for="m in aiModels" :key="m.id">
                        <div class="flex items-center justify-between py-3 border-b border-claude-border-light dark:border-claude-border-dark">
                            <div><span class="font-medium text-[14px]" x-text="m.code"></span><br><span class="text-[12px] text-gray-500" x-text="m.provider"></span></div>
                            <div class="flex gap-2"><button @click="toggleModelActive(m)" class="px-2 py-1 rounded text-[11px] font-medium" :class="m.is_active?'bg-green-100 text-green-700':'bg-red-100 text-red-700'" x-text="m.is_active?'Active':'Inactive'"></button><button @click="deleteModel(m)" class="text-red-500 hover:text-red-700 text-[13px]">Delete</button></div>
                        </div>
                    </template>
                </div>

                {{-- Rynude Code tab --}}
                <div x-show="activeTab === 'claude-code'" x-cloak x-transition>
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Rynude Code</h2>
                    <p class="text-[14px] text-gray-500 mb-6">AI coding assistant that lives in your terminal.</p>
                    <div class="bg-gray-900 text-gray-300 font-mono text-[13px] p-4 rounded-lg">npm install -g @anthropic-ai/rynude-code</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function settingsState() {
    return {
        open: false, activeTab: 'general', flashMessage: null, flashType: 'success',
        name: '', nickname: '', profession: '', customInstructions: '',
        language: 'en', chatFont: 'default', theme: 'light', fontSize: 'medium', accentColor: '#D97757', compactMode: false,
        allowTraining: false, capWebSearch: true, capArtifacts: true, capCodeExecution: false,
        anthropicApiKey: '', openaiApiKey: '', nineRouterApiKey: '', googleApiKey: '', mistralApiKey: '',
        useProxy: false, proxyBaseUrl: '', proxyApiKey: '', huggingfaceApiKey: '',
        tokensUsed: 0, tokensLimit: 0, tokenBreakdown: [], aiModels: [],
        accentColors: ['#D97757','#5E72E4','#11998E','#E0529C','#F5A623','#8B5CF6'],
        navItems: [
            {id:'general',label:'General'},{id:'appearance',label:'Appearance'},{id:'data',label:'Data & Privacy'},
            {id:'capabilities',label:'Capabilities'},{id:'api-keys',label:'API Keys'},{id:'billing',label:'Billing'},
            {id:'models',label:'AI Models'},{id:'claude-code',label:'Rynude Code'},
        ],

        get initials() { return this.name ? this.name.split(' ').map(s => s[0]).join('').toUpperCase().slice(0,2) : '?'; },

        init: function() { this.loadSettings(); },

        loadSettings: function() {
            fetch('/api/settings', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    var profile = resp.profile || {};
                    var prefs = resp.preferences || {};
                    var billing = resp.billing || {};
                    this.name = profile.name || '';
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
            this._patch({theme:this.theme,font_size:this.fontSize,accent_color:this.accentColor,compact_mode:this.compactMode});
        },

        saveApiKeys: function() {
            this._patch({
                anthropic_api_key: this.anthropicApiKey,
                openai_api_key: this.openaiApiKey,
                nine_router_api_key: this.nineRouterApiKey,
                google_api_key: this.googleApiKey,
                mistral_api_key: this.mistralApiKey,
                use_proxy: this.useProxy,
                proxy_base_url: this.proxyBaseUrl,
                proxy_api_key: this.proxyApiKey,
            }).then(function() {
                this.flashMessage = 'API Keys saved successfully!';
                this.flashType = 'success';
            }.bind(this));
        },

        _patch: function(data) {
            return fetch('/api/settings', {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify(data)
            }).then(function(r){return r.json()}).then(function(resp){
                document.dispatchEvent(new CustomEvent('theme-changed', {detail:{theme:this.theme}}));
            }.bind(this));
        },

        deleteAllChats: function() {
            if (!confirm('Are you sure? This will permanently delete ALL your chats.')) return;
            this._patch({_delete_chats: true}); // placeholder
        },

        exportAllChats: function(format) {
            window.location.href = '/api/chats?export=' + format;
        },

        toggleModelActive: function(model) {
            // placeholder
        },

        deleteModel: function(model) {
            // placeholder
        },
    };
}
</script>
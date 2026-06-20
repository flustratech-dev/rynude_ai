<div
    x-data="{ open: $wire.entangle('isOpen') }"
    x-init="
        $watch('open', value => {
            if (value) {
                document.body.classList.add('overflow-hidden');
            } else {
                document.body.classList.remove('overflow-hidden');
            }
        });
    "
    @keydown.escape.window="open = false; $wire.closeModal()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-[#1F1E1B]/40 backdrop-blur-sm"
        @click="$wire.closeModal()"
    ></div>

    {{-- Modal Container --}}
    <div
        class="absolute inset-0 flex items-center justify-center p-4"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="$wire.closeModal()"
            class="bg-white dark:bg-stone-900 w-full max-w-[900px] h-[95vh] md:h-[85vh] max-h-[700px] rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden border border-[#E5E5E5] dark:border-stone-700 relative"
        >
            {{-- Modal Sidebar (Tabs) --}}
            <div class="w-full md:w-[260px] bg-white dark:bg-stone-900 border-b md:border-b-0 md:border-r border-[#E5E5E5] dark:border-stone-700 p-3 md:p-4 flex md:flex-col gap-2 md:gap-1 flex-shrink-0 overflow-x-auto scrollbar-hide">
                {{-- Search --}}
                <div class="relative hidden md:block mb-4">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Search" class="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-lg text-sm focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 placeholder-gray-400 dark:placeholder-stone-500 text-gray-800 dark:text-stone-200">
                </div>

                <div class="px-3 py-1 hidden md:block mb-1">
                    <span class="text-xs font-medium text-gray-500 dark:text-stone-400">Settings</span>
                </div>

                @php
                    $navItems = [
                        ['id' => 'general', 'label' => 'General', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>'],
                        ['id' => 'account', 'label' => 'Account', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"></path>'],
                        ['id' => 'privacy', 'label' => 'Privacy', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>'],
                        ['id' => 'billing', 'label' => 'Billing', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"></path>'],
                        ['id' => 'capabilities', 'label' => 'Capabilities', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"></path>'],
                        ['id' => 'connectors', 'label' => 'Connectors', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z"></path>'],
                        ['id' => 'huggingface', 'label' => 'Hugging Face', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"></path>'],
                        ['id' => 'models', 'label' => 'AI Models', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"></path>'],
                        ['id' => 'claude-code', 'label' => 'Rynude Code', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"></path>'],
                        ['id' => 'api-keys', 'label' => 'API Keys', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"></path>'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    <button
                        wire:click="switchTab('{{ $item['id'] }}')"
                        class="w-auto md:w-full flex items-center gap-2 md:gap-3 px-3 md:px-3 py-2 md:py-2.5 rounded-xl text-[13px] md:text-[14px] transition-all duration-150 whitespace-nowrap {{ $activeTab === $item['id'] ? 'bg-[#EAE9E5] dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 font-medium' : 'text-[#6B6B6B] dark:text-stone-400 hover:bg-[#F3F2EE] dark:hover:bg-stone-800/50 hover:text-[#2D2825] dark:hover:text-stone-200' }}"
                    >
                        <svg class="w-[18px] h-[18px] flex-shrink-0 {{ $activeTab === $item['id'] ? 'text-[#2D2825] dark:text-stone-200' : 'text-[#6B6B6B] dark:text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $item['icon'] !!}
                        </svg>
                        <span>{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Modal Main Content --}}
            <div class="flex-1 bg-white dark:bg-stone-900 p-6 md:p-10 overflow-y-auto relative">
                {{-- Close Button --}}
                <button
                    @click="$wire.closeModal()"
                    class="absolute top-6 right-6 z-10 p-1.5 rounded-lg text-gray-500 dark:text-stone-400 hover:text-gray-800 dark:hover:text-stone-200 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- ========== GENERAL TAB ========== --}}
                <div x-show="$wire.activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    
                    {{-- Profile Section --}}
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Profile</h2>
                    
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Avatar</label>
                            <div class="w-10 h-10 rounded-full bg-[#EAE9E5] dark:bg-stone-800 flex items-center justify-center text-sm font-medium text-[#2D2825] dark:text-stone-300">MR</div>
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Full name</label>
                            <input wire:model="name" type="text" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">What should Rynude call you?</label>
                            <input type="text" value="ryan" class="w-full md:w-[340px] px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                        </div>

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 md:gap-0">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">What best describes your work?</label>
                            <div class="w-full md:w-[340px] flex md:justify-end">
                                <button class="flex items-center gap-1.5 text-[15px] text-gray-500 dark:text-stone-400 hover:text-gray-800 dark:hover:text-stone-200">
                                    Select
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Instructions for Rynude --}}
                    <div class="mt-8">
                        <h3 class="text-[15px] text-[#2D2825] dark:text-stone-200 mb-1">Instructions for Rynude</h3>
                        <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-4">
                            Rynude will keep these in mind across chats and Cowork within <a href="#" class="underline hover:text-gray-800 dark:hover:text-stone-200">Anthropic's guidelines</a>. <a href="#" class="underline hover:text-gray-800 dark:hover:text-stone-200">Learn more</a>
                        </p>
                        <textarea 
                            wire:model="customInstructions"
                            wire:change="saveProfile"
                            class="w-full h-24 p-3 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-gray-400 dark:placeholder-stone-500 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 resize-none" 
                            placeholder="e.g. keep explanations brief and to the point"
                        ></textarea>
                    </div>

                    {{-- Preferences Section --}}
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mt-12 mb-6">Preferences</h2>
                    
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Appearance</label>
                            <div class="flex items-center border border-[#E5E5E5] dark:border-stone-700 rounded-lg overflow-hidden bg-white dark:bg-stone-800">
                                <button 
                                    @click="setTheme('system')"
                                    :class="theme === 'system' ? 'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200' : 'hover:bg-gray-50 dark:hover:bg-stone-700/50 text-gray-600 dark:text-stone-400'"
                                    class="p-1.5 px-3 border-r border-[#E5E5E5] dark:border-stone-700 transition-colors"
                                    title="System Theme"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </button>
                                <button 
                                    @click="setTheme('light')"
                                    :class="theme === 'light' ? 'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200' : 'hover:bg-gray-50 dark:hover:bg-stone-700/50 text-gray-600 dark:text-stone-400'"
                                    class="p-1.5 px-3 border-r border-[#E5E5E5] dark:border-stone-700 transition-colors"
                                    title="Light Theme"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path></svg>
                                </button>
                                <button 
                                    @click="setTheme('dark')"
                                    :class="theme === 'dark' ? 'bg-[#F3F2EE] dark:bg-stone-700 text-gray-800 dark:text-stone-200' : 'hover:bg-gray-50 dark:hover:bg-stone-700/50 text-gray-600 dark:text-stone-400'"
                                    class="p-1.5 px-3 transition-colors"
                                    title="Dark Theme"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"></path></svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pb-8">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Chat font</label>
                            <button class="flex items-center gap-1.5 text-[15px] text-[#2D2825] dark:text-stone-300 hover:text-gray-600 dark:hover:text-stone-400">
                                Anthropic Serif
                                <svg class="w-4 h-4 text-gray-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ========== API KEYS TAB ========== --}}
                <div x-show="$wire.activeTab === 'api-keys'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">API Keys & Quota</h2>
                    
                    <div class="space-y-6">
                        <div class="p-4 bg-[#FBFBFA] dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl mb-6">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-stone-100 mb-1">Status Kuota</h3>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-stone-400">Sisa Kuota Token Anda:</span>
                                <span class="text-lg font-bold text-[#D97757]">{{ number_format($tokensLimit) }} Tokens</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">Anthropic API Key</label>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-2">Masukkan API Key Anthropic Anda untuk model Rynude.</p>
                                <input type="password" wire:model="anthropicApiKey" placeholder="sk-ant-..." class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 mb-4">
                            </div>
                            <div>
                                <label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">OpenAI API Key</label>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-2">Masukkan API Key OpenAI Anda untuk model GPT.</p>
                                <input type="password" wire:model="openaiApiKey" placeholder="sk-proj-..." class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 mb-6">
                            </div>
                            
                            <div class="pt-2 border-t border-[#E5E5E5] dark:border-stone-700"></div>
                            
                            <div>
                                <label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">9Router API Key</label>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-2">Masukkan API Key 9Router Anda.</p>
                                <input type="password" wire:model="nineRouterApiKey" placeholder="sk-..." class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 mb-6">
                            </div>

                            <div class="pt-4 border-t border-[#E5E5E5] dark:border-stone-700">
                                <label class="flex items-center gap-3 cursor-pointer mb-4">
                                    <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out" :class="$wire.useProxy ? 'bg-[#D97757]' : 'bg-gray-200 dark:bg-stone-600'" wire:click="$toggle('useProxy')">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition duration-200 ease-in-out" :class="$wire.useProxy ? 'translate-x-4' : 'translate-x-[3px]'"></span>
                                    </div>
                                    <div>
                                        <span class="text-[15px] text-[#2D2825] dark:text-stone-200 font-medium">Gunakan Custom Proxy API</span>
                                        <p class="text-[13px] text-gray-500 dark:text-stone-400">Gunakan endpoint OpenAI-compatible pihak ketiga (misal: OpenRouter, API2D).</p>
                                    </div>
                                </label>

                                <div x-show="$wire.useProxy" x-collapse>
                                    <div class="space-y-4 pt-2">
                                        <div>
                                            <label class="block text-[14px] text-[#2D2825] dark:text-stone-200 font-medium mb-1.5">Proxy Base URL</label>
                                            <input type="url" wire:model="proxyBaseUrl" placeholder="https://openrouter.ai/api/v1" class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                                        </div>
                                        <div>
                                            <label class="block text-[14px] text-[#2D2825] dark:text-stone-200 font-medium mb-1.5">Proxy API Key</label>
                                            <input type="password" wire:model="proxyApiKey" placeholder="sk-or-..." class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($apiKeyStatus === 'saved')
                                <div class="text-sm text-green-600 dark:text-green-400 mt-2">API Keys berhasil disimpan!</div>
                            @endif

                            <div class="flex justify-end mt-4">
                                <button type="button" wire:click="saveApiKeys" class="px-4 py-2 bg-[#D97757] text-white rounded-lg text-sm font-medium hover:bg-[#c66547] transition-colors">Simpan API Keys</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== ACCOUNT TAB ========== --}}
                <div x-show="$wire.activeTab === 'account'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Account</h2>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-[15px] text-[#2D2825] dark:text-stone-300">Email address</label>
                            <div class="text-[15px] text-gray-500 dark:text-stone-400">user@example.com</div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-[15px] text-[#2D2825] dark:text-stone-300 block mb-1">Delete account</label>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400">Permanently delete your account and all of its contents from Rynude.</p>
                            </div>
                            <button class="px-4 py-2 bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors">Delete</button>
                        </div>
                    </div>
                </div>

                {{-- ========== PRIVACY TAB ========== --}}
                <div x-show="$wire.activeTab === 'privacy'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Privacy</h2>
                    <div class="space-y-8">
                        <div>
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Train on your conversations</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Allow Anthropic to use your conversations to train our models. This helps us improve Rynude for everyone.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" value="" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-stone-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#D97757]"></div>
                                </label>
                            </div>
                        </div>
                        <div class="border-t border-[#E5E5E5] dark:border-stone-700 pt-6">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Export data</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Request an export of your account data. You will receive an email when it's ready.</p>
                                </div>
                                <button class="px-4 py-2 border border-[#E5E5E5] dark:border-stone-700 text-[#2D2825] dark:text-stone-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-stone-800 transition-colors">Export data</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== BILLING TAB ========== --}}
                <div x-show="$wire.activeTab === 'billing'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Billing</h2>
                    
                    <div class="p-5 bg-[#FBFBFA] dark:bg-stone-800/50 border border-[#E5E5E5] dark:border-stone-700 rounded-xl mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-[16px] font-medium text-[#2D2825] dark:text-stone-200">Free Plan</h3>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mt-1">You are currently on the free plan.</p>
                            </div>
                            <span class="px-3 py-1 bg-gray-100 dark:bg-stone-700 text-gray-600 dark:text-stone-300 text-xs font-medium rounded-full">Current</span>
                        </div>
                        <button class="w-full py-2.5 bg-[#D97757] text-white rounded-lg text-sm font-medium hover:bg-[#c66547] transition-colors">Upgrade to Pro</button>
                    </div>

                    <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-4">Pro features include:</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-[14px] text-gray-600 dark:text-stone-300">
                            <svg class="w-5 h-5 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Priority access during high-traffic periods
                        </li>
                        <li class="flex items-center gap-3 text-[14px] text-gray-600 dark:text-stone-300">
                            <svg class="w-5 h-5 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Access to our most capable model, Claude 3.5 Sonnet
                        </li>
                        <li class="flex items-center gap-3 text-[14px] text-gray-600 dark:text-stone-300">
                            <svg class="w-5 h-5 text-[#D97757]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Early access to new features
                        </li>
                    </ul>
                </div>

                {{-- ========== CAPABILITIES TAB ========== --}}
                <div x-show="$wire.activeTab === 'capabilities'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Capabilities</h2>
                    <div class="space-y-8">
                        <div>
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Web Search (Beta)</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Allow Rynude to search the web for up-to-date information to answer your questions more accurately.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" value="" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-stone-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#D97757]"></div>
                                </label>
                            </div>
                        </div>
                        <div class="border-t border-[#E5E5E5] dark:border-stone-700 pt-6">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Artifacts</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Enable Rynude to generate standalone artifacts like code, documents, and SVGs in a dedicated panel.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" value="" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-stone-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#D97757]"></div>
                                </label>
                            </div>
                        </div>
                        <div class="border-t border-[#E5E5E5] dark:border-stone-700 pt-6">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <label class="text-[15px] text-[#2D2825] dark:text-stone-300 font-medium block mb-1">Code Execution (Beta)</label>
                                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 max-w-[500px]">Allow Rynude to write and run code in a secure sandbox to perform complex calculations and data analysis.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" value="" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-stone-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#D97757]"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== CONNECTORS TAB ========== --}}
                <div x-show="$wire.activeTab === 'connectors'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Connectors</h2>
                    <p class="text-[14px] text-gray-500 dark:text-stone-400 mb-6">Connect Rynude to your tools to let it read context and perform actions on your behalf.</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-white dark:bg-stone-800/50">
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
                        
                        <div class="flex items-center justify-between p-4 border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-white dark:bg-stone-800/50">
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

                {{-- ========== HUGGING FACE TAB ========== --}}
                <div x-show="$wire.activeTab === 'huggingface'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Hugging Face Serverless</h2>
                    
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[15px] text-[#2D2825] dark:text-stone-200 font-medium mb-2">Hugging Face API Key</label>
                                <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-2">Masukkan API Key (Token) dari akun Hugging Face Anda.</p>
                                <input type="password" wire:model="huggingfaceApiKey" placeholder="hf_..." class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[15px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500 mb-4">
                            </div>
                            @if($hfStatus === 'saved')
                                <div class="text-sm text-green-600 dark:text-green-400 mt-2">Pengaturan Hugging Face berhasil disimpan!</div>
                            @endif

                            <div class="flex justify-end mt-4 mb-8">
                                <button type="button" wire:click="saveHuggingface" class="px-4 py-2 bg-[#D97757] text-white rounded-lg text-sm font-medium hover:bg-[#c66547] transition-colors">Simpan Konfigurasi</button>
                            </div>

                            <div class="pt-6 border-t border-[#E5E5E5] dark:border-stone-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-bold text-[16px] text-[#2D2825] dark:text-stone-200">Custom Hugging Face Models</h3>
                                    <button wire:click="createModel" class="px-3 py-1.5 bg-[#F3F2EE] dark:bg-stone-700 text-[#2D2825] dark:text-stone-200 rounded-lg text-sm font-medium hover:bg-[#EAE9E5] dark:hover:bg-stone-600 transition-colors">+ Add HF Model</button>
                                </div>
                                <p class="text-[13px] text-gray-500 dark:text-stone-400 mb-4">Tambahkan model spesifik (contoh: <code>zai-org/GLM-4.7-Flash</code>, <code>meta-llama/Meta-Llama-3-8B-Instruct</code>).</p>
                                
                                <div class="overflow-x-auto border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-white dark:bg-stone-800/50">
                                    <table class="w-full text-left text-sm text-gray-600 dark:text-stone-400">
                                        <thead class="bg-[#F3F2EE] dark:bg-stone-800 text-gray-700 dark:text-stone-300">
                                            <tr>
                                                <th class="px-4 py-3 font-medium">Model ID</th>
                                                <th class="px-4 py-3 font-medium">Name</th>
                                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#E5E5E5] dark:divide-stone-700">
                                            @php $hasHf = false; @endphp
                                            @foreach($aiModels as $model)
                                                @php
                                                    $isHf = is_array($model) ? (($model['provider'] ?? '') === 'huggingface') : (($model->provider ?? '') === 'huggingface');
                                                @endphp
                                                @if($isHf)
                                                    @php $hasHf = true; @endphp
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-stone-700/30 transition-colors">
                                                        <td class="px-4 py-3 font-mono text-[13px] text-gray-800 dark:text-stone-200">{{ is_array($model) ? $model['code'] : $model->code }}</td>
                                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-stone-100">{{ is_array($model) ? $model['name'] : $model->name }}</td>
                                                        <td class="px-4 py-3 text-right">
                                                            <button wire:click="deleteModel({{ is_array($model) ? $model['id'] : $model->id }})" class="text-red-500 hover:text-red-700 font-medium text-[13px]" onclick="confirm('Delete this Hugging Face model?') || event.stopImmediatePropagation()">Delete</button>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            
                                            @if(!$hasHf)
                                                <tr>
                                                    <td colspan="3" class="px-4 py-4 text-center text-gray-500">Belum ada model Hugging Face.</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== AI MODELS TAB ========== --}}
                <div x-show="$wire.activeTab === 'models'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200">AI Models Management</h2>
                        <button wire:click="createModel" class="px-4 py-2 bg-[#D97757] text-white rounded-lg text-sm font-medium hover:bg-[#c66547] transition-colors">+ Add Model</button>
                    </div>

                    @if (session()->has('modelMessage'))
                        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-stone-800 dark:text-green-400 border border-green-200 dark:border-stone-700" role="alert">
                            {{ session('modelMessage') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-white dark:bg-stone-800/50">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-stone-400">
                            <thead class="bg-[#F3F2EE] dark:bg-stone-800 text-gray-700 dark:text-stone-300">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Model Code</th>
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium text-center">Status</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E5E5E5] dark:divide-stone-700">
                                @foreach($aiModels as $model)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-stone-700/30 transition-colors">
                                        <td class="px-4 py-3 font-mono text-[13px] text-gray-800 dark:text-stone-200">{{ $model->code }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-stone-100">{{ $model->name }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <button 
                                                wire:click="toggleModelActive({{ $model->id }})"
                                                class="px-3 py-1 rounded-full text-[11px] font-medium transition-colors {{ $model->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' }}"
                                            >
                                                {{ $model->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button wire:click="editModel({{ $model->id }})" class="text-[#D97757] hover:text-[#c66547] font-medium text-[13px] mr-3">Edit</button>
                                            <button wire:click="deleteModel({{ $model->id }})" class="text-red-500 hover:text-red-700 font-medium text-[13px]" onclick="confirm('Are you sure you want to delete this model?') || event.stopImmediatePropagation()">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>

                {{-- ========== RYNUDE CODE TAB ========== --}}
                <div x-show="$wire.activeTab === 'claude-code'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h2 class="font-bold text-lg text-[#2D2825] dark:text-stone-200 mb-6">Rynude Code</h2>
                    <p class="text-[14px] text-gray-500 dark:text-stone-400 mb-6">Rynude Code is an AI coding assistant that lives in your terminal. It understands your codebase and helps you write code faster.</p>
                    
                    <div class="p-6 border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-gray-50 dark:bg-stone-800/30 mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-[#2D2825] dark:bg-stone-900 text-white flex items-center justify-center font-mono text-sm">$&gt;</div>
                            <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Install via npm</h3>
                        </div>
                        <div class="bg-gray-900 text-gray-300 font-mono text-[13px] p-4 rounded-lg flex items-center justify-between">
                            <span>npm install -g @anthropic-ai/claude-code</span>
                            <button class="text-gray-400 hover:text-white transition-colors" title="Copy to clipboard">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-3">Authentication</h3>
                    <p class="text-[13.5px] text-gray-500 dark:text-stone-400 mb-4">Run the following command in your terminal to authenticate with your Anthropic account:</p>
                    
                    <div class="bg-gray-900 text-gray-300 font-mono text-[13px] p-4 rounded-lg flex items-center justify-between mb-6">
                        <span>claude auth login</span>
                        <button class="text-gray-400 hover:text-white transition-colors" title="Copy to clipboard">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </button>
                    </div>

                    <a href="#" class="inline-flex items-center gap-1.5 text-sm font-medium text-[#D97757] hover:text-[#c66547] transition-colors">
                        View documentation
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>

                {{-- Fallback for any unknown tabs --}}
                <div x-show="!['general', 'api-keys', 'account', 'privacy', 'billing', 'capabilities', 'connectors', 'models', 'claude-code', 'huggingface'].includes($wire.activeTab)" x-cloak style="display: none;" class="flex items-center justify-center h-full text-gray-400 dark:text-stone-500">
                    Content for <span x-text="$wire.activeTab" class="ml-1 font-medium text-gray-600 dark:text-stone-300"></span> will go here.
                </div>

                <!-- Create/Edit Modal overlay (Global) -->
                @if($isModelModalOpen)
                    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                        <div class="bg-white dark:bg-stone-900 w-full max-w-sm rounded-xl shadow-2xl border border-gray-200 dark:border-stone-700 overflow-hidden">
                            <div class="p-5 border-b border-gray-200 dark:border-stone-700 flex justify-between items-center bg-[#F3F2EE] dark:bg-stone-800">
                                <h3 class="font-bold text-[#2D2825] dark:text-stone-100">{{ $editModelId ? 'Edit Model' : 'Add New Model' }}</h3>
                                <button wire:click="closeModelModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-stone-300">&times;</button>
                            </div>
                            <div class="p-5">
                                <div class="mb-4">
                                    <label class="block text-[14px] text-[#2D2825] dark:text-stone-300 font-medium mb-1.5">Model Code (ID)</label>
                                    <input type="text" wire:model="modelCode" class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500" placeholder="e.g. google/gemma-7b">
                                    @error('modelCode') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-4">
                                    <label class="block text-[14px] text-[#2D2825] dark:text-stone-300 font-medium mb-1.5">Model Name (Display)</label>
                                    <input type="text" wire:model="modelName" class="w-full px-3 py-2.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 text-[14px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:border-gray-400 dark:focus:border-stone-500" placeholder="e.g. Gemma 7B">
                                    @error('modelName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-6 flex items-center mt-6">
                                    <input type="checkbox" id="modelIsActive" wire:model="modelIsActive" class="w-4 h-4 text-[#D97757] bg-gray-100 border-gray-300 rounded focus:ring-[#D97757] dark:bg-stone-700 dark:border-stone-600">
                                    <label for="modelIsActive" class="ml-2 text-[14px] font-medium text-[#2D2825] dark:text-stone-300">Set as Active</label>
                                </div>
                                <div class="flex justify-end gap-3 pt-4 border-t border-[#E5E5E5] dark:border-stone-700">
                                    <button wire:click="closeModelModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-stone-800 dark:text-stone-300 dark:border-stone-600 dark:hover:bg-stone-700 transition-colors">Cancel</button>
                                    <button wire:click="storeModel" class="px-4 py-2 text-sm font-medium text-white bg-[#D97757] rounded-lg hover:bg-[#c66547] transition-colors">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

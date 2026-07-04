<div class="flex w-full h-full bg-[#F9F8F6] dark:bg-[#1E1E1E]" x-data="{
    activeTab: '{{ $activeTab ?? 'dashboard' }}',
    setTab(tab) { this.activeTab = tab; },
}">
    {{-- Customize Sidebar --}}
    <div class="w-[280px] flex-shrink-0 border-r border-[#E5E5E5] dark:border-stone-700 bg-[#F9F8F6] dark:bg-[#1E1E1E] flex flex-col hidden md:flex">
        <div class="px-4 py-5 flex items-center">
            <button @click="$dispatch('close-customize')" class="mr-2 text-stone-500 hover:text-stone-800 transition-colors">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </button>
            <h2 class="text-base font-medium text-[#2D2825] dark:text-stone-200">Customize</h2>
        </div>

        <div class="px-2 mt-2 space-y-1">
            <button @click="setTab('skills_list')" class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors" :class="activeTab === 'skills_list' || activeTab === 'create_skill' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-200 hover:bg-[#EAE9E5] dark:hover:bg-stone-800'">
                <svg class="w-[18px] h-[18px] text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                Skills
            </button>
            <button @click="setTab('templates')" class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors" :class="activeTab === 'templates' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-medium' : 'text-[#2D2825] dark:text-stone-200 hover:bg-[#EAE9E5] dark:hover:bg-stone-800'">
                <svg class="w-[18px] h-[18px] text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Templates
            </button>
            <button @click="setTab('dashboard')" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-[#2D2825] dark:text-stone-200 rounded-lg hover:bg-[#EAE9E5] dark:hover:bg-stone-800 transition-colors">
                <svg class="w-[18px] h-[18px] text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Connectors
            </button>
        </div>

        <div class="mt-8 px-5">
            <div class="flex items-center justify-between mb-3 text-xs font-medium text-stone-400 uppercase tracking-wider">
                <span>Personal plugins</span>
                <button class="hover:text-stone-600 transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
            </div>

            <div class="text-center mt-6">
                <p class="text-[13px] text-stone-500 mb-4 px-2 leading-relaxed">Give Rynude role-level expertise with plugins</p>
                <button @click="setTab('dashboard')" class="w-full py-1.5 px-3 border border-[#E5E5E5] bg-[#FCFBFA] dark:bg-[#323232] text-[#2D2825] dark:text-stone-200 rounded-lg text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors shadow-sm">
                    Browse plugins
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="flex-1 bg-transparent dark:bg-[#1E1E1E] flex flex-col items-center justify-center p-8 overflow-y-auto">

        <template x-if="activeTab === 'dashboard'">
            <div class="max-w-[600px] w-full flex flex-col items-center">
                <div class="mb-6 text-stone-800 dark:text-stone-300">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="8" width="16" height="12" rx="2"></rect>
                        <path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="4" y1="14" x2="20" y2="14"></line>
                        <path d="M9 14v2"></path>
                        <path d="M15 14v2"></path>
                    </svg>
                </div>

                <h1 class="font-serif text-[28px] font-medium text-[#2D2825] dark:text-stone-200 mb-2">Customize Rynude</h1>
                <p class="text-[15px] text-stone-500 dark:text-stone-400 mb-10 text-center">Skills, connectors, and plugins shape how Rynude works with you.</p>

                <div class="w-full space-y-4">
                    <button class="w-full flex items-center gap-5 p-5 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-800 rounded-2xl hover:shadow-md hover:border-stone-300 transition-all text-left group">
                        <div class="w-10 h-10 rounded-xl bg-stone-100 dark:bg-stone-700 flex items-center justify-center text-stone-600 dark:text-stone-300 group-hover:bg-stone-200 dark:group-hover:bg-stone-600 transition-colors shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Connect your apps</h3>
                            <p class="text-[13.5px] text-stone-500 mt-0.5">Let Rynude read and write to the tools you already use.</p>
                        </div>
                    </button>

                    <button @click="setTab('create_skill')" class="w-full flex items-center gap-5 p-5 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-800 rounded-2xl hover:shadow-md hover:border-stone-300 transition-all text-left group">
                        <div class="w-10 h-10 rounded-xl bg-stone-100 dark:bg-stone-700 flex items-center justify-center text-stone-600 dark:text-stone-300 group-hover:bg-stone-200 dark:group-hover:bg-stone-600 transition-colors shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path><path d="M14 3v5h5M16 13H8M16 17H8M10 9H8"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Create new skills</h3>
                            <p class="text-[13.5px] text-stone-500 mt-0.5">Teach Rynude your processes, team norms, and expertise.</p>
                        </div>
                    </button>

                    <button @click="setTab('templates')" class="w-full flex items-center gap-5 p-5 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-800 rounded-2xl hover:shadow-md hover:border-stone-300 transition-all text-left group">
                        <div class="w-10 h-10 rounded-xl bg-stone-100 dark:bg-stone-700 flex items-center justify-center text-stone-600 dark:text-stone-300 group-hover:bg-stone-200 dark:group-hover:bg-stone-600 transition-colors shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">Start from a template</h3>
                            <p class="text-[13.5px] text-stone-500 mt-0.5">Pick a pre-built skill and customize it in seconds.</p>
                        </div>
                    </button>
                </div>
            </div>
        </template>

        <template x-if="activeTab === 'skills_list'">
            <div class="max-w-[700px] w-full flex flex-col h-full items-start justify-start py-8">
                <div class="flex items-center justify-between w-full mb-8">
                    <h1 class="font-serif text-[28px] font-medium text-[#2D2825] dark:text-stone-200">Skills</h1>
                    <div class="flex items-center gap-2">
                        <button @click="setTab('templates')" class="px-4 py-2 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 text-[#2D2825] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-[#3A3A38] rounded-xl text-sm font-medium transition-colors shadow-sm">
                            Templates
                        </button>
                        <button @click="setTab('create_skill')" class="px-4 py-2 bg-[#2D2825] hover:bg-black text-white dark:bg-stone-200 dark:text-stone-900 dark:hover:bg-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                            Create skill
                        </button>
                    </div>
                </div>

                @if(count($skills ?? []) > 0)
                    <div class="w-full space-y-3">
                        @foreach($skills as $skill)
                            <div class="w-full p-4 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-sm flex items-center justify-between">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-[20px] shrink-0">
                                        {{ $skill['icon'] ?? '🛠️' }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $skill['name'] }}</h3>
                                        <p class="text-[13px] text-stone-500 mt-1 line-clamp-1">{{ $skill['description'] ?: 'No description provided.' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" {{ $skill['is_active'] ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-stone-200 peer-focus:outline-none rounded-full peer dark:bg-stone-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-[#D97757]"></div>
                                    </label>
                                    <button class="text-stone-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center text-center w-full py-16">
                        <div class="w-16 h-16 bg-stone-100 rounded-2xl flex items-center justify-center text-stone-400 mb-4">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path><path d="M14 3v5h5M16 13H8M16 17H8M10 9H8"></path></svg>
                        </div>
                        <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200">No skills yet</h3>
                        <p class="text-[13.5px] text-stone-500 mt-2 mb-6">Create your first skill to teach the AI custom instructions.</p>
                        <button @click="setTab('create_skill')" class="px-4 py-2 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 text-[#2D2825] dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-[#3A3A38] rounded-xl text-sm font-medium transition-colors shadow-sm">
                            Create new skill
                        </button>
                    </div>
                @endif
            </div>
        </template>

        <template x-if="activeTab === 'templates'">
            <div class="max-w-[760px] w-full flex flex-col h-full items-start justify-start py-8">
                <button @click="setTab('skills_list')" class="flex items-center gap-2 text-stone-500 hover:text-stone-800 text-sm font-medium mb-6 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Skills
                </button>

                <h1 class="font-serif text-[28px] font-medium text-[#2D2825] dark:text-stone-200 mb-2">Skill templates</h1>
                <p class="text-[14px] text-stone-500 mb-8">Start from a pre-built skill and tweak the instructions to fit your needs.</p>

                <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($skillTemplates ?? [] as $index => $template)
                        <div class="p-5 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 rounded-2xl shadow-sm flex flex-col">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-[20px] shrink-0">
                                    {{ $template['icon'] }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $template['name'] }}</h3>
                                    <span class="text-[11px] font-medium text-stone-500 uppercase tracking-wider">{{ $template['category'] }}</span>
                                </div>
                            </div>
                            <p class="text-[13px] text-stone-500 dark:text-stone-400 leading-relaxed mb-4 flex-1">{{ $template['description'] }}</p>
                            <button @click="setTab('create_skill')" class="w-full py-2 px-3 bg-[#2D2825] hover:bg-black text-white dark:bg-stone-200 dark:text-stone-900 dark:hover:bg-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                                Use this template
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </template>

        <template x-if="activeTab === 'create_skill'">
            <div class="max-w-[700px] w-full flex flex-col h-full items-start justify-start py-8">
                <button @click="setTab('skills_list')" class="flex items-center gap-2 text-stone-500 hover:text-stone-800 text-sm font-medium mb-6 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Skills
                </button>

                <h1 class="font-serif text-[28px] font-medium text-[#2D2825] dark:text-stone-200 mb-2">Create new skill</h1>
                <p class="text-[14px] text-stone-500 mb-8">Define custom instructions to shape how the AI behaves.</p>

                <form class="w-full space-y-5" x-data="{
                    skillIcon: '🛠️',
                    skillName: '',
                    skillDescription: '',
                    skillInstructions: '',
                    icons: ['🛠️','🧠','💻','📝','🎨','🔍','📊','🤖','⚡','📋','🔒','🌐','🎯','📚','💬','🔄']
                }" @submit.prevent="fetch('/api/skills', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content }, body: JSON.stringify({ icon: skillIcon, name: skillName, description: skillDescription, instructions: skillInstructions }) }).then(() => setTab('skills_list'))">
                    <div>
                        <label class="block text-[13px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Icon</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="icon in icons" :key="icon">
                                <button type="button" @click="skillIcon = icon"
                                    class="w-9 h-9 rounded-lg flex items-center justify-center text-[18px] border transition-all"
                                    :class="skillIcon === icon ? 'border-[#D97757] ring-2 ring-[#D97757]/30 bg-[#D97757]/5' : 'border-[#E5E5E5] dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800'"
                                    x-text="icon">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[13px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Skill name *</label>
                        <input type="text" x-model="skillName" placeholder="e.g. Code Reviewer" class="w-full px-3 py-2 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 rounded-lg text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none focus:ring-0 focus:border-stone-300 dark:focus:border-stone-500" required>
                    </div>

                    <div>
                        <label class="block text-[13px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Description (optional)</label>
                        <input type="text" x-model="skillDescription" placeholder="What does this skill do?" class="w-full px-3 py-2 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 rounded-lg text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none focus:ring-0 focus:border-stone-300 dark:focus:border-stone-500">
                    </div>

                    <div>
                        <label class="block text-[13px] font-medium text-[#2D2825] dark:text-stone-300 mb-1.5">Instructions *</label>
                        <textarea x-model="skillInstructions" rows="6" placeholder="You are an expert code reviewer. When I provide code, you should..." class="w-full px-3 py-2 bg-[#FCFBFA] dark:bg-[#323232] border border-[#E5E5E5] dark:border-stone-700 rounded-lg text-sm text-[#2D2825] dark:text-stone-200 focus:outline-none focus:ring-0 focus:border-stone-300 dark:focus:border-stone-500" required></textarea>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-[#D97757] hover:bg-[#C86444] text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                            Save skill
                        </button>
                    </div>
                </form>
            </div>
        </template>

    </div>
</div>

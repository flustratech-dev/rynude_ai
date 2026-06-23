<div class="h-full w-full flex flex-col bg-[#F9F8F6] dark:bg-stone-900 overflow-hidden shadow-2xl md:shadow-none {{ $fullscreen ? 'fixed inset-0 z-[60] !shadow-2xl' : '' }}">
    @if($isOpen && $currentArtifact)
        {{-- Panel Header --}}
        <div class="px-4 py-3 flex items-center justify-between bg-transparent shrink-0 z-10 relative">
            <div x-data="{ openMenu: false }" class="flex items-center gap-2 max-w-[50%] relative">
                @if($currentArtifact['language'] === 'new')
                    <button @click="openMenu = !openMenu" @click.away="openMenu = false" class="flex items-center gap-1.5 px-2 py-1 -ml-2 rounded-md hover:bg-[#F3F2F1] dark:hover:bg-stone-700 transition-colors text-[14px] font-medium text-[#2D2825] dark:text-stone-200">
                        Untitled
                        <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    {{-- Dropdown Menu --}}
                    <div x-show="openMenu" x-transition.opacity.duration.200ms style="display: none;" class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-50">
                        <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            Star
                        </button>
                        <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            Rename
                        </button>
                        <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            Add to project
                        </button>
                        <div class="h-px w-full bg-[#E5E5E5] dark:bg-stone-700 mb-1"></div>
                        <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            Delete
                        </button>
                    </div>
                @else
                    <div class="w-6 h-6 rounded-md bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-600 dark:text-stone-300 flex-shrink-0">
                        @if($currentArtifact['language'] === 'php' || $currentArtifact['type'] === 'code')
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        @else
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        @endif
                    </div>
                    <h2 class="text-[13px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $currentArtifact['title'] }}</h2>
                @endif
            </div>

            {{-- Middle: Preview/Code Tabs --}}
            <div class="absolute left-1/2 -translate-x-1/2 flex items-center bg-[#F3F2F1] dark:bg-stone-700 p-1 rounded-lg">
                <button 
                    wire:click="$set('activeTab', 'preview')" 
                    class="px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200 {{ $activeTab === 'preview' ? 'bg-white dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 shadow-sm' : 'text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300' }}"
                >
                    Preview
                </button>
                <button 
                    wire:click="$set('activeTab', 'code')" 
                    class="px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200 {{ $activeTab === 'code' ? 'bg-white dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 shadow-sm' : 'text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300' }}"
                >
                    Code
                </button>
            </div>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-1.5">
                @if($currentArtifact['language'] !== 'new')
                    <button wire:click="copyCode" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Copy code">
                        @if($copied)
                            <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        @else
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        @endif
                    </button>
                    {{-- Download dropdown --}}
                    <div x-data="{ openDl: false }" class="relative">
                        <button @click="openDl = !openDl" @click.away="openDl = false" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300 flex items-center gap-0.5" title="Download">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div x-show="openDl" x-cloak x-transition.opacity.duration.200ms style="display: none;" class="absolute top-full right-0 mt-1 w-48 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-50">
                            <button @click="openDl = false" wire:click="downloadAsFile" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                Download as file
                            </button>
                            <button @click="openDl = false" wire:click="downloadAsPdf" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                                Download as PDF
                            </button>
                            <button @click="openDl = false" wire:click="copyCode" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                Copy all
                            </button>
                        </div>
                    </div>
                    {{-- Fullscreen toggle --}}
                    <button wire:click="toggleFullscreen" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="{{ $fullscreen ? 'Exit fullscreen' : 'Fullscreen' }}">
                        @if($fullscreen)
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path></svg>
                        @else
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                        @endif
                    </button>
                    {{-- Publish / share artifact --}}
                    @if(!empty($currentArtifact['id']))
                        @if(!empty($currentArtifact['is_public']))
                            <button wire:click="publishArtifact({{ $currentArtifact['id'] }})" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-green-600 dark:text-green-500" title="Published — copy public link">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            </button>
                            <button wire:click="unpublishArtifact({{ $currentArtifact['id'] }})" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Unpublish">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                            </button>
                        @else
                            <button wire:click="publishArtifact({{ $currentArtifact['id'] }})" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Publish to public link">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                            </button>
                        @endif
                    @endif
                    <div class="w-px h-4 bg-[#E5E5E5] dark:bg-stone-700 mx-1 hidden md:block"></div>
                @endif
                
                {{-- Close panel button --}}
                <button
                    @click="artifactPanelOpen = false"
                    wire:click="closeArtifact"
                    class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 flex items-center justify-center hover:text-stone-800 dark:hover:text-stone-300"
                    title="Close"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-hidden bg-transparent flex flex-col p-4 md:p-6">
            @if($currentArtifact['language'] === 'new')
                {{-- New Artifact View --}}
                <div class="flex-1 flex flex-col items-center justify-center p-8 overflow-y-auto">
                    <h2 class="font-serif text-[22px] font-medium text-[#2D2825] dark:text-stone-200 mb-8">Let's get cooking! Pick an artifact category or start building your idea from scratch.</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-[800px] w-full">
                        {{-- Cards --}}
                        <button wire:click="generateTemplate('Apps and websites')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Apps and<br>websites</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                            </div>
                        </button>
                        <button wire:click="generateTemplate('Documents and templates')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Documents and<br>templates</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </div>
                        </button>
                        <button wire:click="generateTemplate('Games')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Games</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                            </div>
                        </button>
                        <button wire:click="generateTemplate('Productivity tools')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Productivity<br>tools</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            </div>
                        </button>
                        <button wire:click="generateTemplate('Creative projects')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Creative<br>projects</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.836-.437-1.124-.298-.336-.492-.796-.492-1.303 0-1.066.86-1.935 1.926-1.935h1.967c3.164 0 5.738-2.574 5.738-5.738 0-5.63-5.234-10.212-10.35-10.212z"></path></svg>
                            </div>
                        </button>
                        <button wire:click="generateTemplate('Quiz or survey')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Quiz or survey</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            </div>
                        </button>
                        <button wire:click="generateTemplate('a new idea from scratch')" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                            <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left">Start from<br>scratch</span>
                            <div class="w-full flex justify-end">
                                <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            </div>
                        </button>
                    </div>
                </div>
            @else
                <div class="flex-1 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-[1rem] shadow-sm overflow-hidden flex flex-col relative">
                @if($activeTab === 'code')
                    {{-- Language indicator --}}
                    <div class="absolute top-0 right-0 px-3 py-1 bg-[#F3F2F1] dark:bg-stone-700 border-b border-l border-[#E5E5E5] dark:border-stone-700 rounded-bl-lg text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider z-10">
                        {{ $currentArtifact['language'] }}
                    </div>
                    {{-- Code Area --}}
                    <div class="flex-1 overflow-auto bg-[#FBFBFA] dark:bg-stone-900 p-4 pt-8 text-[13px] leading-relaxed text-stone-800 dark:text-stone-200 font-mono" x-data x-init="$nextTick(() => { if (window.hljs) hljs.highlightAll(); })">
                        <pre><code class="language-{{ $currentArtifact['language'] }}">{{ $currentArtifact['content'] }}</code></pre>
                    </div>
                @else
                    {{-- Preview Area --}}
                    <div class="flex-1 overflow-auto bg-[#FAFAFA] dark:bg-stone-900 rounded-b-[1rem]">
                        @if($currentArtifact['language'] === 'html')
                            <iframe sandbox="allow-scripts" srcdoc="{{ $currentArtifact['content'] }}" class="w-full h-full border-0 bg-white"></iframe>
                        @elseif($currentArtifact['language'] === 'react' || $currentArtifact['language'] === 'jsx' || $currentArtifact['language'] === 'tsx')
                            @php
                            $reactHtml = '<!DOCTYPE html><html><head><meta charset="utf-8" /><script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script><script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script><script src="https://unpkg.com/@babel/standalone/babel.min.js"></script><script src="https://cdn.tailwindcss.com"></script></head><body><div id="root"></div><script type="text/babel">'.$currentArtifact['content'].'
                            const App = typeof window.App !== "undefined" ? window.App : (typeof App !== "undefined" ? App : (typeof Example !== "undefined" ? Example : () => <div>Component not found</div>));
                            const root = ReactDOM.createRoot(document.getElementById("root"));
                            root.render(<App />);</script></body></html>';
                            @endphp
                            <iframe sandbox="allow-scripts" srcdoc="{{ $reactHtml }}" class="w-full h-full border-0 bg-white"></iframe>
                        @elseif($currentArtifact['language'] === 'svg')
                            <div class="flex items-center justify-center w-full h-full bg-white">
                                {!! $currentArtifact['content'] !!}
                            </div>
                        @elseif(in_array(strtolower($currentArtifact['language']), ['markdown', 'md']))
                            <div class="p-4 md:p-8 bg-[#F3F2F1] dark:bg-stone-900 overflow-y-auto">
                                <div class="w-full max-w-[210mm] min-h-[297mm] mx-auto bg-white dark:bg-stone-800 p-[15mm] md:p-[25mm] shadow-lg rounded-sm border border-[#E5E5E5] dark:border-stone-700">
                                    <div class="prose prose-stone text-justify max-w-none text-[#2D2825] dark:text-stone-200 dark:prose-invert prose-headings:font-bold prose-h1:text-center prose-h1:text-2xl prose-h2:text-xl prose-p:leading-relaxed prose-li:leading-relaxed">
                                        {!! \Illuminate\Support\Str::markdown($currentArtifact['content']) !!}
                                    </div>
                                </div>
                            </div>
                        @elseif($currentArtifact['type'] === 'code')
                            <div class="p-6 flex items-center justify-center h-full">
                                <div class="text-center text-stone-500 dark:text-stone-400 w-full max-w-2xl mx-auto">
                                    <div class="w-16 h-16 bg-[#F3F2F1] dark:bg-stone-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-stone-400 dark:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                    </div>
                                    <p class="text-[14px] font-medium text-stone-800 dark:text-stone-200 mb-1">Preview not available</p>
                                    <p class="text-[13px]">This backend code cannot be previewed visually.</p>
                                </div>
                            </div>
                        @else
                            <div class="p-4 md:p-8 bg-[#F3F2F1] dark:bg-stone-900 overflow-y-auto">
                                <div class="w-full max-w-[210mm] min-h-[297mm] mx-auto bg-white dark:bg-stone-800 p-[15mm] md:p-[25mm] shadow-lg rounded-sm border border-[#E5E5E5] dark:border-stone-700">
                                    <div class="prose prose-stone text-justify max-w-none text-[#2D2825] dark:text-stone-200 dark:prose-invert prose-headings:font-bold prose-h1:text-center prose-h1:text-2xl prose-h2:text-xl prose-p:leading-relaxed prose-li:leading-relaxed">
                                        {!! \Illuminate\Support\Str::markdown($currentArtifact['content']) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
                
                @if(count($versions) > 1)
                    <div class="absolute bottom-4 right-4 flex items-center bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-lg shadow-sm p-1 z-20">
                        @foreach($versions as $v)
                            <button
                                wire:click="switchVersion({{ $v['id'] }})"
                                class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $v['is_current'] ? 'bg-[#F3F2F1] dark:bg-stone-700 text-[#2D2825] dark:text-stone-200' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}"
                            >
                                V{{ $v['version_number'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif
        </div>
    @else
        {{-- Main Artifacts View (List) --}}
        <div class="flex-1 overflow-y-auto bg-[#F9F8F6] dark:bg-stone-900 p-8 flex flex-col items-center min-h-0 w-full">
            <div class="max-w-4xl w-full flex-1 flex flex-col">
                {{-- Top Header --}}
                <div class="flex items-center justify-between mb-8 w-full mt-4">
                    <h1 class="font-serif text-3xl font-medium text-[#2D2825] dark:text-stone-200">Artifacts</h1>
                    <button wire:click="createNewArtifact" class="bg-[#2D2825] hover:bg-black text-white dark:bg-stone-200 dark:text-stone-900 dark:hover:bg-white px-4 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">
                        New artifact
                    </button>
                </div>

                {{-- Search Bar --}}
                <div class="w-full mb-16">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-[18px] h-[18px] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="Search artifacts..." class="block w-full pl-10 pr-3 py-3 border border-[#E5E5E5] dark:border-stone-700 rounded-xl bg-white dark:bg-stone-800 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-stone-600 transition-all shadow-sm">
                    </div>
                </div>

                @php $visibleArtifacts = $this->filteredArtifacts; @endphp
                @if(count($visibleArtifacts) > 0)
                    {{-- Grid of Artifacts --}}
                    <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pb-12">
                        @foreach($visibleArtifacts as $artifact)
                            <div class="relative group h-[140px]" x-data="{ menuOpen: false }">
                                <button
                                    wire:click="openArtifact({{ $artifact['id'] }})"
                                    class="w-full text-left p-4 bg-white dark:bg-stone-800 hover:shadow-md border border-[#E5E5E5] dark:border-stone-700 rounded-2xl shadow-sm transition-all duration-200 group/card flex flex-col h-full relative overflow-hidden focus:outline-none"
                                >
                                    <div class="flex items-center justify-between w-full mb-auto">
                                        <div class="w-8 h-8 rounded-lg bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-500 dark:text-stone-400 group-hover/card:text-[#D97757] transition-colors shrink-0">
                                            @if($artifact['language'] === 'php' || $artifact['type'] === 'code')
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                            @else
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-[#F3F2F1] dark:bg-stone-700 text-[10px] font-medium text-stone-600 dark:text-stone-400 uppercase tracking-wider mr-6">
                                            {{ $artifact['language'] }}
                                        </span>
                                    </div>
                                    <div class="mt-4 w-full">
                                        <h4 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate mb-1">{{ $artifact['title'] }}</h4>
                                        <p class="text-[12px] text-stone-500 dark:text-stone-400">Created {{ \Carbon\Carbon::parse($artifact['created_at'])->diffForHumans() }}</p>
                                    </div>
                                </button>
                                
                                {{-- CRUD Dropdown Menu --}}
                                <button @click.stop="menuOpen = !menuOpen" @click.away="menuOpen = false" class="absolute top-3 right-3 p-1.5 text-stone-400 hover:text-stone-600 dark:hover:text-stone-300 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-700 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 z-10" title="Options">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                </button>
                                
                                <div x-show="menuOpen" style="display: none;" class="absolute top-10 right-3 w-40 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-20">
                                    <button @click.stop="menuOpen = false; let title = prompt('Rename artifact:', '{{ addslashes($artifact['title']) }}'); if(title) { @this.renameArtifact({{ $artifact['id'] }}, title) }" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Rename
                                    </button>
                                    <div class="h-px w-full bg-[#E5E5E5] dark:bg-stone-700 my-1"></div>
                                    <button @click.stop="menuOpen = false; if(confirm('Are you sure you want to delete this artifact?')) { @this.deleteArtifact({{ $artifact['id'] }}) }" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif(!empty(trim($searchQuery)))
                    {{-- No search results --}}
                    <div class="flex-1 flex flex-col items-center justify-center text-center pb-20">
                        <div class="w-12 h-12 rounded-2xl bg-[#F3F2F1] dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <h2 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-1">No artifacts found</h2>
                        <p class="text-[13px] text-gray-500 dark:text-stone-400">No artifacts match "{{ $searchQuery }}".</p>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="flex-1 flex flex-col items-center justify-center text-center pb-20">
                        {{-- Custom Icon --}}
                        <div class="mb-5 relative">
                            <svg class="w-[88px] h-[88px] text-[#2D2825] dark:text-stone-300" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.5">
                                <!-- Square -->
                                <rect x="35" y="25" width="22" height="22" stroke-linejoin="round" />
                                <!-- Triangle -->
                                <polygon points="68,25 78,42 58,42" stroke-linejoin="round" />
                                <!-- Circle -->
                                <circle cx="70" cy="55" r="11" />
                                <!-- Hand -->
                                <path d="M35,65 C35,65 30,65 30,55 C30,45 30,45 30,45 C30,42 33,42 33,45 L33,55 M37,42 C37,39 40,39 40,42 L40,55 M44,43 C44,40 47,40 47,43 L47,55 M51,45 C51,42 54,42 54,45 L54,60 C54,65 47,70 40,70 L35,70 Z" fill="white" stroke="currentColor" stroke-width="1.5" class="dark:fill-stone-800" />
                            </svg>
                        </div>
                        <h2 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-2.5">What will you build with artifacts?</h2>
                        <p class="text-[13px] text-gray-500 dark:text-stone-400 mb-6 leading-relaxed max-w-[340px] mx-auto">
                            If you can dream it, you can build it. Take apps, games, templates, and tools from thought to reality.
                        </p>
                        <button wire:click="createNewArtifact" class="bg-white hover:bg-gray-50 text-[#2D2825] dark:bg-stone-800 dark:hover:bg-stone-700 dark:text-stone-200 dark:border-stone-600 border border-[#E5E5E5] px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            New artifact
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

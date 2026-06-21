<div class="flex-1 overflow-y-auto w-full flex flex-col" x-data="{ tab: 'connectors', trigger: 'schedule' }">
    {{-- Breadcrumbs Header --}}
    <div class="px-8 py-6 border-b border-stone-200 dark:border-stone-800">
        <div class="flex items-center gap-2 text-[14px]">
            <button wire:click="$set('currentView', 'routines')" class="text-[#2D2825] dark:text-stone-200 hover:text-[#D97757] transition-colors flex items-center gap-1.5 font-medium">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Routines
            </button>
            <span class="text-stone-400">/</span>
            <span class="text-stone-600 dark:text-stone-400">New routine</span>
        </div>
    </div>

    {{-- Form Content --}}
    <div class="flex-1 overflow-y-auto p-8 max-w-4xl mx-auto w-full flex flex-col gap-8 pb-24">
        
        {{-- Name --}}
        <div>
            <label class="block text-[13px] text-[#2D2825] dark:text-stone-200 font-medium mb-1.5">
                Name <span class="text-[#D97757]">*</span>
            </label>
            <input type="text" placeholder="e.g., Daily code review" class="w-full bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 rounded-lg px-3 py-2 text-[14px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-stone-300 dark:focus:border-stone-700 shadow-sm placeholder:text-stone-400">
        </div>

        {{-- Instructions --}}
        <div>
            <label class="block text-[13px] text-stone-500 font-medium mb-1.5">Instructions</label>
            <div class="border border-[#E5E5E5] dark:border-stone-800 rounded-xl bg-white dark:bg-stone-900 shadow-sm flex flex-col overflow-hidden focus-within:ring-1 focus-within:ring-stone-300 focus-within:border-stone-300 dark:focus-within:ring-stone-700">
                <textarea rows="6" placeholder="Describe what Rynude should do in each session" class="w-full bg-transparent border-0 outline-none text-[14px] text-stone-800 dark:text-stone-200 placeholder:text-stone-400 p-4 resize-y min-h-[150px]"></textarea>
                
                {{-- Textarea Footer --}}
                <div class="bg-white dark:bg-stone-900 border-t border-[#E5E5E5] dark:border-stone-800 px-3 py-2 flex items-center justify-between">
                    <button class="flex items-center gap-1.5 text-[12px] text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
                        Select a repository
                    </button>
                    
                    <div class="flex items-center gap-3">
                        <button class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-300">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
                        </button>
                        <button class="flex items-center gap-1 text-[12px] text-stone-600 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Sonnet 4.6
                        </button>
                        <button class="flex items-center gap-1 text-[12px] text-stone-500 hover:text-stone-800 dark:hover:text-stone-200">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            Default
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Select a trigger --}}
        <div>
            <label class="block text-[13px] text-stone-500 font-medium mb-2">Select a trigger</label>
            
            <div class="flex flex-col gap-2">
                {{-- Schedule --}}
                <button @click="trigger = 'schedule'" :class="trigger === 'schedule' ? 'border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-800 shadow-sm' : 'border-transparent bg-[#F9F9F9] dark:bg-[#1E1E1E] opacity-70 hover:opacity-100'" class="w-full text-left p-3 rounded-xl border flex items-start gap-3 transition-all">
                    <div class="mt-0.5">
                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div>
                        <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">Schedule</div>
                        <div class="text-[12px] text-stone-500">Run on a recurring cron schedule or once at a future time</div>
                    </div>
                </button>

                {{-- GitHub event --}}
                <button @click="trigger = 'github'" :class="trigger === 'github' ? 'border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-800 shadow-sm' : 'border-transparent bg-[#F9F9F9] dark:bg-[#1E1E1E] opacity-70 hover:opacity-100'" class="w-full text-left p-3 rounded-xl border flex items-center justify-between transition-all">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5">
                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                        </div>
                        <div>
                            <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">GitHub event</div>
                            <div class="text-[12px] text-stone-500">Run when a GitHub webhook event fires</div>
                        </div>
                    </div>
                    <div class="text-[12px] text-stone-300 dark:text-stone-600">Select a repository first</div>
                </button>

                {{-- API --}}
                <button @click="trigger = 'api'" :class="trigger === 'api' ? 'border-[#E5E5E5] dark:border-stone-700 bg-stone-50 dark:bg-stone-800 shadow-sm' : 'border-transparent bg-[#F9F9F9] dark:bg-[#1E1E1E] opacity-70 hover:opacity-100'" class="w-full text-left p-3 rounded-xl border flex items-start gap-3 transition-all">
                    <div class="mt-0.5">
                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    </div>
                    <div>
                        <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200">API</div>
                        <div class="text-[12px] text-stone-500">Trigger from your own code by sending a POST request</div>
                    </div>
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div>
            <div class="flex items-center gap-1 bg-stone-100 dark:bg-stone-900 rounded-lg p-1 w-fit mb-4">
                <button @click="tab = 'connectors'" :class="tab === 'connectors' ? 'bg-white dark:bg-stone-800 shadow-sm text-stone-800 dark:text-stone-200' : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300'" class="px-3 py-1 rounded-md text-[13px] font-medium transition-colors">Connectors</button>
                <button @click="tab = 'behavior'" :class="tab === 'behavior' ? 'bg-white dark:bg-stone-800 shadow-sm text-stone-800 dark:text-stone-200' : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300'" class="px-3 py-1 rounded-md text-[13px] font-medium transition-colors">Behavior</button>
                <button @click="tab = 'notifications'" :class="tab === 'notifications' ? 'bg-white dark:bg-stone-800 shadow-sm text-stone-800 dark:text-stone-200' : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300'" class="px-3 py-1 rounded-md text-[13px] font-medium transition-colors">Notifications</button>
                <button @click="tab = 'permissions'" :class="tab === 'permissions' ? 'bg-white dark:bg-stone-800 shadow-sm text-stone-800 dark:text-stone-200' : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300'" class="px-3 py-1 rounded-md text-[13px] font-medium transition-colors">Permissions</button>
            </div>

            <div x-show="tab === 'connectors'">
                <h3 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 mb-1">Connectors</h3>
                <p class="text-[13px] text-stone-500 mb-4">Integrations available to Rynude during each run.</p>
                <button class="px-3 py-1.5 border border-dashed border-[#E5E5E5] dark:border-stone-700 hover:border-stone-400 dark:hover:border-stone-500 text-stone-500 rounded-lg text-[13px] font-medium flex items-center gap-1.5 transition-colors">
                    <span class="font-normal">+</span> Add connector
                </button>
            </div>
            <div x-show="tab === 'behavior'" style="display: none;">
                <p class="text-[13px] text-stone-500">Behavior settings will appear here.</p>
            </div>
            <div x-show="tab === 'notifications'" style="display: none;">
                <p class="text-[13px] text-stone-500">Notification settings will appear here.</p>
            </div>
            <div x-show="tab === 'permissions'" style="display: none;">
                <p class="text-[13px] text-stone-500">Permission settings will appear here.</p>
            </div>
        </div>

    </div>

    {{-- Bottom Bar --}}
    <div class="fixed bottom-0 left-[300px] right-0 border-t border-stone-200 dark:border-stone-800 bg-white/80 dark:bg-stone-950/80 backdrop-blur-sm p-4 px-8 flex justify-end gap-3 z-20">
        <button wire:click="$set('currentView', 'routines')" class="px-4 py-2 text-[13px] font-medium text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-lg hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors">
            Cancel
        </button>
        <button class="px-4 py-2 text-[13px] font-medium text-stone-400 bg-stone-100 dark:bg-stone-900 rounded-lg cursor-not-allowed">
            Create
        </button>
    </div>
</div>

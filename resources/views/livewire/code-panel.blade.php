<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-stone-900">
    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-[#E5E5E5] dark:border-stone-700">
        <div class="flex items-center gap-3">
            <h2 class="font-serif text-2xl text-claude-800 dark:text-stone-200">Code</h2>
            @if(!$isPremium)
                <span class="text-xs font-medium px-2.5 py-1 rounded-full border border-claude-300 text-claude-500">Pro</span>
            @endif
        </div>
        <button
            wire:click="closePanel"
            class="p-2 rounded-lg hover:bg-[#F3F3F3] dark:hover:bg-stone-800 transition-colors text-claude-500 dark:text-stone-400 hover:text-claude-700 dark:hover:text-stone-200"
            title="Close"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-y-auto">
        @if(!$isPremium)
            {{-- Upgrade Gate --}}
            <div class="flex flex-col items-center justify-center h-full px-6 text-center">
                <div class="w-20 h-20 rounded-2xl bg-claude-100 dark:bg-stone-800 flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-claude-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-claude-800 dark:text-stone-200 mb-2">Upgrade to access Code</h3>
                <p class="text-sm text-claude-500 dark:text-stone-400 max-w-sm mb-6 leading-relaxed">
                    Get access to advanced code generation, code review, refactoring tools, and more with a Pro subscription.
                </p>
                <div class="space-y-3 w-full max-w-xs">
                    <div class="flex items-center gap-3 text-sm text-claude-600 dark:text-stone-300">
                        <svg class="w-5 h-5 text-claude-400 dark:text-stone-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>AI-powered code generation</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-claude-600 dark:text-stone-300">
                        <svg class="w-5 h-5 text-claude-400 dark:text-stone-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Automated code review</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-claude-600 dark:text-stone-300">
                        <svg class="w-5 h-5 text-claude-400 dark:text-stone-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Smart refactoring suggestions</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-claude-600 dark:text-stone-300">
                        <svg class="w-5 h-5 text-claude-400 dark:text-stone-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Unlimited code artifacts</span>
                    </div>
                </div>
                <button
                    wire:click="openUpgradeModal"
                    class="mt-8 px-8 py-3 rounded-xl bg-claude-800 text-white text-[15px] font-medium hover:bg-claude-900 transition-colors"
                >
                    Upgrade to Pro
                </button>
            </div>
        @else
            {{-- Premium Content (future) --}}
            <div class="flex flex-col items-center justify-center h-full px-6 text-center">
                <div class="w-16 h-16 rounded-full bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-claude-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>
                    </svg>
                </div>
                <p class="text-claude-500 dark:text-stone-400 text-sm">No code snippets yet</p>
                <p class="text-claude-400 dark:text-stone-500 text-xs mt-1">Start a chat and ask Claude to generate code</p>
            </div>
        @endif
    </div>
</div>

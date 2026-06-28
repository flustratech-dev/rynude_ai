<div class="flex-1 overflow-y-auto w-full flex flex-col p-8 max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-stone-600 dark:text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            <h1 class="text-[15px] font-medium text-stone-800 dark:text-stone-200">Routines</h1>
        </div>
        <button @click="$set('currentView', 'new-routine')" class="px-3 py-1.5 bg-black dark:bg-white text-white dark:text-black rounded-lg text-[13px] font-medium flex items-center gap-1.5 hover:opacity-90 transition-opacity">
            <span class="font-normal">+</span> New routine
        </button>
    </div>
    
    <p class="text-[13px] text-stone-500 mb-6">Create templated routines that can be kicked off on schedule, by API, or webhook.</p>
    
    {{-- Input Area --}}
    <div class="relative w-full border border-[#E5E5E5] dark:border-stone-800 rounded-xl overflow-hidden bg-white dark:bg-stone-900 shadow-sm flex items-center">
        <input type="text" placeholder="What do you want automated?" class="w-full bg-transparent border-0 outline-none text-[13px] text-stone-800 dark:text-stone-200 placeholder:text-stone-300 dark:placeholder:text-stone-600 py-3 px-4">
        <button class="mr-2 px-3 py-1 text-[13px] font-medium text-stone-300 dark:text-stone-600 bg-[#F5F5F5] dark:bg-stone-800 rounded-lg cursor-not-allowed whitespace-nowrap">Draft routine</button>
    </div>
    
    {{-- Pills --}}
    <div class="flex flex-wrap gap-2 mt-3">
        <button class="px-3 py-1.5 bg-[#F5F5F5] dark:bg-stone-800 hover:bg-[#EAE9E5] dark:hover:bg-stone-700 text-stone-500 dark:text-stone-400 rounded-lg text-[12px] transition-colors">Summarize my open PRs every weekday morning</button>
        <button class="px-3 py-1.5 bg-[#F5F5F5] dark:bg-stone-800 hover:bg-[#EAE9E5] dark:hover:bg-stone-700 text-stone-500 dark:text-stone-400 rounded-lg text-[12px] transition-colors">Triage new issues and flag duplicates each morning</button>
        <button class="px-3 py-1.5 bg-[#F5F5F5] dark:bg-stone-800 hover:bg-[#EAE9E5] dark:hover:bg-stone-700 text-stone-500 dark:text-stone-400 rounded-lg text-[12px] transition-colors">Draft release notes whenever a PR merges</button>
    </div>
    
    {{-- Empty State --}}
    <div class="flex flex-col items-center justify-center my-16 text-stone-400 dark:text-stone-500">
        <svg class="w-5 h-5 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        <p class="text-[13px]">No routines yet.</p>
    </div>
    
    {{-- Templates --}}
    <h3 class="text-[13px] text-stone-500 font-medium mb-3">Or start from a template</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pb-8">
        {{-- Briefing --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                Briefing
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Summary of your calendar, emails, and messages.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs weekdays at 19:30 GMT+7
            </div>
            <div class="text-[11px] text-stone-400">Works with Google Calendar · Gmail · Slack</div>
        </button>

        {{-- Email triage --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><path d="Mm22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                Email triage
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Categorize and prioritize your inbox, with draft responses for urgent items.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs weekdays at 22:00 GMT+7
            </div>
            <div class="text-[11px] text-stone-400">Works with Gmail</div>
        </button>

        {{-- System health check --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                System health check
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Monitor infrastructure and services for errors, outages, and performance issues.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs daily at 19:00 GMT+7
            </div>
            <div class="text-[11px] text-stone-400">Works with PagerDuty · Datadog · Sentry</div>
        </button>

        {{-- Issue triage --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                Issue triage
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Review and categorize incoming issues, bugs, and feature requests.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs weekdays at 22:30 GMT+7
            </div>
            <div class="text-[11px] text-stone-400">Works with Linear</div>
        </button>

        {{-- PR review digest --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="18" cy="18" r="3"></circle><circle cx="6" cy="6" r="3"></circle><path d="M13 6h3a2 2 0 0 1 2 2v7"></path><line x1="6" y1="9" x2="6" y2="21"></line></svg>
                PR review digest
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Overview of open PRs, review status, and what needs attention.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs weekdays at 1:00 GMT+7
            </div>
        </button>

        {{-- Dependency update check --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Dependency update check
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Scan for outdated packages, security patches, and breaking changes.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs every Tuesday at 1:30 GMT+7
            </div>
        </button>

        {{-- Release notes drafter --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Release notes drafter
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Draft user-facing release notes each time a PR merges to the main branch.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="18" cy="18" r="3"></circle><circle cx="6" cy="6" r="3"></circle><path d="M13 6h3a2 2 0 0 1 2 2v7"></path><line x1="6" y1="9" x2="6" y2="21"></line></svg>
                Triggered by pull request closed
            </div>
        </button>

        {{-- Flaky test tracker --}}
        <button @click="$set('currentView', 'new-routine')" class="text-left p-4 rounded-xl bg-white dark:bg-stone-900 border border-[#E5E5E5] dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-colors shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-[#2D2825] dark:text-stone-200 font-medium text-[13px]">
                <svg class="w-4 h-4 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 2v7.31"></path><path d="M14 9.3V1.99"></path><path d="M8.5 2h7"></path><path d="M14 9.3a6.5 6.5 0 1 1-4 0"></path><path d="M5.52 16h12.96"></path></svg>
                Flaky test tracker
            </div>
            <p class="text-[12px] text-stone-600 dark:text-stone-400 mb-3">Find tests that pass and fail intermittently across recent CI runs.</p>
            <div class="flex items-center gap-1.5 text-[11px] text-stone-400 mb-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Runs every Monday at 23:00 GMT+7
            </div>
        </button>
    </div>
</div>

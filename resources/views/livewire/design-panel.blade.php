<style>
    .scrollbar-none::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-none {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<div class="min-h-screen bg-[#F9F8F6] dark:bg-stone-900 flex flex-col font-sans" x-data="{ currentTab: 'recent' }">
    {{-- Header --}}
    <div class="w-full bg-[#F9F8F6] dark:bg-stone-900 border-b border-[#E5E5E5] dark:border-stone-800/80 px-8 py-3 flex items-center justify-between">
        {{-- Logo --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" class="font-serif text-[17px] font-medium text-[#2D2825] dark:text-stone-100 whitespace-nowrap hover:opacity-80 transition-opacity">Rynude Design</a>
            <span class="text-[9px] font-semibold tracking-wider uppercase px-1.5 py-0.5 bg-[#EAE9E5] dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-[4px] whitespace-nowrap">Beta</span>
        </div>

        {{-- Tabs --}}
        <div class="flex items-center gap-1">
            <button @click="currentTab = 'recent'" :class="currentTab === 'recent' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200'" class="px-3 py-1 rounded-lg text-[13px] transition-all">Recent</button>
            <button @click="currentTab = 'yours'" :class="currentTab === 'yours' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200'" class="px-3 py-1 rounded-lg text-[13px] transition-all">Your designs</button>
            <button @click="currentTab = 'systems'" :class="currentTab === 'systems' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200'" class="px-3 py-1 rounded-lg text-[13px] transition-all">Design systems</button>
            <button @click="currentTab = 'examples'" :class="currentTab === 'examples' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200'" class="px-3 py-1 rounded-lg text-[13px] transition-all">Examples</button>
        </div>

        {{-- Right Controls --}}
        <div class="flex items-center gap-3">
            <div class="relative flex items-center">
                <svg class="w-3.5 h-3.5 text-stone-400 absolute left-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Search designs" class="pl-8 pr-3 py-1 bg-white dark:bg-stone-850 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] placeholder-stone-400 text-stone-850 dark:text-stone-200 focus:outline-none w-44 focus:w-52 transition-all">
            </div>
            <svg class="w-7 h-7 flex-shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="8" cy="8" r="8" fill="#5C92D1"/>
                <rect x="6" y="6" width="4" height="4" fill="#DBE0ED"/>
                <rect x="7" y="3" width="2" height="3" fill="#DBE0ED"/>
                <rect x="7" y="10" width="2" height="3" fill="#DBE0ED"/>
                <rect x="3" y="7" width="3" height="2" fill="#DBE0ED"/>
                <rect x="10" y="7" width="3" height="2" fill="#DBE0ED"/>
                <rect x="4" y="4" width="2" height="2" fill="#DBE0ED"/>
                <rect x="10" y="4" width="2" height="2" fill="#DBE0ED"/>
                <rect x="4" y="10" width="2" height="2" fill="#DBE0ED"/>
                <rect x="10" y="10" width="2" height="2" fill="#DBE0ED"/>
            </svg>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="max-w-6xl w-full mx-auto px-8 py-8 flex-1 flex flex-col gap-6 overflow-y-auto">
        {{-- Section: Make something new --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="text-[13px] font-semibold text-[#2D2825] dark:text-stone-200">Make something new</span>
                <div class="flex items-center gap-1 text-[11.5px] text-stone-400 hover:text-stone-600 cursor-pointer">
                    <span>Design system: None</span>
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
            </div>

            {{-- Row of Cards --}}
            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none select-none">
                {{-- 1. Start with a file --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-dashed border-stone-300 dark:border-stone-700 flex items-center justify-center hover:border-[#D97757] dark:hover:border-[#D97757] hover:bg-stone-50 dark:hover:bg-stone-850/40 transition-all">
                        <svg class="w-7 h-7 text-stone-400 group-hover:text-[#D97757] transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m0 0l-3-3m3 3l3-3m-9-3h12a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                        </svg>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Start with a file</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Turn it into a design</div>
                </div>

                {{-- 2. Slides --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-stone-300 dark:hover:border-stone-700 shadow-sm transition-all relative overflow-hidden">
                        <div class="w-14 h-10 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex flex-col p-1 gap-1">
                            <div class="h-1.5 w-1/2 bg-stone-300 dark:bg-stone-600 rounded-sm"></div>
                            <div class="h-1.5 w-full bg-stone-200 dark:bg-stone-700 rounded-sm"></div>
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Slides</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Presentations & pitch decks</div>
                </div>

                {{-- 3. Product prototype --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-stone-300 dark:hover:border-stone-700 shadow-sm transition-all relative overflow-hidden">
                        <div class="w-14 h-10 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex flex-col p-1 gap-1 relative">
                            <div class="flex gap-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-stone-300 dark:bg-stone-600"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-stone-300 dark:bg-stone-600"></div>
                            </div>
                            <div class="h-2 w-3/4 bg-stone-200 dark:bg-stone-700 rounded-sm mt-0.5"></div>
                            <div class="h-2 w-1/2 bg-[#D97757]/80 rounded-sm absolute bottom-1 right-1"></div>
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Product prototype</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Interactive app mockups</div>
                </div>

                {{-- 4. Product wireframe --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-stone-300 dark:hover:border-stone-700 shadow-sm transition-all relative overflow-hidden">
                        <div class="w-14 h-10 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex flex-col p-1 justify-center items-center">
                            <div class="w-10 h-6 border border-dashed border-stone-300 dark:border-stone-600 relative flex items-center justify-center">
                                <svg class="w-full h-full text-stone-300 dark:text-stone-600" viewBox="0 0 100 60" preserveAspectRatio="none"><line x1="0" y1="0" x2="100" y2="60" stroke="currentColor" stroke-width="1.5"/><line x1="100" y1="0" x2="0" y2="60" stroke="currentColor" stroke-width="1.5"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Product wireframe</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Lo-fi screens & flows</div>
                </div>

                {{-- 5. Document --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-stone-300 dark:hover:border-stone-700 shadow-sm transition-all relative overflow-hidden">
                        <div class="w-10 h-12 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex flex-col p-1.5 gap-1.5">
                            <div class="h-1 w-full bg-stone-300 dark:bg-stone-600 rounded-sm"></div>
                            <div class="h-1 w-full bg-stone-200 dark:bg-stone-700 rounded-sm"></div>
                            <div class="h-1 w-2/3 bg-stone-200 dark:bg-stone-700 rounded-sm"></div>
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Document</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Resumes, PDFs, etc</div>
                </div>

                {{-- 6. Animation --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-stone-300 dark:hover:border-stone-700 shadow-sm transition-all relative overflow-hidden">
                        <div class="w-14 h-10 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex flex-col justify-between p-1">
                            <div class="flex-1 flex flex-col items-center justify-center gap-0.5">
                                <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                            <div class="h-1 w-full bg-stone-300 dark:bg-stone-600 rounded-sm"></div>
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Animation</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Motion graphics & loops</div>
                </div>

                {{-- 7. Blank canvas --}}
                <div class="w-[145px] shrink-0 flex flex-col group cursor-pointer">
                    <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-stone-300 dark:hover:border-stone-700 shadow-sm transition-all relative overflow-hidden">
                        <div class="w-10 h-12 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex flex-col">
                            <!-- Folded corner effect -->
                            <div class="self-end w-3 h-3 bg-stone-200 dark:bg-stone-700 border-l border-b border-stone-300 dark:border-stone-600"></div>
                        </div>
                    </div>
                    <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">Blank canvas</div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">Start from scratch</div>
                </div>
            </div>
        </div>

        {{-- Design System Setup Banner --}}
        <div class="w-full bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl p-5 flex flex-col items-center justify-center text-center shadow-sm">
            <span class="text-[13px] text-stone-500 dark:text-stone-400">Set up your design system so anyone can create consistent designs and assets.</span>
            <button class="px-4 py-1.5 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-xl text-[13px] font-medium transition-colors mt-3 shadow-sm focus:outline-none">
                Set up design system
            </button>
        </div>

        {{-- Designs List Section --}}
        <div>
            <h3 class="text-[13.5px] font-semibold text-[#2D2825] dark:text-stone-200 mb-3">Designs</h3>

            <div class="w-full bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl overflow-hidden shadow-sm py-12 flex flex-col items-center justify-center text-center">
                <div class="w-12 h-12 rounded-xl bg-stone-50 dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-3 border border-stone-200 dark:border-stone-700">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
                <p class="text-stone-500 dark:text-stone-400 text-[13.5px] font-medium">No designs yet</p>
                <p class="text-stone-400 dark:text-stone-500 text-xs mt-1">Start a chat and ask Rynude to generate designs</p>
            </div>
        </div>
    </div>
</div>

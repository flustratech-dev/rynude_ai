<div class="min-h-screen h-full bg-[#F9F8F6] dark:bg-stone-900 flex flex-col font-sans"
     x-data="designPanelState()"
     x-init="init()">
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }

        /* Fanned template card row */
        .tpl-row { display: flex; justify-content: center; align-items: flex-end; padding-top: 20px; }
        .tpl-card {
            position: relative;
            width: 120px;
            transition: transform .28s cubic-bezier(.2,.75,.3,1), box-shadow .28s ease;
            transform-origin: bottom center;
            cursor: pointer;
            margin: 0 -6px;
        }
        .tpl-card .tpl-thumb {
            background: #fff;
            border: 1px solid #E7E4DE;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(60,50,40,.06);
            padding: 14px;
            transition: box-shadow .28s ease, border-color .28s ease;
        }
        .dark .tpl-card .tpl-thumb { background: #1c1917; border-color: #292524; }
        .tpl-card:hover {
            transform: translateY(-14px) scale(1.06) rotate(0deg) !important;
            z-index: 20;
        }
        .tpl-card:hover .tpl-thumb {
            box-shadow: 0 18px 40px rgba(60,50,40,.18);
            border-color: #D97757;
        }
        .tpl-card .tpl-label {
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            margin-top: 12px;
            color: #2D2825;
            transition: color .2s ease, opacity .2s ease;
            opacity: .85;
        }
        .dark .tpl-card .tpl-label { color: #e7e5e4; }
        .tpl-card:hover .tpl-label { opacity: 1; color: #D97757; }

        .tpl-glyph { color: #9c968d; transition: color .28s ease; }
        .tpl-card:hover .tpl-glyph { color: #D97757; }
    </style>

    {{-- Header --}}
    <div class="w-full bg-[#F9F8F6] dark:bg-stone-900 border-b border-[#E5E5E5] dark:border-stone-800/80 px-8 py-3 flex items-center justify-between flex-shrink-0">
        <a href="{{ route('home') }}" class="flex flex-col leading-none hover:opacity-80 transition-opacity">
            <span class="font-serif text-[19px] font-semibold text-[#2D2825] dark:text-stone-100 whitespace-nowrap">Rynude Design</span>
            <span class="text-[11px] text-stone-400 mt-0.5">Beta</span>
        </a>

        <div class="w-8 h-8 rounded-full bg-[#EAE9E5] dark:bg-stone-800 flex items-center justify-center text-[12px] font-semibold text-stone-600 dark:text-stone-300">
            {{ strtoupper(substr(auth()->user()->name ?? 'JR', 0, 2)) }}
        </div>
    </div>

    {{-- Main Container --}}
    <div class="max-w-5xl w-full mx-auto px-8 py-10 flex-1 flex flex-col gap-8 overflow-y-auto">
        <template x-if="flashMessage">
            <div class="px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 rounded-lg text-[13px]" x-text="flashMessage"></div>
        </template>

        {{-- Hero heading --}}
        <h1 class="font-serif text-[42px] leading-tight font-semibold text-center text-[#2D2825] dark:text-stone-100 mt-4">What will you design today?</h1>

        {{-- Prompt composer --}}
        <div class="w-full bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl shadow-sm px-5 pt-4 pb-3">
            <textarea x-model="heroPrompt" rows="2" @keydown.enter.prevent="generateFromHero()"
                placeholder="Sketch a landing page layout"
                class="w-full bg-transparent border-0 focus:outline-none resize-none text-[15px] text-stone-800 dark:text-stone-200 placeholder-stone-400 leading-relaxed"></textarea>

            <div class="flex items-center gap-2 mt-2">
                <button @click="openDialog('blank')" class="w-9 h-9 flex items-center justify-center rounded-lg border border-[#E5E5E5] dark:border-stone-700 text-stone-500 hover:border-[#D97757] hover:text-[#D97757] transition-colors" title="New">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <div class="px-3 py-1.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 flex flex-col leading-tight cursor-default">
                    <span class="text-[10px] text-stone-400 flex items-center gap-1">Design system
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                    <span class="text-[12.5px] font-medium text-[#2D2825] dark:text-stone-200">None</span>
                </div>

                <div class="px-3 py-1.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 flex flex-col leading-tight cursor-default">
                    <span class="text-[10px] text-stone-400 flex items-center gap-1">Template
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                    <span class="text-[12.5px] font-medium text-[#2D2825] dark:text-stone-200">None</span>
                </div>

                <button class="w-9 h-9 flex items-center justify-center rounded-lg border border-[#E5E5E5] dark:border-stone-700 text-stone-500 hover:border-[#D97757] hover:text-[#D97757] transition-colors" title="Code">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </button>

                <div class="flex-1"></div>

                <div class="px-3 py-1.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 flex flex-col leading-tight cursor-default">
                    <span class="text-[10px] text-stone-400 flex items-center gap-1">Model
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                    <span class="text-[12.5px] font-medium text-[#2D2825] dark:text-stone-200">Rynude Sonnet 4.6</span>
                </div>

                <button @click="generateFromHero()" :disabled="generating" class="w-9 h-9 flex items-center justify-center rounded-lg bg-[#E9BBA7] hover:bg-[#D97757] text-white transition-colors disabled:opacity-60" title="Generate">
                    <svg x-show="!generating" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                    <svg x-show="generating" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                </button>
            </div>
        </div>

        {{-- Templates fan --}}
        <div>
            <p class="text-center text-[14px] text-stone-500 dark:text-stone-400 font-medium">Start with a template…</p>
            <div class="tpl-row">
                <template x-for="(meta, type) in templateCards" :key="type">
                    <div class="tpl-card" :style="'transform: rotate(' + meta.rot + 'deg)'" @click="openDialog(type)">
                        <div class="tpl-thumb">
                            <div class="tpl-glyph flex items-center justify-center h-[68px]">
                                <svg class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                    <g x-show="type==='prototype'"><rect x="3" y="4" width="18" height="13" rx="2"/><line x1="7" y1="8" x2="13" y2="8"/><rect x="7" y="11" width="10" height="3" rx="1"/></g>
                                    <g x-show="type==='slides'"><rect x="3" y="4" width="18" height="14" rx="2"/><rect x="6" y="7" width="5" height="5" rx="1"/><line x1="13" y1="8" x2="18" y2="8"/><line x1="13" y1="11" x2="17" y2="11"/></g>
                                    <g x-show="type==='document'"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><polyline points="14 3 14 8 19 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="16" x2="13" y2="16"/></g>
                                    <g x-show="type==='wireframe'"><rect x="3" y="4" width="18" height="14" rx="2" stroke-dasharray="3 2.5"/><path d="M3 9h18M10 9v9"/></g>
                                    <g x-show="type==='animation'"><rect x="3" y="4" width="18" height="14" rx="2"/><polygon points="10 8 15 11 10 14 10 8" fill="currentColor" stroke="none"/><line x1="6" y1="16" x2="18" y2="16"/></g>
                                </svg>
                            </div>
                        </div>
                        <div class="tpl-label" x-text="meta.label"></div>
                    </div>
                </template>
            </div>

            <p class="text-center mt-6">
                <button @click="openDialog('blank')" class="text-[14px] text-stone-500 dark:text-stone-400 hover:text-[#D97757] transition-colors inline-flex items-center gap-1.5">
                    …or start a blank project
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </p>
        </div>

        {{-- Projects list --}}
        <div class="mt-4">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-1 bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-0.5">
                    <template x-for="(label, key) in {recent: 'Projects', yours: 'Design systems', examples: 'Templates'}" :key="key">
                        <button @click="currentTab = key; loadDesigns()" class="px-3 py-1.5 rounded-lg text-[13px] transition-all"
                            :class="currentTab === key ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200'"
                            x-text="label"></button>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative flex items-center">
                        <svg class="w-3.5 h-3.5 text-stone-400 absolute left-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" x-model="search" @input.debounce.300ms="loadDesigns()" placeholder="Search" class="pl-8 pr-3 py-1.5 bg-white dark:bg-stone-850 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] placeholder-stone-400 text-stone-800 dark:text-stone-200 focus:outline-none w-44">
                    </div>
                    <button @click="viewMode = 'list'" class="p-1.5 rounded-lg transition-colors" :class="viewMode === 'list' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200' : 'text-stone-400 hover:text-stone-600'" title="List view">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </button>
                    <button @click="viewMode = 'grid'" class="p-1.5 rounded-lg transition-colors" :class="viewMode === 'grid' ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200' : 'text-stone-400 hover:text-stone-600'" title="Grid view">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </button>
                </div>
            </div>

            <template x-if="loading">
                <div class="flex items-center justify-center py-12"><svg class="animate-spin h-8 w-8 text-[#D97757]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>
            </template>

            <template x-if="!loading && designs.length === 0">
                <div class="w-full bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl py-12 flex flex-col items-center justify-center text-center shadow-sm">
                    <div class="w-12 h-12 rounded-xl bg-stone-50 dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-3 border border-stone-200 dark:border-stone-700">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <p class="text-stone-500 dark:text-stone-400 text-[13.5px] font-medium">No projects yet</p>
                    <p class="text-stone-400 dark:text-stone-500 text-xs mt-1">Pick a template above and describe what you want Rynude to design.</p>
                </div>
            </template>

            {{-- List view --}}
            <template x-if="!loading && designs.length > 0 && viewMode === 'list'">
                <div>
                    <div class="grid grid-cols-[1fr_140px_160px_90px] gap-4 px-3 pb-2 border-b border-[#E5E5E5] dark:border-stone-800 text-[12px] text-stone-400 font-medium">
                        <span>Name</span><span>Last viewed</span><span>All owners</span><span>Access</span>
                    </div>
                    <template x-for="design in designs" :key="design.id">
                        <div class="group grid grid-cols-[1fr_140px_160px_90px] gap-4 items-center px-3 py-2.5 rounded-lg hover:bg-white dark:hover:bg-stone-850 transition-colors cursor-pointer" @click="viewDesign(design)">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-14 h-10 rounded-md bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                    <template x-if="design.status === 'ready' && design.content">
                                        <iframe :srcdoc="design.content" class="w-[400%] h-[160px] origin-top-left scale-[0.25] pointer-events-none border-0" sandbox="allow-scripts"></iframe>
                                    </template>
                                    <template x-if="!(design.status === 'ready' && design.content)">
                                        <svg class="w-4 h-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><polyline points="14 3 14 8 19 8"/></svg>
                                    </template>
                                </div>
                                <span class="text-[13.5px] text-[#2D2825] dark:text-stone-100 truncate" x-text="design.title"></span>
                            </div>
                            <span class="text-[12.5px] text-stone-500" x-text="design.created_at"></span>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-[10px] font-semibold flex items-center justify-center">Y</span>
                                <span class="text-[12.5px] text-stone-500">You</span>
                            </div>
                            <div class="flex items-center gap-1 text-stone-300" @click.stop>
                                <button @click="toggleStar(design)" class="p-1 transition-colors" :class="design.is_starred ? 'text-amber-400' : 'text-stone-300 hover:text-amber-400'" title="Star">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" :fill="design.is_starred ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </button>
                                <button @click="deleteDesign(design)" class="p-1 text-stone-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100" title="Delete">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Grid view --}}
            <template x-if="!loading && designs.length > 0 && viewMode === 'grid'">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <template x-for="design in designs" :key="design.id">
                        <div class="group bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl overflow-hidden shadow-sm hover:border-stone-300 dark:hover:border-stone-700 hover:shadow transition-all">
                            <div @click="viewDesign(design)" class="h-[140px] bg-stone-50 dark:bg-stone-800 relative cursor-pointer overflow-hidden border-b border-stone-100 dark:border-stone-800">
                                <template x-if="design.status === 'ready' && design.content">
                                    <iframe :srcdoc="design.content" class="w-[200%] h-[280px] origin-top-left scale-50 pointer-events-none border-0" sandbox="allow-scripts"></iframe>
                                </template>
                                <template x-if="design.status === 'generating'">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-stone-400 gap-2">
                                        <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        <span class="text-[11px]">Generating…</span>
                                    </div>
                                </template>
                                <template x-if="design.status === 'failed'">
                                    <div class="absolute inset-0 flex items-center justify-center text-red-400 text-[11px]">Generation failed</div>
                                </template>
                            </div>
                            <div class="p-3 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-[12.5px] font-medium text-[#2D2825] dark:text-stone-100 truncate" x-text="design.title"></div>
                                    <div class="text-[10.5px] text-stone-400 mt-0.5" x-text="(designTypes[design.type]?.label || design.type) + ' · ' + design.created_at"></div>
                                </div>
                                <button @click="toggleStar(design)" class="p-1 transition-colors flex-shrink-0" :class="design.is_starred ? 'text-amber-400' : 'text-stone-300 hover:text-amber-400'" title="Star">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" :fill="design.is_starred ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Generation Dialog --}}
    <template x-if="showDialog">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-4" @click.self="closeDialog()">
            <div class="bg-white dark:bg-stone-850 border border-stone-200 dark:border-stone-700 rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <h3 class="font-serif text-[18px] font-medium text-[#2D2825] dark:text-stone-100 mb-1" x-text="'Generate ' + (designTypes[dialogType]?.label || 'design')"></h3>
                <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mb-4" x-text="designTypes[dialogType]?.sub || ''"></p>

                <textarea x-model="dialogPrompt" rows="4" placeholder="Describe the design you want… e.g. A pricing page for a SaaS product with three tiers"
                    class="w-full px-3 py-2.5 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13.5px] text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:border-[#D97757] resize-none"></textarea>
                <template x-if="dialogError">
                    <span class="text-red-500 text-[11.5px] mt-1 block" x-text="dialogError"></span>
                </template>

                <div class="flex items-center justify-end gap-2 mt-4">
                    <button @click="closeDialog()" class="px-4 py-2 text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 text-[13px] font-medium transition-colors">Cancel</button>
                    <button @click="generate()" :disabled="generating" class="flex items-center gap-1.5 px-4 py-2 bg-[#D97757] hover:bg-[#c56647] text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">
                        <svg x-show="generating" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <span x-text="generating ? 'Generating…' : 'Generate'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Viewer --}}
    <template x-if="viewing">
        <div class="fixed inset-0 z-50 flex flex-col bg-stone-900/60 backdrop-blur-sm" @click.self="closeViewer()">
            <div class="flex items-center justify-between px-6 py-3 bg-white dark:bg-stone-850 border-b border-stone-200 dark:border-stone-700 flex-shrink-0">
                <div class="min-w-0">
                    <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-100 truncate" x-text="viewing.title"></div>
                    <div class="text-[11px] text-stone-400" x-text="designTypes[viewing.type]?.label || viewing.type"></div>
                </div>
                <button @click="closeViewer()" class="p-2 text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex-1 bg-white">
                <template x-if="viewing.status === 'ready' && viewing.content">
                    <iframe :srcdoc="viewing.content" class="w-full h-full border-0" sandbox="allow-scripts allow-same-origin"></iframe>
                </template>
                <template x-if="viewing.status === 'generating'">
                    <div class="h-full flex flex-col items-center justify-center text-stone-400 gap-3">
                        <svg class="w-7 h-7 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <span class="text-[13px]">Rynude is generating your design…</span>
                    </div>
                </template>
                <template x-if="viewing.status === 'failed'">
                    <div class="h-full flex items-center justify-center text-red-400 text-[13px] px-8 text-center" x-text="viewing.content || 'Generation failed. Please try again.'"></div>
                </template>
            </div>
        </div>
    </template>
</div>

<script>
function designPanelState() {
    return {
        currentTab: 'recent',
        search: '',
        designs: [],
        loading: false,
        flashMessage: '',
        viewMode: 'list',
        heroPrompt: '',
        designTypes: {
            slides: {label: 'Slides', sub: 'Presentations & pitch decks'},
            prototype: {label: 'Product prototype', sub: 'Interactive app mockups'},
            wireframe: {label: 'Product wireframe', sub: 'Lo-fi screens & flows'},
            document: {label: 'Document', sub: 'Resumes, PDFs, etc'},
            animation: {label: 'Animation', sub: 'Motion graphics & loops'},
            blank: {label: 'Blank canvas', sub: 'Start from scratch'},
        },
        // Cards shown in the fanned template row (order + fan rotation)
        templateCards: {
            prototype: {label: 'Prototype', rot: -8},
            slides: {label: 'Slides', rot: -4},
            document: {label: 'Document', rot: 0},
            wireframe: {label: 'Wireframe', rot: 4},
            animation: {label: 'Animation', rot: 8},
        },
        showDialog: false,
        dialogType: 'prototype',
        dialogPrompt: '',
        dialogError: '',
        generating: false,
        viewing: null,

        init: function() {
            this.loadDesigns();
        },

        loadDesigns: function() {
            this.loading = true;
            var params = new URLSearchParams();
            if (this.search.trim()) params.append('search', this.search.trim());
            params.append('tab', this.currentTab);
            fetch('/api/designs?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    this.designs = resp.data || [];
                    this.loading = false;
                }.bind(this))
                .catch(function() { this.loading = false; }.bind(this));
        },

        openDialog: function(type) {
            this.dialogType = type;
            this.dialogPrompt = '';
            this.dialogError = '';
            this.showDialog = true;
        },

        closeDialog: function() {
            this.showDialog = false;
            this.dialogPrompt = '';
            this.dialogError = '';
        },

        // Generate straight from the hero prompt composer
        generateFromHero: function() {
            if (!this.heroPrompt.trim()) { this.openDialog('prototype'); return; }
            this.dialogType = 'prototype';
            this.dialogPrompt = this.heroPrompt;
            this.generate();
        },

        generate: function() {
            var prompt = (this.dialogPrompt || this.heroPrompt || '').trim();
            if (!prompt) { this.dialogError = 'Please describe your design.'; return; }
            if (prompt.length > 2000) { this.dialogError = 'Prompt too long (max 2000 chars).'; return; }
            this.generating = true;
            this.dialogError = '';
            fetch('/api/designs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ type: this.dialogType, prompt: prompt })
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                this.generating = false;
                this.showDialog = false;
                this.dialogPrompt = '';
                this.heroPrompt = '';
                this.currentTab = 'yours';
                this.loadDesigns();
                if (resp.data) {
                    this.viewing = resp.data;
                }
            }.bind(this))
            .catch(function() {
                this.generating = false;
                this.dialogError = 'Failed to generate. Please try again.';
            }.bind(this));
        },

        viewDesign: function(design) {
            this.viewing = design;
        },

        closeViewer: function() {
            this.viewing = null;
        },

        toggleStar: function(design) {
            fetch('/api/designs/' + design.id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ is_starred: !design.is_starred })
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.data) {
                    design.is_starred = resp.data.is_starred;
                }
            }.bind(this));
        },

        deleteDesign: function(design) {
            if (!confirm('Delete this design?')) return;
            fetch('/api/designs/' + design.id, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) {
                if (r.ok) {
                    if (this.viewing && this.viewing.id === design.id) this.viewing = null;
                    this.loadDesigns();
                }
            }.bind(this));
        },
    };
}
</script>

<div class="h-full w-full flex flex-col bg-[#F9F8F6] dark:bg-stone-900 overflow-hidden shadow-2xl md:shadow-none"
     x-data="artifactPanelState()"
     x-init="init()"
     :class="fullscreen ? 'fixed inset-0 z-[60] !shadow-2xl' : ''">

    {{-- OPEN ARTIFACT VIEW --}}
    <template x-if="currentArtifact">
        <div class="h-full w-full flex flex-col">
            <div class="px-4 py-3 flex items-center justify-between bg-transparent shrink-0 z-10 relative">
                <div x-data="{ openMenu: false }" class="flex items-center gap-2 max-w-[50%] relative">
                    <template x-if="currentArtifact.language === 'new'">
                        <div>
                            <button @click="openMenu = !openMenu" @click.away="openMenu = false" class="flex items-center gap-1.5 px-2 py-1 -ml-2 rounded-md hover:bg-[#F3F2F1] dark:hover:bg-stone-700 transition-colors text-[14px] font-medium text-[#2D2825] dark:text-stone-200">
                                Untitled <svg class="w-3.5 h-3.5 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <div x-show="openMenu" x-transition.opacity.duration.200ms x-cloak class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-50">
                                <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> Star
                                </button>
                                <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Rename
                                </button>
                                <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg> Add to project
                                </button>
                                <div class="h-px w-full bg-[#E5E5E5] dark:bg-stone-700 mb-1"></div>
                                <button class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="currentArtifact.language !== 'new'">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-600 dark:text-stone-300 flex-shrink-0">
                                <svg x-show="currentArtifact.language==='php'||currentArtifact.type==='code'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                <svg x-show="currentArtifact.language!=='php'&&currentArtifact.type!=='code'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </div>
                            <h2 class="text-[13px] font-medium text-[#2D2825] dark:text-stone-200 truncate" x-text="currentArtifact.title"></h2>
                        </div>
                    </template>
                </div>

                <div class="absolute left-1/2 -translate-x-1/2 flex items-center bg-[#F3F2F1] dark:bg-stone-700 p-1 rounded-lg">
                    <button @click="activeTab = 'preview'" class="px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200" :class="activeTab === 'preview' ? 'bg-white dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 shadow-sm' : 'text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300'">Preview</button>
                    <button @click="activeTab = 'code'" class="px-3 py-1.5 text-[13px] font-medium rounded-md transition-all duration-200" :class="activeTab === 'code' ? 'bg-white dark:bg-stone-800 text-[#2D2825] dark:text-stone-200 shadow-sm' : 'text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300'">Code</button>
                </div>

                <div class="flex items-center gap-1.5">
                    <template x-if="currentArtifact.language !== 'new'">
                        <div class="flex items-center gap-1.5">
                            <div x-show="downloading" class="flex items-center gap-1.5 px-2 text-[12px] text-[#FCFBFA]">
                                <img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="w-3.5 h-3.5 animate-spin object-contain">
                                <span>Generating…</span>
                            </div>
                            <button @click="copyCode()" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Copy code">
                                <svg x-show="!copied" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                <svg x-show="copied" class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </button>
                            <div x-data="{ openDl: false }" class="relative">
                                <button @click="openDl = !openDl" @click.away="openDl = false" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300 flex items-center gap-0.5" title="Download">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                                <div x-show="openDl" x-cloak x-transition.opacity.duration.200ms class="absolute top-full right-0 mt-1 w-52 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-50">
                                    <button @click="openDl = false; downloadPdf()" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg> PDF
                                    </button>
                                    <button @click="openDl = false; downloadDocx()" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg> DOCX
                                    </button>
                                    <button @click="openDl = false; downloadMarkdown()" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg> Markdown
                                    </button>
                                    <button @click="openDl = false; downloadTxt()" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line><line x1="12" y1="15" x2="12" y2="11"></line></svg> TXT
                                    </button>
                                    <button @click="openDl = false; downloadHtml()" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg> HTML
                                    </button>
                                </div>
                            </div>
                            <button @click="toggleFullscreen()" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Fullscreen">
                                <svg x-show="!fullscreen" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                                <svg x-show="fullscreen" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path></svg>
                            </button>
                            <template x-if="currentArtifact.id">
                                <div>
                                    <button x-show="!currentArtifact.is_public" @click="publishArtifact(currentArtifact.id)" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Publish">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                    </button>
                                    <button x-show="currentArtifact.is_public" @click="unpublishArtifact(currentArtifact.id)" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Unpublish">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                                    </button>
                                    <button x-show="currentArtifact.is_public" @click="publishArtifact(currentArtifact.id)" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-green-600 dark:text-green-500" title="Published — copy link">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                    </button>
                                    <div class="w-px h-4 bg-[#E5E5E5] dark:bg-stone-700 mx-1 hidden md:block"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <button @click="closeArtifact()" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Close">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-hidden bg-transparent flex flex-col p-4 md:p-6">
                <template x-if="currentArtifact.language === 'new'">
                    <div class="flex-1 flex flex-col items-center justify-center p-8 overflow-y-auto">
                        <h2 class="font-serif text-[22px] font-medium text-[#2D2825] dark:text-stone-200 mb-8">Let's get cooking! Pick an artifact category or start building your idea from scratch.</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-[800px] w-full">
                            <template x-for="card in [{label:'Apps and websites',icon:'web'},{label:'Documents and templates',icon:'doc'},{label:'Games',icon:'game'},{label:'Productivity tools',icon:'tool'},{label:'Creative projects',icon:'creative'},{label:'Quiz or survey',icon:'quiz'},{label:'Start from scratch',icon:'plus'}]" :key="card.label">
                                <button @click="generateTemplate(card.label)" class="bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-2xl p-4 aspect-[4/3] flex flex-col items-start justify-between hover:border-[#D97757] hover:shadow-sm transition-all group">
                                    <span class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 text-left" x-text="card.label"></span>
                                    <div class="w-full flex justify-end">
                                        <svg class="w-5 h-5 text-stone-400 group-hover:text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="currentArtifact.language !== 'new'">
                    <div class="flex-1 overflow-hidden flex flex-col relative">
                        <template x-if="activeTab === 'code'">
                            <div class="h-full flex flex-col">
                                <div class="absolute top-0 right-0 px-3 py-1 bg-[#F3F2F1] dark:bg-stone-700 border-b border-l border-[#E5E5E5] dark:border-stone-700 rounded-bl-lg text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider z-10" x-text="currentArtifact.language"></div>
                                <div class="flex-1 overflow-auto bg-[#FBFBFA] dark:bg-stone-900 p-4 pt-8 text-[13px] leading-relaxed text-stone-800 dark:text-stone-200 font-mono">
                                    <pre><code class="language-html" x-text="artifactContent"></code></pre>
                                </div>
                            </div>
                        </template>
                        <template x-if="activeTab === 'preview'">
                            <div class="flex-1 overflow-auto bg-white dark:bg-stone-900">
                                <template x-if="['html','svg','react','jsx','tsx'].includes(currentArtifact.language)">
                                    <iframe :srcdoc="previewContent" class="w-full h-full border-0 bg-white" sandbox="allow-scripts"></iframe>
                                </template>
                                <template x-if="currentPdfArtifactId && ['markdown','md','pdf','document'].includes(currentArtifact.language)">
                                    <div class="h-full w-full overflow-y-auto custom-scrollbar bg-stone-100 dark:bg-stone-900" x-data="pdfViewer('/artifact/' + currentPdfArtifactId + '/preview.pdf')">
                                        <div class="w-full min-h-full py-8 flex flex-col items-center">
                                            <div x-ref="container" class="relative w-full max-w-[210mm] flex flex-col items-center gap-6">
                                                {{-- Loading Spinner --}}
                                                <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-stone-900/80 z-10">
                                                    <img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-8 h-8 object-contain">
                                                </div>
                                                {{-- Error Message --}}
                                                <div x-show="error" class="p-6 bg-red-50 text-red-600 rounded-lg shadow-sm border border-red-200 mt-8">
                                                    <p class="font-medium flex items-center gap-2">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        Gagal memuat PDF
                                                    </p>
                                                    <p x-text="errorMsg" class="text-sm mt-1"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="currentArtifact.type === 'code' && !['html','svg','react','jsx','tsx','markdown','md','pdf','document'].includes(currentArtifact.language)">
                                    <div class="p-6 flex items-center justify-center h-full">
                                        <div class="text-center text-stone-500 dark:text-stone-400">
                                            <div class="w-16 h-16 bg-[#F3F2F1] dark:bg-stone-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                                <svg class="w-8 h-8 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                            </div>
                                            <p class="text-[14px] font-medium text-stone-800 dark:text-stone-200 mb-1">Preview not available</p>
                                            <p class="text-[13px]">This backend code cannot be previewed visually.</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="versions.length > 1">
                            <div class="absolute bottom-4 right-4 flex items-center bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-lg shadow-sm p-1 z-20">
                                <template x-for="v in versions" :key="v.id">
                                    <button @click="switchVersion(v.id)" class="px-3 py-1 text-xs font-medium rounded-md transition-colors" :class="v.is_current ? 'bg-[#F3F2F1] dark:bg-stone-700 text-[#2D2825] dark:text-stone-200' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200'" x-text="'V'+v.version_number"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- ARTIFACTS LIST VIEW --}}
    <template x-if="!currentArtifact">
        <div class="flex-1 overflow-y-auto bg-[#F9F8F6] dark:bg-stone-900 p-8 flex flex-col items-center min-h-0 w-full">
            <div class="max-w-4xl w-full flex-1 flex flex-col">
                <div class="flex items-center justify-between mb-8 w-full mt-4">
                    <h1 class="font-serif text-3xl font-medium text-[#2D2825] dark:text-stone-200">Artifacts</h1>
                    <button @click="createNewArtifact()" class="bg-[#2D2825] hover:bg-black text-white dark:bg-stone-200 dark:text-stone-900 dark:hover:bg-white px-4 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">New artifact</button>
                </div>
                <div class="w-full mb-16">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-[18px] h-[18px] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input x-model="searchQuery" @input.debounce.300ms="filterArtifacts()" type="text" placeholder="Search artifacts..." class="block w-full pl-10 pr-3 py-3 border border-stone-200 dark:border-stone-700 rounded-xl bg-[#FCFBFA] dark:bg-[#323232] text-sm placeholder-stone-400 focus:outline-none focus:ring-0 focus:border-stone-300 dark:focus:border-stone-500 transition-all shadow-sm">
                    </div>
                </div>

                <template x-if="loading">
                    <div class="flex items-center justify-center py-12"><img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-8 h-8 object-contain"></div>
                </template>
                <template x-if="!loading && artifacts.length > 0">
                    <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pb-12">
                        <template x-for="artifact in filteredArtifacts" :key="artifact.id">
                            <div class="relative group" x-data="{ menuOpen: false }">
                                <button @click="openArtifact(artifact.id)" class="w-full text-left p-2 bg-[#FCFBFA] dark:bg-[#323232] hover:shadow-md border border-stone-200 dark:border-stone-700 rounded-xl shadow-sm transition-all duration-200 group/card flex flex-col h-full relative overflow-hidden focus:outline-none">
                                    <div class="flex items-center justify-between w-full mb-auto pr-8">
                                        <div class="w-6 h-6 rounded-lg bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-500 dark:text-stone-400 group-hover/card:text-[#D97757] transition-colors shrink-0">
                                            <svg x-show="artifact.language==='php'||artifact.type==='code'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                            <svg x-show="artifact.language!=='php'&&artifact.type!=='code'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        </div>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-[#F3F2F1] dark:bg-stone-700 text-[9px] font-medium text-stone-600 dark:text-stone-400 uppercase tracking-wider" x-text="artifact.language"></span>
                                    </div>
                                    <div class="mt-1.5 w-full">
                                        <h4 class="text-[12px] font-medium text-[#2D2825] dark:text-stone-200 truncate" x-text="artifact.title"></h4>
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5" x-text="'Created ' + timeAgo(artifact.created_at)"></p>
                                    </div>
                                </button>
                                <button @click.stop="menuOpen = !menuOpen" @click.away="menuOpen = false" class="absolute top-3 right-3 p-1.5 text-stone-400 hover:text-stone-600 dark:hover:text-stone-300 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-700 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 z-10" title="Options">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                </button>
                                <div x-show="menuOpen" x-cloak class="absolute top-10 right-3 w-40 bg-white dark:bg-stone-800 border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-20">
                                    <button @click.stop="menuOpen = false; renameArtifact(artifact)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-stone-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Rename
                                    </button>
                                    <div class="h-px w-full bg-[#E5E5E5] dark:bg-stone-700 my-1"></div>
                                    <button @click.stop="menuOpen = false; deleteArtifact(artifact)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="!loading && filteredArtifacts.length === 0 && searchQuery">
                    <div class="flex-1 flex flex-col items-center justify-center text-center pb-20">
                        <div class="w-12 h-12 rounded-2xl bg-[#F3F2F1] dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <h2 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-1">No artifacts found</h2>
                        <p class="text-[13px] text-gray-500 dark:text-stone-400" x-text="'No artifacts match \"' + searchQuery + '\".'"></p>
                    </div>
                </template>
                <template x-if="!loading && filteredArtifacts.length === 0 && !searchQuery">
                    <div class="flex-1 flex flex-col items-center justify-center text-center pb-20">
                        <div class="mb-5 relative">
                            <svg class="w-[88px] h-[88px] text-[#2D2825] dark:text-stone-300" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="35" y="25" width="22" height="22" stroke-linejoin="round"/><polygon points="68,25 78,42 58,42" stroke-linejoin="round"/><circle cx="70" cy="55" r="11"/><path d="M35,65 C35,65 30,65 30,55 C30,45 30,45 30,45 C30,42 33,42 33,45 L33,55 M37,42 C37,39 40,39 40,42 L40,55 M44,43 C44,40 47,40 47,43 L47,55 M51,45 C51,42 54,42 54,45 L54,60 C54,65 47,70 40,70 L35,70 Z" fill="white" stroke="currentColor" stroke-width="1.5" class="dark:fill-stone-800"/></svg>
                        </div>
                        <h2 class="text-[15px] font-medium text-[#2D2825] dark:text-stone-200 mb-2.5">What will you build with artifacts?</h2>
                        <p class="text-[13px] text-gray-500 dark:text-stone-400 mb-6 leading-relaxed max-w-[340px] mx-auto">If you can dream it, you can build it. Take apps, games, templates, and tools from thought to reality.</p>
                        <button @click="createNewArtifact()" class="bg-white hover:bg-gray-50 text-[#2D2825] dark:bg-stone-800 dark:hover:bg-stone-700 dark:text-stone-200 dark:border-stone-600 border border-[#E5E5E5] px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors shadow-sm">New artifact</button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

<script>
function artifactPanelState() {
    return {
        currentArtifact: null,
        currentPdfArtifactId: null,
        artifacts: [],
        filteredArtifacts: [],
        activeTab: 'code',
        versions: [],
        fullscreen: false,
        searchQuery: '',
        copied: false,
        downloading: false,
        loading: false,

        init: function() {
            this.loadArtifacts();
            window.addEventListener('open-artifact', function(e) {
                if (e.detail && e.detail.id) {
                    this.currentPdfArtifactId = null;
                    this.loadArtifact(e.detail.id);
                }
            }.bind(this));
            window.addEventListener('close-artifact-panel', function() {
                this.currentArtifact = null;
                this.currentPdfArtifactId = null;
                this.loadArtifacts();
            }.bind(this));
        },

        loadArtifacts: function() {
            this.loading = true;
            fetch('/api/artifacts', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    this.artifacts = resp.data || [];
                    this.filteredArtifacts = this.artifacts;
                    this.loading = false;
                }.bind(this))
                .catch(function(){this.loading=false;}.bind(this));
        },

        filterArtifacts: function() {
            var q = this.searchQuery.toLowerCase().trim();
            if (!q) { this.filteredArtifacts = this.artifacts; return; }
            this.filteredArtifacts = this.artifacts.filter(function(a) {
                return (a.title && a.title.toLowerCase().includes(q)) || (a.language && a.language.toLowerCase().includes(q));
            });
        },

        loadArtifact: function(id) {
            this.loading = true;
            this.currentPdfArtifactId = null;
            fetch('/api/artifacts/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        this.currentArtifact = resp.data;
                        this.versions = resp.data.versions || [];
                        this.activeTab = 'preview';
                        this.$nextTick(function() {
                            this.currentPdfArtifactId = resp.data.id;
                        }.bind(this));

                        // Jika ada data message terkait, buka chat-nya
                        if (resp.data.message && resp.data.message.conversation_id) {
                            console.log('[Artifact] Opening related chat, conversation_id:', resp.data.message.conversation_id);
                            // Delay kecil untuk memastikan event listener siap
                            setTimeout(function() {
                                this.openRelatedChat(resp.data.message.conversation_id);
                            }.bind(this), 100);
                        } else {
                            console.log('[Artifact] No related chat found for artifact', id);
                        }
                    }
                    this.loading = false;
                }.bind(this))
                .catch(function(err){
                    console.error('[Artifact] Error loading artifact:', err);
                    this.loading=false;
                }.bind(this));
        },

        get artifactContent() {
            return this.currentArtifact ? (this.currentArtifact.content || '') : '';
        },

        get previewContent() {
            if (!this.currentArtifact || !this.currentArtifact.content) return '';
            var lang = this.currentArtifact.language;
            if (['react','jsx','tsx'].includes(lang)) {
                return '<!DOCTYPE html><html><head><meta charset="utf-8"/><script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin><\/script><script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin><\/script><script src="https://unpkg.com/@babel/standalone/babel.min.js"><\/script><script src="https://cdn.tailwindcss.com"><\/script></head><body><div id="root"></div><script type="text/babel">' + this.currentArtifact.content + '\nconst App = typeof window.App !== "undefined" ? window.App : (typeof App !== "undefined" ? App : (typeof Example !== "undefined" ? Example : () => React.createElement("div", null, "Component not found")));\nconst root = ReactDOM.createRoot(document.getElementById("root"));\nroot.render(React.createElement(App));<\/script></body></html>';
            }
            if (lang === 'svg') {
                return '<html><body style="display:flex;align-items:center;justify-content:center;height:100vh;margin:0">' + this.currentArtifact.content + '</body></html>';
            }
            return this.currentArtifact.content;
        },

        get markdownContent() {
            if (!this.currentArtifact) return '';
            return this.currentArtifact.content;
        },

        openArtifact: function(id) {
            window.dispatchEvent(new CustomEvent('open-artifact', {detail: {id: id}}));
            this.loadArtifact(id);
        },

        closeArtifact: function() {
            this.currentArtifact = null;
            this.currentPdfArtifactId = null;
            this.versions = [];
            window.dispatchEvent(new CustomEvent('close-artifact-panel'));
            this.loadArtifacts();
        },

        createNewArtifact: function() {
            this.currentPdfArtifactId = null;
            this.currentArtifact = {id:null, title:'Untitled', language:'new', type:'new', content:''};
            window.dispatchEvent(new CustomEvent('show-artifact-panel'));
        },

        generateTemplate: function(type) {
            var prompt = "Create a new artifact for: " + type + ". Please generate a full, working example.";
            window.dispatchEvent(new CustomEvent('sendPromptFromArtifact', {detail: {prompt: prompt}}));
        },

        openRelatedChat: function(conversationId) {
            // Dispatch event openChat yang sudah ada di sistem
            // Event listener mengharapkan chatId, bukan conversationId
            console.log('[Artifact] Dispatching openChat event with chatId:', conversationId);
            window.dispatchEvent(new CustomEvent('openChat', {
                detail: { chatId: conversationId }
            }));
        },

        switchVersion: function(id) {
            this.currentPdfArtifactId = null;
            this.loadArtifact(id);
        },

        publishArtifact: function(id) {
            fetch('/api/artifacts/' + id, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({is_public: true})
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                if (resp.data) {
                    this.currentArtifact.is_public = resp.data.is_public;
                    if (resp.data.public_url) navigator.clipboard.writeText(resp.data.public_url);
                }
            }.bind(this));
        },

        unpublishArtifact: function(id) {
            fetch('/api/artifacts/' + id, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({is_public: false})
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                if (resp.data) this.currentArtifact.is_public = resp.data.is_public;
            }.bind(this));
        },

        renameArtifact: function(artifact) {
            var title = prompt('Rename artifact:', artifact.title);
            if (!title) return;
            fetch('/api/artifacts/' + artifact.id, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({title: title})
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                if (resp.data) {
                    artifact.title = resp.data.title;
                    if (this.currentArtifact && this.currentArtifact.id === artifact.id) {
                        this.currentArtifact = resp.data;
                    }
                    this.loadArtifacts();
                }
            }.bind(this));
        },

        deleteArtifact: function(artifact) {
            if (!confirm('Are you sure you want to delete this artifact?')) return;
            fetch('/api/artifacts/' + artifact.id, {
                method: 'DELETE',
                headers: {'Accept':'application/json'}
            })
            .then(function(r){
                if (r.ok) {
                    if (this.currentArtifact && this.currentArtifact.id === artifact.id) this.closeArtifact();
                    else this.loadArtifacts();
                }
            }.bind(this));
        },

        copyCode: function() {
            var self = this;
            if (!this.currentArtifact) return;
            var content = this.currentArtifact.content || '';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(content).then(function() {
                    self.copied = true;
                    setTimeout(function(){self.copied = false;}, 2000);
                });
            }
        },

        downloadPdf: function(mode) {
            if (!this.currentArtifact || !this.currentArtifact.id) return;
            this.downloading = true;
            var url = '/api/artifacts/' + this.currentArtifact.id + '/download/pdf';
            if (mode) url += '?mode=' + mode;
            window.location.href = url;
            setTimeout(function(){this.downloading = false;}.bind(this), 3000);
        },

        downloadMarkdown: function() {
            if (!this.currentArtifact || !this.currentArtifact.id) return;
            this.downloading = true;
            window.location.href = '/api/artifacts/' + this.currentArtifact.id + '/download/markdown';
            setTimeout(function(){this.downloading = false;}.bind(this), 3000);
        },

        downloadFile: function() {
            if (!this.currentArtifact || !this.currentArtifact.id) return;
            this.downloading = true;
            window.location.href = '/api/artifacts/' + this.currentArtifact.id + '/download/file';
            setTimeout(function(){this.downloading = false;}.bind(this), 3000);
        },

        downloadDocx: function() {
            if (!this.currentArtifact || !this.currentArtifact.id) return;
            this.downloading = true;
            window.location.href = '/api/artifacts/' + this.currentArtifact.id + '/download/docx';
            setTimeout(function(){this.downloading = false;}.bind(this), 3000);
        },

        downloadTxt: function() {
            if (!this.currentArtifact || !this.currentArtifact.id) return;
            this.downloading = true;
            window.location.href = '/api/artifacts/' + this.currentArtifact.id + '/download/txt';
            setTimeout(function(){this.downloading = false;}.bind(this), 3000);
        },

        downloadHtml: function() {
            if (!this.currentArtifact || !this.currentArtifact.id) return;
            this.downloading = true;
            window.location.href = '/api/artifacts/' + this.currentArtifact.id + '/download/html';
            setTimeout(function(){this.downloading = false;}.bind(this), 3000);
        },

        toggleFullscreen: function() {
            this.fullscreen = !this.fullscreen;
        },

        timeAgo: function(iso) {
            if (!iso) return '';
            var d = new Date(iso);
            var now = new Date();
            var sec = Math.floor((now - d) / 1000);
            if (sec < 60) return 'just now';
            var min = Math.floor(sec / 60);
            if (min < 60) return min + 'm ago';
            var hr = Math.floor(min / 60);
            if (hr < 24) return hr + 'h ago';
            var days = Math.floor(hr / 24);
            if (days < 7) return days + 'd ago';
            return d.toLocaleDateString();
        },
    };
}
</script>

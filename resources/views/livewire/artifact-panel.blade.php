<div class="h-full w-full flex flex-col bg-white dark:bg-stone-800 overflow-hidden shadow-2xl md:shadow-none">
    {{-- Panel Header --}}
    <div class="px-4 py-3 border-b border-[#E5E5E5] dark:border-stone-700 flex items-center justify-between bg-white dark:bg-stone-800 shrink-0 z-10 relative">
        {{-- Left: Icon and Title --}}
        <div class="flex items-center gap-2 max-w-[30%]">
            @if($isOpen && $currentArtifact)
                <div class="w-6 h-6 rounded-md bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-600 dark:text-stone-300 flex-shrink-0">
                    @if($currentArtifact['language'] === 'php' || $currentArtifact['type'] === 'code')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    @else
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    @endif
                </div>
                <h2 class="text-[13px] font-medium text-[#2D2825] dark:text-stone-200 truncate">{{ $currentArtifact['title'] }}</h2>
            @else
                <h2 class="text-[13px] font-medium text-[#2D2825] dark:text-stone-200">Artifacts</h2>
            @endif
        </div>

        {{-- Middle: Preview/Code Tabs --}}
        @if($isOpen && $currentArtifact)
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
        @endif

        {{-- Right: Actions --}}
        <div class="flex items-center gap-1.5">
            @if($isOpen && $currentArtifact)
                <button wire:click="copyCode" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Copy code">
                    @if($copied)
                        <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    @else
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    @endif
                </button>
                <button wire:click="downloadAsPdf" class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-300" title="Download">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
                <div class="w-px h-4 bg-[#E5E5E5] dark:bg-stone-700 mx-1 hidden md:block"></div>
            @endif
            
            {{-- Close panel button --}}
            <button
                wire:click="closeArtifact"
                class="p-1.5 hover:bg-[#F3F2F1] dark:hover:bg-stone-700 rounded-md transition-colors text-stone-500 dark:text-stone-400 flex items-center justify-center hover:text-stone-800 dark:hover:text-stone-300"
                title="Close"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-hidden bg-[#FAFAFA] dark:bg-stone-900 flex flex-col p-4 md:p-6">
        @if($isOpen && $currentArtifact)
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
                            <iframe srcdoc="{{ $currentArtifact['content'] }}" class="w-full h-full border-0 bg-white"></iframe>
                        @elseif($currentArtifact['language'] === 'react' || $currentArtifact['language'] === 'jsx' || $currentArtifact['language'] === 'tsx')
                            @php
                            $reactHtml = '<!DOCTYPE html><html><head><meta charset="utf-8" /><script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script><script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script><script src="https://unpkg.com/@babel/standalone/babel.min.js"></script><script src="https://cdn.tailwindcss.com"></script></head><body><div id="root"></div><script type="text/babel">'.$currentArtifact['content'].'
                            const App = typeof window.App !== "undefined" ? window.App : (typeof App !== "undefined" ? App : (typeof Example !== "undefined" ? Example : () => <div>Component not found</div>));
                            const root = ReactDOM.createRoot(document.getElementById("root"));
                            root.render(<App />);</script></body></html>';
                            @endphp
                            <iframe srcdoc="{{ $reactHtml }}" class="w-full h-full border-0 bg-white"></iframe>
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
                                wire:click="openArtifact({{ $v['id'] }})"
                                class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $v['is_current'] ? 'bg-[#F3F2F1] dark:bg-stone-700 text-[#2D2825] dark:text-stone-200' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}"
                            >
                                V{{ $v['version_number'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            {{-- Artifacts List --}}
            <div class="flex-1 overflow-y-auto">
                <div class="mb-4">
                    <h3 class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200">Recent Artifacts</h3>
                    <p class="text-[13px] text-stone-500 dark:text-stone-400">Your created documents, code, and more.</p>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    @forelse($artifacts as $artifact)
                        <button
                            wire:click="openArtifact({{ $artifact['id'] }})"
                            class="text-left p-4 bg-white dark:bg-stone-800 hover:bg-[#FBFBFA] dark:hover:bg-stone-700/50 border border-[#E5E5E5] dark:border-stone-700 rounded-[1rem] shadow-sm transition-all duration-200 group flex items-start gap-4"
                        >
                            <div class="w-10 h-10 rounded-lg bg-[#F3F2F1] dark:bg-stone-700 flex items-center justify-center text-stone-500 dark:text-stone-400 group-hover:text-[#D97757] transition-colors shrink-0">
                                @if($artifact['language'] === 'php' || $artifact['type'] === 'code')
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                @else
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate mb-0.5">{{ $artifact['title'] }}</h4>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-[#F3F2F1] dark:bg-stone-700 text-[10px] font-medium text-stone-600 dark:text-stone-400 uppercase tracking-wider">
                                        {{ $artifact['language'] }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="text-center py-12">
                            <div class="w-12 h-12 bg-[#F3F2F1] dark:bg-stone-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-stone-400 dark:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                            </div>
                            <p class="text-[14px] font-medium text-stone-800 dark:text-stone-200">No artifacts yet</p>
                            <p class="text-[13px] text-stone-500 dark:text-stone-400">Ask Claude to write code or create documents.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>


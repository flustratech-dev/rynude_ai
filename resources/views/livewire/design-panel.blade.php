<div class="min-h-screen h-full bg-[#F9F8F6] dark:bg-stone-900 flex flex-col font-sans">
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    {{-- Header --}}
    <div class="w-full bg-[#F9F8F6] dark:bg-stone-900 border-b border-[#E5E5E5] dark:border-stone-800/80 px-8 py-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" class="font-serif text-[17px] font-medium text-[#2D2825] dark:text-stone-100 whitespace-nowrap hover:opacity-80 transition-opacity">Rynude Design</a>
            <span class="text-[9px] font-semibold tracking-wider uppercase px-1.5 py-0.5 bg-[#EAE9E5] dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-[4px] whitespace-nowrap">Beta</span>
        </div>

        <div class="flex items-center gap-1">
            @foreach(['recent' => 'Recent', 'yours' => 'Your designs', 'examples' => 'Examples'] as $key => $label)
                <button wire:click="$set('currentTab', '{{ $key }}')" class="px-3 py-1 rounded-lg text-[13px] transition-all {{ $currentTab === $key ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <div class="relative flex items-center">
                <svg class="w-3.5 h-3.5 text-stone-400 absolute left-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search designs" class="pl-8 pr-3 py-1 bg-white dark:bg-stone-850 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] placeholder-stone-400 text-stone-850 dark:text-stone-200 focus:outline-none w-44 focus:w-52 transition-all">
            </div>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="max-w-6xl w-full mx-auto px-8 py-8 flex-1 flex flex-col gap-6 overflow-y-auto">
        @if (session('designMessage'))
            <div class="px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 rounded-lg text-[13px]">
                {{ session('designMessage') }}
            </div>
        @endif
        {{-- Section: Make something new --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="text-[13px] font-semibold text-[#2D2825] dark:text-stone-200">Make something new</span>
            </div>

            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none select-none">
                @php
                    $typeIcons = [
                        'slides' => '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
                        'prototype' => '<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/>',
                        'wireframe' => '<rect x="3" y="3" width="18" height="18" rx="2" stroke-dasharray="3 3"/><path d="M3 9h18M9 9v12"/>',
                        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>',
                        'animation' => '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>',
                        'blank' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
                    ];
                @endphp
                @foreach($designTypes as $type => $meta)
                    <button wire:click="openDialog('{{ $type }}')" class="w-[145px] shrink-0 flex flex-col group text-left">
                        <div class="h-[95px] w-full rounded-xl border border-[#E5E5E5] dark:border-stone-800 bg-white dark:bg-stone-850 flex items-center justify-center hover:border-[#D97757] dark:hover:border-[#D97757] shadow-sm transition-all">
                            <div class="w-12 h-9 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 flex items-center justify-center text-stone-400 group-hover:text-[#D97757] transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">{!! $typeIcons[$type] ?? $typeIcons['blank'] !!}</svg>
                            </div>
                        </div>
                        <div class="text-[12.5px] font-semibold text-[#2D2825] dark:text-stone-200 mt-2">{{ $meta['label'] }}</div>
                        <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">{{ $meta['sub'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Designs Gallery --}}
        <div>
            <h3 class="text-[13.5px] font-semibold text-[#2D2825] dark:text-stone-200 mb-3">Designs</h3>

            @php $designs = $this->designs; @endphp
            @if($designs->isEmpty())
                <div class="w-full bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl py-12 flex flex-col items-center justify-center text-center shadow-sm">
                    <div class="w-12 h-12 rounded-xl bg-stone-50 dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-3 border border-stone-200 dark:border-stone-700">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <p class="text-stone-500 dark:text-stone-400 text-[13.5px] font-medium">No designs yet</p>
                    <p class="text-stone-400 dark:text-stone-500 text-xs mt-1">Pick a type above and describe what you want Rynude to design.</p>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($designs as $design)
                        <div class="group bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl overflow-hidden shadow-sm hover:border-stone-300 dark:hover:border-stone-700 hover:shadow transition-all">
                            <div wire:click="viewDesign({{ $design->id }})" class="h-[140px] bg-stone-50 dark:bg-stone-800 relative cursor-pointer overflow-hidden border-b border-stone-100 dark:border-stone-800">
                                @if($design->status === 'ready' && $design->content)
                                    <iframe srcdoc="{{ $design->content }}" class="w-[200%] h-[280px] origin-top-left scale-50 pointer-events-none border-0" sandbox="allow-scripts"></iframe>
                                @elseif($design->status === 'generating')
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-stone-400 gap-2">
                                        <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        <span class="text-[11px]">Generating…</span>
                                    </div>
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center text-red-400 text-[11px]">Generation failed</div>
                                @endif
                            </div>
                            <div class="p-3 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-[12.5px] font-medium text-[#2D2825] dark:text-stone-100 truncate">{{ $design->title }}</div>
                                    <div class="text-[10.5px] text-stone-400 mt-0.5">{{ $designTypes[$design->type]['label'] ?? ucfirst($design->type) }} · {{ $design->created_at->diffForHumans() }}</div>
                                </div>
                                <div class="flex items-center gap-0.5 flex-shrink-0">
                                    <button wire:click="toggleStar({{ $design->id }})" class="p-1 transition-colors {{ $design->is_starred ? 'text-amber-400' : 'text-stone-300 hover:text-amber-400' }}" title="Star">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="{{ $design->is_starred ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    </button>
                                    <button wire:click="deleteDesign({{ $design->id }})" class="p-1 text-stone-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100" title="Delete">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ============ GENERATION DIALOG ============ --}}
    @if($showDialog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-4" wire:click.self="closeDialog">
            <div class="bg-white dark:bg-stone-850 border border-stone-200 dark:border-stone-700 rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <h3 class="font-serif text-[18px] font-medium text-[#2D2825] dark:text-stone-100 mb-1">Generate {{ $designTypes[$dialogType]['label'] ?? 'design' }}</h3>
                <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mb-4">{{ $designTypes[$dialogType]['sub'] ?? '' }}</p>

                <textarea wire:model="dialogPrompt" rows="4" placeholder="Describe the design you want… e.g. A pricing page for a SaaS product with three tiers"
                    class="w-full px-3 py-2.5 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13.5px] text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:border-[#D97757] resize-none"></textarea>
                @error('dialogPrompt') <span class="text-red-500 text-[11.5px] mt-1 block">{{ $message }}</span> @enderror

                <div class="flex items-center justify-end gap-2 mt-4">
                    <button wire:click="closeDialog" class="px-4 py-2 text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 text-[13px] font-medium transition-colors">Cancel</button>
                    <button wire:click="generate" wire:loading.attr="disabled" wire:target="generate" class="flex items-center gap-1.5 px-4 py-2 bg-[#D97757] hover:bg-[#c56647] text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">
                        <svg wire:loading wire:target="generate" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <span wire:loading.remove wire:target="generate">Generate</span>
                        <span wire:loading wire:target="generate">Generating…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ VIEWER ============ --}}
    @if($this->viewing)
        @php $v = $this->viewing; @endphp
        <div class="fixed inset-0 z-50 flex flex-col bg-stone-900/60 backdrop-blur-sm" wire:click.self="closeViewer">
            <div class="flex items-center justify-between px-6 py-3 bg-white dark:bg-stone-850 border-b border-stone-200 dark:border-stone-700 flex-shrink-0">
                <div class="min-w-0">
                    <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-100 truncate">{{ $v->title }}</div>
                    <div class="text-[11px] text-stone-400">{{ $designTypes[$v->type]['label'] ?? ucfirst($v->type) }}</div>
                </div>
                <button wire:click="closeViewer" class="p-2 text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex-1 bg-white">
                @if($v->status === 'ready' && $v->content)
                    <iframe srcdoc="{{ $v->content }}" class="w-full h-full border-0" sandbox="allow-scripts allow-same-origin"></iframe>
                @elseif($v->status === 'generating')
                    <div class="h-full flex flex-col items-center justify-center text-stone-400 gap-3">
                        <svg class="w-7 h-7 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <span class="text-[13px]">Rynude is generating your design…</span>
                    </div>
                @else
                    <div class="h-full flex items-center justify-center text-red-400 text-[13px] px-8 text-center">{{ $v->content ?: 'Generation failed. Please try again.' }}</div>
                @endif
            </div>
        </div>
    @endif
</div>

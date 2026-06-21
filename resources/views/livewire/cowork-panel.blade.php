<div class="h-full bg-[#F9F8F6] dark:bg-stone-900 flex flex-col font-sans overflow-hidden">
    {{-- Header --}}
    <div class="w-full border-b border-[#E5E5E5] dark:border-stone-800/80 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <h1 class="font-serif text-[17px] font-medium text-[#2D2825] dark:text-stone-100">Cowork</h1>
            <span class="text-[9px] font-semibold tracking-wider uppercase px-1.5 py-0.5 bg-[#EAE9E5] dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-[4px]">Beta</span>
        </div>
        @if($view === 'list')
            <button wire:click="showCreate" class="flex items-center gap-1.5 px-3.5 py-1.5 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New task
            </button>
        @elseif($view === 'create' || $view === 'detail')
            <button wire:click="showList" class="flex items-center gap-1.5 px-3 py-1.5 text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 text-[13px] font-medium transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </button>
        @endif
    </div>

    @if (session('coworkMessage'))
        <div class="mx-6 mt-3 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-lg text-[13px]">
            {{ session('coworkMessage') }}
        </div>
    @endif

    <div class="flex-1 overflow-y-auto px-6 py-5">
        <div class="max-w-3xl mx-auto w-full">

        {{-- ============ LANDING VIEW ============ --}}
        @if($view === 'landing')
            <div class="h-full flex flex-col items-center justify-center px-6 py-6">
                <div class="max-w-3xl w-full flex flex-col items-center justify-center">
                    {{-- Video Container --}}
                    <div class="w-full max-w-[560px] aspect-[1.6] rounded-3xl border border-stone-200 dark:border-stone-800 overflow-hidden shadow-[0_8px_30px_rgba(0,0,0,0.02)] bg-white dark:bg-stone-950 flex items-center justify-center relative">
                        <video
                            class="w-full h-full object-cover"
                            src="{{ asset('video/desktop-cowork-tab.mp4') }}"
                            autoplay
                            muted
                            loop
                            playsinline
                        ></video>
                    </div>

                    {{-- Heading --}}
                    <h2 class="text-[26px] font-serif text-[#2D2825] dark:text-stone-100 font-medium tracking-tight text-center mt-6 mb-2 leading-tight">
                        Hand off complex tasks
                    </h2>

                    {{-- Description --}}
                    <p class="text-[14.5px] text-stone-500 dark:text-stone-400 max-w-[480px] text-center leading-relaxed mb-6">
                        Describe what you need and come back to polished work. Rynude works across your files and connected tools, on the schedule you set.
                    </p>

                    {{-- Button --}}
                    <button
                        wire:click="getStarted"
                        class="px-5 py-2.5 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-xl text-[14px] font-medium transition-colors shadow-sm focus:outline-none"
                    >
                        Get started
                    </button>
                </div>
            </div>

        {{-- ============ LIST VIEW ============ --}}
        @elseif($view === 'list')
            {{-- Stats --}}
            <div class="grid grid-cols-4 gap-3 mb-5">
                @php
                    $stats = $this->stats;
                    $cards = [
                        ['label' => 'Total', 'value' => $stats['total'], 'color' => 'text-[#2D2825] dark:text-stone-100'],
                        ['label' => 'Pending', 'value' => $stats['pending'], 'color' => 'text-amber-600 dark:text-amber-400'],
                        ['label' => 'Running', 'value' => $stats['in_progress'], 'color' => 'text-blue-600 dark:text-blue-400'],
                        ['label' => 'Completed', 'value' => $stats['completed'], 'color' => 'text-emerald-600 dark:text-emerald-400'],
                    ];
                @endphp
                @foreach($cards as $card)
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-3.5 shadow-sm">
                        <div class="text-[22px] font-semibold {{ $card['color'] }} leading-none">{{ $card['value'] }}</div>
                        <div class="text-[11.5px] text-stone-500 dark:text-stone-400 mt-1.5">{{ $card['label'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Filter --}}
            <div class="flex items-center gap-1 mb-4">
                @foreach(['all' => 'All', 'pending' => 'Pending', 'in_progress' => 'Running', 'completed' => 'Completed', 'failed' => 'Failed'] as $key => $label)
                    <button wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3 py-1 rounded-lg text-[12.5px] transition-all {{ $statusFilter === $key ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Task list --}}
            @php $tasks = $this->tasks; @endphp
            @if($tasks->isEmpty())
                <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl py-14 flex flex-col items-center justify-center text-center shadow-sm">
                    <div class="w-12 h-12 rounded-xl bg-stone-50 dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-3 border border-stone-200 dark:border-stone-700">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <p class="text-stone-600 dark:text-stone-300 text-[14px] font-medium">No tasks yet</p>
                    <p class="text-stone-400 dark:text-stone-500 text-[12.5px] mt-1 max-w-xs">Hand off complex work to Rynude. Describe what you need and come back to a polished result.</p>
                    <button wire:click="showCreate" class="mt-4 px-4 py-2 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                        Create your first task
                    </button>
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($tasks as $task)
                        @php
                            $statusStyles = [
                                'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                                'in_progress' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
                                'completed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                                'failed' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
                            ];
                            $statusLabels = ['pending' => 'Pending', 'in_progress' => 'Running', 'completed' => 'Completed', 'failed' => 'Failed'];
                            $priorityDot = ['low' => 'bg-stone-400', 'medium' => 'bg-amber-400', 'high' => 'bg-red-500'];
                        @endphp
                        <div wire:click="openTask({{ $task->id }})" class="group bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl px-4 py-3.5 shadow-sm hover:border-stone-300 dark:hover:border-stone-700 hover:shadow transition-all cursor-pointer flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $priorityDot[$task->priority] ?? 'bg-stone-400' }}" title="{{ ucfirst($task->priority) }} priority"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-100 truncate">{{ $task->title }}</div>
                                @if($task->description)
                                    <div class="text-[12px] text-stone-500 dark:text-stone-400 truncate mt-0.5">{{ $task->description }}</div>
                                @endif
                                <div class="flex items-center gap-2 mt-1.5 text-[11px] text-stone-400">
                                    <span>{{ $task->model }}</span>
                                    @if($task->scheduled_for)
                                        <span>· Scheduled {{ $task->scheduled_for->format('M j, H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-medium flex-shrink-0 {{ $statusStyles[$task->status] ?? '' }}">{{ $statusLabels[$task->status] ?? $task->status }}</span>
                            <button wire:click.stop="deleteTask({{ $task->id }})" class="opacity-0 group-hover:opacity-100 p-1 text-stone-400 hover:text-red-500 transition-all" title="Delete">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

        {{-- ============ CREATE VIEW ============ --}}
        @elseif($view === 'create')
            <h2 class="font-serif text-[20px] font-medium text-[#2D2825] dark:text-stone-100 mb-1">Hand off a task</h2>
            <p class="text-[13px] text-stone-500 dark:text-stone-400 mb-5">Describe what you need and assign it to a model.</p>

            <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl p-5 shadow-sm space-y-4">
                <div>
                    <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Title</label>
                    <input type="text" wire:model="title" placeholder="e.g. Draft a launch announcement"
                        class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13.5px] text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:border-[#D97757]">
                    @error('title') <span class="text-red-500 text-[11.5px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Description / instructions</label>
                    <textarea wire:model="description" rows="5" placeholder="Provide context, requirements, and the desired output…"
                        class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13.5px] text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:border-[#D97757] resize-none"></textarea>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Model</label>
                        <select wire:model="model" class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-[#D97757]">
                            @foreach($modelOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Priority</label>
                        <select wire:model="priority" class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-[#D97757]">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Schedule (optional)</label>
                        <input type="datetime-local" wire:model="scheduledFor" class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-[#D97757]">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button wire:click="showList" class="px-4 py-2 text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 text-[13px] font-medium transition-colors">Cancel</button>
                    <button wire:click="createTask" wire:loading.attr="disabled" class="px-4 py-2 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">
                        Create task
                    </button>
                </div>
            </div>

        {{-- ============ DETAIL VIEW ============ --}}
        @elseif($view === 'detail' && $this->activeTask)
            @php $task = $this->activeTask; @endphp
            <div class="flex items-start justify-between mb-1">
                <h2 class="font-serif text-[20px] font-medium text-[#2D2825] dark:text-stone-100">{{ $task->title }}</h2>
                @php
                    $statusStyles = [
                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                        'in_progress' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
                        'completed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                        'failed' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
                    ];
                    $statusLabels = ['pending' => 'Pending', 'in_progress' => 'Running', 'completed' => 'Completed', 'failed' => 'Failed'];
                @endphp
                <span class="px-2.5 py-1 rounded-md text-[12px] font-medium {{ $statusStyles[$task->status] ?? '' }}">{{ $statusLabels[$task->status] ?? $task->status }}</span>
            </div>
            <div class="flex items-center gap-2 text-[12px] text-stone-400 mb-4">
                <span>{{ $task->model }}</span>
                <span>· {{ ucfirst($task->priority) }} priority</span>
                @if($task->scheduled_for)<span>· Scheduled {{ $task->scheduled_for->format('M j, H:i') }}</span>@endif
            </div>

            @if($task->description)
                <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-4 shadow-sm mb-4">
                    <div class="text-[11.5px] font-semibold uppercase tracking-wide text-stone-400 mb-1.5">Instructions</div>
                    <p class="text-[13.5px] text-stone-700 dark:text-stone-300 whitespace-pre-wrap leading-relaxed">{{ $task->description }}</p>
                </div>
            @endif

            {{-- Action bar --}}
            <div class="flex items-center gap-2 mb-4">
                <button wire:click="runTask({{ $task->id }})" wire:loading.attr="disabled" wire:target="runTask"
                    class="flex items-center gap-1.5 px-4 py-2 bg-[#D97757] hover:bg-[#c56647] text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">
                    <svg wire:loading.remove wire:target="runTask" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    <svg wire:loading wire:target="runTask" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    <span wire:loading.remove wire:target="runTask">{{ $task->result ? 'Re-run' : 'Run task' }}</span>
                    <span wire:loading wire:target="runTask">Running…</span>
                </button>
                @if($task->status !== 'completed')
                    <button wire:click="updateStatus({{ $task->id }}, 'completed')" class="px-3.5 py-2 border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300 rounded-lg text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors">Mark complete</button>
                @else
                    <button wire:click="updateStatus({{ $task->id }}, 'pending')" class="px-3.5 py-2 border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300 rounded-lg text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors">Reopen</button>
                @endif
                <button wire:click="deleteTask({{ $task->id }})" class="px-3.5 py-2 border border-stone-200 dark:border-stone-700 text-red-500 rounded-lg text-[13px] font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">Delete</button>
            </div>

            <div wire:loading.flex wire:target="runTask" class="items-center gap-2 text-[13px] text-stone-500 dark:text-stone-400 mb-4">
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                Rynude is working on this task…
            </div>

            {{-- Result --}}
            @if($task->result)
                <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[11.5px] font-semibold uppercase tracking-wide text-stone-400">Result</div>
                        @if($task->completed_at)<span class="text-[11px] text-stone-400">Completed {{ $task->completed_at->diffForHumans() }}</span>@endif
                    </div>
                    <div class="text-[13.5px] text-stone-700 dark:text-stone-200 whitespace-pre-wrap leading-relaxed">{{ $task->result }}</div>
                </div>
            @endif
        @endif

        </div>
    </div>
</div>

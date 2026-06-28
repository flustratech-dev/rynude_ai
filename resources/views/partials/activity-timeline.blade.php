{{--
    Phase 2 (Blade Template Migration) — Activity Timeline presentation.

    Livewire-independent partial: it renders the workflow-stage timeline using only
    Alpine.js state and browser CustomEvents (`agent-stream-event`,
    `agent-generation-start`). It does NOT reference the Livewire `$wire` bridge —
    the previous `$wire.initialEvents` / `$wire.workflowId` lookups are replaced by
    Blade-injected values baked into the Alpine component at render time.

    Inputs (plain PHP values, no Livewire required):
      - $initialEvents array   historical events to replay on init (default [])
      - $workflowId    ?string only process events for this workflow (default null = all)

    Today this is included by the thin Livewire wrapper (livewire/activity-timeline),
    so the mount point in chat-interface stays unchanged. Later phases can
    @include this partial directly from any Blade view with no Livewire at all.
--}}
<div wire:ignore x-data="{
    workflowId: @js($workflowId ?? null),
    initialEvents: @js($initialEvents ?? []),
    stages: [
        { id: 'UNDERSTAND', label: 'Understand Request', status: 'pending' },
        { id: 'PLAN', label: 'Plan Execution', status: 'pending' },
        { id: 'RESEARCH', label: 'Research Context', status: 'pending' },
        { id: 'WRITE', label: 'Draft Content', status: 'pending' },
        { id: 'REVIEW', label: 'Review Output', status: 'pending' },
        { id: 'FINAL', label: 'Complete', status: 'pending' }
    ],
    init() {
        if (this.initialEvents) {
            this.initialEvents.forEach(e => this.processEvent(e));
        }
    },
    handleEvent(e) {
        const data = e.detail;
        if (this.workflowId && data.workflowId !== this.workflowId) return;
        this.processEvent(data);
    },
    processEvent(event) {
        let stageId = event.stage;

        // Handle global completion
        if (!stageId && event.eventType === 'completed') {
            this.updateStageStatus('FINAL', 'completed');
            return;
        }
        if (!stageId && (event.eventType === 'error' || event.eventType === 'cancelled' || event.eventType === 'timeout')) {
            this.updateStageStatus('FINAL', 'error');
            return;
        }

        if (!stageId) return; // Skip other non-stage events if any

        if (event.eventType === 'start' || event.eventType === 'running') {
            this.updateStageStatus(stageId, 'running');
        } else if (event.eventType === 'completed') {
            this.updateStageStatus(stageId, 'completed');
        } else if (event.eventType === 'error' || event.eventType === 'cancelled' || event.eventType === 'timeout') {
            this.updateStageStatus(stageId, 'error');
        }
    },
    updateStageStatus(id, status) {
        const idx = this.stages.findIndex(s => s.id === id);
        if (idx !== -1) {
            // Do not downgrade a completed status to running if there's out-of-order events
            if (this.stages[idx].status === 'completed' && status !== 'error') return;
            this.stages[idx].status = status;
        }
    }
}"
@agent-stream-event.window="handleEvent"
@agent-generation-start.window="stages = stages.map(function(s) { return { id: s.id, label: s.label, status: 'pending' }; })"
class="flex flex-col font-sans px-4 py-4 mb-4 rounded-xl border border-stone-200 dark:border-stone-700 bg-[#FAFAFA] dark:bg-stone-800 shadow-sm"
>
    <h3 class="text-[11px] font-bold uppercase tracking-widest text-stone-500 dark:text-stone-400 mb-4 opacity-80">Workflow Stages</h3>
    <div class="relative pl-1">
        <div class="absolute left-[11px] top-2 bottom-4 w-px bg-stone-200 dark:bg-stone-700"></div>
        <template x-for="(stage, index) in stages" :key="stage.id">
            <div class="relative flex items-start gap-3 mb-3 last:mb-0">
                <div class="relative z-10 shrink-0 mt-0.5 bg-[#FAFAFA] dark:bg-stone-800 rounded-full py-0.5">
                    <!-- Icon based on status -->
                    <template x-if="stage.status === 'pending'">
                        <div class="w-3.5 h-3.5 mx-[1px] rounded-full border-[1.5px] border-stone-300 dark:border-stone-600 bg-transparent"></div>
                    </template>
                    <template x-if="stage.status === 'running'">
                        <div class="w-4 h-4 flex items-center justify-center bg-[#FAFAFA] dark:bg-stone-800">
                            <svg class="animate-spin w-4 h-4 text-[#D97757]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </template>
                    <template x-if="stage.status === 'completed'">
                        <div class="w-4 h-4 rounded-full bg-green-500 flex items-center justify-center text-white ring-[3px] ring-[#FAFAFA] dark:ring-stone-800">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
                        </div>
                    </template>
                    <template x-if="stage.status === 'error'">
                        <div class="w-4 h-4 rounded-full bg-red-500 flex items-center justify-center text-white ring-[3px] ring-[#FAFAFA] dark:ring-stone-800">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                        </div>
                    </template>
                </div>
                <div class="flex flex-col">
                    <span class="text-[13px] font-medium leading-tight mt-[3px]" :class="{
                        'text-stone-400 dark:text-stone-500': stage.status === 'pending',
                        'text-[#D97757]': stage.status === 'running',
                        'text-stone-800 dark:text-stone-200': stage.status === 'completed',
                        'text-red-600 dark:text-red-400': stage.status === 'error'
                    }" x-text="stage.label"></span>
                </div>
            </div>
        </template>
    </div>
</div>

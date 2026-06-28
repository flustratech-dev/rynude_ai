<div wire:ignore class="flex flex-col w-full px-2 mt-4 font-sans max-h-[300px] overflow-y-auto scrollbar-hide" 
    x-data="{
        events: @js($events),
        handleEvent(e) {
            const data = e.detail;
            if (this.$wire.workflowId && data.workflowId !== this.$wire.workflowId) return;
            if (!this.events) this.events = [];
            
            // Deduplicate by id (exact same event delivered twice)
            if (this.events.some(x => x.id === data.id)) return;
            
            if (data.stage || data.eventType === 'tool_start' || data.eventType === 'tool_end') {
                const identifier = data.stage || data.message;
                const idx = this.events.findIndex(x => (x.stage === identifier || x.message === identifier) && x.eventType !== 'completed' && x.eventType !== 'error' && x.eventType !== 'tool_end');
                
                if (idx >= 0) {
                    this.events[idx] = data;
                } else {
                    this.events.push(data);
                }
            } else {
                this.events.push(data);
            }
            
            // Sort by sequenceNumber
            this.events.sort((a, b) => (a.sequenceNumber || 0) - (b.sequenceNumber || 0));
            
            this.$nextTick(() => {
                const container = this.$refs.feedContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    }"
    @agent-stream-event.window="handleEvent"
    x-ref="feedContainer"
>
    <template x-for="(event, index) in events" :key="event.id || index">
        <div class="flex items-start gap-3 py-1.5 transition-all duration-300">
            <div class="mt-0.5 shrink-0">
                <template x-if="event.eventType === 'start' || event.eventType === 'tool_start' || event.eventType === 'running'">
                    <svg class="animate-spin h-4 w-4 text-stone-400 dark:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </template>
                <template x-if="event.eventType === 'completed' || event.eventType === 'tool_end'">
                    <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
                </template>
                <template x-if="event.eventType === 'error' || event.eventType === 'cancelled' || event.eventType === 'timeout'">
                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                </template>
            </div>
            <div class="flex flex-col">
                <span x-text="event.message" class="text-[13px] font-medium leading-relaxed" :class="{
                    'text-stone-800 dark:text-stone-200': event.eventType === 'start' || event.eventType === 'tool_start',
                    'text-stone-500 dark:text-stone-400': event.eventType === 'completed' || event.eventType === 'tool_end',
                    'text-red-600 dark:text-red-400': event.eventType === 'error' || event.eventType === 'cancelled' || event.eventType === 'timeout'
                }"></span>
                <template x-if="event.stage && event.eventType === 'completed'">
                    <span class="text-[11px] text-stone-400 dark:text-stone-500" x-text="event.stage + ' finished'"></span>
                </template>
            </div>
        </div>
    </template>
</div>

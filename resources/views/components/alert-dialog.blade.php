<div
    x-data="{
        open: false,
        title: '',
        message: '',
        type: 'info',
        confirming: false,
        resolve: null,
        init() {
            window.addEventListener('show-alert', (e) => {
                this.title = e.detail.title || 'Info';
                this.message = e.detail.message;
                this.type = e.detail.type || 'info';
                this.confirming = false;
                this.resolve = window._alertResolve;
                this.open = true;
            });
            window.addEventListener('show-confirm', (e) => {
                this.title = e.detail.title || 'Confirm';
                this.message = e.detail.message;
                this.type = e.detail.type || 'warning';
                this.confirming = true;
                this.resolve = window._alertResolve;
                this.open = true;
            });
        },
        close(value) {
            if (this.resolve) this.resolve(value);
            this.open = false;
        }
    }"
    @keydown.escape.window="if (open) close(false)"
    @keydown.enter.window="if (open && !confirming) close(true)"
>
    <div x-show="open" x-cloak
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div @click.away="if (!confirming) close(false)"
             x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-md bg-white dark:bg-[#2C2C2A] border border-stone-200 dark:border-stone-700 rounded-lg shadow-xl p-5">

            <!-- Title + Message -->
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-1 min-w-0">
                    <h3 class="text-[15px] font-semibold text-stone-800 dark:text-stone-100" x-text="title"></h3>
                    <p class="text-[12.5px] text-stone-500 dark:text-stone-400 mt-0.5" x-text="message"></p>
                </div>
                <button type="button" @click="close(false)" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-[#3A3A38] transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-2 mt-4">
                <template x-if="confirming">
                    <button @click="close(false)" type="button" class="text-[13px] font-medium text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-[#3A3A38] rounded-lg px-3 py-1.5 transition-colors">
                        Cancel
                    </button>
                </template>
                <button @click="close(true)" type="button"
                        class="text-[13px] font-medium text-white rounded-lg px-3.5 py-1.5 transition-colors shadow-sm bg-red-500 hover:bg-red-600"
                        x-text="confirming ? 'Confirm' : 'OK'">
                </button>
            </div>
        </div>
    </div>
</div>

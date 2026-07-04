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
             class="bg-white dark:bg-[#1C1C1C] rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
            
            <!-- Icon -->
            <div class="pt-7 pb-1 px-6 text-center">
                <template x-if="type === 'error'">
                    <div class="mx-auto w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                </template>
                <template x-if="type === 'success'">
                    <div class="mx-auto w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </template>
                <template x-if="type === 'warning'">
                    <div class="mx-auto w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L2.12 19.86A1 1 0 003 21h18a1 1 0 00.88-1.14L13.71 3.86a1 1 0 00-1.72 0z"/></svg>
                    </div>
                </template>
                <template x-if="!type || type === 'info'">
                    <div class="mx-auto w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20 10 10 0 010-20z"/></svg>
                    </div>
                </template>

                <h3 class="text-[15px] font-semibold text-[#2D2825] dark:text-stone-200" x-text="title"></h3>
                <p class="mt-1.5 text-[13px] text-gray-600 dark:text-stone-400 leading-relaxed" x-text="message"></p>
            </div>

            <!-- Buttons -->
            <div class="px-6 pb-6 pt-4 flex gap-2 justify-center">
                <template x-if="confirming">
                    <button @click="close(false)" type="button"
                            class="px-4 py-2 text-[13px] font-medium text-gray-700 dark:text-stone-300 bg-white dark:bg-transparent border border-gray-300 dark:border-stone-600 rounded-xl hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors">
                        Cancel
                    </button>
                </template>
                <button @click="close(true)" type="button"
                        class="px-5 py-2 text-[13px] font-medium text-white rounded-xl transition-colors"
                        :class="type === 'error' ? 'bg-red-600 hover:bg-red-700' : 'bg-[#D97757] hover:bg-[#c56647]'"
                        x-text="confirming ? 'Confirm' : 'OK'">
                </button>
            </div>
        </div>
    </div>
</div>

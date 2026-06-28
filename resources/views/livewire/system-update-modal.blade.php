<div
    x-data="{
        open: false,
        showCopied: false,
        updateCommand: '{{ $updateCommand }}',
        closeModal() {
            this.open = false;
        },
        copyCommand() {
            navigator.clipboard.writeText(this.updateCommand).then(() => {
                this.showCopied = true;
                setTimeout(() => this.showCopied = false, 2000);
            });
        }
    }"
    @open-system-update.window="open = true; updateCommand = $event.detail?.command || '{{ $updateCommand }}'"
    @keydown.escape.window="if (open) closeModal()"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm sm:p-6 transition-opacity"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="relative w-full max-w-2xl overflow-hidden bg-white dark:bg-[#1e1e2e] rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.outside="closeModal()"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Pembaruan Sistem Tersedia</h3>
                </div>
                <button @click="closeModal()" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="px-6 py-6 overflow-y-auto max-h-[70vh]">
                <div class="space-y-6">
                    <p class="text-gray-600 dark:text-gray-300">
                        Sistem telah mendeteksi adanya pembaruan kode terbaru. Untuk memastikan aplikasi berjalan dengan baik dan mendapatkan fitur terbaru, silakan lakukan pembaruan sistem.
                    </p>

                    <!-- Update Steps -->
                    <div class="p-5 bg-gray-50 dark:bg-[#252538] rounded-xl border border-gray-100 dark:border-gray-700/50">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Langkah-langkah Pembaruan:
                        </h4>
                        <ol class="space-y-3 text-sm text-gray-600 dark:text-gray-300 list-decimal list-inside">
                            <li>Buka terminal atau command prompt (cmd/powershell).</li>
                            <li>Salin perintah pembaruan di bawah ini dan jalankan langsung di terminal (tidak perlu masuk ke direktori project).</li>
                            <li>Tunggu hingga proses instalasi atau pembaruan selesai.</li>
                            <li>Muat ulang (refresh) halaman ini.</li>
                        </ol>
                    </div>

                    <!-- Command Copy Section -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Perintah Pembaruan</label>
                        <div class="relative flex items-center">
                            <pre class="w-full p-4 overflow-x-auto text-sm text-gray-200 bg-gray-900 rounded-xl dark:bg-black font-mono select-all" x-text="updateCommand"></pre>
                            <button
                                @click="copyCommand()"
                                class="absolute right-3 p-2 text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors border border-gray-700"
                                title="Salin ke clipboard"
                            >
                                <svg x-show="!showCopied" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg x-show="showCopied" x-cloak class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                        <p x-show="showCopied" x-cloak class="text-sm text-green-500 dark:text-green-400 mt-2 font-medium" x-transition>
                            Berhasil disalin ke clipboard!
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button @click="closeModal()" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 transition-colors">
                    Mengerti, Tutup
                </button>
            </div>
        </div>
    </div>
</div>

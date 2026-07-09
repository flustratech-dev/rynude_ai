<x-app-layout>
<div x-data="modelHubPage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <a href="{{ route('chat') }}?panel=api-keys" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-[#2D2825] dark:text-stone-400 dark:hover:text-stone-200 mb-3 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    Kembali
                </a>
                <h1 class="text-2xl font-bold text-[#2D2825] dark:text-stone-200">Model Hub (Local AI Engine)</h1>
                <p class="text-sm text-gray-500 dark:text-stone-400 mt-1">Unduh dan kelola model AI lokal (.gguf) dari Hugging Face secara aman dan otomatis.</p>
            </div>
            <div class="flex items-center gap-2 bg-[#F5F4F0] dark:bg-[#2C2C2A] px-4 py-2 rounded-xl border border-gray-200 dark:border-stone-700 shadow-sm">
                <svg class="w-5 h-5 text-stone-700 dark:text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                </svg>
                <span class="text-xs font-medium text-gray-600 dark:text-stone-300">Sisa Hardisk: <strong x-text="freeSpaceGb + ' GB'"></strong></span>
            </div>
        </div>

        {{-- Centered Modal Box Popup Alert --}}
        <div x-show="flashMessage" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="relative w-full max-w-sm p-6 bg-white dark:bg-[#1E1E20] border border-gray-200 dark:border-stone-700 rounded-2xl shadow-2xl transform transition-all text-center"
                 @click.away="flashMessage = null"
                 x-effect="if(flashMessage){clearTimeout(ft);ft=setTimeout(()=>flashMessage=null,3500)}">
                
                <div class="mx-auto mb-4 w-14 h-14 rounded-full flex items-center justify-center shadow-inner"
                     :class="flashType==='success'?'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400':'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400'">
                    <template x-if="flashType==='success'">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </template>
                    <template x-if="flashType!=='success'">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    </template>
                </div>

                <h3 class="text-lg font-bold text-[#2D2825] dark:text-stone-100 mb-1.5"
                    x-text="flashType==='success'?'Berhasil!':'Pemberitahuan'"></h3>

                <p class="text-sm text-gray-600 dark:text-stone-300 mb-6 leading-relaxed font-medium"
                   x-text="flashMessage"></p>

                <button @click="flashMessage = null"
                        type="button"
                        class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white shadow-md transition-all active:scale-95"
                        :class="flashType==='success'?'bg-green-600 hover:bg-green-700':'bg-red-600 hover:bg-red-700'">
                    OK, Mengerti
                </button>
            </div>
        </div>

        {{-- Hardware Recommendation Banner (Phase 2 Integration) --}}
        <div class="mb-8 p-5 rounded-2xl border transition-all"
             :class="hwStatus === 'high' ? 'bg-green-50/70 border-green-200 dark:bg-green-900/10 dark:border-green-800' : (hwStatus === 'medium' ? 'bg-amber-50/70 border-amber-200 dark:bg-amber-900/10 dark:border-amber-800' : 'bg-red-50/70 border-red-200 dark:bg-red-900/10 dark:border-red-800')">
            <div class="flex items-start gap-4">
                <div class="p-3 rounded-xl bg-white dark:bg-[#2C2C2A] shadow-sm shrink-0">
                    <svg class="w-6 h-6" :class="hwStatus === 'high' ? 'text-green-600 dark:text-green-400' : (hwStatus === 'medium' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400')" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-stone-100">Spesifikasi Hardware Anda: <span x-text="totalRamGb + ' GB RAM'"></span></h3>
                        <span x-show="hasGpu" class="px-2 py-0.5 text-[11px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-md" x-text="'GPU: ' + gpuName + ' (' + vramGb + ' GB VRAM)'"></span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-stone-300 mt-1" x-text="hwMessage"></p>
                    <div class="mt-2 flex items-center gap-2 text-xs font-semibold"
                         :class="hwStatus === 'high' ? 'text-green-700 dark:text-green-400' : (hwStatus === 'medium' ? 'text-amber-700 dark:text-amber-400' : 'text-red-700 dark:text-red-400')">
                        <span>Rekomendasi Batas Ukuran Model:</span>
                        <span class="px-2 py-0.5 rounded bg-white/80 dark:bg-black/20 border" x-text="'Maksimal ' + maxParamSize + ' Parameter'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Model Catalog Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="model in models" :key="model.id">
                <div class="bg-[#F5F4F0] dark:bg-[#2C2C2A] border border-stone-300/80 dark:border-stone-700 rounded-2xl p-6 flex flex-col justify-between shadow-[0_2px_12px_rgba(45,40,37,0.03)] dark:shadow-none hover:shadow-[0_4px_20px_rgba(45,40,37,0.06)] hover:border-stone-400 dark:hover:border-stone-500 transition-all relative overflow-hidden">
                    
                    {{-- Warning Ribbon if RAM is insufficient --}}
                    <template x-if="totalRamGb < model.required_ram_gb">
                        <div class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider shadow">
                            RAM Kurang (< <span x-text="model.required_ram_gb + 'GB'"></span>)
                        </div>
                    </template>

                    {{-- Recommended ribbon (only when RAM is sufficient, so it never overlaps the RAM warning) --}}
                    <template x-if="model.recommended && totalRamGb >= model.required_ram_gb">
                        <div class="absolute top-0 right-0 bg-stone-900/90 dark:bg-stone-300 text-white dark:text-stone-950 text-[10px] font-bold px-3.5 py-1 rounded-bl-xl uppercase tracking-wider shadow-md">
                            ⭐ Rekomendasi
                        </div>
                    </template>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white text-stone-800 border border-stone-200 dark:bg-[#3A3A38] dark:text-stone-300 dark:border-stone-700" x-text="model.parameter_size + ' PARAMS'"></span>
                            <span class="text-xs font-semibold text-gray-500 dark:text-stone-400" x-text="model.file_size_label"></span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-stone-100" x-text="model.name"></h3>
                        <p class="text-sm text-gray-600 dark:text-stone-400 mt-2 leading-relaxed" x-text="model.description"></p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-stone-700/60">
                        {{-- Status: Completed --}}
                        <template x-if="model.status === 'completed'">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-1.5 text-xs font-bold text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Terunduh Siap Pakai
                                </span>
                                <button @click="deleteModel(model.id)" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Hapus file model">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </template>

                        {{-- Status: Downloading --}}
                        <template x-if="model.status === 'downloading'">
                            <div>
                                <div class="flex items-center justify-between text-xs font-bold text-[#2D2825] dark:text-stone-200 mb-1.5">
                                    <span>Mengunduh... (<span x-text="model.progress + '%'"></span>)</span>
                                    <button @click="deleteModel(model.id)" class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline flex items-center gap-1" title="Batalkan dan Hapus Unduhan">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Batalkan & Hapus
                                    </button>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-stone-700 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-stone-900/90 dark:bg-stone-300 h-2.5 rounded-full transition-all duration-300 shadow-sm" :style="'width: ' + model.progress + '%'"></div>
                                </div>
                            </div>
                        </template>

                        {{-- Status: Not Downloaded or Error --}}
                        <template x-if="model.status !== 'completed' && model.status !== 'downloading'">
                            <div>
                                <template x-if="model.status === 'error'">
                                    <p class="text-xs text-red-600 dark:text-red-400 font-medium mb-2" x-text="model.error_message || 'Gagal mengunduh'"></p>
                                </template>
                                <button @click="downloadModel(model.id)"
                                        :disabled="totalRamGb < model.required_ram_gb"
                                        class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-sm border"
                                        :class="totalRamGb < model.required_ram_gb ? 'bg-stone-50 border-stone-200/80 text-stone-400 dark:bg-stone-800/40 dark:border-stone-700/60 dark:text-stone-500 cursor-not-allowed' : 'bg-stone-900 border-stone-950/10 text-white hover:bg-black dark:bg-stone-200 dark:border-stone-100/10 dark:text-stone-900 dark:hover:bg-stone-100 active:scale-95'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    <span x-text="totalRamGb < model.required_ram_gb ? 'Tidak Disarankan (RAM < ' + model.required_ram_gb + 'GB)' : 'Unduh Model (' + model.file_size_label + ')'"></span>
                                </button>
                            </div>
                        </template>
                    </div>

                </div>
            </template>
        </div>
    </div>
</div>

<script>
function modelHubPage() {
    return {
        models: [],
        freeSpaceGb: 0,
        totalRamGb: 8.0,
        hwStatus: 'medium',
        maxParamSize: '7B',
        hwMessage: 'Memuat informasi hardware...',
        hasGpu: false,
        gpuName: '',
        vramGb: 0,
        flashMessage: null,
        flashType: 'info',
        ft: null,
        pollingInterval: null,

        init() {
            this.fetchHardware();
            this.fetchModels();
            // Start polling progress every 2 seconds
            this.pollingInterval = setInterval(() => {
                const hasDownloading = this.models.some(m => m.status === 'downloading');
                if (hasDownloading) {
                    this.fetchProgress();
                }
            }, 2000);
        },

        showAlert(msg, type = 'info') {
            this.flashMessage = msg;
            this.flashType = type;
        },

        async fetchHardware() {
            try {
                const res = await fetch('/api/system/hardware');
                const data = await res.json();
                if (data.success) {
                    this.totalRamGb = data.total_ram_gb;
                    this.hasGpu = data.has_gpu;
                    this.gpuName = data.gpu_name || '';
                    this.vramGb = data.vram_gb || 0;
                    this.hwStatus = data.recommendation.status;
                    this.maxParamSize = data.recommendation.max_parameter_size;
                    this.hwMessage = data.recommendation.message;
                }
            } catch (e) {
                console.error('Failed to fetch hardware:', e);
                this.hwMessage = 'Gagal mendeteksi spesifikasi sistem. Menggunakan rekomendasi default.';
            }
        },

        async fetchModels() {
            try {
                const res = await fetch('/api/models');
                const data = await res.json();
                if (data.success) {
                    this.models = data.models;
                    this.freeSpaceGb = data.free_space_gb;
                }
            } catch (e) {
                console.error('Failed to fetch models:', e);
                this.showAlert('Gagal memuat katalog model.', 'error');
            }
        },

        async fetchProgress() {
            try {
                const res = await fetch('/api/models/progress');
                const data = await res.json();
                if (data.success && data.progress) {
                    let anyUpdated = false;
                    this.models = this.models.map(m => {
                        if (data.progress[m.id]) {
                            const p = data.progress[m.id];
                            return {
                                ...m,
                                status: p.status,
                                progress: p.progress || 0,
                                error_message: p.message || null
                            };
                        }
                        return m;
                    });
                    // Refresh catalog if any completed to update disk space
                    if (this.models.some(m => m.status === 'completed' && !m.is_downloaded)) {
                        this.fetchModels();
                    }
                }
            } catch (e) {
                console.error('Failed to fetch progress:', e);
            }
        },

        async downloadModel(modelId) {
            try {
                const res = await fetch('/api/models/download', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ model_id: modelId })
                });
                const data = await res.json();
                if (data.success) {
                    this.showAlert(data.message, 'success');
                    this.fetchModels();
                } else {
                    this.showAlert(data.message || 'Gagal memulai unduhan.', 'error');
                }
            } catch (e) {
                console.error('Download error:', e);
                this.showAlert('Terjadi kesalahan koneksi saat mengunduh.', 'error');
            }
        },

        async deleteModel(modelId) {
            if (!confirm('Apakah Anda yakin ingin menghapus atau membatalkan unduhan model ini dari sistem?')) return;
            try {
                const res = await fetch('/api/models/' + modelId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showAlert(data.message, 'success');
                    this.fetchModels();
                } else {
                    this.showAlert(data.message || 'Gagal menghapus model.', 'error');
                }
            } catch (e) {
                console.error('Delete error:', e);
                this.showAlert('Terjadi kesalahan saat menghapus model.', 'error');
            }
        }
    };
}
</script>
</x-app-layout>

<x-app-layout>
<div x-data="modelHubPage()" x-init="init()" class="min-h-screen bg-[#FAF9F5] dark:bg-[#141413] text-[#2D2825] dark:text-stone-200 transition-colors">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Top Navigation & Header --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-stone-200/80 dark:border-stone-800/80 pb-6">
            <div>
                <a href="{{ route('chat') }}?panel=api-keys" class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-500 dark:text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-100 mb-2.5 transition-colors group">
                    <svg class="w-3.5 h-3.5 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    Kembali ke Pengaturan AI
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#2D2825] dark:text-stone-100 font-serif">Model Hub & Local AI Engine</h1>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 max-w-2xl leading-relaxed">
                    Kelola dan unduh model AI lokal (.gguf) secara langsung di perangkat Anda. Privasi penuh, tanpa ketergantungan internet saat dijalankan.
                </p>
            </div>

            {{-- Hardware Status Indicator Strip --}}
            <div class="flex flex-wrap items-center gap-2 bg-[#F3F1EA] dark:bg-[#1E1E20] px-4 py-3 rounded-2xl border border-stone-200/80 dark:border-stone-800/80 shadow-[0_2px_10px_rgba(45,40,37,0.02)] self-start md:self-auto">
                <div class="flex items-center gap-2 pr-3 border-r border-stone-200 dark:border-stone-800">
                    <div class="w-2 h-2 rounded-full animate-pulse" :class="hwStatus === 'high' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : (hwStatus === 'medium' ? 'bg-amber-500' : 'bg-red-500')"></div>
                    <span class="text-xs font-semibold text-stone-700 dark:text-stone-300" x-text="totalRamGb + ' GB RAM'"></span>
                </div>
                <template x-if="hasGpu">
                    <div class="flex items-center gap-1.5 pr-3 border-r border-stone-200 dark:border-stone-800 text-xs font-semibold text-stone-700 dark:text-stone-300">
                        <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        <span x-text="gpuName + ' (' + vramGb + ' GB)'"></span>
                    </div>
                </template>
                <div class="flex items-center gap-1.5 text-xs font-medium text-stone-600 dark:text-stone-400">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    <span>Sisa Storage: <strong class="text-stone-900 dark:text-stone-100 font-bold" x-text="freeSpaceGb + ' GB'"></strong></span>
                </div>
            </div>
        </div>

        {{-- Flash Message Modal Alert --}}
        <div x-show="flashMessage" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="relative w-full max-w-sm p-6 bg-[#FAF9F5] dark:bg-[#1E1E20] border border-stone-200 dark:border-stone-700 rounded-2xl shadow-2xl transform transition-all text-center"
                 @click.away="flashMessage = null"
                 x-effect="if(flashMessage){clearTimeout(ft);ft=setTimeout(()=>flashMessage=null,3500)}">
                <div class="mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center"
                     :class="flashType==='success'?'bg-emerald-500/10 text-emerald-500':'bg-red-500/10 text-red-500'">
                    <template x-if="flashType==='success'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </template>
                    <template x-if="flashType!=='success'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    </template>
                </div>
                <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 mb-1" x-text="flashType==='success'?'Berhasil!':'Pemberitahuan'"></h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 mb-6 leading-relaxed font-medium" x-text="flashMessage"></p>
                <button @click="flashMessage = null"
                        type="button"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white transition-all active:scale-95"
                        :class="flashType==='success'?'bg-emerald-600 hover:bg-emerald-700':'bg-red-600 hover:bg-red-700'">
                    Mengerti
                </button>
            </div>
        </div>

        {{-- Filter & Search Toolbar (Sleek Claude Style) --}}
        <div class="mb-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            
            {{-- Category Filter Tabs --}}
            <div class="flex items-center gap-1.5 p-1 bg-[#DFDBD0] dark:bg-[#1E1E20] border border-[#C5C1B4] dark:border-stone-800 rounded-xl overflow-x-auto no-scrollbar">
                <button @click="activeTab = 'all'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'all' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-stone-900 dark:text-stone-100 shadow-sm' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200'">
                    <span>Semua Katalog</span>
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-[#DCD8CD] dark:bg-stone-800 text-stone-700 dark:text-stone-300 font-bold" x-text="models.length"></span>
                </button>
                
                <button @click="activeTab = 'recommended'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'recommended' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-amber-600 dark:text-amber-400 shadow-sm' : 'text-stone-600 dark:text-stone-400 hover:text-amber-600 dark:hover:text-amber-400'">
                    <span>🔥 Pilihan Utama</span>
                </button>

                <button @click="activeTab = 'chat'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'chat' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-stone-900 dark:text-stone-100 shadow-sm' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200'">
                    <span>💬 Model Chat</span>
                </button>

                <button @click="activeTab = 'embed'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'embed' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-stone-900 dark:text-stone-100 shadow-sm' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200'">
                    <span>🧩 Modul RAG</span>
                </button>

                <button @click="activeTab = 'downloaded'" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'downloaded' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-stone-600 dark:text-stone-400 hover:text-emerald-600 dark:hover:text-emerald-400'">
                    <span>✔ Terunduh</span>
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold" x-text="models.filter(m => m.status === 'completed').length"></span>
                </button>
            </div>

            {{-- Search & View Mode Toggle --}}
            <div class="flex items-center gap-2">
                <div class="relative flex-1 sm:w-64">
                    <svg class="absolute left-3.5 top-2.5 w-4 h-4 text-[#635E55] dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Cari model, parameter, atau spesifikasi..." 
                           class="w-full pl-9 pr-4 py-2 bg-[#CEC9BC] dark:bg-[#1E1E20] border border-[#B8B3A4] dark:border-stone-700 rounded-xl text-xs font-semibold text-[#1F1C1A] dark:text-stone-100 placeholder-[#635E55] dark:placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-[#8E887B]/40 focus:border-[#8E887B] focus:bg-[#C2BDAD] transition-all shadow-inner">
                </div>

                <div class="flex items-center p-1 bg-[#DFDBD0] dark:bg-[#1E1E20] border border-[#C5C1B4] dark:border-stone-800 rounded-xl shrink-0">
                    <button @click="viewMode = 'list'" 
                            title="Tampilan Baris (Compact List)"
                            class="p-1.5 rounded-lg transition-all"
                            :class="viewMode === 'list' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-stone-900 dark:text-stone-100 shadow-sm' : 'text-stone-500 hover:text-stone-900 dark:hover:text-stone-200'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <button @click="viewMode = 'grid'" 
                            title="Tampilan Kartu (Compact Grid)"
                            class="p-1.5 rounded-lg transition-all"
                            :class="viewMode === 'grid' ? 'bg-[#CCC7B8] dark:bg-[#2C2C2A] text-stone-900 dark:text-stone-100 shadow-sm' : 'text-stone-500 hover:text-stone-900 dark:hover:text-stone-200'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 13.5V3.75m0 9.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 010 3m0-3a1.5 1.5 0 000 3m0 9.75V10.5"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Main Model Catalog Content --}}
        <div>
            {{-- Empty State if Filter yields nothing --}}
            <template x-if="filteredModels.length === 0">
                <div class="py-16 text-center border border-dashed border-stone-300 dark:border-stone-800 rounded-2xl bg-[#F3F1EA]/60 dark:bg-[#1E1E20]/50">
                    <svg class="w-10 h-10 mx-auto text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <h3 class="text-sm font-bold text-stone-700 dark:text-stone-300">Model tidak ditemukan</h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">Coba gunakan kata kunci lain atau pilih tab kategori yang berbeda.</p>
                </div>
            </template>

            {{-- VIEW MODE 1: SLEEK LINEAR LIST (Default Authentic Claude Style) --}}
            <template x-if="viewMode === 'list' && filteredModels.length > 0">
                <div class="bg-[#F3F1EA] dark:bg-[#191919] border border-stone-200/80 dark:border-stone-800/80 rounded-2xl overflow-hidden divide-y divide-stone-200/60 dark:divide-stone-800/60 shadow-[0_2px_15px_rgba(45,40,37,0.02)]">
                    <template x-for="model in filteredModels" :key="model.id">
                        <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:bg-[#EBE8E0]/80 dark:hover:bg-[#21201C]/80 transition-all relative group"
                             :class="model.recommended || model.id === 'rynude-lyric-plus-1' ? 'bg-amber-500/[0.015] dark:bg-amber-500/[0.02]' : ''">
                            
                            {{-- Left Column: Model Info & Badges --}}
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <div class="mt-1 w-2.5 h-2.5 rounded-full shrink-0"
                                     :class="model.status === 'completed' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]' : (model.status === 'downloading' ? 'bg-blue-500 animate-pulse' : 'bg-stone-300 dark:bg-stone-700')"></div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 font-serif" x-text="model.name"></h3>
                                        
                                        {{-- Size Badge --}}
                                        <span class="px-2 py-0.5 rounded-md bg-[#DCD8CD] dark:bg-stone-800/80 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-300 font-mono text-[11px] font-semibold" x-text="model.parameter_size + ' PARAMS'"></span>

                                        {{-- Recommended Badge --}}
                                        <template x-if="model.recommended || model.id === 'rynude-lyric-plus-1'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-[11px] font-bold">
                                                <span>⭐ Rekomendasi Utama</span>
                                            </span>
                                        </template>

                                        {{-- Status Pill --}}
                                        <template x-if="model.status === 'completed'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[11px] font-semibold">
                                                <span>✔ Terunduh Siap Pakai</span>
                                            </span>
                                        </template>
                                    </div>

                                    <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-400 leading-relaxed max-w-3xl" x-text="model.description"></p>

                                    {{-- Micro compatibility indicators --}}
                                    <div class="flex items-center gap-4 mt-2 text-[11px] font-medium">
                                        <span class="text-stone-400 dark:text-stone-500">Ukuran File: <strong class="text-stone-700 dark:text-stone-300 font-mono" x-text="model.file_size_label"></strong></span>
                                        
                                        <template x-if="totalRamGb < model.required_ram_gb">
                                            <span class="text-red-600 dark:text-red-400 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                                RAM Kurang (< <span x-text="model.required_ram_gb + 'GB'"></span>)
                                            </span>
                                        </template>

                                        <template x-if="totalRamGb >= model.required_ram_gb">
                                            <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                <span>● Optimal untuk Hardware Anda</span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Column: Actions & Progress --}}
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 shrink-0 self-end md:self-center w-full md:w-auto mt-2 md:mt-0 pt-3 md:pt-0 border-t border-stone-100 dark:border-stone-800/40 md:border-t-0">
                                
                                {{-- Completed Actions --}}
                                <template x-if="model.status === 'completed'">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('chat') }}" 
                                           class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-1.5">
                                            <span>Aktifkan di Obrolan</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                        <button @click="deleteModel(model.id)" 
                                                class="p-2 rounded-xl border border-stone-200 dark:border-stone-700 bg-[#DCD8CD] dark:bg-stone-800 text-stone-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                                title="Hapus file model dari penyimpanan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </template>

                                {{-- Downloading State --}}
                                <template x-if="model.status === 'downloading'">
                                    <div class="w-full md:w-56">
                                        <div class="flex items-center justify-between text-xs font-bold text-stone-800 dark:text-stone-200 mb-1">
                                            <span>Mengunduh... (<span x-text="model.progress + '%'"></span>)</span>
                                            <button @click="deleteModel(model.id)" class="text-[11px] font-semibold text-red-600 dark:text-red-400 hover:underline">
                                                Batalkan
                                            </button>
                                        </div>
                                        <div class="w-full bg-stone-200 dark:bg-stone-800 rounded-full h-2 overflow-hidden border border-stone-300 dark:border-stone-700">
                                            <div class="bg-gradient-to-r from-blue-500 to-emerald-400 h-2 rounded-full transition-all duration-300" :style="'width: ' + model.progress + '%'"></div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Not Downloaded / Error State --}}
                                <template x-if="model.status !== 'completed' && model.status !== 'downloading'">
                                    <div class="flex items-center gap-2">
                                        <template x-if="model.status === 'error'">
                                            <span class="text-xs text-red-600 dark:text-red-400 font-medium" x-text="model.error_message || 'Gagal'"></span>
                                        </template>
                                        
                                        <button @click="downloadModel(model.id)"
                                                :disabled="totalRamGb < model.required_ram_gb"
                                                class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center gap-2 border shadow-sm"
                                                :class="totalRamGb < model.required_ram_gb ? 'bg-[#DCD8CD] border-stone-200 text-stone-500 dark:bg-stone-800/50 dark:border-stone-800 dark:text-stone-600 cursor-not-allowed' : 'bg-[#2D2825] dark:bg-stone-100 text-white dark:text-[#2D2825] hover:bg-black dark:hover:bg-white active:scale-95 border-stone-800 dark:border-stone-200'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span x-text="totalRamGb < model.required_ram_gb ? 'RAM Kurang' : 'Unduh (' + model.file_size_label + ')'"></span>
                                        </button>
                                    </div>
                                </template>

                            </div>

                        </div>
                    </template>
                </div>
            </template>

            {{-- VIEW MODE 2: COMPACT REFINED GRID (If user toggles to Grid view) --}}
            <template x-if="viewMode === 'grid' && filteredModels.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <template x-for="model in filteredModels" :key="model.id">
                        <div class="bg-[#F3F1EA] dark:bg-[#191919] border border-stone-200/80 dark:border-stone-800/80 rounded-2xl p-5 flex flex-col justify-between transition-all hover:border-stone-400 dark:hover:border-stone-600 shadow-sm relative overflow-hidden"
                             :class="model.recommended || model.id === 'rynude-lyric-plus-1' ? 'ring-1 ring-amber-500/30 dark:ring-amber-400/30' : ''">
                            
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span class="px-2 py-0.5 rounded bg-[#DCD8CD] dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-800 dark:text-stone-300 text-[11px] font-mono font-bold" x-text="model.parameter_size + ' PARAMS'"></span>
                                    
                                    <template x-if="model.recommended || model.id === 'rynude-lyric-plus-1'">
                                        <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-bold border border-amber-500/20">⭐ Rekomendasi</span>
                                    </template>
                                </div>

                                <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 font-serif" x-text="model.name"></h3>
                                <p class="text-xs text-stone-600 dark:text-stone-400 mt-1.5 leading-relaxed line-clamp-3" x-text="model.description"></p>
                            </div>

                            <div class="mt-5 pt-4 border-t border-stone-100 dark:border-stone-800/60">
                                <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 mb-3">
                                    <span>File: <strong class="text-stone-800 dark:text-stone-200 font-mono" x-text="model.file_size_label"></strong></span>
                                    <template x-if="totalRamGb < model.required_ram_gb">
                                        <span class="text-red-500 text-[11px] font-semibold">RAM Kurang (< <span x-text="model.required_ram_gb + 'GB'"></span>)</span>
                                    </template>
                                </div>

                                {{-- Status Actions --}}
                                <template x-if="model.status === 'completed'">
                                    <div class="flex items-center justify-between gap-2">
                                        <a href="{{ route('chat') }}" class="flex-1 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold text-center transition-all">
                                            ✔ Aktif di Obrolan
                                        </a>
                                        <button @click="deleteModel(model.id)" class="p-2 rounded-xl bg-[#DCD8CD] dark:bg-stone-800 text-stone-400 hover:text-red-500 transition-colors" title="Hapus file">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </template>

                                <template x-if="model.status === 'downloading'">
                                    <div>
                                        <div class="flex justify-between text-[11px] font-bold text-stone-800 dark:text-stone-200 mb-1">
                                            <span>Mengunduh... (<span x-text="model.progress + '%'"></span>)</span>
                                            <button @click="deleteModel(model.id)" class="text-red-500 hover:underline">Batal</button>
                                        </div>
                                        <div class="w-full bg-stone-200 dark:bg-stone-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-500 h-1.5 rounded-full" :style="'width: ' + model.progress + '%'"></div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="model.status !== 'completed' && model.status !== 'downloading'">
                                    <button @click="downloadModel(model.id)"
                                            :disabled="totalRamGb < model.required_ram_gb"
                                            class="w-full py-2 px-3 rounded-xl text-xs font-semibold transition-all border flex items-center justify-center gap-1.5"
                                            :class="totalRamGb < model.required_ram_gb ? 'bg-[#DCD8CD] dark:bg-stone-800/50 text-stone-500 border-stone-200 dark:border-stone-800 cursor-not-allowed' : 'bg-[#2D2825] dark:bg-stone-100 text-white dark:text-[#2D2825] hover:bg-black dark:hover:bg-white border-stone-800 dark:border-stone-200'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                        <span x-text="totalRamGb < model.required_ram_gb ? 'Tidak Disarankan' : 'Unduh Model'"></span>
                                    </button>
                                </template>
                            </div>

                        </div>
                    </template>
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

        // New Navigation & Filtering State
        activeTab: 'all',
        searchQuery: '',
        viewMode: 'list',

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

        get filteredModels() {
            return this.models.filter(m => {
                // Tab filter
                if (this.activeTab === 'recommended' && (!m.recommended && m.id !== 'rynude-lyric-plus-1')) return false;
                if (this.activeTab === 'chat' && m.id === 'rynude-embed-0.6b') return false;
                if (this.activeTab === 'embed' && m.id !== 'rynude-embed-0.6b') return false;
                if (this.activeTab === 'downloaded' && m.status !== 'completed') return false;

                // Search filter
                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase();
                    const nameMatch = (m.name || '').toLowerCase().includes(q);
                    const descMatch = (m.description || '').toLowerCase().includes(q);
                    const paramMatch = (m.parameter_size || '').toLowerCase().includes(q);
                    return nameMatch || descMatch || paramMatch;
                }
                return true;
            });
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
                this.hwMessage = 'Gagal mendeteksi spesifikasi sistem.';
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
            if (!confirm('Apakah Anda yakin ingin menghapus atau membatalkan unduhan model ini dari penyimpanan lokal?')) return;
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

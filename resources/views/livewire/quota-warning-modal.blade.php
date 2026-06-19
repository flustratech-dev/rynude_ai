<div>
    @if($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" x-data @keydown.escape.window="$wire.closeModal()">
            <div class="bg-white rounded-[1.5rem] shadow-xl w-full max-w-md overflow-hidden relative transform transition-all" @click.away="$wire.closeModal()">
                <div class="p-6 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-2">Kuota Habis</h3>
                    <div class="mt-2 text-sm text-gray-500">
                        <p>Harap Isi API Key Anda atau Berlangganan untuk melanjutkan menggunakan layanan kami.</p>
                    </div>
                    <div class="mt-6 flex gap-3 justify-center">
                        <button type="button" wire:click="closeModal" class="inline-flex justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors">Tutup</button>
                        <button type="button" wire:click="$dispatch('open-settings-modal', {tab: 'api-keys'}); closeModal" class="inline-flex justify-center rounded-xl bg-[#D97757] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#c66547] transition-colors">Isi API Key</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<div
    x-data="{
        sidebarOpen: new URLSearchParams(window.location.search).get('sidebar') !== 'false',
        isMobile: false,
        activePanel: new URLSearchParams(window.location.search).get('panel') || null,
        openArtifactId: new URLSearchParams(window.location.search).get('artifact')
            ? parseInt(new URLSearchParams(window.location.search).get('artifact'))
            : null,
        shortcutsOpen: false,
        artifactWidth: 50,
        isResizing: false,
        _onMove: null,
        _onUp: null,
        conversationId: new URLSearchParams(window.location.search).get('conversation')
            ? parseInt(new URLSearchParams(window.location.search).get('conversation'))
            : null,
        get artifactPanelOpen() {
            return this.openArtifactId !== null && this.openArtifactId !== '' && this.openArtifactId !== undefined;
        },
        init() {
            this.checkMobile();
            window.addEventListener('resize', () => this.checkMobile());
            window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: this.sidebarOpen } }));

            window.addEventListener('selectConversation', (e) => {
                if (e.detail && e.detail.conversationId) this.conversationId = e.detail.conversationId;
            });
            window.addEventListener('conversationCreated', (e) => {
                if (e.detail && e.detail.id) this.conversationId = e.detail.id;
            });
            window.addEventListener('newChat', () => {
                this.conversationId = null;
            });

            this._onMove = (e) => {
                if (!this.isResizing) return;
                let newWidth = ((window.innerWidth - e.clientX) / window.innerWidth) * 100;
                newWidth = Math.max(20, Math.min(newWidth, 80));
                this.artifactWidth = newWidth;
            };
            this._onUp = () => {
                if (this.isResizing) {
                    this.isResizing = false;
                    document.body.style.cursor = 'default';
                }
            };
            document.addEventListener('mousemove', this._onMove);
            document.addEventListener('mouseup', this._onUp);
        },
        destroy() {
            if (this._onMove) document.removeEventListener('mousemove', this._onMove);
            if (this._onUp) document.removeEventListener('mouseup', this._onUp);
        },
        handleShortcut(e) {
            const mod = e.metaKey || e.ctrlKey;

            if (e.key === 'Escape') {
                if (this.shortcutsOpen) { this.shortcutsOpen = false; return; }
                if (this.artifactPanelOpen || this.activePanel === 'artifacts') {
                    if (this.artifactPanelOpen) { window.dispatchEvent(new CustomEvent('close-artifact-panel')); }
                    if (this.activePanel === 'artifacts') this.activePanel = null;
                    return;
                }
                if (this.activePanel) { this.activePanel = null; return; }
                return;
            }

            if (!mod) return;

            if (e.key.toLowerCase() === 'k' && !e.shiftKey) {
                e.preventDefault();
                this.activePanel = null;
                window.dispatchEvent(new CustomEvent('close-artifact-panel'));
                window.location.href = '/chat';
                return;
            }
            if (e.key.toLowerCase() === 's' && e.shiftKey) {
                e.preventDefault();
                this.toggle();
                return;
            }
            if (e.key === ',' && e.shiftKey) {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('open-settings-ui', { detail: 'general' }));
                return;
            }
            if (e.key === '/') {
                e.preventDefault();
                this.shortcutsOpen = !this.shortcutsOpen;
                return;
            }
        },
        toggle() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: this.sidebarOpen } }));
        },
        checkMobile() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth < 768;
            if (wasMobile && !this.isMobile) {
                this.sidebarOpen = true;
                window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: true } }));
            }
            if (!wasMobile && this.isMobile) {
                this.sidebarOpen = false;
                window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: false } }));
            }
        }
    }"
    class="h-[100dvh] flex bg-[#F9F8F6] dark:bg-claude-bg-dark overflow-hidden"
    x-init="init()"
    @keydown.window="handleShortcut($event)"
    @show-shortcuts.window="shortcutsOpen = true"
    @toggle-sidebar.window="toggle()"
    @close-customize.window="activePanel = null; sidebarOpen = true; window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: true } }));"
    @open-panel.window="activePanel = $event.detail"
    @close-panel.window="activePanel = null"
    @open-artifact.window="
        const d = $event.detail;
        openArtifactId = typeof d === 'number' ? d : (d.id || null);
        if (activePanel === 'artifacts') activePanel = null;
    "
    @close-artifact-panel.window="openArtifactId = null"
    @artifact-ready.window="
        if ($event.detail && activePanel === null) { openArtifactId = parseInt($event.detail); }
    "
    @show-artifact-panel.window="
        if (openArtifactId === null) openArtifactId = 0;
        if (activePanel === 'artifacts') activePanel = null;
    "
>
    {{-- ========== MOBILE SIDEBAR OVERLAY ========== --}}
    <div x-show="isMobile && sidebarOpen" x-cloak class="relative z-40">
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="sidebar-backdrop backdrop-blur-sm"
            @click="sidebarOpen = false; window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: false } }));"
        ></div>
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-transform ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform ease-in duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-40 w-[300px] shadow-2xl bg-[#F9F8F6] dark:bg-claude-bg-dark"
        >
            @include('livewire.sidebar')
        </div>
    </div>

    <div
        x-show="!isMobile"
        x-cloak
        :class="sidebarOpen ? 'w-[290px]' : 'w-[60px]'"
        class="transition-all duration-300 overflow-hidden flex-shrink-0 border-r border-claude-border-light dark:border-claude-border-dark hidden md:block bg-[#F9F8F6] dark:bg-claude-bg-dark"
    >
        @include('livewire.sidebar')
    </div>

    {{-- ========== MAIN CONTENT ========== --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Mobile Sidebar Toggle --}}
        <div
            x-show="isMobile && !sidebarOpen"
            x-cloak
            class="absolute top-3 left-3 z-10"
        >
            <button
                @click="toggle()"
                class="p-1.5 rounded-md hover:bg-claude-200/60 dark:hover:bg-stone-700/50 transition-colors"
            >
                <svg class="w-[18px] h-[18px] text-stone-500 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                </svg>
            </button>
        </div>
        <div class="flex-1 flex overflow-hidden relative">
            <div class="flex-1 flex flex-col min-w-0 relative">
                <!-- SPA Pre-mounted panels managed by AlpineJS -->
                <div x-show="activePanel === 'chats'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    @include('livewire.chats-panel')
                </div>
                <div x-show="activePanel === 'projects'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    @include('livewire.projects-panel')
                </div>
                <div x-show="activePanel === 'code'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    @include('livewire.code-panel')
                </div>
                <div x-show="activePanel === 'cowork'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    @include('livewire.cowork-panel')
                </div>
                <div x-show="activePanel === 'design'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    @include('livewire.design-panel')
                </div>

                <div x-show="activePanel === 'customize'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    @include('livewire.customize-panel')
                </div>

                <div :class="activePanel ? 'invisible pointer-events-none' : 'flex flex-col'" class="absolute inset-0 z-0 h-full">
                    @include('livewire.chat-interface')
                </div>
            </div>

            <div
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                :class="((artifactPanelOpen && !activePanel) || activePanel === 'artifacts') ? (activePanel === 'artifacts' ? 'absolute inset-0 z-20 flex bg-white dark:bg-[#3A3A38] w-full' : 'flex border-l border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-[#3A3A38] flex-shrink-0 shadow-[-10px_0_30px_rgba(0,0,0,0.02)] z-20 relative') : 'hidden'"
                :style="activePanel !== 'artifacts' ? `width: ${artifactWidth}vw; min-width: 400px; max-width: 80vw;` : ''"
            >
                <!-- Drag Handle -->
                <div
                    x-show="activePanel !== 'artifacts'"
                    @mousedown="isResizing = true; document.body.style.cursor = 'col-resize';"
                    class="absolute left-0 top-0 bottom-0 w-2 cursor-col-resize hover:bg-[#D97757] transition-colors z-30"
                    style="transform: translateX(-50%);"
                ></div>

                <!-- Overlay to capture mouse events over iframe while dragging -->
                <div x-show="isResizing" class="absolute inset-0 z-40 cursor-col-resize"></div>

                @include('livewire.artifact-panel')
            </div>

            <div
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-8"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-8"
                :class="(isMobile && ((artifactPanelOpen && !activePanel) || activePanel === 'artifacts')) ? 'fixed inset-0 z-30 flex flex-col bg-white dark:bg-[#3A3A38] md:hidden' : 'hidden'"
            >
                @include('livewire.artifact-panel')
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @include('livewire.settings-modal')
    @include('livewire.help-modal')

    {{-- ========== KEYBOARD SHORTCUTS MODAL ========== --}}
    <div x-show="shortcutsOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm px-4" @click.self="shortcutsOpen = false">
        <div
            x-show="shortcutsOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="bg-white dark:bg-stone-850 border border-stone-200 dark:border-stone-700 rounded-2xl shadow-2xl w-full max-w-md p-6"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-serif text-[18px] font-medium text-[#2D2825] dark:text-stone-100">Keyboard shortcuts</h3>
                <button @click="shortcutsOpen = false" class="p-1.5 text-stone-400 hover:text-[#2D2825] dark:hover:text-stone-200 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            @php
                $isMac = str_contains(strtolower(request()->userAgent() ?? ''), 'mac');
                $mod = $isMac ? '⌘' : 'Ctrl';
                $shortcuts = [
                    ['New chat', [$mod, 'K']],
                    ['Toggle sidebar', [$mod, 'Shift', 'S']],
                    ['Open settings', [$mod, 'Shift', ',']],
                    ['Show this panel', [$mod, '/']],
                    ['Close panel / modal', ['Esc']],
                ];
            @endphp
            <div class="space-y-2.5">
                @foreach($shortcuts as [$label, $keys])
                    <div class="flex items-center justify-between">
                        <span class="text-[13.5px] text-stone-600 dark:text-stone-300">{{ $label }}</span>
                        <div class="flex items-center gap-1">
                            @foreach($keys as $key)
                                <kbd class="px-2 py-1 min-w-[26px] text-center bg-stone-100 dark:bg-[#3A3A38] border border-stone-200 dark:border-stone-700 rounded-md text-[11.5px] font-medium text-stone-600 dark:text-stone-300">{{ $key }}</kbd>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

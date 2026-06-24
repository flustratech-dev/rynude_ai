<div
    x-data="{
        sidebarOpen: {{ Js::from($sidebarOpen) }},
        isMobile: false,
        activePanel: @entangle('activePanel'),
        artifactPanelOpen: @entangle('artifactPanelOpen'),
        shortcutsOpen: false,
        init() {
            this.checkMobile();
            window.addEventListener('resize', () => this.checkMobile());
            window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: this.sidebarOpen } }));
        },
        handleShortcut(e) {
            const mod = e.metaKey || e.ctrlKey;

            // Esc closes any open overlay/panel
            if (e.key === 'Escape') {
                if (this.shortcutsOpen) { this.shortcutsOpen = false; return; }
                if (this.artifactPanelOpen || this.activePanel === 'artifacts') { this.artifactPanelOpen = false; if (this.activePanel === 'artifacts') this.activePanel = null; return; }
                if (this.activePanel) { this.activePanel = null; return; }
                return;
            }

            if (!mod) return;

            // Cmd/Ctrl + K → New chat
            if (e.key.toLowerCase() === 'k' && !e.shiftKey) {
                e.preventDefault();
                this.activePanel = null;
                this.artifactPanelOpen = false;
                Livewire.dispatch('newChat');
                return;
            }
            // Cmd/Ctrl + Shift + S → Toggle sidebar
            if (e.key.toLowerCase() === 's' && e.shiftKey) {
                e.preventDefault();
                this.toggle();
                return;
            }
            // Cmd/Ctrl + Shift + , → Open settings
            if (e.key === ',' && e.shiftKey) {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('open-settings-ui', { detail: 'general' }));
                return;
            }
            // Cmd/Ctrl + / → Show shortcuts reference
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
    @close-artifact-panel.window="artifactPanelOpen = false; if (activePanel === 'artifacts') activePanel = null;"
    @close-customize.window="activePanel = null; sidebarOpen = true; window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: true } }));"
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
            <livewire:sidebar :activePanel="$activePanel" :artifactPanelOpen="$artifactPanelOpen" :sidebarOpen="$sidebarOpen" key="mobile-sidebar" />
        </div>
    </div>

    <div
        x-show="!isMobile"
        x-cloak
        :class="sidebarOpen ? 'w-[290px]' : 'w-[60px]'"
        class="transition-all duration-300 overflow-hidden flex-shrink-0 border-r border-claude-border-light dark:border-claude-border-dark hidden md:block bg-[#F9F8F6] dark:bg-claude-bg-dark"
    >
        <livewire:sidebar :activePanel="$activePanel" :artifactPanelOpen="$artifactPanelOpen" :sidebarOpen="$sidebarOpen" key="desktop-sidebar" />
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
                    <livewire:chats-panel key="panel-chats" />
                </div>
                <div x-show="activePanel === 'projects'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    <livewire:projects-panel key="panel-projects" />
                </div>
                <div x-show="activePanel === 'code'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    <livewire:code-panel key="panel-code" />
                </div>
                <div x-show="activePanel === 'cowork'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    <livewire:cowork-panel key="panel-cowork" />
                </div>
                <div x-show="activePanel === 'design'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    <livewire:design-panel key="panel-design" />
                </div>
                
                <div x-show="activePanel === 'customize'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-claude-bg-dark h-full overflow-hidden">
                    <livewire:customize-panel key="panel-customize" />
                </div>
                
                <div :class="activePanel ? 'invisible pointer-events-none' : 'flex flex-col'" class="absolute inset-0 z-0 h-full">
                    <livewire:chat-interface key="panel-chat-interface" />
                </div>
            </div>

            <div 
                x-show="artifactPanelOpen || activePanel === 'artifacts'"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                :class="activePanel === 'artifacts' ? 'absolute inset-0 z-20 flex bg-white dark:bg-stone-800 w-full' : 'hidden md:flex w-[50%] min-w-[400px] border-l border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 flex-shrink-0 shadow-[-10px_0_30px_rgba(0,0,0,0.02)] z-20 relative'"
            >
                <livewire:artifact-panel key="desktop-artifact-panel" />
            </div>

            <div
                x-show="isMobile && (artifactPanelOpen || activePanel === 'artifacts')"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-8"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-8"
                class="fixed inset-0 z-30 flex flex-col bg-white dark:bg-stone-800 md:hidden"
            >
                <livewire:artifact-panel key="mobile-artifact-panel" />
            </div>
        </div>
    </div>

    {{-- Modals --}}
    <livewire:settings-modal />
    <livewire:help-modal />

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
                                <kbd class="px-2 py-1 min-w-[26px] text-center bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-md text-[11.5px] font-medium text-stone-600 dark:text-stone-300">{{ $key }}</kbd>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div
    x-data="{
        sidebarOpen: {{ Js::from($sidebarOpen) }},
        isMobile: false,
        activePanel: @entangle('activePanel'),
        artifactPanelOpen: @entangle('artifactPanelOpen'),
        init() {
            this.checkMobile();
            window.addEventListener('resize', () => this.checkMobile());
            window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: this.sidebarOpen } }));
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
    class="h-[100dvh] flex bg-[#F9F8F6] dark:bg-stone-900 overflow-hidden"
    x-init="init()"
    @toggle-sidebar.window="toggle()"
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
            class="fixed inset-y-0 left-0 z-40 w-[300px] shadow-2xl bg-[#F9F8F6] dark:bg-stone-900"
        >
            <livewire:sidebar :activePanel="$activePanel" :artifactPanelOpen="$artifactPanelOpen" key="mobile-sidebar" />
        </div>
    </div>

    {{-- ========== DESKTOP SIDEBAR ========== --}}
    <div
        x-show="!isMobile"
        x-cloak
        :class="sidebarOpen ? 'w-[260px]' : 'w-[60px]'"
        class="transition-all duration-300 overflow-hidden flex-shrink-0 border-r border-[#E5E5E5] dark:border-stone-700 hidden md:block"
    >
        <livewire:sidebar :activePanel="$activePanel" :artifactPanelOpen="$artifactPanelOpen" key="desktop-sidebar" />
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
        <div class="flex-1 flex overflow-hidden">
            <div class="flex-1 flex flex-col min-w-0 relative">
                <!-- SPA Pre-mounted panels managed by AlpineJS -->
                <div x-show="activePanel === 'chats'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-stone-900 h-full overflow-hidden">
                    <livewire:chats-panel key="panel-chats" />
                </div>
                <div x-show="activePanel === 'projects'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-stone-900 h-full overflow-hidden">
                    <livewire:projects-panel key="panel-projects" />
                </div>
                <div x-show="activePanel === 'code'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-stone-900 h-full overflow-hidden">
                    <livewire:code-panel key="panel-code" />
                </div>
                <div x-show="activePanel === 'cowork'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-stone-900 h-full overflow-hidden">
                    <livewire:cowork-panel key="panel-cowork" />
                </div>
                <div x-show="activePanel === 'design'" x-cloak class="absolute inset-0 z-10 bg-[#F9F8F6] dark:bg-stone-900 h-full overflow-hidden">
                    <livewire:design-panel key="panel-design" />
                </div>
                
                <div :class="activePanel ? 'invisible pointer-events-none' : 'flex flex-col'" class="absolute inset-0 z-0 h-full">
                    <livewire:chat-interface key="panel-chat-interface" />
                </div>
            </div>

            <div 
                x-show="artifactPanelOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                class="hidden md:flex w-[50%] min-w-[400px] border-l border-[#E5E5E5] dark:border-stone-700 bg-white dark:bg-stone-800 flex-shrink-0 shadow-[-10px_0_30px_rgba(0,0,0,0.02)] z-20"
            >
                <livewire:artifact-panel key="desktop-artifact-panel" />
            </div>

            <div
                x-show="isMobile && artifactPanelOpen"
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
</div>

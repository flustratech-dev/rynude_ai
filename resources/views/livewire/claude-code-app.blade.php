<div
    class="flex w-full h-screen overflow-hidden font-mono bg-[#1A1A1A] text-[#E5E5E5]"
    x-data="claudeCodeState()"
    x-init="init()">

    <style>
        @keyframes rynude-bob {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25%      { transform: translateY(-3px) rotate(-5deg); }
            50%      { transform: translateY(0) rotate(0deg); }
            75%      { transform: translateY(-3px) rotate(5deg); }
        }
        .rynude-mascot { animation: rynude-bob 2.4s ease-in-out infinite; transform-origin: bottom center; }
    </style>

    {{-- LEFT SIDEBAR --}}
    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="-translate-x-full opacity-0"
         class="w-[240px] flex-shrink-0 flex flex-col bg-[#141414] border-r border-[#2A2A2A] h-full overflow-hidden">
        <div class="flex items-center justify-between px-3 py-3 border-b border-[#2A2A2A] flex-shrink-0">
            <div class="flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 100 100" class="text-[#CC785C] fill-current flex-shrink-0"><path d="m19.6 66.5 19.7-11 .3-1-.3-.5h-1l-3.3-.2-11.2-.3L14 53l-9.5-.5-2.4-.5L0 49l.2-1.5 2-1.3 2.9.2 6.3.5 9.5.6 6.9.4L38 49.1h1.6l.2-.7-.5-.4-.4-.4L29 41l-10.6-7-5.6-4.1-3-2-1.5-2-.6-4.2 2.7-3 3.7.3.9.2 3.7 2.9 8 6.1L37 36l1.5 1.2.6-.4.1-.3-.7-1.1L33 25l-6-10.4-2.7-4.3-.7-2.6c-.3-1-.4-2-.4-3l3-4.2L28 0l4.2.6L33.8 2l2.6 6 4.1 9.3L47 29.9l2 3.8 1 3.4.3 1h.7v-.5l.5-7.2 1-8.7 1-11.2.3-3.2 1.6-3.8 3-2L61 2.6l2 2.9-.3 1.8-1.1 7.7L59 27.1l-1.5 8.2h.9l1-1.1 4.1-5.4 6.9-8.6 3-3.5L77 13l2.3-1.8h4.3l3.1 4.7-1.4 4.9-4.4 5.6-3.7 4.7-5.3 7.1-3.2 5.7.3.4h.7l12-2.6 6.4-1.1 7.6-1.3 3.5 1.6.4 1.6-1.4 3.4-8.2 2-9.6 2-14.3 3.3-.2.1.2.3 6.4.6 2.8.2h6.8l12.6 1 3.3 2 1.9 2.7-.3 2-5.1 2.6-6.8-1.6-16-3.8-5.4-1.3h-.8v.4l4.6 4.5 8.3 7.5L89 80.1l.5 2.4-1.3 2-1.4-.2-9.2-7-3.6-3-8-6.8h-.5v.7l1.8 2.7 9.8 14.7.5 4.5-.7 1.4-2.6 1-2.7-.6-5.8-8-6-9-4.7-8.2-.5.4-2.9 30.2-1.3 1.5-3 1.2-2.5-2-1.4-3 1.4-6.2 1.6-8 1.3-6.4 1.2-7.9.7-2.6v-.2H49L43 72l-9 12.3-7.2 7.6-1.7.7-3-1.5.3-2.8L24 86l10-12.8 6-7.9 4-4.6-.1-.5h-.3L17.2 77.4l-4.7.6-2-2 .2-3 1-1 8-5.5Z"/></svg>
                <span class="text-[13px] font-semibold text-[#E5E5E5] tracking-tight">Rynude Code</span>
                <span class="text-[9px] px-1 py-0.5 bg-[#CC785C]/20 text-[#CC785C] rounded font-sans font-medium">Preview</span>
            </div>
            <button @click="sidebarOpen = false" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#999]">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
        </div>

        <div class="px-2 pt-2 pb-1 flex-shrink-0">
            <button @click="newSession()" class="w-full flex items-center gap-2 px-2.5 py-2 text-[12px] text-[#DDD] hover:text-[#E5E5E5] hover:bg-[#252525] rounded-md transition-colors group font-sans">
                <svg class="w-3.5 h-3.5 group-hover:rotate-90 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New session <kbd class="ml-auto text-[10px] bg-[#1F1F1F] border border-[#2A2A2A] text-[#888] px-1 rounded">N</kbd>
            </button>
        </div>

        <div class="px-2 space-y-0.5 flex-shrink-0">
            <button @click="currentView='routines'" class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] rounded-md transition-colors font-sans" :class="currentView==='routines'||currentView==='new-routine'?'bg-[#252525] text-[#E5E5E5]':'text-[#BBB] hover:text-[#CCC] hover:bg-[#1F1F1F]'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Routines
            </button>
            <a href="{{ route('chat', ['panel' => 'customize']) }}" class="w-full flex items-center gap-2 px-2.5 py-1.5 text-[12px] text-[#BBB] hover:text-[#CCC] hover:bg-[#1F1F1F] rounded-md transition-colors font-sans">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6M4.93 19.07l4.24-4.24M14.83 9.17l4.24-4.24"/></svg> Customize
            </a>
        </div>

        <div class="mx-3 my-2 border-t border-[#222] flex-shrink-0"></div>

        <div class="flex-1 overflow-y-auto scrollbar-hide px-2 min-h-0">
            <p class="px-2.5 text-[10px] font-semibold text-[#777] uppercase tracking-widest mb-1.5 font-sans">Recents</p>
            <template x-for="session in recentSessions" :key="session.id">
                <div class="group relative flex items-center rounded-md mb-0.5" :class="conversation?.id===session.id?'bg-[#252525] border border-[#333]':'hover:bg-[#1F1F1F]'">
                    <button @click="loadSession(session.id)" class="flex-1 flex items-center gap-2 px-2.5 py-2 text-left min-w-0">
                        <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="conversation?.id===session.id?'bg-[#4ADE80] animate-pulse':'bg-[#333] group-hover:bg-[#555]'"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] truncate font-sans transition-colors" :class="conversation?.id===session.id?'text-[#E5E5E5]':'text-[#CCC]'" x-text="session.title"></p>
                            <p class="text-[10px] text-[#666] font-sans mt-0.5" x-text="session.ago"></p>
                        </div>
                    </button>
                    <button @click="deleteSession(session.id)" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-[#333] transition-all text-[#777] hover:text-[#F87171] absolute right-1" title="Delete session">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </template>
            <template x-if="recentSessions.length === 0">
                <p class="text-[11px] text-[#555] px-2.5 py-3 font-sans">No recent sessions</p>
            </template>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-[#1A1A1A]">
        {{-- Top bar --}}
        <div class="flex items-center justify-between px-3 py-1.5 border-b border-[#2A2A2A] flex-shrink-0">
            <div class="flex items-center gap-2">
                <button @click="sidebarOpen = !sidebarOpen" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#999]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open=!open" type="button" class="flex items-center gap-1.5 px-2 py-1 rounded bg-[#252525] hover:bg-[#2A2A2A] transition-colors text-[13px] text-[#CCC]">
                        <span class="max-w-[80px] truncate" x-text="selectedModelName"></span>
                        <svg class="w-3 h-3 text-[#777]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" @click.away="open=false" x-cloak class="absolute right-0 top-full mt-1 w-[200px] bg-[#1F1F1F] border border-[#333] rounded-md shadow-lg py-1 z-50">
                        <template x-for="m in codeModels" :key="m.code">
                            <button @click="selectedModel=m.code; open=false" class="w-full text-left px-3 py-1.5 text-[12px] hover:bg-[#252525] transition-colors" :class="selectedModel===m.code?'text-white':'text-[#999]'" x-text="m.name"></button>
                        </template>
                    </div>
                </div>
                <button @click="rightPanelOpen=!rightPanelOpen" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#999]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
                </button>
            </div>
        </div>

        {{-- Chat area --}}
        <div class="flex-1 flex overflow-hidden">
            <div class="flex-1 flex flex-col overflow-hidden" x-ref="chatContainer">
                {{-- Messages --}}
                <div class="flex-1 overflow-y-auto px-4 py-6 space-y-4" x-ref="messagesContainer">
                    <template x-for="(msg, idx) in messages" :key="idx">
                        <div class="max-w-[800px] mx-auto w-full">
                            <div class="flex items-start gap-3" :class="msg.role==='user'?'flex-row-reverse':''">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-medium shrink-0"
                                     :class="msg.role==='user'?'bg-[#CC785C] text-white':'bg-[#252525] text-[#999]'"
                                     x-text="msg.role==='user'?'U':'R'"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[14px] leading-7 text-[#E5E5E5] whitespace-pre-wrap break-words font-sans"
                                         x-text="msg.content"></div>
                                    <template x-if="msg.attachments && msg.attachments.length > 0">
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <template x-for="att in msg.attachments" :key="att.id">
                                                <div class="flex items-center gap-1.5 px-2 py-1 bg-[#252525] rounded text-[12px] text-[#999]">
                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                    <span x-text="att.file_name"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="isStreaming">
                        <div class="max-w-[800px] mx-auto w-full">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-[#252525] flex items-center justify-center shrink-0">
                                    <div class="w-4 h-4 rounded-full border-2 border-[#CC785C] border-t-transparent animate-spin"></div>
                                </div>
                                <div class="cc-prose text-[14px] leading-7 text-[#CCCCCC] font-sans" x-html="streamContent"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Input --}}
                <div class="px-4 pb-3 pt-2">
                    <div class="max-w-[800px] mx-auto w-full bg-[#252525] border border-[#333] rounded-xl overflow-hidden">
                        <div x-show="attachments.length > 0" class="px-3 pt-3 flex flex-wrap gap-2">
                            <template x-for="(att, idx) in attachments" :key="idx">
                                <div class="flex items-center gap-1 px-2 py-1 bg-[#1F1F1F] rounded text-[12px] text-[#999]">
                                    <span x-text="att.name"></span>
                                    <button @click="removeAttachment(idx)" class="text-[#2A4A3A] hover:text-[#F87171] transition-colors text-sm leading-none">×</button>
                                </div>
                            </template>
                        </div>
                        <div x-show="uploading" class="flex items-center gap-2 px-3 py-2 text-[12px] text-[#999] font-sans">
                            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                            Uploading files...
                        </div>
                        <textarea x-model="message" @keydown.enter.prevent="sendMessage()" @input="autoResize($event)" rows="1"
                            class="w-full bg-transparent border-0 focus:ring-0 px-4 py-3 resize-none text-[14px] text-[#E5E5E5] placeholder-[#666] font-sans min-h-[44px] max-h-32"
                            placeholder="Message Rynude Code..."></textarea>
                        <div class="flex items-center justify-between px-3 pb-2">
                            <div class="flex items-center gap-1">
                                <button @click="$refs.ccFile.click()" class="p-1.5 rounded hover:bg-[#2A2A2A] transition-colors text-[#777] hover:text-[#999]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                                <input type="file" x-ref="ccFile" class="hidden" multiple @change="handleFileUpload($event)">
                                <button @click="repoModalOpen=true" class="p-1.5 rounded hover:bg-[#2A2A2A] transition-colors text-[#777] hover:text-[#999]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open=!open" class="flex items-center gap-1 text-[11px] text-[#666] hover:text-[#999]">
                                        <span x-text="selectedModelName"></span>
                                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open=false" x-cloak class="absolute bottom-full right-0 mb-2 w-[180px] bg-[#1F1F1F] border border-[#333] rounded-md shadow-lg py-1 z-50">
                                        <template x-for="m in codeModels" :key="m.code">
                                            <button @click="selectedModel=m.code; open=false" class="w-full text-left px-3 py-1.5 text-[12px] hover:bg-[#252525]" :class="selectedModel===m.code?'text-white':'text-[#999]'" x-text="m.name"></button>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="isStreaming">
                                    <button @click="stopGeneration()" class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] text-[#CC785C] hover:bg-[#CC785C]/10 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg> Stop
                                    </button>
                                </template>
                                <button @click="sendMessage()" :disabled="sending||!message.trim()" class="p-1.5 rounded transition-colors" :class="sending||!message.trim()?'text-[#444]':'text-[#CC785C] hover:bg-[#CC785C]/10'">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL --}}
    <div x-show="rightPanelOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="w-[300px] flex-shrink-0 bg-[#141414] border-l border-[#2A2A2A] h-full flex flex-col z-10">
        <div class="flex items-center justify-between px-3 py-2 border-b border-[#2A2A2A]">
            <div class="flex items-center gap-2">
                <button @click="rightPanelTab='files'" class="px-2 py-1 text-[11px] rounded transition-colors font-sans" :class="rightPanelTab==='files'?'bg-[#252525] text-[#CCC]':'text-[#666] hover:text-[#999]'">Files</button>
                <button @click="rightPanelTab='repo'" class="px-2 py-1 text-[11px] rounded transition-colors font-sans" :class="rightPanelTab==='repo'?'bg-[#252525] text-[#CCC]':'text-[#666] hover:text-[#999]'">Repo</button>
            </div>
            <button @click="rightPanelOpen=false" class="p-1 rounded hover:bg-[#2A2A2A] transition-colors text-[#666]">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto p-3 font-sans">
            <template x-if="rightPanelTab==='files'">
                <div class="space-y-2">
                    <p class="text-[12px] text-[#666]">Attached files will appear here.</p>
                </div>
            </template>
            <template x-if="rightPanelTab==='repo'">
                <div class="space-y-2">
                    <p x-show="repoConnected" class="flex items-center gap-2 text-[12px] text-[#4ADE80]">
                        <span class="w-2 h-2 rounded-full bg-[#4ADE80]"></span>Connected: <span x-text="repoUrl" class="text-[#CCC]"></span>
                        <button @click="disconnectRepo()" class="text-[#2A4A3A] hover:text-[#F87171] transition-colors ml-0.5">×</button>
                    </p>
                    <p x-show="!repoConnected" class="text-[12px] text-[#666]">No repo connected.</p>
                </div>
            </template>
        </div>
    </div>

    {{-- Repo Modal --}}
    <template x-if="repoModalOpen">
        <div class="fixed inset-0 z-[70] bg-black/70 flex items-center justify-center">
            <div class="bg-[#1F1F1F] border border-[#333] rounded-xl p-6 w-full max-w-md">
                <h3 class="text-[15px] font-semibold text-[#E5E5E5] mb-4 font-sans">Connect GitHub Repository</h3>
                <input x-model="repoUrl" type="text" placeholder="https://github.com/user/repo"
                    class="w-full px-3 py-2 bg-[#252525] border border-[#333] rounded-lg text-[13px] text-[#CCC] placeholder-[#555] focus:outline-none focus:border-[#CC785C] mb-4 font-sans">
                <div class="flex justify-end gap-2">
                    <button @click="repoModalOpen=false" class="px-3 py-1.5 text-[12px] text-[#999] hover:text-[#CCC] transition-colors font-sans">Cancel</button>
                    <button @click="connectRepo()" class="px-3 py-1.5 text-[12px] bg-[#CC785C] text-white rounded-lg hover:bg-[#B86A50] transition-colors font-sans">Connect</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Permission Modal --}}
    <template x-if="pendingPermission">
        <div class="fixed inset-0 z-[80] bg-black/70 flex items-center justify-center">
            <div class="bg-[#1F1F1F] border border-[#333] rounded-xl p-6 w-full max-w-md">
                <h3 class="text-[15px] font-semibold text-[#E5E5E5] mb-4 font-sans">Permission Request</h3>
                <p class="text-[13px] text-[#999] mb-6 font-sans" x-text="pendingPermission"></p>
                <div class="flex justify-end gap-2">
                    <button @click="denyPermission()" class="px-3 py-1.5 text-[12px] text-[#999] hover:text-[#CCC] transition-colors font-sans">Deny</button>
                    <button @click="approvePermission()" class="px-3 py-1.5 text-[12px] bg-[#CC785C] text-white rounded-lg hover:bg-[#B86A50] transition-colors font-sans">Approve</button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function claudeCodeState() {
    return {
        sidebarOpen: true,
        rightPanelOpen: false,
        rightPanelTab: 'files',
        isStarted: false,
        isStreaming: false,
        currentView: 'chat',
        conversation: null,
        recentSessions: [],
        messages: [],
        message: '',
        attachments: [],
        uploading: false,
        sending: false,
        streamContent: '',
        repoModalOpen: false,
        repoUrl: '',
        repoConnected: false,
        pendingPermission: null,
        selectedModel: 'claude-haiku-4-5',
        codeModels: [],

        get selectedModelName() {
            var m = this.codeModels.find(function(m) { return m.code === this.selectedModel; }.bind(this));
            return m ? m.name : 'Haiku 4.5';
        },

        init: function() {
            this.codeModels = [
                {code:'claude-opus-4-8',name:'Opus 4.8'},
                {code:'claude-sonnet-4-6',name:'Sonnet 4.6'},
                {code:'claude-haiku-4-5',name:'Haiku 4.5'},
            ];
        },

        newSession: function() {
            this.messages = [];
            this.conversation = null;
            this.isStarted = true;
            this.isStreaming = false;
            this.message = '';
        },

        loadSession: function(id) {
            var self = this;
            fetch('/api/chats/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        self.conversation = resp.data;
                        self.messages = resp.data.messages || [];
                        self.isStarted = true;
                    }
                });
        },

        deleteSession: function(id) {
            if (!confirm('Delete this session?')) return;
            var self = this;
            fetch('/api/chats/' + id, {method:'DELETE',headers:{'Accept':'application/json'}})
                .then(function(r){
                    if (r.ok && self.conversation?.id === id) { self.newSession(); }
                });
        },

        sendMessage: function() {
            if (!this.message.trim() || this.sending) return;
            this.sending = true;
            this.isStreaming = true;
            var self = this;
            self.messages.push({role:'user',content:self.message,attachments:[{file_name:self.message}]});

            fetch('/api/chats/send', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'text/event-stream'},
                body: JSON.stringify({prompt:self.message.trim(),model:self.selectedModel})
            })
            .then(function(response) {
                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';
                self.message = '';
                self.streamContent = '';

                function read() {
                    reader.read().then(function(result) {
                        if (result.done) { self.isStreaming=false; self.sending=false; return; }
                        buffer += decoder.decode(result.value, {stream:true});
                        var lines = buffer.split('\n');
                        buffer = lines.pop() || '';
                        lines.forEach(function(line) {
                            if (line.startsWith('data: ')) {
                                try { var d=JSON.parse(line.slice(6)); if(d.type==='content') self.streamContent+=d.data; } catch(e) {}
                            }
                        });
                        read();
                    });
                }
                read();
            })
            .catch(function(){self.isStreaming=false;self.sending=false;});
        },

        stopGeneration: function() {
            this.isStreaming = false;
            this.sending = false;
        },

        handleFileUpload: function(event) {
            var files = event.target.files;
            if (!files) return;
            for (var i=0;i<files.length;i++) this.attachments.push({name:files[i].name,file:files[i]});
        },

        removeAttachment: function(idx) { this.attachments.splice(idx,1); },
        autoResize: function(e) { var el=e.target; el.style.height='auto'; el.style.height=el.scrollHeight+'px'; },
        connectRepo: function() { this.repoModalOpen=false; this.repoConnected=!!this.repoUrl; },
        disconnectRepo: function() { this.repoConnected=false; this.repoUrl=''; },
        approvePermission: function() { this.pendingPermission=null; },
        denyPermission: function() { this.pendingPermission=null; },
    };
}
</script>
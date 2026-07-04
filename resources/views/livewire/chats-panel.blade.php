<div class="h-full flex flex-col bg-[#F9F8F6] dark:bg-claude-bg-dark overflow-y-auto"
     x-data="chatsPanelState()"
     x-init="init()">
    <div class="max-w-[1000px] mx-auto w-full px-4 sm:px-8 py-6 sm:py-10 flex flex-col h-full">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <h2 class="font-serif text-[28px] sm:text-[32px] text-[#2D2825] dark:text-stone-200">Chats</h2>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <template x-if="isSelectMode">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <template x-if="selectedChats.length > 0">
                            <button @click="archiveSelectedChats()" class="flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors bg-white dark:bg-claude-bg-dark active:scale-95">
                                <span x-text="(showArchived ? 'Unarchive' : 'Archive') + ' (' + selectedChats.length + ')'"></span>
                            </button>
                        </template>
                        <template x-if="selectedChats.length > 0">
                            <button @click="deleteSelectedChats()" class="flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl border border-red-200 text-[13px] sm:text-[14px] font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors bg-white dark:bg-claude-bg-dark active:scale-95">
                                <span x-text="'Delete (' + selectedChats.length + ')'"></span>
                            </button>
                        </template>
                        <button @click="toggleSelectMode()" class="px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors bg-white dark:bg-claude-bg-dark active:scale-95">Cancel</button>
                    </div>
                </template>
                <template x-if="!isSelectMode">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <button @click="toggleShowArchived()" class="px-3 sm:px-4 py-2 rounded-xl border text-[13px] sm:text-[14px] font-medium transition-colors active:scale-95" :class="showArchived ? 'border-[#D97757] text-[#D97757] bg-[#D97757]/5' : 'border-[#E5E5E5] dark:border-stone-700 text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-[#3A3A38] bg-white dark:bg-claude-bg-dark'">
                            <span class="hidden sm:inline" x-text="showArchived ? 'Active chats' : 'Archived'"></span>
                            <span class="sm:hidden" x-text="showArchived ? 'Active' : 'Archive'"></span>
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] text-gray-500 dark:text-stone-400 hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors bg-white dark:bg-claude-bg-dark active:scale-95">
                                <span class="hidden sm:inline">Filter by <strong class="font-medium text-[#2D2825] dark:text-stone-200" x-text="filterType === 'all' ? 'All' : (filterType === 'today' ? 'Today' : 'Past 7 days')"></strong></span>
                                <span class="sm:hidden font-medium text-[#2D2825] dark:text-stone-200" x-text="filterType === 'all' ? 'Filter' : (filterType === 'today' ? 'Today' : '7 Days')"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 dark:text-stone-500 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 sm:right-0 sm:left-auto mt-2 w-48 rounded-xl bg-white dark:bg-claude-bg-dark border border-gray-200 dark:border-stone-700 shadow-lg py-1 z-50">
                                <button @click="setFilter('all'); open = false" class="block w-full text-left px-4 py-2 text-sm" :class="filterType === 'all' ? 'bg-gray-50 dark:bg-stone-700 text-black dark:text-white' : 'text-gray-700 dark:text-stone-300 hover:bg-gray-50 dark:hover:bg-[#3A3A38]'">All</button>
                                <button @click="setFilter('today'); open = false" class="block w-full text-left px-4 py-2 text-sm" :class="filterType === 'today' ? 'bg-gray-50 dark:bg-stone-700 text-black dark:text-white' : 'text-gray-700 dark:text-stone-300 hover:bg-gray-50 dark:hover:bg-[#3A3A38]'">Today</button>
                                <button @click="setFilter('week'); open = false" class="block w-full text-left px-4 py-2 text-sm" :class="filterType === 'week' ? 'bg-gray-50 dark:bg-stone-700 text-black dark:text-white' : 'text-gray-700 dark:text-stone-300 hover:bg-gray-50 dark:hover:bg-[#3A3A38]'">Past 7 days</button>
                            </div>
                        </div>
                        <button @click="toggleSelectMode()" class="px-3 sm:px-4 py-2 rounded-xl border border-[#E5E5E5] dark:border-stone-700 text-[13px] sm:text-[14px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-gray-50 dark:hover:bg-[#3A3A38] transition-colors bg-white dark:bg-claude-bg-dark active:scale-95">
                            <span class="hidden sm:inline">Select chats</span>
                            <span class="sm:hidden">Select</span>
                        </button>
                        <button @click="startNewChat()" class="px-3 sm:px-4 py-2 rounded-xl bg-[#2D2825] dark:bg-stone-100 text-white dark:text-stone-900 text-[13px] sm:text-[14px] font-medium hover:bg-black dark:hover:bg-white transition-colors active:scale-95">New chat</button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Search --}}
        <div class="relative mb-6">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input
                x-model="searchQuery"
                @input.debounce.300ms="loadConversations()"
                type="text"
                placeholder="Search chats..."
                class="w-full pl-11 pr-4 py-3 rounded-xl border border-stone-200 dark:border-stone-700 bg-[#F9F8F6] dark:bg-claude-bg-dark text-[15px] text-[#2D2825] dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-0 focus:border-stone-300 dark:focus:border-stone-500 transition-all"
            >
        </div>

        {{-- Chat List --}}
        <div class="flex-1">
            <template x-if="loading">
                <div class="flex items-center justify-center py-24"><img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-8 h-8 object-contain"></div>
            </template>
            <template x-if="!loading && groupedConversations.length === 0">
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <p class="text-gray-500 text-[15px]">No chats found</p>
                </div>
            </template>
            <template x-if="!loading && groupedConversations.length > 0">
                <div class="flex flex-col">
                    <template x-for="(items, period) in groupedConversationsMap" :key="period">
                        <div>
                            <div class="text-[12px] font-semibold text-gray-400 dark:text-stone-500 uppercase tracking-wider pt-5 pb-2" x-text="period"></div>
                            <template x-for="conversation in items" :key="conversation.id">
                                <div class="group flex items-center justify-between py-4 border-b border-[#E5E5E5] dark:border-stone-800 hover:bg-gray-50/50 dark:hover:bg-[#3A3A38]/50 transition-colors">
                                    <template x-if="renamingId === conversation.id">
                                        <div class="flex items-center flex-1 gap-2 pr-4" @click.stop>
                                            <input
                                                x-model="renameTitle"
                                                @keydown.enter="renameConversation(conversation.id)"
                                                @keydown.escape="cancelRename()"
                                                autofocus
                                                class="flex-1 px-3 py-1.5 rounded-lg border border-[#D97757] bg-white dark:bg-stone-800 text-[14.5px] text-[#2D2825] dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-[#D97757]/20"
                                            >
                                            <button @click="renameConversation(conversation.id)" class="px-3 py-1.5 rounded-lg bg-[#2D2825] dark:bg-stone-200 text-white dark:text-stone-900 text-[13px] font-medium">Save</button>
                                            <button @click="cancelRename()" class="px-3 py-1.5 rounded-lg border border-[#E5E5E5] dark:border-stone-700 text-[13px] font-medium text-stone-500">Cancel</button>
                                        </div>
                                    </template>
                                    <template x-if="renamingId !== conversation.id">
                                        <div class="flex items-center flex-1 min-w-0 pr-4 cursor-pointer"
                                             @click="isSelectMode ? toggleChatSelection(conversation.id) : selectConversation(conversation.id)">
                                            <template x-if="isSelectMode">
                                                <div class="mr-3 w-5 h-5 rounded border flex-shrink-0 flex items-center justify-center transition-colors"
                                                     :class="selectedChats.includes(conversation.id) ? 'bg-[#2D2825] border-[#2D2825] text-white dark:bg-stone-200 dark:border-stone-200 dark:text-stone-900' : 'border-gray-300 dark:border-stone-600 bg-white dark:bg-stone-800'">
                                                    <svg x-show="selectedChats.includes(conversation.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </template>
                                            <div class="min-w-0">
                                                <span class="text-[14.5px] font-medium text-[#2D2825] dark:text-stone-200 truncate block" x-text="conversation.title"></span>
                                                <span x-show="conversation.preview" class="text-[13px] text-gray-400 dark:text-stone-500 truncate block mt-0.5" x-text="conversation.preview"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="renamingId !== conversation.id">
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span class="text-[13.5px] text-gray-400 dark:text-stone-500" x-text="conversation.updated_at"></span>
                                            <template x-if="!isSelectMode">
                                                <div class="relative" x-data="{ menuOpen: false }">
                                                    <button @click.stop="menuOpen = !menuOpen" @click.away="menuOpen = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-700 opacity-0 group-hover:opacity-100 transition-all" title="Options">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                                    </button>
                                                    <div x-show="menuOpen" x-cloak x-transition.opacity class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-claude-bg-dark border border-[#E5E5E5] dark:border-stone-700 rounded-xl shadow-lg py-1.5 z-50">
                                                        <button @click.stop="menuOpen = false; startRename(conversation.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-[#3A3A38] flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Rename
                                                        </button>
                                                        <button @click.stop="menuOpen = false; archiveConversation(conversation.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-[#3A3A38] flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                                                            <span x-text="conversation.archived ? 'Unarchive' : 'Archive'"></span>
                                                        </button>
                                                        <button @click.stop="menuOpen = false; shareConversation(conversation.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-[#3A3A38] flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                                                            <span x-text="conversation.shared ? 'Copy share link' : 'Share'"></span>
                                                        </button>
                                                        <button x-show="conversation.shared" @click.stop="menuOpen = false; unshareConversation(conversation.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-[#3A3A38] flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg> Stop sharing
                                                        </button>
                                                        <button @click.stop="menuOpen = false; exportChat(conversation.id, 'md')" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-[#3A3A38] flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export Markdown
                                                        </button>
                                                        <button @click.stop="menuOpen = false; exportChat(conversation.id, 'json')" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-[#2D2825] dark:text-stone-200 hover:bg-claude-bg-light dark:hover:bg-[#3A3A38] flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export JSON
                                                        </button>
                                                        <div class="h-px w-full bg-[#E5E5E5] dark:bg-stone-700 my-1"></div>
                                                        <button @click.stop="menuOpen = false; deleteChat(conversation.id)" class="w-full text-left px-3 py-1.5 text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function chatsPanelState() {
    return {
        searchQuery: '',
        conversations: [],
        isSelectMode: false,
        selectedChats: [],
        filterType: 'all',
        showArchived: false,
        renamingId: null,
        renameTitle: '',
        loading: false,

        get groupedConversations() {
            return this.objectToArray(this.groupedConversationsMap);
        },

        get groupedConversationsMap() {
            var filtered = this.conversations;
            if (this.filterType !== 'all') {
                filtered = filtered.filter(function(c) {
                    if (this.filterType === 'today') return c.group === 'Today';
                    if (this.filterType === 'week') return ['Today','Yesterday','Previous 7 days'].includes(c.group);
                    return true;
                }.bind(this));
            }
            if (this.searchQuery.trim() !== '') {
                var q = this.searchQuery.toLowerCase().trim();
                filtered = filtered.filter(function(c) {
                    return c.title.toLowerCase().includes(q);
                });
            }
            var grouped = {};
            filtered.forEach(function(c) {
                if (!grouped[c.group]) grouped[c.group] = [];
                grouped[c.group].push(c);
            });
            return grouped;
        },

        objectToArray: function(obj) {
            var arr = [];
            for (var k in obj) arr.push({ period: k, items: obj[k] });
            return arr;
        },

        init: function() {
            this.loadConversations();
        },

        loadConversations: function() {
            this.loading = true;
            var params = new URLSearchParams();
            params.append('archived', this.showArchived ? '1' : '0');
            if (this.searchQuery.trim()) params.append('search', this.searchQuery.trim());
            if (this.filterType !== 'all') params.append('filter', this.filterType);
            fetch('/api/chats?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    this.conversations = resp.data || [];
                    this.loading = false;
                }.bind(this))
                .catch(function() { this.loading = false; }.bind(this));
        },

        toggleShowArchived: function() {
            this.showArchived = !this.showArchived;
            this.isSelectMode = false;
            this.selectedChats = [];
            this.loadConversations();
        },

        setFilter: function(type) {
            this.filterType = type;
        },

        toggleSelectMode: function() {
            this.isSelectMode = !this.isSelectMode;
            if (!this.isSelectMode) this.selectedChats = [];
        },

        toggleChatSelection: function(id) {
            var idx = this.selectedChats.indexOf(id);
            if (idx > -1) this.selectedChats.splice(idx, 1);
            else this.selectedChats.push(id);
        },

        startNewChat: function() {
            window.dispatchEvent(new CustomEvent('close-panel'));
            window.dispatchEvent(new CustomEvent('newChat'));
        },

        selectConversation: function(id) {
            window.dispatchEvent(new CustomEvent('close-panel'));
            window.dispatchEvent(new CustomEvent('selectConversation', { detail: { conversationId: id } }));
            window.history.pushState({}, '', '/chat?conversation=' + id);
        },

        startRename: function(id) {
            var conv = this.conversations.find(function(c) { return c.id === id; });
            this.renamingId = id;
            this.renameTitle = conv ? conv.title : '';
        },

        cancelRename: function() {
            this.renamingId = null;
            this.renameTitle = '';
        },

        renameConversation: function(id) {
            var title = this.renameTitle.trim();
            if (!title) { this.cancelRename(); return; }
            fetch('/api/chats/' + id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ title: title })
            })
            .then(function(r) {
                if (!r.ok) throw new Error('rename failed');
                this.cancelRename();
                this.loadConversations();
            }.bind(this))
            .catch(function() { this.cancelRename(); this.loadConversations(); }.bind(this));
        },

        archiveConversation: function(id) {
            var conv = this.conversations.find(function(c) { return c.id === id; });
            var archived = !(conv && conv.archived);
            fetch('/api/chats/' + id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ archived: archived })
            })
            .then(function(r) {
                if (r.ok) this.loadConversations();
            }.bind(this));
        },

        shareConversation: function(id) {
            fetch('/api/chats/' + id + '/share', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                this.loadConversations();
                if (resp.data && resp.data.share_url) {
                    navigator.clipboard.writeText(resp.data.share_url);
                }
            }.bind(this));
        },

        unshareConversation: function(id) {
            fetch('/api/chats/' + id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ unshare: true })
            })
            .then(function(r) {
                if (r.ok) this.loadConversations();
            }.bind(this));
        },

        exportChat: function(id, format) {
            window.location.href = '/api/chats/' + id + '/export?format=' + format;
        },

        deleteChat: function(id) {
            if (!confirm('Delete this chat?')) return;
            fetch('/api/chats/' + id, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) {
                if (r.ok) this.loadConversations();
            }.bind(this));
        },

        archiveSelectedChats: function() {
            if (this.selectedChats.length === 0) return;
            var archived = !this.showArchived;
            var promises = this.selectedChats.map(function(id) {
                return fetch('/api/chats/' + id, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ archived: archived })
                });
            });
            Promise.all(promises).then(function() {
                this.loadConversations();
                this.isSelectMode = false;
                this.selectedChats = [];
            }.bind(this));
        },

        deleteSelectedChats: function() {
            if (this.selectedChats.length === 0 || !confirm('Delete ' + this.selectedChats.length + ' chats?')) return;
            var promises = this.selectedChats.map(function(id) {
                return fetch('/api/chats/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json' } });
            });
            Promise.all(promises).then(function() {
                this.loadConversations();
                this.isSelectMode = false;
                this.selectedChats = [];
            }.bind(this));
        }
    };
}
</script>
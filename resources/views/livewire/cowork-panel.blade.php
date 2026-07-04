<div class="h-full bg-[#F9F8F6] dark:bg-stone-900 flex flex-col font-sans overflow-hidden"
     x-data="coworkPanelState()"
     x-init="init()">
    {{-- Header --}}
    <div class="w-full border-b border-[#E5E5E5] dark:border-stone-800/80 px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-2">
            <h1 class="font-serif text-[17px] font-medium text-[#2D2825] dark:text-stone-100">Cowork</h1>
            <span class="text-[9px] font-semibold tracking-wider uppercase px-1.5 py-0.5 bg-[#EAE9E5] dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-[4px]">Beta</span>
        </div>
        <template x-if="view === 'list'">
            <button @click="showCreate()" class="flex items-center gap-1.5 px-3.5 py-1.5 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> New task
            </button>
        </template>
        <template x-if="view === 'create' || view === 'detail'">
            <button @click="showList()" class="flex items-center gap-1.5 px-3 py-1.5 text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 text-[13px] font-medium transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Back
            </button>
        </template>
    </div>

    <template x-if="flashMessage">
        <div class="mx-6 mt-3 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-lg text-[13px]" x-text="flashMessage"></div>
    </template>

    <div class="flex-1 overflow-y-auto px-6 py-5">
        <div class="max-w-3xl mx-auto w-full">

        {{-- LANDING VIEW --}}
        <template x-if="view === 'landing'">
            <div class="h-full flex flex-col items-center justify-center px-6 py-6">
                <div class="max-w-3xl w-full flex flex-col items-center justify-center">
                    <div class="w-full max-w-[560px] aspect-[1.6] rounded-3xl border border-stone-200 dark:border-stone-800 overflow-hidden shadow-[0_8px_30px_rgba(0,0,0,0.02)] bg-white dark:bg-stone-950 flex items-center justify-center relative">
                        <video class="w-full h-full object-cover" src="{{ asset('video/desktop-cowork-tab.mp4') }}" autoplay muted loop playsinline></video>
                    </div>
                    <h2 class="text-[26px] font-serif text-[#2D2825] dark:text-stone-100 font-medium tracking-tight text-center mt-6 mb-2 leading-tight">Hand off complex tasks</h2>
                    <p class="text-[14.5px] text-stone-500 dark:text-stone-400 max-w-[480px] text-center leading-relaxed mb-6">Describe what you need and come back to polished work. Rynude works across your files and connected tools, on the schedule you set.</p>
                    <button @click="getStarted()" class="px-5 py-2.5 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-xl text-[14px] font-medium transition-colors shadow-sm focus:outline-none">Get started</button>
                </div>
            </div>
        </template>

        {{-- LIST VIEW --}}
        <template x-if="view === 'list'">
            <div>
                <div class="grid grid-cols-4 gap-3 mb-5">
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-3.5 shadow-sm">
                        <div class="text-[22px] font-semibold text-[#2D2825] dark:text-stone-100 leading-none" x-text="stats.total"></div>
                        <div class="text-[11.5px] text-stone-500 dark:text-stone-400 mt-1.5">Total</div>
                    </div>
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-3.5 shadow-sm">
                        <div class="text-[22px] font-semibold text-amber-600 dark:text-amber-400 leading-none" x-text="stats.pending"></div>
                        <div class="text-[11.5px] text-stone-500 dark:text-stone-400 mt-1.5">Pending</div>
                    </div>
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-3.5 shadow-sm">
                        <div class="text-[22px] font-semibold text-blue-600 dark:text-blue-400 leading-none" x-text="stats.in_progress"></div>
                        <div class="text-[11.5px] text-stone-500 dark:text-stone-400 mt-1.5">Running</div>
                    </div>
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-3.5 shadow-sm">
                        <div class="text-[22px] font-semibold text-emerald-600 dark:text-emerald-400 leading-none" x-text="stats.completed"></div>
                        <div class="text-[11.5px] text-stone-500 dark:text-stone-400 mt-1.5">Completed</div>
                    </div>
                </div>

                <div class="flex items-center gap-1 mb-4">
                    <template x-for="(label, key) in {all:'All',pending:'Pending',in_progress:'Running',completed:'Completed',failed:'Failed'}">
                        <button @click="statusFilter = key; loadTasks()" class="px-3 py-1 rounded-lg text-[12.5px] transition-all" :class="statusFilter === key ? 'bg-[#EAE9E5] text-[#2D2825] dark:bg-stone-800 dark:text-stone-200 font-semibold' : 'text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200'" x-text="label"></button>
                    </template>
                </div>

                <template x-if="loading">
                    <div class="flex items-center justify-center py-12"><img src="{{ asset('images/logo_rynudee.png') }}" alt="" class="animate-spin w-8 h-8 object-contain"></div>
                </template>
                <template x-if="!loading && tasks.length === 0">
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl py-14 flex flex-col items-center justify-center text-center shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-stone-50 dark:bg-stone-800 flex items-center justify-center text-stone-400 mb-3 border border-stone-200 dark:border-stone-700">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <p class="text-stone-600 dark:text-stone-300 text-[14px] font-medium">No tasks yet</p>
                        <p class="text-stone-400 dark:text-stone-500 text-[12.5px] mt-1 max-w-xs">Hand off complex work to Rynude. Describe what you need and come back to a polished result.</p>
                        <button @click="showCreate()" class="mt-4 px-4 py-2 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm">Create your first task</button>
                    </div>
                </template>
                <template x-if="!loading && tasks.length > 0">
                    <div class="space-y-2.5">
                        <template x-for="task in tasks" :key="task.id">
                            <div @click="openTask(task)" class="group bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl px-4 py-3.5 shadow-sm hover:border-stone-300 dark:hover:border-stone-700 hover:shadow transition-all cursor-pointer flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" :class="{'bg-stone-400':task.priority==='low','bg-amber-400':task.priority==='medium','bg-red-500':task.priority==='high'}" :title="task.priority"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[14px] font-medium text-[#2D2825] dark:text-stone-100 truncate" x-text="task.title"></div>
                                    <div x-show="task.description" class="text-[12px] text-stone-500 dark:text-stone-400 truncate mt-0.5" x-text="task.description"></div>
                                    <div class="flex items-center gap-2 mt-1.5 text-[11px] text-stone-400">
                                        <span x-text="task.model"></span>
                                        <span x-show="task.scheduled_for">· Scheduled <span x-text="task.scheduled_for"></span></span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-medium flex-shrink-0" :class="{'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300':task.status==='pending','bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300':task.status==='in_progress','bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300':task.status==='completed','bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300':task.status==='failed'}" x-text="statusLabel(task.status)"></span>
                                <button @click.stop="deleteTask(task.id)" class="opacity-0 group-hover:opacity-100 p-1 text-stone-400 hover:text-red-500 transition-all" title="Delete">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        {{-- CREATE VIEW --}}
        <template x-if="view === 'create'">
            <div>
                <h2 class="font-serif text-[20px] font-medium text-[#2D2825] dark:text-stone-100 mb-1">Hand off a task</h2>
                <p class="text-[13px] text-stone-500 dark:text-stone-400 mb-5">Describe what you need and assign it to a model.</p>
                <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-2xl p-5 shadow-sm space-y-4">
                    <div>
                        <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Title</label>
                        <input type="text" x-model="form.title" placeholder="e.g. Draft a launch announcement"
                            class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13.5px] text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:border-[#D97757]">
                        <template x-if="formErrors.title"><span class="text-red-500 text-[11.5px] mt-1 block" x-text="formErrors.title"></span></template>
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Description / instructions</label>
                        <textarea x-model="form.description" rows="5" placeholder="Provide context, requirements, and the desired output…"
                            class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13.5px] text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:border-[#D97757] resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Model</label>
                            <select x-model="form.model" class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-[#D97757]">
                                <option value="fable-5">Fable 5</option>
                                <option value="claude-sonnet-5">Sonnet 5</option>
                                <option value="claude-opus-4-8">Opus 4.8</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Priority</label>
                            <select x-model="form.priority" class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-[#D97757]">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-stone-600 dark:text-stone-300 mb-1.5">Schedule (optional)</label>
                            <input type="datetime-local" x-model="form.scheduledFor" class="w-full px-3 py-2 bg-[#F9F8F6] dark:bg-stone-900 border border-stone-200 dark:border-stone-700 rounded-lg text-[13px] text-stone-800 dark:text-stone-200 focus:outline-none focus:border-[#D97757]">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button @click="showList()" class="px-4 py-2 text-stone-500 hover:text-[#2D2825] dark:hover:text-stone-200 text-[13px] font-medium transition-colors">Cancel</button>
                        <button @click="createTask()" :disabled="saving" class="px-4 py-2 bg-[#191919] hover:bg-black dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">Create task</button>
                    </div>
                </div>
            </div>
        </template>

        {{-- DETAIL VIEW --}}
        <template x-if="view === 'detail' && activeTask">
            <div>
                <div class="flex items-start justify-between mb-1">
                    <h2 class="font-serif text-[20px] font-medium text-[#2D2825] dark:text-stone-100" x-text="activeTask.title"></h2>
                    <span class="px-2.5 py-1 rounded-md text-[12px] font-medium" :class="{'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300':activeTask.status==='pending','bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300':activeTask.status==='in_progress','bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300':activeTask.status==='completed','bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300':activeTask.status==='failed'}" x-text="statusLabel(activeTask.status)"></span>
                </div>
                <div class="flex items-center gap-2 text-[12px] text-stone-400 mb-4">
                    <span x-text="activeTask.model"></span>
                    <span x-text="'· ' + activeTask.priority + ' priority'"></span>
                    <span x-show="activeTask.scheduled_for">· Scheduled <span x-text="activeTask.scheduled_for"></span></span>
                </div>
                <div x-show="activeTask.description" class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-4 shadow-sm mb-4">
                    <div class="text-[11.5px] font-semibold uppercase tracking-wide text-stone-400 mb-1.5">Instructions</div>
                    <p class="text-[13.5px] text-stone-700 dark:text-stone-300 whitespace-pre-wrap leading-relaxed" x-text="activeTask.description"></p>
                </div>
                <div class="flex items-center gap-2 mb-4">
                    <button @click="runTask(activeTask.id)" :disabled="running" class="flex items-center gap-1.5 px-4 py-2 bg-[#D97757] hover:bg-[#c56647] text-white rounded-lg text-[13px] font-medium transition-colors shadow-sm disabled:opacity-50">
                        <svg x-show="!running" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        <svg x-show="running" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <span x-text="running ? 'Running…' : (activeTask.result ? 'Re-run' : 'Run task')"></span>
                    </button>
                    <template x-if="activeTask.status !== 'completed'">
                        <button @click="updateStatus(activeTask.id, 'completed')" class="px-3.5 py-2 border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300 rounded-lg text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors">Mark complete</button>
                    </template>
                    <template x-if="activeTask.status === 'completed'">
                        <button @click="updateStatus(activeTask.id, 'pending')" class="px-3.5 py-2 border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300 rounded-lg text-[13px] font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors">Reopen</button>
                    </template>
                    <button @click="deleteTask(activeTask.id)" class="px-3.5 py-2 border border-stone-200 dark:border-stone-700 text-red-500 rounded-lg text-[13px] font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">Delete</button>
                </div>
                <div x-show="running" class="flex items-center gap-2 text-[13px] text-stone-500 dark:text-stone-400 mb-4">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    Rynude is working on this task…
                </div>
                <template x-if="activeTask.result">
                    <div class="bg-white dark:bg-stone-850 border border-[#E5E5E5] dark:border-stone-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-[11.5px] font-semibold uppercase tracking-wide text-stone-400">Result</div>
                            <span x-show="activeTask.completed_at" class="text-[11px] text-stone-400" x-text="'Completed ' + activeTask.completed_at"></span>
                        </div>
                        <div class="text-[13.5px] text-stone-700 dark:text-stone-200 whitespace-pre-wrap leading-relaxed" x-text="activeTask.result"></div>
                    </div>
                </template>
            </div>
        </template>
        </div>
    </div>
</div>

<script>
function coworkPanelState() {
    return {
        view: 'landing',
        activeTask: null,
        statusFilter: 'all',
        tasks: [],
        stats: {total:0, pending:0, in_progress:0, completed:0},
        loading: false,
        saving: false,
        running: false,
        flashMessage: '',
        form: { title: '', description: '', priority: 'medium', model: 'claude-haiku-4-5', scheduledFor: null },
        formErrors: {},

        init: function() { this.loadTasks(); },

        statusLabel: function(s) {
            return {pending:'Pending',in_progress:'Running',completed:'Completed',failed:'Failed'}[s] || s;
        },

        loadTasks: function() {
            this.loading = true;
            var params = new URLSearchParams();
            if (this.statusFilter !== 'all') params.append('status', this.statusFilter);
            fetch('/api/tasks?' + params.toString(), {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    this.tasks = resp.data || [];
                    this.stats = resp.stats || {total:0,pending:0,in_progress:0,completed:0};
                    this.loading = false;
                }.bind(this))
                .catch(function(){this.loading=false;}.bind(this));
        },

        showCreate: function() {
            this.form = {title:'', description:'', priority:'medium', model:'claude-haiku-4-5', scheduledFor:null};
            this.formErrors = {};
            this.view = 'create';
        },

        getStarted: function() { this.view = 'list'; this.loadTasks(); },

        showList: function() {
            this.view = 'list';
            this.activeTask = null;
            this.loadTasks();
        },

        openTask: function(task) {
            this.activeTask = task;
            this.view = 'detail';
        },

        createTask: function() {
            this.formErrors = {};
            if (!this.form.title.trim()) { this.formErrors.title = 'Title is required.'; return; }
            this.saving = true;
            fetch('/api/tasks', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify(this.form)
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                this.saving = false;
                this.flashMessage = 'Task created successfully.';
                if (resp.data) { this.activeTask = resp.data; this.view = 'detail'; }
                this.loadTasks();
            }.bind(this))
            .catch(function(){this.saving=false;}.bind(this));
        },

        runTask: function(id) {
            this.running = true;
            this.activeTask = null;
            this.view = 'list';
            fetch('/api/tasks/' + id + '/run', {
                method: 'POST',
                headers: {'Accept':'application/json'}
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                this.running = false;
                if (resp.data) { this.activeTask = resp.data; this.view = 'detail'; }
                this.loadTasks();
            }.bind(this))
            .catch(function(){this.running=false; this.loadTasks();}.bind(this));
        },

        updateStatus: function(id, status) {
            fetch('/api/tasks/' + id, {
                method: 'PATCH',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify({status: status})
            })
            .then(function(r){return r.json()})
            .then(function(resp){
                if (resp.data) {
                    this.activeTask = resp.data;
                    if (this.view === 'list') this.loadTasks();
                }
            }.bind(this));
        },

        deleteTask: function(id) {
            if (!confirm('Delete this task?')) return;
            fetch('/api/tasks/' + id, {
                method: 'DELETE',
                headers: {'Accept':'application/json'}
            })
            .then(function(r){
                if (r.ok) {
                    if (this.activeTask && this.activeTask.id === id) this.showList();
                    else this.loadTasks();
                }
            }.bind(this));
        },
    };
}
</script>
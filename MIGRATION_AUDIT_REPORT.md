# LIVEWIRE → PURE LARAVEL MIGRATION AUDIT & ROADMAP

**Project:** Claude UI Clone  
**Target:** Remove Livewire completely, migrate to pure Laravel + AJAX  
**Status:** Audit Complete - No Code Changes  
**Generated:** 2026-06-28  

---

## EXECUTIVE SUMMARY

This codebase is **deeply integrated with Livewire** despite being described as "pure Laravel." Removing Livewire requires systematic refactoring across 6 major areas:

| Category | Count | Complexity | Risk |
|----------|-------|-----------|------|
| Livewire Components | 17 | HIGH | CRITICAL |
| Blade Files (Livewire) | 19 | MEDIUM | HIGH |
| Blade Directives Used | ~150+ | MEDIUM | MEDIUM |
| Routes (Livewire-based) | 2 | LOW | MEDIUM |
| Service Dependencies | 3 | MEDIUM | MEDIUM |
| Business Logic Lines | ~5,000+ | HIGH | CRITICAL |

**Estimated Effort:** 40-60 engineering hours  
**Phases:** 6 sequential phases (3-4 weeks)  
**Risk Level:** HIGH (major architectural change)

---

## PART 1: COMPLETE LIVEWIRE INVENTORY

### 1.1 LIVEWIRE COMPONENTS (17 Total)

```
app/Livewire/
├── ActivityFeed.php              (24 lines)   - Displays activity stream
├── ActivityTimeline.php          (30 lines)   - Timeline view of activities
├── ArtifactPanel.php             (416 lines)  - Document/artifact management (CRITICAL)
├── ChatInterface.php             (1,106 lines)- Main chat AI interface (CRITICAL)
├── ChatLayout.php                (154 lines)  - Layout wrapper for chat
├── ChatsPanel.php                (313 lines)  - Chat history/list management
├── ClaudeCodeApp.php             (827 lines)  - Claude Code IDE app (CRITICAL)
├── CodePanel.php                 (30 lines)   - Code display panel
├── CoworkPanel.php               (412 lines)  - Collaboration/task management
├── CustomizePanel.php            (161 lines)  - UI customization settings
├── DesignPanel.php               (162 lines)  - Design/artifact tools
├── HelpModal.php                 (33 lines)   - Help dialog
├── ProjectsPanel.php             (412 lines)  - Project management
├── QuotaWarningModal.php         (25 lines)   - API quota alerts
├── SettingsModal.php             (558 lines)  - Settings interface (CRITICAL)
├── Sidebar.php                   (147 lines)  - Navigation sidebar
└── SystemUpdateModal.php         (28 lines)   - System updates dialog
```

**Total Lines of Code in Components:** ~5,500 lines

**Criticality Classification:**
- 🔴 **CRITICAL (4):** ChatInterface, ClaudeCodeApp, ArtifactPanel, SettingsModal
- 🟡 **HIGH (5):** CoworkPanel, ProjectsPanel, ChatsPanel, ChatLayout, CustomizePanel
- 🟢 **MEDIUM (8):** Rest

---

### 1.2 BLADE VIEW FILES WITH LIVEWIRE (19 Total)

All located in `resources/views/livewire/`:

```
├── activity-feed.blade.php        - wire:ignore, dynamic data
├── activity-timeline.blade.php    - wire:ignore, event listeners
├── artifact-panel.blade.php       - wire:model, wire:click (60+ directives)
├── chat-interface.blade.php       - wire:stream, wire:loading (COMPLEX)
├── chat-layout.blade.php          - Parent layout component
├── chats-panel.blade.php          - wire:model.live.debounce, wire:click (40+ directives)
├── claude-code-app.blade.php      - Form handling, file uploads (70+ directives)
├── code-panel.blade.php           - wire:click
├── cowork-panel.blade.php         - Task management UI (35+ directives)
├── customize-panel.blade.php      - Settings form
├── design-panel.blade.php         - wire:model.live.debounce (25+ directives)
├── help-modal.blade.php           - Simple modal
├── new-routine.blade.php          - Form handling
├── projects-panel.blade.php       - Project list management
├── quota-warning-modal.blade.php  - Modal dialog
├── routines-list.blade.php        - List view
├── settings-modal.blade.php       - Settings form (CRITICAL)
├── sidebar.blade.php              - Navigation (wire:navigate)
└── system-update-modal.blade.php  - Modal dialog
```

**Total Blade Directives Used:** 150+

---

### 1.3 LIVEWIRE DIRECTIVES BREAKDOWN

| Directive | Count | Complexity | Notes |
|-----------|-------|-----------|-------|
| `@livewire()` | 1 | LOW | Only in chat.blade.php |
| `wire:click` | 45+ | MEDIUM | Button handlers, event dispatchers |
| `wire:model` | 30+ | MEDIUM | Form inputs, two-way binding |
| `wire:model.live.debounce` | 8+ | HIGH | Real-time search with debounce |
| `wire:loading` / `.remove` | 15+ | MEDIUM | Loading states, spinners |
| `wire:loading.attr` | 10+ | MEDIUM | Disable buttons during loading |
| `wire:loading.class` | 8+ | MEDIUM | Hide/show loading indicators |
| `wire:target` | 12+ | MEDIUM | Specific action targeting |
| `wire:keydown.enter` | 5+ | LOW | Form submission on Enter |
| `wire:keydown.escape` | 3+ | LOW | Modal/dialog closing |
| `wire:confirm` | 3+ | LOW | Delete confirmations |
| `wire:navigate.hover` | 3+ | MEDIUM | Client-side navigation |
| `wire:stream` | 1 | HIGH | SSE streaming (CRITICAL) |
| `wire:ignore` | 3+ | MEDIUM | Alpine.js integration |
| Livewire events | 20+ | HIGH | `dispatch()`, event listeners |

---

### 1.4 LIVEWIRE ROUTES

**File:** `routes/web.php` (lines 20-21)

```php
Route::get('/code', \App\Livewire\ClaudeCodeApp::class)->middleware(['auth', 'verified'])->name('code');
Route::get('/design', \App\Livewire\DesignPanel::class)->middleware(['auth', 'verified'])->name('design');
```

**Impact:** 2 routes directly use Livewire components as controllers.

---

### 1.5 DEPENDENCY ANALYSIS

#### Composer Dependencies
```json
"livewire/livewire": "^4.3"
```

**Side Effects of Package:**
- Registers routes: `/livewire-eb0b983b/*` (asset serving, updates)
- Service provider auto-discovery
- Blade compiler extensions
- JavaScript bundling

#### Service Providers Using Livewire

**File:** `app/Providers/AppServiceProvider.php` (needs verification)

#### Views Using Livewire

**Main Entry Point:** `resources/views/chat.blade.php`
```blade
<x-app-layout>
    @livewire('chat-layout')
</x-app-layout>
```

This single directive loads the entire chat system.

---

## PART 2: BLADE TEMPLATE ANALYSIS

### 2.1 Livewire Component Files Requiring Refactoring

#### 2.1.1 Component Templates with Heavy wire: Usage

**TOP 5 BY DIRECTIVE DENSITY:**

1. **claude-code-app.blade.php** (70+ directives)
   - `wire:click` - button handlers
   - `wire:model` - text inputs, file uploads
   - `wire:keydown.*` - keyboard shortcuts
   - `wire:loading` - async operation feedback
   - `wire:stream` - SSE message streaming
   - `livewire:` component includes (nested components)

2. **chats-panel.blade.php** (45+ directives)
   - `wire:model.live.debounce.300ms` - search with debounce
   - `wire:click` - selection, actions
   - `wire:keydown.enter/escape` - input handling

3. **artifact-panel.blade.php** (40+ directives)
   - `wire:model` - form fields
   - `wire:click` - tab switching, delete actions

4. **cowork-panel.blade.php** (35+ directives)
   - `wire:click` - task operations
   - `wire:model` - form inputs
   - `wire:loading` - operation feedback

5. **design-panel.blade.php** (25+ directives)
   - `wire:model.live.debounce.300ms` - search
   - `wire:click` - design actions

#### 2.1.2 Shared Blade Components Using wire:navigate

**Files:**
- `resources/views/components/nav-link.blade.php` - `wire:navigate.hover`
- `resources/views/components/dropdown-link.blade.php` - `wire:navigate.hover`
- `resources/views/components/responsive-nav-link.blade.php` - `wire:navigate.hover`

**Impact:** ~3 component files need navigation updates

---

### 2.2 Complex Blade Patterns Requiring Special Handling

#### Pattern 1: Real-Time Debounced Search
```blade
<input type="text" 
    wire:model.live.debounce.300ms="searchQuery"
    placeholder="Search…">
```
**Components:** ChatsPanel, DesignPanel (2 instances)  
**Replacement:** AJAX + JavaScript debouncing

#### Pattern 2: Loading States with wire:loading
```blade
<button wire:loading.attr="disabled" 
        wire:target="someAction"
        wire:click="someAction">
    <span wire:loading.remove>{{ $label }}</span>
    <span wire:loading>Loading…</span>
</button>
```
**Components:** ~15+ instances  
**Replacement:** Manual JavaScript state management

#### Pattern 3: Streaming with wire:stream
```blade
<div wire:stream="message-stream" class="prose"></div>
```
**Components:** claude-code-app.blade.php (CRITICAL)  
**Replacement:** Direct SSE implementation with JavaScript

#### Pattern 4: Modal with wire:click.self
```blade
<div wire:click.self="closeDialog" class="modal">
    <!-- content -->
</div>
```
**Components:** ~5 instances  
**Replacement:** Alpine.js or Vanilla JavaScript

#### Pattern 5: Nested Livewire Components
```blade
<livewire:settings-modal />
<livewire:sidebar />
```
**Locations:** claude-code-app.blade.php, chat-layout.blade.php  
**Replacement:** Blade component includes

---

## PART 3: CRITICAL BUSINESS LOGIC MIGRATION REQUIREMENTS

### 3.1 Livewire Component Methods to Migrate

#### ChatInterface Component (1,106 lines) - MOST CRITICAL
**Methods to convert:**
- `generateResponse()` - LLM API streaming
- `sendMessage()` - Message submission
- `stopGeneration()` - Stream cancellation
- `updateConversation()` - Title updates
- Event handlers: `#[On('generateResponse')]`

**Key Challenge:** Wire streaming with SSE

#### ClaudeCodeApp Component (827 lines) - CRITICAL
**Methods to convert:**
- `sendMessage()` - Message handling
- `generateResponse()` - Code generation
- `newSession()` - Session management
- `loadSession()` - Session loading
- `connectRepo()` / `disconnectRepo()` - Git repo handling
- File upload handling via `WithFileUploads` trait

**Key Challenge:** File upload management, session state

#### SettingsModal Component (558 lines) - CRITICAL
**Methods to convert:**
- Settings form submission
- API key validation
- Model selection
- Theme switching

**Key Challenge:** Persistent state across sessions

#### ArtifactPanel Component (416 lines) - CRITICAL
**Methods to convert:**
- `viewArtifact()` - Artifact display
- `updateArtifact()` - Edits
- `deleteArtifact()` - Deletion
- Event listeners for artifact updates

**Key Challenge:** Real-time updates via broadcast/polling

---

### 3.2 Event System Migration

**Current Livewire Events Used:**

```php
#[On('generateResponse')]        // Event listener in ChatInterface
#[On('open-panel')]              // Event listener in ChatLayout
#[On('showArtifactPanel')]       // Event listener in ChatLayout
dispatch('open-settings-modal')  // Event dispatch
dispatch('generateResponse')     // Event dispatch
```

**Total Events:** 20+ custom events  
**Replacement Strategy:**
- Convert to AJAX POST/GET requests
- Use JavaScript global event emitters
- Or use Laravel Broadcasting (WebSockets)

---

### 3.3 Service Dependencies in Livewire Components

**Services used by components:**

1. **ActivityStreamService** - Used in ChatInterface
   - Tracks activity timeline
   - Publishes events

2. **AiService** - Used in ChatInterface, ClaudeCodeApp
   - LLM API integration
   - Token counting
   - Streaming response handling

3. **PdfRenderer** - Used in ArtifactPanel
   - mPDF document generation

**Status:** Services are framework-agnostic ✅  
**No changes needed** to services themselves

---

## PART 4: JAVASCRIPT & FRONTEND DEPENDENCIES

### 4.1 JavaScript Files Involved

**Primary Files:**
- `resources/js/app.js` - Main application bootstrap
- `resources/js/bootstrap.js` - Environment setup
- `resources/js/chat-alpine.js` - Alpine.js directives (mixed with Livewire)

### 4.2 Livewire JavaScript Integration Points

**File:** `resources/views/livewire/claude-code-app.blade.php` (line 749)
```javascript
document.addEventListener('livewire:initialized', () => {
    // Livewire JavaScript initialization
});
```

**Events Listened to:**
- `livewire:initialized` - Component ready
- Custom Livewire events from `@dispatch()`

### 4.3 Alpine.js Integration

**Current Pattern:**
```blade
<div wire:ignore x-data="{ open: false }">
    <!-- Alpine component with wire:ignore to prevent Livewire interference -->
</div>
```

**Instances:** 3+ components use this pattern  
**Status:** Will continue working fine with pure Laravel

---

## PART 5: MIGRATION ROADMAP

### PHASE 1: ROUTING MIGRATION (Effort: 4-6 hours)

**Objective:** Convert Livewire routes to traditional controllers

**Files to Create/Modify:**

```
BEFORE:
routes/web.php:
  Route::get('/code', \App\Livewire\ClaudeCodeApp::class)
  Route::get('/design', \App\Livewire\DesignPanel::class)

AFTER:
routes/web.php:
  Route::get('/code', [ClaudeCodeController::class, 'index'])
  Route::get('/design', [DesignController::class, 'index'])
  Route::post('/api/chat/send', [ChatApiController::class, 'send'])
  Route::post('/api/artifact/*', [ArtifactApiController::class, '*'])
  Route::post('/api/cowork/*', [CoworkApiController::class, '*'])
  ... (10-15 API routes)

NEW FILES:
  app/Http/Controllers/ClaudeCodeController.php
  app/Http/Controllers/DesignController.php
  app/Http/Controllers/ChatApiController.php
  app/Http/Controllers/ArtifactApiController.php
  app/Http/Controllers/CoworkApiController.php
  app/Http/Controllers/SettingsController.php
```

**Risks:**
- ⚠️ Missing API endpoints cause 404s
- ⚠️ Different response format breaks frontend
- ✅ Can test incrementally with feature flags

**Dependencies:** None (Phase 1 is standalone)

**Testing:** Unit tests for each controller method

---

### PHASE 2: BLADE TEMPLATE MIGRATION (Effort: 8-12 hours)

**Objective:** Convert Livewire Blade directives to vanilla/Alpine.js

**Stages:**

**Stage 2.A: Simple Directives (Days 1-2)**
- Replace `wire:navigate.hover` → Regular `<a href="">` + JavaScript
- Replace `wire:confirm` → Vanilla JavaScript `confirm()`
- Replace `wire:keydown.enter` → Alpine `@keydown.enter`

**Files:** nav-link, dropdown-link, responsive-nav-link, modals  
**Complexity:** LOW  
**Risk:** LOW  

**Stage 2.B: Basic Handlers (Days 2-3)**
- Replace `wire:click="methodName"` → `@click="postTo('/api/...')`
- Replace `wire:model="property"` → Alpine `x-model="property"`
- Replace `wire:keydown.escape` → Alpine `@keydown.escape`

**Files:** SettingsModal, HelpModal, QuotaWarningModal, Sidebar  
**Complexity:** MEDIUM  
**Risk:** MEDIUM  

**Stage 2.C: Complex Patterns (Days 3-4)**
- Replace `wire:model.live.debounce.300ms` → `@input.debounce.300ms`
- Replace `wire:loading.*` → Manual JavaScript state classes
- Replace `wire:target` → AJAX completion handlers

**Files:** ChatsPanel, DesignPanel, CoworkPanel  
**Complexity:** MEDIUM-HIGH  
**Risk:** MEDIUM  

**Stage 2.D: Critical Patterns (Days 4-5)**
- Replace `wire:stream` → Direct JavaScript SSE EventSource
- Replace nested `<livewire:component />` → Blade `@include('livewire.component')`
- Replace event dispatch/listeners → AJAX + JavaScript callbacks

**Files:** claude-code-app, chat-interface, artifact-panel  
**Complexity:** HIGH  
**Risk:** HIGH  

**Risks:**
- ⚠️ Logic moved to frontend JavaScript (security implications)
- ⚠️ Form validation must be duplicated client/server-side
- ⚠️ Real-time debouncing may have race conditions
- ✅ Test each template change individually

**Dependencies:** Phase 1 routes must exist

**Testing:** 
- E2E tests for each UI interaction
- Check network requests match expected format

---

### PHASE 3: STATE MANAGEMENT MIGRATION (Effort: 6-8 hours)

**Objective:** Move Livewire component state → Server-side sessions or client-side JavaScript

**Approach A: Server-Side Session State** (Recommended)
```php
// Controller
public function updateSearchQuery(Request $request)
{
    session(['search_query' => $request->input('query')]);
    return response()->json(['results' => $this->search(...)]);
}
```

**Approach B: Client-Side State** (Simpler, less secure)
```javascript
// JavaScript
let state = {
    searchQuery: '',
    results: [],
    selectedModel: 'claude-opus'
};
```

**Files to Modify:**
- ChatsPanel (search state)
- DesignPanel (search, tab selection)
- SettingsModal (form state)
- ClaudeCodeApp (session management)

**Risks:**
- ⚠️ Lost session state on page refresh (if client-side)
- ⚠️ Increased server calls for state updates
- ✅ Server-side is more secure but slower

**Dependencies:** Phase 2 must be mostly complete

**Testing:** Check state persistence across navigation

---

### PHASE 4: API ENDPOINT MIGRATION (Effort: 10-14 hours)

**Objective:** Create REST API endpoints for all Livewire methods

**Pattern:** Convert each Livewire method to API endpoint

```php
BEFORE (Livewire):
public function sendMessage() { ... }

AFTER (Pure Laravel):
Route::post('/api/chat/send', [ChatApiController::class, 'send']);

public function send(Request $request)
{
    $validated = $request->validate([...]);
    return response()->json([...]);
}
```

**Endpoints to Create (30+):**

**Chat APIs (8 endpoints):**
- POST `/api/chat/send` - Send message
- POST `/api/chat/stop` - Stop generation
- GET `/api/chat/{id}` - Get conversation
- POST `/api/chat/{id}/rename` - Rename conversation
- DELETE `/api/chat/{id}` - Delete conversation
- POST `/api/chat/{id}/share` - Share conversation
- POST `/api/chat/{id}/export` - Export to PDF/Markdown
- GET `/api/search-chats` - Search conversations

**Artifact APIs (6 endpoints):**
- GET `/api/artifacts` - List artifacts
- POST `/api/artifacts` - Create artifact
- PATCH `/api/artifacts/{id}` - Update artifact
- DELETE `/api/artifacts/{id}` - Delete artifact
- GET `/api/artifacts/{id}/preview.pdf` - PDF preview
- GET `/api/artifacts/{id}` - Get details

**Cowork APIs (7 endpoints):**
- GET `/api/tasks` - List tasks
- POST `/api/tasks` - Create task
- PATCH `/api/tasks/{id}` - Update task
- POST `/api/tasks/{id}/run` - Execute task
- DELETE `/api/tasks/{id}` - Delete task
- PATCH `/api/tasks/{id}/status` - Update status
- GET `/api/tasks/{id}/result` - Get result

**Settings APIs (4 endpoints):**
- GET `/api/settings` - Get user settings
- PATCH `/api/settings` - Update settings
- POST `/api/settings/validate-api-key` - Validate API key
- POST `/api/settings/test-connection` - Test connection

**Project/Design APIs (5+ endpoints):**
- GET `/api/projects` - List projects
- POST `/api/projects` - Create project
- PATCH `/api/projects/{id}` - Update
- DELETE `/api/projects/{id}` - Delete
- Similar for designs

**Risks:**
- ⚠️ API contract breaks if responses differ from frontend expectations
- ⚠️ Missing validation on server-side
- ⚠️ Rate limiting needed for API endpoints
- ✅ Can test each endpoint independently

**Dependencies:** Phase 3 state management defined

**Testing:** 
- API tests (laravel/tests/Feature)
- Request/response validation
- Error handling

---

### PHASE 5: STREAMING SYSTEM MIGRATION (Effort: 6-10 hours)

**Objective:** Replace Livewire streaming with direct SSE or WebSockets

**Current Livewire Wire:Stream:**
```blade
<div wire:stream="message-stream"></div>
```

**OPTION A: Server-Sent Events (SSE) - Simpler**
```javascript
const eventSource = new EventSource('/api/chat/stream?conversation_id=123');
eventSource.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    document.getElementById('message-stream').innerHTML += data.chunk;
});
```

**OPTION B: WebSockets - More Complex**
```javascript
// Requires Laravel Broadcasting + Pusher/Redis
window.Echo.channel(`chat.${conversationId}`).listen('MessageChunk', (event) => {
    // Handle message chunk
});
```

**Recommendation:** Use SSE (simpler, no dependencies)

**Implementation:**
1. Create SSE endpoint: `GET /api/chat/{id}/stream`
2. Convert `generateResponse()` to stream chunks via SSE
3. Update JavaScript to handle EventSource
4. Test streaming with large responses

**Risks:**
- ⚠️ SSE doesn't work with proxies/load balancers (may need WebSockets)
- ⚠️ Browser-side chunking may cause UI lag
- ⚠️ Need heartbeat to keep connection alive
- ✅ Simple fallback to polling if SSE fails

**Dependencies:** Phase 4 API endpoints needed

**Testing:**
- Stream large response (10,000+ tokens)
- Check memory usage
- Test connection drops and recovery

---

### PHASE 6: LIVEWIRE PACKAGE REMOVAL (Effort: 2-4 hours)

**Objective:** Remove Livewire from project entirely

**Steps:**

1. **Remove from composer.json**
   ```bash
   composer remove livewire/livewire
   ```

2. **Delete Livewire directory**
   ```bash
   rm -rf app/Livewire
   rm -rf resources/views/livewire
   ```

3. **Clean up config**
   - Remove Livewire config file (if exists)
   - Update `bootstrap/cache/config.php`

4. **Remove service provider registration**
   - Check `config/app.php` for Livewire provider
   - Remove from `AppServiceProvider.php`

5. **Update main layout**
   - Replace `@livewire('chat-layout')` with component includes
   - Import all sub-components

6. **Test**
   - Run `php artisan cache:clear`
   - Run `php artisan route:clear`
   - Run `php artisan view:clear`
   - Test all routes

**Risks:**
- ⚠️ Routes will break if Phase 1 not complete
- ⚠️ Blade views will error if directives still present
- ⚠️ JavaScript may reference Livewire objects
- ✅ Can do dry-run first

**Dependencies:** All previous phases must be complete

**Testing:**
- Full regression test (all features)
- Browser console for JavaScript errors
- Performance benchmarks before/after

---

## PART 6: IMPLEMENTATION CHECKLIST & DEPENDENCY MATRIX

### Phase Dependency Graph
```
Phase 1 (Routes)
    ↓
Phase 2 (Blade Templates)
    ↓
Phase 3 (State Management)
    ↓
Phase 4 (API Endpoints)
    ↓
Phase 5 (Streaming)
    ↓
Phase 6 (Package Removal)
```

**All phases are sequential.** Phase N cannot start until Phase N-1 is 90% complete.

### Risk Summary by Phase

| Phase | Risk Level | If Failed | Rollback Time |
|-------|-----------|-----------|--------------|
| 1 | MEDIUM | 404 errors on routes | 30 min |
| 2 | MEDIUM | UI broken | 2 hours |
| 3 | LOW | State not preserved | 1 hour |
| 4 | HIGH | API calls fail | 4 hours |
| 5 | HIGH | Streaming broken | 2 hours |
| 6 | LOW | App won't load | 1 hour |

**Recommendation:** Use git branches for each phase with frequent commits.

---

## PART 7: DETAILED FILE-BY-FILE MIGRATION GUIDE

### Phase 1 Files (Routing)

**STATUS:** ⚫ Not Started

```
MODIFY:
  routes/web.php
    - Change 2 Livewire routes to controller-based routes
    - Add 30+ API routes

CREATE:
  app/Http/Controllers/ClaudeCodeController.php (new)
  app/Http/Controllers/DesignController.php (new)
  app/Http/Controllers/ChatApiController.php (new)
  app/Http/Controllers/ArtifactApiController.php (new)
  app/Http/Controllers/CoworkApiController.php (new)
  app/Http/Controllers/SettingsController.php (new)
  
  routes/api.php (restructured)
    - Or keep all routes in routes/web.php

FILES AFFECTED: 7-8
LINES OF CODE TO WRITE: 400-500
TESTING TIME: 2-3 hours
```

---

### Phase 2 Files (Blade Templates)

**STATUS:** ⚫ Not Started

```
MODIFY (19 Blade files):
  resources/views/livewire/activity-feed.blade.php
  resources/views/livewire/activity-timeline.blade.php
  resources/views/livewire/artifact-panel.blade.php      [HIGH PRIORITY]
  resources/views/livewire/chat-interface.blade.php      [HIGH PRIORITY]
  resources/views/livewire/chat-layout.blade.php
  resources/views/livewire/chats-panel.blade.php
  resources/views/livewire/claude-code-app.blade.php     [HIGHEST PRIORITY]
  resources/views/livewire/code-panel.blade.php
  resources/views/livewire/cowork-panel.blade.php
  resources/views/livewire/customize-panel.blade.php
  resources/views/livewire/design-panel.blade.php
  resources/views/livewire/help-modal.blade.php
  resources/views/livewire/new-routine.blade.php
  resources/views/livewire/projects-panel.blade.php
  resources/views/livewire/quota-warning-modal.blade.php
  resources/views/livewire/routines-list.blade.php
  resources/views/livewire/settings-modal.blade.php      [HIGH PRIORITY]
  resources/views/livewire/sidebar.blade.php
  resources/views/livewire/system-update-modal.blade.php

MODIFY (3 component files):
  resources/views/components/nav-link.blade.php           [Remove wire:navigate.hover]
  resources/views/components/dropdown-link.blade.php      [Remove wire:navigate.hover]
  resources/views/components/responsive-nav-link.blade.php [Remove wire:navigate.hover]

MODIFY (1 view file):
  resources/views/chat.blade.php                          [Replace @livewire with includes]

FILES AFFECTED: 23
LINES MODIFIED: 600-800
TESTING TIME: 6-8 hours
```

---

### Phase 3 Files (State Management)

**STATUS:** ⚫ Not Started

```
MODIFY:
  app/Http/Controllers/ChatApiController.php
    - Add session state handling for search queries, filters
    - Use session(['chat_search' => $query])

  app/Http/Controllers/DesignController.php
    - Add session state for selected tab, search
    
  app/Http/Controllers/SettingsController.php
    - Add session state for preferences

  resources/js/chat-alpine.js (or create new files)
    - Add JavaScript state management
    - window.state = { searchQuery: '', selectedModel: ... }

FILES AFFECTED: 3-4
LINES OF CODE: 100-200
TESTING TIME: 2 hours
```

---

### Phase 4 Files (API Endpoints)

**STATUS:** ⚫ Not Started

```
CONVERT (17 Livewire components → 30+ API endpoints):
  
  From: app/Livewire/ChatInterface.php
  To:   app/Http/Controllers/ChatApiController.php
    - generateResponse() → POST /api/chat/generate
    - sendMessage() → POST /api/chat/send
    - stopGeneration() → POST /api/chat/stop
    - updateConversation() → PATCH /api/chat/{id}
    etc.

  From: app/Livewire/ClaudeCodeApp.php
  To:   app/Http/Controllers/CodeApiController.php
    - sendMessage() → POST /api/code/send
    - generateResponse() → POST /api/code/generate
    - newSession() → POST /api/code/sessions
    - loadSession() → GET /api/code/sessions/{id}
    etc.

  From: app/Livewire/SettingsModal.php
  To:   app/Http/Controllers/SettingsController.php (new)
    - updateSettings() → PATCH /api/settings
    - validateApiKey() → POST /api/settings/validate-key
    etc.

  From: app/Livewire/ArtifactPanel.php
  To:   app/Http/Controllers/ArtifactApiController.php (new)
    - viewArtifact() → GET /api/artifacts/{id}
    - updateArtifact() → PATCH /api/artifacts/{id}
    - deleteArtifact() → DELETE /api/artifacts/{id}
    etc.

  From: app/Livewire/CoworkPanel.php
  To:   app/Http/Controllers/CoworkApiController.php (new)
    - createTask() → POST /api/tasks
    - runTask() → POST /api/tasks/{id}/run
    - updateStatus() → PATCH /api/tasks/{id}
    etc.

FILES TO CREATE/MODIFY: 6-8 controllers
TOTAL LINES OF CODE: 1,500-2,000
TESTING TIME: 8-10 hours
```

---

### Phase 5 Files (Streaming)

**STATUS:** ⚫ Not Started

```
MODIFY:
  app/Http/Controllers/ChatApiController.php
    - Add streaming endpoint: GET /api/chat/{id}/stream
    - Convert generateResponse() to SSE output
    
    Example:
    public function stream($id)
    {
        return response()->stream(function () use ($id) {
            while ($chunk = $this->aiService->nextChunk()) {
                echo "data: " . json_encode($chunk) . "\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
        ]);
    }

  resources/js/chat-stream.js (new)
    - Handle EventSource connection
    - Parse SSE messages
    - Update DOM with message chunks

MODIFY:
  resources/views/livewire/chat-interface.blade.php
    - Replace wire:stream with JavaScript EventSource handler

FILES AFFECTED: 2-3
LINES OF CODE: 200-400
TESTING TIME: 3-4 hours
```

---

### Phase 6 Files (Cleanup)

**STATUS:** ⚫ Not Started

```
DELETE:
  app/Livewire/                    (entire directory, 17 files)
  resources/views/livewire/        (entire directory, 19 files)

REMOVE FROM:
  composer.json                    (dependency)
  config/app.php                   (service provider, if present)
  bootstrap/cache/                 (auto-generated, will be recreated)

RUN:
  composer remove livewire/livewire
  php artisan cache:clear
  php artisan route:clear
  php artisan view:clear

FILES AFFECTED: 37+
TESTING TIME: 1-2 hours
```

---

## PART 8: CRITICAL SUCCESS FACTORS & RISKS

### ✅ What Will Help

1. **Feature Flags** - Gate new pure Laravel routes alongside old Livewire ones
2. **API Contract Tests** - Ensure response formats match Blade expectations
3. **Branch Strategy** - One branch per phase, daily merges to dev
4. **E2E Tests** - Run tests after each phase
5. **Rollback Plan** - Keep Livewire working until Phase 6

### ❌ What Could Go Wrong

1. **API Response Format Mismatch** (High Risk)
   - Livewire JSON responses may differ from raw API
   - Fix: Define strict API schema early, test first

2. **Real-Time Streaming Failures** (High Risk)
   - SSE doesn't work with proxies/load balancers
   - Fix: Test SSE before Phase 5, have WebSocket fallback

3. **File Upload Handling** (Medium Risk)
   - Livewire's `WithFileUploads` trait has special handling
   - Fix: Test file uploads thoroughly in Phase 4

4. **Session State Loss** (Medium Risk)
   - Moving state from Livewire → server → client may lose data
   - Fix: Implement proper session persistence

5. **Race Conditions in Debouncing** (Low Risk)
   - Multiple AJAX requests from debounced inputs
   - Fix: Implement request debouncing on client-side

---

## PART 9: EFFORT ESTIMATION & TIMELINE

### Time Breakdown

```
Phase 1 (Routing):          4-6 hours      (1 day)
Phase 2 (Blade):            8-12 hours     (2-3 days)
Phase 3 (State):            6-8 hours      (1-2 days)
Phase 4 (API):              10-14 hours    (3-4 days)
Phase 5 (Streaming):        6-10 hours     (2-3 days)
Phase 6 (Cleanup):          2-4 hours      (½ day)
─────────────────────────────────────────
TOTAL:                      40-60 hours    (2-3 weeks)
```

### With Testing & Review
```
Code Writing:               40-60 hours
Unit Testing:               8-12 hours
Integration Testing:        6-10 hours
E2E Testing:                4-6 hours
Code Review:                4-6 hours
Bugfixes:                   4-8 hours
─────────────────────────────────────────
GRAND TOTAL:                60-100 hours   (3-4 weeks)
```

### Recommended Timeline
- **Week 1:** Phases 1-2 (Routes + Blade templates)
- **Week 2:** Phases 3-4 (State + API endpoints)
- **Week 3:** Phase 5 (Streaming)
- **Week 4:** Phase 6 + Testing + Bugfixes

---

## PART 10: RECOMMENDATIONS & NEXT STEPS

### ✅ DO THIS FIRST

1. **Create Feature Branch**
   ```bash
   git checkout -b livewire-migration-phase1
   ```

2. **Set Up Testing Infrastructure**
   - Create `tests/Feature/ChatApiTest.php`
   - Create `tests/Feature/ArtifactApiTest.php`
   - Add E2E tests for critical flows

3. **Document API Contract**
   - Create `docs/API.md` with all endpoint specs
   - Define request/response formats
   - List all validation rules

4. **Establish Code Review Process**
   - 2+ approvals per phase
   - Test coverage minimum 80%
   - Performance benchmarks before/after

### ⚠️ RISKS TO MITIGATE

1. **De-Couple Livewire Early**
   - Extract business logic from Livewire components NOW
   - Move to Service classes (ActivityStreamService pattern)
   - This makes Phase 4 easier

2. **Test Streaming Separately**
   - Build SSE handler before Phase 5
   - Test with real ChatGPT-scale responses (10K+ tokens)
   - Have WebSocket fallback ready

3. **API Rate Limiting**
   - Add rate limiting to all new API routes
   - Prevent AJAX spam from debouncing
   - Use `throttle` middleware

### 🎯 SUCCESS METRICS

- All routes return 200 status
- All forms submit successfully
- Real-time search/debouncing works
- Message streaming completes in <5s
- Zero Livewire JavaScript errors
- 95%+ E2E test pass rate

---

## CONCLUSION

This migration is **achievable but requires careful planning**. The 6-phase approach ensures:

✅ Each phase can be tested independently  
✅ Rollback is possible at any point  
✅ Team can work in parallel on different controllers  
✅ Existing features remain stable during transition  

**Estimated Resource:** 2-3 senior developers for 3-4 weeks  
**Risk Level:** MEDIUM-HIGH (architectural change)  
**Impact:** Major improvement (no JavaScript framework dependency)

**Proceed to Phase 1 only after:**
1. ✅ This audit is reviewed and approved
2. ✅ Testing strategy is finalized
3. ✅ Team is trained on new patterns
4. ✅ Dev environment is set up with feature flags

---

**Report Generated:** 2026-06-28  
**Status:** READY FOR IMPLEMENTATION  
**Next Action:** Initiate Phase 1 (Routing Migration)

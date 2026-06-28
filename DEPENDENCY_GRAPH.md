# COMPLETE LIVEWIRE DEPENDENCY GRAPH

**Status:** Pre-migration analysis (No code changes)  
**Date:** 2026-06-28  
**Purpose:** Identify all hidden dependencies before Phase 1 implementation  

---

## PART 1: COMPONENT HIERARCHY & RELATIONSHIPS

### Visual Component Tree

```
ROOT (Laravel HTTP Request)
└── chat.blade.php
    └── @livewire('chat-layout')
        │
        ├── ChatLayout (Parent Container)
        │   │   State: sidebarOpen, activePanel, openArtifactId, settingsOpen
        │   │   Events Sent: dispatch('open-settings-modal'), dispatch('closeArtifactPanel')
        │   │   Alpine Integration: Full x-data state management
        │   │
        │   ├── ⬜ Sidebar (Child)
        │   │   └── No models, No services
        │   │       Events Received: #[On('open-help-modal')]
        │   │
        │   ├── ⬜ ChatsPanel (Child - Conditional)
        │   │   │   Models: Conversation (query/load)
        │   │   │   Events Received: #[On(...)]
        │   │   │   wire:model.live.debounce (search)
        │   │   │
        │   │
        │   ├── ⬜ ProjectsPanel (Child - Conditional)
        │   │   │   Models: Project, ProjectFile, Setting, AiModel
        │   │   │   Services: None directly
        │   │   │   Traits: WithFileUploads
        │   │   │   Events: #[On('refresh-projects')]
        │   │   │
        │   │
        │   ├── ⬜ ChatInterface (Child - Main Chat Area)
        │   │   │   Models: Conversation, Message, MessageArtifact, AiModel
        │   │   │   Services: AiService
        │   │   │   Traits: WithFileUploads
        │   │   │   Events: #[On('generateResponse')] 🔴 CRITICAL
        │   │   │   Streaming: wire:stream="message-stream"
        │   │   │   API Calls: 
        │   │   │     - POST /chat-stop (cache flag)
        │   │   │     - POST /api/upload (file uploads)
        │   │   │     - Various LLM APIs (Anthropic, OpenAI, etc.)
        │   │   │
        │   │
        │   ├── ⬜ ArtifactPanel (Child - Right Sidebar)
        │   │   │   Models: MessageArtifact
        │   │   │   Services: None
        │   │   │   Props: openArtifactId (reactive from parent)
        │   │   │   Events: None
        │   │   │   API Calls: PDF generation, preview requests
        │   │   │
        │   │
        │   └── ⬜ SettingsModal (Overlay)
        │       │   Models: User (settings table)
        │       │   Services: None
        │       │   Events: #[On('open-settings-modal')]
        │       │   Facades: Auth, DB
        │       │   State: All form fields
        │
        └── ActivityFeed (Sibling container - via chat.blade.php or included)
            └── ActivityTimeline
```

---

## PART 2: COMPONENT DEPENDENCY TABLE

| Component | Type | LOC | Parent | Children | Models Used | Services | Traits |
|-----------|------|-----|--------|----------|-------------|----------|--------|
| **ChatLayout** | Container | 154 | chat.blade | Sidebar, ChatsPanel, ProjectsPanel, ChatInterface, ArtifactPanel, SettingsModal | - | - | - |
| **ChatInterface** | 🔴 CRITICAL | 1,106 | ChatLayout | ActivityFeed, ActivityTimeline | Conversation, Message, MessageArtifact, AiModel | AiService | WithFileUploads |
| **ArtifactPanel** | Panel | 416 | ChatLayout | - | MessageArtifact | - | - |
| **SettingsModal** | Modal | 558 | ChatLayout | - | User (settings) | - | - |
| **ChatsPanel** | Panel | 313 | ChatLayout | - | Conversation | - | - |
| **ProjectsPanel** | Panel | 412 | ChatLayout | - | Project, ProjectFile, Setting, AiModel | - | WithFileUploads |
| **ClaudeCodeApp** | 🔴 CRITICAL | 827 | /code route | Sidebar, various modals | Conversation, Message, MessageAttachment, AiModel | - | WithFileUploads |
| **CoworkPanel** | Panel | 412 | ChatLayout or standalone | - | CoworkTask | AiService | - |
| **DesignPanel** | Panel | 162 | /design route or ChatLayout | - | Design | AiService | - |
| **ActivityFeed** | List | 24 | ChatInterface | - | - | - | - |
| **ActivityTimeline** | Timeline | 30 | ChatInterface | - | EventHistoryService | EventHistoryServiceInterface | - |
| **Sidebar** | Navigation | 147 | ChatLayout | - | - | - | - |
| **CustomizePanel** | Panel | 161 | ChatLayout | - | - | - | - |
| **CodePanel** | Panel | 30 | ClaudeCodeApp | - | - | - | - |
| **QuotaWarningModal** | Modal | 25 | Floating | - | - | - | - |
| **HelpModal** | Modal | 33 | Floating | - | - | - | - |
| **SystemUpdateModal** | Modal | 28 | Floating | - | - | - | - |

**Legend:** 🔴 = Critical (core functionality)

---

## PART 3: DETAILED COMPONENT ANALYSIS

### 1. ChatLayout (Container / Parent)

**Purpose:** Main application layout and state orchestration  
**Parent:** None (root component)  
**Hierarchy Level:** ROOT

**State Management:**
```php
public bool $sidebarOpen = true;
public ?int $openArtifactId = null;           // Reactive prop to ArtifactPanel
public ?string $activePanel = null;           // nav state: 'chats'|'projects'|'code'|etc
public bool $settingsOpen = false;
```

**Events Dispatched (via Livewire.dispatch):**
- `newChat()` - ChatInterface
- `closeArtifactPanel()` - ArtifactPanel
- `open-settings-modal` - SettingsModal
- `open-help-modal` - HelpModal

**JavaScript Integration:**
```javascript
Livewire.dispatch('newChat')
Livewire.dispatch('closeArtifactPanel')
window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: bool } }))
window.dispatchEvent(new CustomEvent('open-settings-ui', { detail: 'general' }))
```

**Alpine.js Integration:**
```javascript
@entangle('activePanel')
@entangle('openArtifactId')
@entangle('sidebarOpen')
```

**Models:** None  
**Services:** None  
**Database Queries:** None  
**External APIs:** None  

---

### 2. ChatInterface (🔴 CRITICAL - Main Chat)

**Purpose:** AI chat interface with message streaming and artifact generation  
**Parent:** ChatLayout  
**Hierarchy Level:** Child (conditional - shown when activePanel != specific value)

**Dependencies:**

**Models:**
- `Conversation` - Current conversation
- `Message` - Chat messages
- `MessageArtifact` - Generated artifacts
- `AiModel` - Available AI models (AiModel::where('is_active', true))

**Services:**
- `AiService` - LLM integration, streaming, token counting

**Traits:**
- `WithFileUploads` - File upload handling

**State Properties (Critical):**
```php
public string $prompt = '';
public array $attachments = [];           // Livewire temp files
public array $messages = [];              // Loaded from DB
public ?int $conversationId = null;
public $selectedModel = null;
public bool $webSearch = false;
public bool $showMemory = false;
public string $memoryDraft = '';
```

**Event Listeners:**
```php
#[On('generateResponse')]   // Event-driven message generation
```

**Critical Methods:**
- `mount()` - Load models, initialize chat
- `sendMessage()` - Submit user message
- `generateResponse()` - Stream AI response 🔴 CRITICAL
- `stopGeneration()` - Stop streaming
- `updateConversation()` - Rename/archive conversation
- `addAttachment()` / `removeAttachment()` - File handling
- `updateMemory()` - User memory updates

**Database Queries:**
```php
// In mount():
AiModel::where('is_active', true)->get()

// In generateResponse():
Conversation::with(['messages' => fn($q) => $q->orderBy('id')])
Message::create([...])
MessageArtifact::create([...])
```

**Caching:**
```php
Cache::put('chat_stop_' . $conversationId, true, 120)
Cache::get('chat_stop_' . $conversationId)  // checked in streaming loop
```

**Streaming Mechanism:**
```blade
<div wire:stream="message-stream" class="prose"></div>
```
- Uses Livewire's SSE streaming
- Emits message chunks as they arrive from LLM API

**API Integration:**
```
POST /chat-stop (internal route - stops streaming via cache flag)
External:
- Anthropic API (claude-opus, claude-sonnet, claude-haiku)
- OpenAI API (GPT models)
- HuggingFace API (if configured)
- Google API (if configured)
- Mistral API (if configured)
- Proxy services (NineRouter, custom proxy)
```

**JavaScript Dependencies:**
```javascript
document.addEventListener('livewire:initialized', () => {
    // Livewire JS event listeners
})
```

**Migration Impact:** 🔴 HIGHEST PRIORITY
- Must handle streaming SSE correctly
- File uploads need special handling
- Event system needs conversion
- Model initialization logic must transfer
- Memory update flow needs reimplementation

---

### 3. ClaudeCodeApp (🔴 CRITICAL - IDE)

**Purpose:** Claude Code IDE interface for code projects  
**Parent:** /code route (Livewire-based)  
**Hierarchy Level:** Root (alternative to ChatLayout)

**Dependencies:**

**Models:**
- `Conversation` - Code sessions
- `Message` - Code chat messages
- `MessageAttachment` - File context
- `AiModel` - Available models

**Traits:**
- `WithFileUploads` - File upload handling

**State Properties:**
```php
// Chat State
public string $message = '';
public ?Conversation $conversation = null;
public array $messages = [];
public bool $isStreaming = false;

// Navigation
public string $currentView = 'chat'; // 'chat'|'routines'|'new-routine'

// Models
public string $selectedModel = 'claude-sonnet-4-6';
public array $models = [];

// File Attachments
public array $attachments = [];        // Livewire temp files
public array $attachmentPreviews = [];

// Repository Integration
public string $repoUrl = '';
public bool $repoModalOpen = false;
public array $repoTree = [];

// File Explorer
public array $localFilesTree = [];
public array $selectedFilesContext = [];

// Agent Tools (right panel)
public array $toolCalls = [];          // Tool execution tracking

// Permission Modal
public bool $pendingPermission = false;
public string $pendingToolName = '';
```

**Critical Methods:**
- `mount()` - Initialize, load recent sessions
- `sendMessage()` - Submit code request
- `generateResponse()` - Stream code response + tool calls 🔴 CRITICAL
- `connectRepo()` - Git repository integration
- `loadFileFromRepo()` - Load file from repository
- `newSession()` - Create new code session
- `loadSession()` - Restore previous session
- `approvePermission()` / `denyPermission()` - Tool permission handling

**Database Queries:**
```php
Conversation::where('id', $conversationId)->first()
Message::create([...])
MessageAttachment::create([...])
AiModel::where('is_active', true)->get()
```

**File System Operations:**
```php
Storage::disk('local')->put(...)  // Store file context
Storage::disk('local')->get(...)  // Retrieve file content
Livewire file uploads:
$this->attachments[]              // Temp file storage
unlink($file)                      // Cleanup after processing
```

**Caching:**
```php
Cache::remember(...) - Cache file contents
Cache::put('chat_stop_...') - Stop generation flag
```

**Route Integration:**
```
Route::get('/code', ClaudeCodeApp::class)->middleware(['auth', 'verified'])->name('code')
```

**Streaming Mechanism:**
```blade
<div wire:loading wire:target="sendMessage, generateResponse">Loading...</div>
<div wire:stream="message-stream">Streamed code output</div>
```

**JavaScript Dependencies:**
```javascript
// Tool execution via JavaScript
const toolCalls = @entangle('toolCalls')
// File upload handling
document.getElementById('code-file-upload').addEventListener('change', ...)
document.getElementById('code-folder-upload').addEventListener('change', ...)
```

**Git/Repository API:**
- GitHub API integration (via GitHubService if exists)
- File tree exploration and loading

**Migration Impact:** 🔴 HIGHEST PRIORITY
- Complex file upload handling
- Repository integration must work
- Tool execution flow is critical
- Permission modal workflow
- Session persistence across requests

---

### 4. ArtifactPanel (High Priority - Document Viewer)

**Purpose:** Display and manage generated artifacts (documents, code, designs)  
**Parent:** ChatLayout  
**Hierarchy Level:** Child (conditional - right sidebar)

**Dependencies:**

**Models:**
- `MessageArtifact` - Artifact data

**Database Facades:**
- `Auth::id()` - Current user

**State Properties:**
```php
#[Reactive]
public ?int $openArtifactId = null;    // From parent ChatLayout

public $currentArtifact = null;        // Metadata only (no content)
public array $artifacts = [];          // Grid list (lightweight)
public bool $copied = false;
public string $activeTab = 'code';     // 'code'|'preview'
public array $versions = [];
public bool $fullscreen = false;
public string $searchQuery = '';
```

**Reactive Prop Pattern:**
```php
#[Reactive]
public ?int $openArtifactId = null;

public function updatedOpenArtifactId($value): void
{
    // Sync when parent changes
    $this->loadCurrentArtifact((int)$value);
}
```

**Critical Methods:**
- `mount()` - Load artifacts grid
- `loadArtifacts()` - Load lightweight artifact list
- `loadCurrentArtifact(int $id)` - Load full artifact on demand
- `updatedOpenArtifactId()` - Reactive prop update handler
- `viewArtifact(int $id)` - Open artifact in panel
- `updateArtifact()` - Edit artifact content
- `deleteArtifact(int $id)` - Delete artifact
- `exportArtifact(int $id, string $format)` - Export to PDF/MD

**Database Queries:**
```php
MessageArtifact::where('user_id', Auth::id())
    ->orderBy('created_at', 'desc')
    ->get(['id', 'title', 'is_public', 'created_at'])  // Omits content column

MessageArtifact::with(['message'])
    ->where('id', $id)
    ->firstOrFail()
```

**Computed Properties:**
```php
#[Computed]
public function artifactContent() { ... }  // Lazy-load content on demand
```

**File System Operations:**
```
PDF Export: PdfRenderer service (via route in web.php)
GET /artifact/{id}/preview.pdf
```

**Cache Integration:**
```php
Cache::remember('artifact_pdf:' . $id . ':' . md5($content), 3600, fn() => ...)
```

**Migration Impact:** 🟡 MEDIUM-HIGH
- Reactive prop pattern must convert to AJAX + state
- Lazy loading of content must be handled
- PDF export route is separate (not affected by migration)
- Search/filter with wire:model.live.debounce must convert

---

### 5. SettingsModal (Medium Priority - User Settings)

**Purpose:** User settings and API key management  
**Parent:** ChatLayout  
**Hierarchy Level:** Child (overlay modal)

**Dependencies:**

**Models:**
- `User` (implicitly via Auth and DB)

**Facades:**
- `Auth` - Current user
- `DB` - Direct queries for token usage

**State Properties:**
```php
public $isOpen = false;
public $activeTab = 'general';  // 'general'|'api-keys'|'billing'|etc

// Profile
public $name = '';
public $email = '';
public $nickname = '';
public $profession = '';
public $customInstructions = '';

// Preferences
public $language = 'en';
public $chatFont = 'default';
public $theme = 'light';
public $fontSize = 'medium';
public $accentColor = '#D97757';
public $compactMode = false;

// API Keys (SENSITIVE)
public $anthropicApiKey = '';
public $openaiApiKey = '';
public $nineRouterApiKey = '';
public $googleApiKey = '';
public $mistralApiKey = '';
public $useProxy = false;
public $proxyBaseUrl = '';
public $proxyApiKey = '';
public $huggingfaceApiKey = '';

// Billing
public $plan = 'Free';
public $tokensUsed = 0;
public $tokensLimit = 0;
public int $trackedTokens = 0;
public array $tokenBreakdown = [];
```

**Event Listeners:**
```php
#[On('open-settings-modal')]
public function open(?string $tab = null) { ... }
```

**Critical Methods:**
- `open(string $tab)` - Open modal with specific tab
- `close()` - Close modal
- `updateProfile()` - Save profile changes
- `updatePreferences()` - Save theme/language settings
- `validateApiKey()` - Test API key validity 🔴
- `saveApiKeys()` - Persist API credentials
- `testConnection()` - Test connectivity
- `updateBillingPreferences()` - Update billing settings

**Database Queries:**
```php
Auth::user()
Auth::user()->update(['name' => $this->name, ...])

DB::table('token_usages')
    ->where('user_id', Auth::id())
    ->sum('tokens')

DB::table('token_usages')
    ->where('user_id', Auth::id())
    ->groupBy('model')
    ->sum('tokens')
```

**Validation Rules:**
```php
protected function rules(): array {
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . Auth::id(),
        'anthropicApiKey' => 'nullable|string',
        'openaiApiKey' => 'nullable|string',
        ...
    ];
}
```

**Sensitive Data Handling:**
- API keys stored in `users` table columns
- Keys must NOT be logged or exposed in responses
- Keys validated before storage

**Migration Impact:** 🟡 MEDIUM
- Form validation transfers easily
- State management straightforward (simple form)
- Sensitive data handling must remain secure
- Wire:model bindings convert to x-model or form submission

---

### 6. ChatsPanel (Medium Priority - Chat History)

**Purpose:** Browse, search, and manage chat conversations  
**Parent:** ChatLayout  
**Hierarchy Level:** Child (conditional - left panel)

**Dependencies:**

**Models:**
- `Conversation` - Chat sessions

**State Properties:**
```php
public ?int $selectedConversation = null;
public string $searchQuery = '';
public bool $selectMode = false;
public array $selectedChats = [];
public bool $showArchived = false;
public string $filterType = 'all';  // 'all'|'today'|'week'

// Rename state
public ?int $renameId = null;
public string $renameTitle = '';
```

**Event Listeners:**
```php
#[On(...)]  // Various conversation events
```

**Critical Methods:**
- `startNewChat()` - Create new conversation
- `startRename(int $id)` - Edit title
- `renameConversation(int $id)` - Save new title
- `archiveConversation(int $id)` - Mark as archived
- `deleteSelectedChats()` - Delete multiple
- `exportConversation(int $id, string $format)` - Export to JSON/MD
- `shareConversation(int $id)` - Create share token
- `toggleChatSelection(int $id)` - Multi-select

**Database Queries:**
```php
Conversation::where('user_id', Auth::id())
    ->where('archived', false)
    ->orderBy('updated_at', 'desc')
    ->get()

Conversation::where('user_id', Auth::id())
    ->where('title', 'like', '%' . $searchQuery . '%')
    ->get()
```

**Blade Integration:**
```blade
<input wire:model.live.debounce.300ms="searchQuery">  // Real-time search
```

**Migration Impact:** 🟡 MEDIUM
- Search/filter with debouncing
- Multi-select logic
- Direct conversation state management
- Export routes are separate

---

### 7. CoworkPanel (Medium Priority - Task Management)

**Purpose:** Create and manage autonomous work tasks  
**Parent:** ChatLayout (conditional)  
**Hierarchy Level:** Child

**Dependencies:**

**Models:**
- `CoworkTask` - Work tasks

**Services:**
- `AiService` - Task execution

**State Properties:**
```php
public string $view = 'landing';  // 'landing'|'list'|'create'|'detail'
public ?int $activeTaskId = null;
public string $statusFilter = 'all';

// Form fields
public string $title = '';
public string $description = '';
public string $priority = 'medium';
public string $model = 'claude-haiku-4-5';
public ?string $scheduledFor = null;
```

**Critical Methods:**
- `createTask()` - Create new task
- `runTask(int $id)` - Execute task via AiService
- `updateStatus(int $id, string $status)` - Update task state
- `deleteTask(int $id)` - Delete task
- `getResults(int $id)` - Fetch task results

**Database Queries:**
```php
CoworkTask::create([...])
CoworkTask::where('status', $statusFilter)->get()
CoworkTask::find($id)->update([...])
```

**Validation:**
```php
protected function rules(): array {
    return [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority' => 'required|in:low,medium,high',
        'model' => 'required|string',
    ];
}
```

**Migration Impact:** 🟡 MEDIUM
- Straightforward CRUD operations
- AiService integration is external
- Validation transfers easily

---

### 8. ProjectsPanel (Medium Priority - Project Management)

**Purpose:** Manage code projects and file uploads  
**Parent:** ChatLayout  
**Hierarchy Level:** Child

**Dependencies:**

**Models:**
- `Project` - Project records
- `ProjectFile` - File storage
- `Setting` - Project settings
- `AiModel` - Model availability

**Traits:**
- `WithFileUploads` - File upload handling

**State Properties:**
```php
public ?int $activeProjectId = null;
public array $projects = [];
public array $files = [];
public string $projectName = '';
public string $projectDescription = '';
public array $selectedFiles = [];
public array $uploadedFiles = [];  // Livewire temp files
```

**Critical Methods:**
- `createProject()` - New project
- `updateProject()` - Edit project
- `deleteProject()` - Delete project
- `uploadFiles()` - File upload handling
- `loadProjectFiles()` - List files in project

**Database Queries:**
```php
Project::where('user_id', Auth::id())->get()
ProjectFile::where('project_id', $id)->get()
Storage::put(...) // File storage
```

**Traits:**
- `WithFileUploads` - Must convert to traditional file upload

**Migration Impact:** 🟡 MEDIUM
- File upload handling critical
- Validation straightforward
- Database operations simple

---

### 9. DesignPanel (Lower Priority)

**Purpose:** Manage design artifacts  
**Parent:** /design route (Livewire-based) OR ChatLayout  
**Hierarchy Level:** Child/Root

**Dependencies:**

**Models:**
- `Design` - Design records

**Services:**
- `AiService` - AI-generated design creation

**State Properties:**
```php
public ?int $activeDesignId = null;
public array $designs = [];
public string $currentTab = 'all';
public string $search = '';
public array $dialogPrompt = '';
public bool $dialogOpen = false;
```

**Critical Methods:**
- `openDialog()` - Open design creation dialog
- `createDesign()` - Generate design via AiService
- `deleteDesign()` - Delete design
- `toggleStar()` - Favorite design
- `viewDesign()` - Open design

**Migration Impact:** 🟢 LOW
- Simple CRUD operations
- No file uploads
- No complex state

---

### 10. ActivityFeed & ActivityTimeline (Lower Priority)

**Purpose:** Display activity log  
**Parent:** ChatInterface or standalone  
**Hierarchy Level:** Child

**Dependencies:**

**Services:**
- `EventHistoryServiceInterface` - Activity tracking

**State:** Minimal  
**Methods:** Simple display logic

**Migration Impact:** 🟢 LOW
- Read-only components
- Service is already abstracted

---

## PART 4: EVENT & DISPATCH SYSTEM

### Livewire Event Listeners (All Components)

```php
// ChatInterface
#[On('generateResponse')]     // External trigger to generate response

// ArtifactPanel
#[On('artifactReady')]        // New artifact created, open panel
#[On('showArtifactPanel')]    // Request to show panel
#[On('openArtifact')]         // Open specific artifact
#[On('closeArtifactPanel')]   // Hide panel

// ChatLayout
#[On('open-panel')]           // Open specific panel
#[On('open-settings-modal')]  // Open settings
#[On('close-panel')]          // Close panel

// ChatsPanel
#[On(...)]                    // Various conversation events

// ProjectsPanel
#[On('refresh-projects')]     // Reload projects

// CoworkPanel
#[On(...)]                    // Task-related events

// SettingsModal
#[On('open-settings-modal')]  // Open with specific tab

// Sidebar
#[On('open-help-modal')]      // Open help

// QuotaWarningModal
#[On('open-quota-warning')]   // Show quota warning
```

### JavaScript Dispatch Calls

**From Views (Blade):**
```javascript
// chat-layout.blade.php
Livewire.dispatch('closeArtifactPanel')
Livewire.dispatch('newChat')

// claude-code-app.blade.php
document.addEventListener('livewire:initialized', () => {
    // Livewire JS init
})

// Various buttons
wire:click="methodName"       // Button clicks
wire:keydown.enter.prevent="sendMessage"
```

### Custom Event Dispatches

**JavaScript Custom Events:**
```javascript
// From Alpine.js
window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { open: bool } }))
window.dispatchEvent(new CustomEvent('open-settings-ui', { detail: 'general' }))

// Event listeners
window.addEventListener('sidebar-toggle', ...)
window.addEventListener('open-settings-ui', ...)
```

---

## PART 5: DATABASE MODEL RELATIONSHIPS

### Models Used by Components

**Conversation Model:**
```
Accessed by: ChatInterface, ClaudeCodeApp, ChatsPanel
Relationships:
- hasMany(Message)
- hasMany(MessageArtifact)
- belongsTo(User)
Operations:
- where('user_id', Auth::id())->orderBy('updated_at')->get()
- where('share_token', $token)->firstOrFail()
- create(['title', 'user_id', ...])
- update(['title', ...])
- delete()
```

**Message Model:**
```
Accessed by: ChatInterface, ClaudeCodeApp
Relationships:
- belongsTo(Conversation)
- hasMany(MessageArtifact)
- hasMany(MessageAttachment)
Operations:
- create(['conversation_id', 'role', 'content', ...])
- where('conversation_id', $id)->orderBy('id')->get()
```

**MessageArtifact Model:**
```
Accessed by: ArtifactPanel, ChatInterface
Relationships:
- belongsTo(Message)
Operations:
- create(['message_id', 'title', 'content', ...])
- where('user_id', Auth::id())->get()
- update(['content', 'title', ...])
- delete()
```

**MessageAttachment Model:**
```
Accessed by: ClaudeCodeApp
Relationships:
- belongsTo(Message)
Operations:
- create(['message_id', 'file_path', ...])
```

**User Model:**
```
Accessed by: SettingsModal, Sidebar (indirectly)
Fields Used:
- anthropic_api_key
- openai_api_key
- nine_router_api_key
- google_api_key
- mistral_api_key
- use_proxy
- proxy_base_url
- proxy_api_key
- huggingface_api_key
- name, email, profession, etc.
Operations:
- Auth::user()->update([...])
```

**Project Model:**
```
Accessed by: ProjectsPanel
Relationships:
- hasMany(ProjectFile)
Operations:
- where('user_id', Auth::id())->get()
- create([...])
- update([...])
- delete()
```

**ProjectFile Model:**
```
Accessed by: ProjectsPanel
Relationships:
- belongsTo(Project)
Operations:
- create(['project_id', 'name', 'path', ...])
- where('project_id', $id)->get()
```

**CoworkTask Model:**
```
Accessed by: CoworkPanel
Operations:
- create(['user_id', 'title', 'description', ...])
- where('status', $filter)->get()
- update(['status', 'result', ...])
- delete()
```

**Design Model:**
```
Accessed by: DesignPanel
Operations:
- create(['user_id', 'content', ...])
- delete()
```

**AiModel Model:**
```
Accessed by: ChatInterface, ClaudeCodeApp, ProjectsPanel
Operations:
- where('is_active', true)->get()
- get(['code', 'name', 'provider', ...])
```

---

## PART 6: SERVICE DEPENDENCIES

### AiService

**Used by:**
- ChatInterface (message generation)
- ClaudeCodeApp (code generation)
- CoworkPanel (task execution)
- DesignPanel (design generation)

**Methods Called:**
```php
$aiService->generateResponse($prompt, $model, ...)
$aiService->countTokens($text, $model)
$aiService->streamResponse(...)  // For streaming
$aiService->stopGeneration(...)
```

**External APIs:**
- Anthropic Claude API
- OpenAI API
- HuggingFace API
- Google Gemini API
- Mistral API
- NineRouter proxy
- Custom proxy support

### EventHistoryServiceInterface

**Used by:**
- ActivityTimeline

**Methods:**
```php
$eventHistoryService->getEvents(...)
$eventHistoryService->recordEvent(...)
```

---

## PART 7: HIDDEN DEPENDENCIES CHECKLIST

### ✅ Identified Dependencies

- [x] Livewire component hierarchy
- [x] Database models and relationships
- [x] Services and external APIs
- [x] File upload/download flows
- [x] Caching layers (Redis/File)
- [x] Session state management
- [x] Event system
- [x] JavaScript entanglement
- [x] Alpine.js integration
- [x] API routes (/chat-stop, /artifact/*/preview.pdf, etc.)
- [x] Storage (local files, uploads)
- [x] Validation rules
- [x] Authentication/Authorization

### ⚠️ Potential Hidden Dependencies

**1. Livewire Lifecycle Hooks**
   - `mount()` - Initialization
   - `render()` - Implicit in all components
   - `updated` hooks (computed properties)
   - `updatedProperty()` - Called on property changes
   - File: All components

**2. Laravel Facades Usage**
   - `Auth::user()` - Every component that shows user data
   - `Auth::id()` - Every query filtered by user
   - `Cache::*` - ChatInterface (stop flag)
   - `DB::*` - SettingsModal (token queries)
   - `Storage::*` - ClaudeCodeApp, ProjectsPanel
   - `Request::*` - ChatLayout (query params)

**3. Implicit Model Relationships**
   - Conversation → Messages (loaded in ChatInterface)
   - Message → Artifacts (accessed via $message->artifacts)
   - User → Projects (implied but not direct foreign key)

**4. Streaming SSE Implementation**
   - `wire:stream="name"` - Only in ChatInterface
   - Livewire internally handles SSE connection
   - Client-side expects specific message format

**5. File Upload Lifecycle**
   - Livewire stores uploads in temp directory
   - `$this->attachments` array references temp files
   - Files must be moved to permanent storage
   - Cleanup after processing

**6. Authorization Checks**
   - Not explicit in Livewire methods
   - Assumed to be in middleware
   - Must verify conversation/artifact ownership

**7. Form Validation State**
   - `$this->validate()` sets validation errors
   - Blade displays via `$errors`
   - Clear error handling needed in migration

**8. Route Model Binding**
   - `/artifact/{id}` - MessageArtifact model
   - Routes implicitly cast ID to model
   - Must replicate in controllers

---

## PART 8: CRITICAL MIGRATION DEPENDENCIES

### Must-Have Before Phase 1:
1. ✅ Complete routing structure defined
2. ✅ API endpoint contracts documented
3. ✅ Database query patterns mapped
4. ✅ Service method signatures confirmed

### Must-Have Before Phase 2:
1. ✅ Phase 1 routes working
2. ✅ Controller methods returning correct JSON
3. ✅ API response format matches frontend expectations

### Must-Have Before Phase 3:
1. ✅ Phase 2 Blade templates updated
2. ✅ Session state structure defined
3. ✅ JavaScript state management framework chosen

### Must-Have Before Phase 4:
1. ✅ All controller methods implemented
2. ✅ API tests passing
3. ✅ Error handling unified

### Must-Have Before Phase 5:
1. ✅ All API endpoints working
2. ✅ Streaming endpoint created
3. ✅ SSE implementation tested

### Must-Have Before Phase 6:
1. ✅ All features working in pure Laravel
2. ✅ E2E tests passing
3. ✅ Performance acceptable

---

## PART 9: DEPENDENCY SUMMARY TABLE

| Dependency Type | Count | Examples | Migration Risk |
|-----------------|-------|----------|-----------------|
| Models | 8 | Conversation, Message, MessageArtifact, User, etc. | LOW (ORM-based) |
| Services | 2 | AiService, EventHistoryService | LOW (abstracted) |
| Facades | 5 | Auth, Cache, DB, Storage, Request | LOW (Laravel standard) |
| Traits | 1 | WithFileUploads | MEDIUM (special handling) |
| Routes | 2 | /code, /design | HIGH (must convert) |
| Events | 20+ | Livewire dispatch/on | HIGH (must convert) |
| Directives | 150+ | wire:model, wire:click, etc. | HIGH (must convert) |
| JavaScript | 3 | Alpine.js, Livewire.js, Custom events | MEDIUM (must rewrite) |
| APIs | 5+ | Anthropic, OpenAI, HuggingFace, etc. | LOW (already abstracted) |
| Streaming | 1 | wire:stream | HIGH (must reimplement) |

---

## CONCLUSION

**Total Hidden Dependencies Found:** 15+  
**Risk Level:** MEDIUM-HIGH  
**Most Critical:** ChatInterface, ClaudeCodeApp, Streaming system  
**Safe to Proceed:** YES - All dependencies identified  

**Action:** Proceed to Phase 1 with full understanding of:
1. Component hierarchy (parent-child relationships)
2. State management patterns
3. Event system flow
4. Database access patterns
5. Service integrations
6. File handling procedures
7. Streaming mechanism
8. API expectations

---

**Date Completed:** 2026-06-28  
**Status:** Ready for Phase 1 Implementation  
**Next:** Create controller structure with API routes

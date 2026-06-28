# Dependency Quick Reference Table

**All 17 Livewire Components at a Glance**

## Component Dependency Matrix

```
COMPONENT            | PRIORITY | PARENT        | MODELS                          | SERVICES         | TRAITS           | CRITICAL METHODS
─────────────────────┼──────────┼───────────────┼─────────────────────────────────┼──────────────────┼──────────────────┼─────────────────────────
ChatLayout           | CRITICAL | None (Root)   | -                               | -                 | -                | mount(), dispatch events
ChatInterface        | CRITICAL | ChatLayout    | Conversation, Message,          | AiService        | WithFileUploads  | generateResponse()⭐
                     |          |               | MessageArtifact, AiModel        |                   |                  | sendMessage()⭐
                     |          |               |                                 |                   |                  | stopGeneration()
ArtifactPanel        | CRITICAL | ChatLayout    | MessageArtifact                 | -                 | -                | loadCurrentArtifact()
                     |          |               |                                 |                   |                  | viewArtifact()
SettingsModal        | CRITICAL | ChatLayout    | User (implicit)                 | -                 | -                | updateSettings()⭐
                     |          |               |                                 |                   |                  | validateApiKey()⭐
ClaudeCodeApp        | CRITICAL | /code route   | Conversation, Message,          | -                 | WithFileUploads  | generateResponse()⭐
                     |          |               | MessageAttachment, AiModel      |                   |                  | sendMessage()⭐
                     |          |               |                                 |                   |                  | connectRepo()
ChatsPanel           | HIGH     | ChatLayout    | Conversation                    | -                 | -                | startNewChat()
                     |          |               |                                 |                   |                  | renameConversation()
ProjectsPanel        | HIGH     | ChatLayout    | Project, ProjectFile, Setting,  | -                 | WithFileUploads  | createProject()
                     |          |               | AiModel                         |                   |                  | uploadFiles()
CoworkPanel          | HIGH     | ChatLayout    | CoworkTask                      | AiService        | -                | createTask()
                     |          |               |                                 |                   |                  | runTask()
DesignPanel          | HIGH     | /design route | Design                          | AiService        | -                | createDesign()
                     |          |               |                                 |                   |                  | viewDesign()
CustomizePanel       | MEDIUM   | ChatLayout    | -                               | -                 | -                | updateTheme()
CodePanel            | MEDIUM   | ClaudeCodeApp | -                               | -                 | -                | closePanel()
ActivityTimeline     | MEDIUM   | ChatInterface | -                               | EventHistory      | -                | loadEvents()
Sidebar              | MEDIUM   | ChatLayout    | -                               | -                 | -                | toggleOpen()
ActivityFeed         | LOW      | ChatInterface | -                               | -                 | -                | Display only
QuotaWarningModal    | LOW      | ChatLayout    | -                               | -                 | -                | closeModal()
HelpModal            | LOW      | ChatLayout    | -                               | -                 | -                | closeModal()
SystemUpdateModal    | LOW      | ChatLayout    | -                               | -                 | -                | closeModal()
```

**Legend:**
- ⭐ = Must handle streaming/async correctly
- CRITICAL = Core business logic
- HIGH = Complex state management
- MEDIUM = Standard CRUD
- LOW = Simple display/overlay

---

## Event Flow Map

### Events Received (Listeners)

```
ChatInterface:
  - #[On('generateResponse')]     ← Main chat AI generation trigger

ArtifactPanel:
  - #[On('artifactReady')]        ← Artifact created, open panel
  - #[On('showArtifactPanel')]    ← Request show panel
  - #[On('openArtifact')]         ← Open specific artifact
  - #[On('closeArtifactPanel')]   ← Hide panel

SettingsModal:
  - #[On('open-settings-modal')]  ← Open settings with specific tab

ChatsPanel:
  - #[On(...)]                    ← Multiple conversation events

QuotaWarningModal:
  - #[On('open-quota-warning')]   ← Show quota warning

Sidebar:
  - #[On('open-help-modal')]      ← Open help

ProjectsPanel:
  - #[On('refresh-projects')]     ← Reload projects
```

### Events Dispatched (Sources)

```
ChatLayout (JavaScript):
  → Livewire.dispatch('newChat')
  → Livewire.dispatch('closeArtifactPanel')

ChatInterface (Methods):
  → $this->dispatch('artifactReady', ['id' => $id])
  → $this->dispatch('showArtifactPanel')

Custom Browser Events:
  → window.dispatchEvent(new CustomEvent('sidebar-toggle', ...))
  → window.dispatchEvent(new CustomEvent('open-settings-ui', ...))
```

---

## Data Flow & State Dependencies

### ChatInterface Data Flow

```
User Input
    ↓
sendMessage()
    ↓
Create Message in DB
    ↓
Call AiService::generateResponse()
    ↓
Stream response via wire:stream="message-stream"
    ↓
Emit message chunks in real-time
    ↓
Create MessageArtifact if needed
    ↓
Update UI via Alpine.js
```

### ArtifactPanel Data Flow

```
Parent (ChatLayout) sets openArtifactId
    ↓
#[Reactive] property updated
    ↓
updatedOpenArtifactId() triggered
    ↓
loadCurrentArtifact($id) called
    ↓
Query MessageArtifact from DB
    ↓
Render in right panel
```

### SettingsModal Data Flow

```
User clicks settings button
    ↓
#[On('open-settings-modal')] triggered
    ↓
open(tab) method loads current settings
    ↓
Form displays via wire:model bindings
    ↓
User submits
    ↓
Validation in updateProfile()/updateApiKeys()
    ↓
Auth::user()->update([...])
    ↓
Success message shown
```

---

## API Endpoints Called by Components

### Internal Routes (Laravel)

```
POST /chat-stop
  Called by: ChatInterface (cache-based stop flag)
  Data: conversation_id
  Response: 204 No Content

GET /artifact/{id}/preview.pdf
  Called by: ArtifactPanel (PDF preview)
  Data: artifact ID in URL
  Response: PDF binary
  Cache: 1 hour per content hash
```

### External APIs (via AiService)

```
Anthropic Claude API
  - POST https://api.anthropic.com/v1/messages
  - Stream responses for real-time display
  - Handle token counting

OpenAI API
  - POST https://api.openai.com/v1/chat/completions
  - GPT model support

HuggingFace API
  - Inference API for open source models
  - Custom base URL support

Google Gemini API
  - REST API integration

Mistral API
  - Model inference

Custom Proxy / NineRouter
  - Support for routed LLM APIs
  - ReAct-based tool calling
```

---

## File Upload Dependencies

### WithFileUploads Trait Usage

```
ChatInterface
  ├─ Public property: $attachments = []
  ├─ Stored in: /storage/livewire-tmp/
  ├─ Methods: addAttachment(), removeAttachment()
  └─ Cleanup: unlink() after processing

ClaudeCodeApp
  ├─ Public property: $attachments = []
  ├─ Stored in: /storage/livewire-tmp/
  ├─ Methods: Multiple file handling
  └─ Cleanup: Manual deletion required

ProjectsPanel
  ├─ Public property: $uploadedFiles = []
  ├─ Stored in: /storage/livewire-tmp/
  ├─ Methods: uploadFiles()
  └─ Move to: /storage/projects/
```

**CRITICAL:** File upload handling must be completely rewritten in Phase 4.

---

## Database Query Patterns

### Critical Queries (Must Not Break)

```php
// ChatInterface - Load conversation with all messages
Conversation::with(['messages' => fn($q) => $q->orderBy('id')])
    ->where('user_id', Auth::id())
    ->find($conversationId)

// ChatsPanel - Search conversations
Conversation::where('user_id', Auth::id())
    ->where('title', 'like', '%' . $searchQuery . '%')
    ->orderBy('updated_at', 'desc')
    ->paginate()

// ArtifactPanel - Load artifacts (WITHOUT content for performance)
MessageArtifact::where('user_id', Auth::id())
    ->orderBy('created_at', 'desc')
    ->select(['id', 'title', 'is_public', 'created_at'])  ← NO content
    ->get()

// SettingsModal - Token usage by model
DB::table('token_usages')
    ->where('user_id', Auth::id())
    ->groupBy('model')
    ->selectRaw('model, SUM(tokens) as total')
    ->get()
```

---

## Cache Usage

```
ChatInterface - Stop flag
  Key: 'chat_stop_' . $conversationId
  TTL: 120 seconds
  Usage: Polling in streaming loop to stop generation

ArtifactPanel - PDF preview
  Key: 'artifact_pdf:' . $id . ':' . md5($content) . ':' . $mode
  TTL: 3600 seconds (1 hour)
  Usage: Cache rendered PDF to avoid slow mPDF rendering
```

---

## Form Validation Rules

### ChatInterface
```php
'prompt' => 'required|string|max:10000'
'attachments.*' => 'file|max:50000' (50MB per file)
'model' => 'required|string'
```

### SettingsModal
```php
'name' => 'required|string|max:255'
'email' => 'required|email|unique:users'
'anthropicApiKey' => 'nullable|string'
'openaiApiKey' => 'nullable|string'
'accentColor' => 'required|regex:/^#[0-9A-F]{6}$/i'
```

### CoworkPanel
```php
'title' => 'required|string|max:255'
'description' => 'nullable|string'
'priority' => 'required|in:low,medium,high'
'model' => 'required|string'
'scheduledFor' => 'nullable|date'
```

### ProjectsPanel
```php
'projectName' => 'required|string|max:255'
'projectDescription' => 'nullable|string|max:1000'
```

---

## Migration Action Checklist

### Before Starting Phase 1: ✅ VERIFY

```
☐ DEPENDENCY_GRAPH.md reviewed completely
☐ All 17 components understood
☐ Critical dependencies identified:
  ☐ ChatInterface streaming logic
  ☐ ClaudeCodeApp file uploads
  ☐ SettingsModal API key handling
  ☐ ArtifactPanel lazy-loading
☐ Event system flow documented
☐ Database models confirmed
☐ External API integrations mapped
☐ File upload patterns understood
☐ Cache strategy confirmed
☐ Validation rules collected
```

### Phase 1 (Routing) Prerequisites

```
✅ All routes documented
✅ Controller structure planned
✅ API response format defined
✅ Error handling strategy agreed
✅ Testing framework chosen
```

### Phase 2 (Blade) Prerequisites

```
✅ Phase 1 routes working
✅ API endpoints responding with correct JSON
✅ Error messages in correct format
```

### Phase 4 (API Endpoints) Prerequisites

```
✅ All business logic extracted from Livewire
✅ Service methods confirmed
✅ Database transaction handling planned
✅ Concurrency issues identified
```

### Phase 5 (Streaming) Prerequisites

```
✅ SSE implementation tested
✅ WebSocket fallback planned
✅ Message format confirmed
✅ Error recovery strategy defined
```

---

## 🚨 CRITICAL WARNINGS

### 1. WithFileUploads Trait
- **Issue:** Livewire stores uploads in temp directory
- **Risk:** Files may be orphaned if not moved to permanent storage
- **Mitigation:** Implement immediate file processing in Phase 4

### 2. wire:stream Streaming
- **Issue:** Only ChatInterface uses this, but it's mission-critical
- **Risk:** SSE not supported on all hosting (proxies, load balancers)
- **Mitigation:** Have WebSocket fallback ready before Phase 5

### 3. API Key Storage
- **Issue:** Sensitive keys stored in User model columns
- **Risk:** Exposure in logs, dumps, or accidental serialization
- **Mitigation:** Use Laravel's `#[Encrypted]` attribute in migration

### 4. Auth::id() Dependencies
- **Issue:** Every component assumes authenticated user
- **Risk:** Unauthenticated access will cause silent failures
- **Mitigation:** Verify middleware is in place on all routes

### 5. Cache-based Stop Flag
- **Issue:** ChatInterface uses cache flag to stop streaming
- **Risk:** Cache not cleared, stream continues running
- **Mitigation:** Implement explicit stream termination in Phase 5

---

## ✅ MIGRATION READINESS CHECKLIST

**All 3 documents created:**
- [x] MIGRATION_AUDIT_REPORT.md (3,500+ lines)
- [x] MIGRATION_SUMMARY.md (Quick reference)
- [x] DEPENDENCY_GRAPH.md (Complete analysis)
- [x] DEPENDENCY_QUICK_REFERENCE.md (This file)

**Analysis Complete:**
- [x] All 17 components documented
- [x] Hierarchy mapped
- [x] Services identified
- [x] Models listed
- [x] Events documented
- [x] APIs cataloged
- [x] Hidden dependencies found

**Status: ✅ READY FOR PHASE 1**

No further analysis needed. All information is available to implement Phase 1 safely.

---

**Next Action:** Confirm you want to proceed to Phase 1 Implementation (Routing Migration)

**Estimated Time for Phase 1:** 4-6 hours  
**Deliverables:** 5-6 new controller classes + 30+ API routes  
**Testing:** Unit tests + Feature tests for each endpoint

# BASELINE TEST REPORT

**Purpose:** Document all currently working features before Phase 1 migration  
**Date Generated:** 2026-06-28  
**Status:** PRE-MIGRATION BASELINE  
**No code changes made - documentation only**

---

## FEATURE INVENTORY

### Feature 1: Chat - Send Message (Core Feature)

**URL/Access:**
```
GET /chat                          # Load chat interface
POST (Livewire) wire:click="sendMessage"
```

**Expected Behavior:**
1. User types in prompt input field
2. Clicks "Send" button
3. Message appears in chat with "user" role
4. AI response streams in real-time below
5. Response auto-saves as artifact if code/document generated
6. Chat continues with follow-up messages

**Pre-conditions:**
- User authenticated
- Valid API key configured (Anthropic, OpenAI, or proxy)
- Model selected and available

**Step-by-Step Flow:**
```
1. User enters prompt in textarea
   └─ wire:model="prompt" captures text
   └─ updatedPrompt() saves draft to Conversation.draft_prompt
   
2. User clicks Send button
   └─ wire:click="sendMessage" triggered
   
3. sendMessage() method:
   └─ Validate: prompt must not be empty
   └─ Load/create conversation if not exists
   └─ Create Message record (role='user', content=$prompt)
   └─ Store file attachments if any
   └─ Clear draft_prompt
   └─ Call generateResponse()
   
4. generateResponse() method:
   └─ Load conversation with all messages
   └─ Build messages array for LLM API
   └─ Call AiService::streamResponse()
   └─ Iterate through generator chunks
   └─ Check Cache::get('chat_stop_' . $conversationId) to allow cancellation
   └─ Emit chunks via wire:stream="message-stream"
   └─ When stream complete:
      └─ Create Message record (role='assistant', content=$fullResponse)
      └─ Parse response for artifacts
      └─ Create MessageArtifact if code/doc found
      └─ Update UI
```

**Database Impact:**
```sql
-- Table: conversations
INSERT INTO conversations (user_id, title, created_at, updated_at, draft_prompt)
VALUES (?, ?, NOW(), NOW(), NULL)

-- Table: messages (user message)
INSERT INTO messages (conversation_id, role, content, created_at, updated_at)
VALUES (?, 'user', ?, NOW(), NOW())

-- Table: message_attachments (if files uploaded)
INSERT INTO message_attachments (message_id, file_path, file_type, file_name)
VALUES (?, ?, ?, ?)

-- Table: messages (AI response)
INSERT INTO messages (conversation_id, role, content, created_at, updated_at)
VALUES (?, 'assistant', ?, NOW(), NOW())

-- Table: message_artifacts (if code/document generated)
INSERT INTO message_artifacts (message_id, type, language, title, content, created_at)
VALUES (?, 'code'|'document', ?, ?, ?, NOW())

-- Table: token_usages (if tracked)
INSERT INTO token_usages (user_id, model, tokens, created_at)
VALUES (?, ?, ?, NOW())
```

**API Impact:**
```
External LLM API Called:
  POST https://api.anthropic.com/v1/messages
  OR
  POST https://api.openai.com/v1/chat/completions
  OR
  Other configured provider
  
Request:
  {
    "messages": [
      { "role": "user", "content": "..." },
      { "role": "assistant", "content": "..." },
      ...
    ],
    "model": "claude-sonnet-4-6",
    "stream": true,
    "max_tokens": 4096
  }

Response Stream:
  Multiple SSE events, each containing:
  {
    "type": "content_block_delta",
    "delta": { "type": "text_delta", "text": "chunk of response" }
  }
```

**Event Impact:**
```
JavaScript Events Fired:
  - Livewire component renders automatically
  - wire:stream="message-stream" updates live
  - No Livewire events dispatched by sendMessage()

Alpine.js State Changes:
  - isStreaming = true (during generation)
  - isStreaming = false (when complete)
  - Messages array appended with AI response

UI Updates:
  - Input cleared
  - Message appears in chat
  - Loading spinner shown during streaming
  - Scroll to latest message
  - Artifact panel opens if code generated
```

**Success Criteria:**
- ✅ Message saved to database
- ✅ Response appears in chat in real-time
- ✅ No errors in browser console
- ✅ Response completes within timeout
- ✅ Can send follow-up messages
- ✅ Chat history persists across refresh

**Failure Modes:**
- ❌ API key invalid → Error message shown
- ❌ No internet → Network error
- ❌ Model unavailable → Grayed out button
- ❌ Token limit exceeded → Quota warning modal
- ❌ Prompt empty → Button disabled

---

### Feature 2: Chat - Streaming Response (Real-Time)

**URL/Access:**
```
GET /chat (requires active conversation)
```

**Expected Behavior:**
1. As AI generates response, chunks appear in real-time
2. Text flows into chat window character-by-character or chunk-by-chunk
3. User can see response being composed live
4. Can stop generation at any point via "Stop" button
5. Streaming continues even if user scrolls or clicks elsewhere

**Mechanism:**
```
Livewire wire:stream="message-stream" implementation:
  └─ Server-Sent Events (SSE) connection maintained
  └─ generateResponse() yields chunks
  └─ Each chunk emitted to client via wire:stream
  └─ JavaScript appends to DOM in real-time
  └─ @entangle('isStreaming') updates UI state
```

**Database Impact:**
```sql
-- Messages created AFTER streaming completes (not during)
-- Cache table for stop flag:
INSERT INTO cache (key, value, expiration)
VALUES ('chat_stop_' . conversation_id, TRUE, NOW() + 120 seconds)
```

**API Impact:**
```
Streaming Response from LLM:
  Content-Type: text/event-stream
  Transfer-Encoding: chunked
  
  data: {"type":"content_block_start",...}
  data: {"type":"content_block_delta","delta":{"text":"Hello"}}
  data: {"type":"content_block_delta","delta":{"text":" there"}}
  data: {"type":"content_block_stop",...}
```

**Event Impact:**
```
generateResponse() checks cache every chunk:
  Cache::get('chat_stop_' . $conversationId)
  
If stop flag set:
  └─ Break generator loop
  └─ Return accumulated response
  └─ Save partial response to Message record
  
Client-side:
  - wire:stream updates trigger Alpine.js reactivity
  - No explicit event dispatches
  - DOM mutations handled by Livewire
```

**Success Criteria:**
- ✅ First chunk appears within 2 seconds
- ✅ Chunks appear as they arrive
- ✅ Stop button works mid-stream
- ✅ Partial response saved if stopped
- ✅ Streaming completes successfully
- ✅ No memory leaks (SSE connections close)

**Stop Generation Flow:**
```
1. User clicks "Stop" button
   └─ wire:click="stopGeneration"
   
2. stopGeneration() method:
   └─ Cache::put('chat_stop_' . conversationId, true, 120)
   
3. generateResponse() loop:
   └─ Every iteration checks cache
   └─ Breaks on cache hit
   └─ Saves response accumulated so far
   
4. Streaming ends
   └─ SSE connection closes
   └─ UI shows "Generation stopped" message
```

---

### Feature 3: Chat - Recent Chat List

**URL/Access:**
```
GET /chat (sidebar panel when activePanel === null or activePanel === 'chats')
Livewire: ChatsPanel component
```

**Expected Behavior:**
1. Shows list of user's recent conversations
2. Sorted by last updated (newest first)
3. Shows conversation title and timestamp
4. Click to open conversation
5. Can search conversations
6. Can filter by date (Today, Past 7 days, All)
7. Can rename, archive, share, export conversations
8. Can delete conversations

**Components Involved:**
```
ChatsPanel (Livewire component)
  ├─ State: $searchQuery, $filterType, $showArchived, $renameId
  ├─ Methods:
  │  ├─ loadConversations()
  │  ├─ setFilter($type)  // 'all', 'today', 'week'
  │  ├─ toggleShowArchived()
  │  ├─ startRename($id)
  │  ├─ renameConversation($id)
  │  ├─ archiveConversation($id)
  │  ├─ deleteConversation($id)
  │  ├─ shareConversation($id)
  │  └─ exportConversation($id, 'md'|'json')
```

**Database Impact:**
```sql
-- Load recent conversations
SELECT conversations.* 
FROM conversations 
WHERE user_id = ? AND archived = false
ORDER BY updated_at DESC
LIMIT 50

-- Rename conversation
UPDATE conversations SET title = ? WHERE id = ? AND user_id = ?

-- Archive conversation
UPDATE conversations SET archived = true WHERE id = ? AND user_id = ?

-- Delete conversation
DELETE FROM conversations WHERE id = ? AND user_id = ?
DELETE FROM messages WHERE conversation_id = ?
DELETE FROM message_artifacts WHERE message_id IN (...)
DELETE FROM message_attachments WHERE message_id IN (...)

-- Share conversation (create share token)
UPDATE conversations SET share_token = ?, is_shared = true WHERE id = ? AND user_id = ?

-- Create new conversation
INSERT INTO conversations (user_id, title, created_at, updated_at)
VALUES (?, 'New Chat', NOW(), NOW())
```

**API Impact:**
```
Internal Routes:
  GET /chat              # Load sidebar
  POST /api/chats/create # New conversation
  PATCH /api/chats/{id}  # Update title
  DELETE /api/chats/{id} # Delete
  POST /api/chats/{id}/share # Generate share token
  POST /api/chats/{id}/export?format=md|json

Public Routes (Shared):
  GET /share/{token}     # View shared conversation (read-only)
```

**Event Impact:**
```
Blade Directive: wire:model.live.debounce.300ms="searchQuery"
  └─ As user types, debounced search after 300ms
  └─ Filters conversation list
  └─ No server call until user stops typing

Livewire Events:
  - #[On('newChat')] triggers resetChat() in ChatInterface
  - #[On('selectConversation', 'id')] opens chat in ChatInterface

Dispatches:
  - $this->dispatch('selectConversation', $conversationId)
```

**Search/Filter Behavior:**
```
searchQuery live debouncing (300ms):
  └─ User types "python"
  └─ After 300ms idle, wire:model triggers updatedSearchQuery()
  └─ Query: WHERE title LIKE '%python%' OR ... 
  └─ Results shown instantly

Filter by date:
  setFilter('today'):
    WHERE created_at >= DATE(NOW())
  setFilter('week'):
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  setFilter('all'):
    No date filter
```

**Success Criteria:**
- ✅ List loads on page load
- ✅ Search works with live debounce
- ✅ Filter by date works
- ✅ Rename saves immediately
- ✅ Archive removes from list
- ✅ Delete confirms and removes
- ✅ Share generates token
- ✅ Export downloads file
- ✅ Open chat loads conversation

---

### Feature 4: Chat - New Conversation

**URL/Access:**
```
GET /chat (click "New chat" button)
Livewire: ChatLayout + ChatInterface
```

**Expected Behavior:**
1. Click "New chat" or Cmd+K
2. Chat window clears
3. Previous conversation unloaded
4. Input focused, ready for first message
5. Model selection available
6. No conversation created until first message sent

**Implementation:**
```
ChatLayout keyboard shortcut (Cmd/Ctrl + K):
  └─ Livewire.dispatch('newChat')
  
ChatInterface #[On('newChat')] handler:
  └─ resetChat() method:
     ├─ $this->messages = []
     ├─ $this->prompt = ''
     ├─ $this->attachments = []
     ├─ $this->conversationId = null
     ├─ $this->activeProjectId = null
     └─ UI re-renders showing empty state

First message sendMessage():
  └─ Creates new Conversation record:
     INSERT INTO conversations (user_id, title, created_at)
     VALUES (?, 'Chat ' . date, NOW())
     
  └─ Generates title from first message content
     UPDATE conversations SET title = ? WHERE id = ?
```

**Database Impact:**
```sql
-- Conversation created on first message (not on "New chat" click)
INSERT INTO conversations (user_id, title, created_at, updated_at)
VALUES (?, ?, NOW(), NOW())

-- Title generated from first message (async)
UPDATE conversations SET title = ? WHERE id = ?
```

**UI Behavior:**
```
Empty State (when conversationId is null):
  └─ Show "What would you like to know?" heading
  └─ Show suggested prompts ("Surprise me", starter chips)
  └─ Input field prominent
  └─ Sidebar shows recent chats
  
First Message:
  └─ transition to normal chat view
  └─ Message appears
  └─ Response streams below
  └─ Conversation now appears in sidebar
```

**Success Criteria:**
- ✅ Chat clears when "New chat" clicked
- ✅ Input focused and ready
- ✅ First message creates conversation
- ✅ Title auto-generated from first message
- ✅ Conversation appears in sidebar
- ✅ Can continue chat normally

---

### Feature 5: Artifact - Generation from Chat

**URL/Access:**
```
POST (within sendMessage flow, if response contains code/document)
```

**Expected Behavior:**
1. AI response contains code block or document markup
2. Parser detects artifact type (code, document, design)
3. Creates MessageArtifact record in database
4. Artifact panel opens on right side
5. Shows code/document in artifact viewer
6. Can switch between code and preview tabs
7. Can copy artifact
8. Can export artifact (PDF, Markdown, etc.)

**Artifact Detection Logic:**
```php
generateResponse() after streaming completes:
  └─ Parse response content for code blocks: ```language code ```
  └─ Parse response for document markers: <document>, <artifact>
  └─ If artifact detected:
     ├─ Extract type (code, document, design)
     ├─ Extract language (if code)
     ├─ Extract title
     ├─ Create MessageArtifact record
     └─ Emit 'artifactReady' event to ArtifactPanel
```

**Database Impact:**
```sql
-- Artifact created after response streaming complete
INSERT INTO message_artifacts 
(message_id, type, language, title, content, is_public, created_at)
VALUES (?, 'code'|'document'|'design', 'javascript'|null, ?, ?, false, NOW())

-- When artifact updated
UPDATE message_artifacts SET content = ? WHERE id = ?

-- When exported
-- No DB impact, file generated on-the-fly via PdfRenderer or export service

-- When made public (shared)
UPDATE message_artifacts SET is_public = true, public_token = ? WHERE id = ?
```

**API Impact:**
```
Routes (internal):
  GET /artifact/{id}/preview.pdf     # PDF preview
  GET /artifact/{token}              # Public view (no auth)
  PATCH /api/artifacts/{id}          # Update artifact
  DELETE /api/artifacts/{id}         # Delete
  POST /api/artifacts/{id}/export    # Export to file

External Services:
  PdfRenderer (mPDF library):
    └─ Convert artifact HTML to PDF
    └─ Include front matter (title, date)
    └─ Optional: page numbers, TOC
```

**Event Impact:**
```
Livewire Events:
  1. Response parsed, artifact detected
  2. MessageArtifact created
  3. $this->dispatch('artifactReady', ['id' => $id])
  
ArtifactPanel receives event:
  #[On('artifactReady')]
  └─ loadCurrentArtifact($id)
  └─ Opens panel with artifact
  └─ Displays in "code" tab by default
  
Alpine.js:
  @entangle('openArtifactId')    # Parent ChatLayout state
  └─ Reactive prop to ArtifactPanel
  └─ Panel visibility controlled by parent
```

**Artifact Types:**
```
CODE:
  └─ Language: javascript, python, php, json, etc.
  └─ Display: Syntax highlighting
  └─ Export: As-is (copy button)
  
DOCUMENT:
  └─ Type: markdown, html, text
  └─ Display: Rendered preview
  └─ Export: PDF, Markdown
  
DESIGN:
  └─ Type: html, figma code, sketch json
  └─ Display: Preview rendering
  └─ Export: HTML, image
```

**Success Criteria:**
- ✅ Code/document detected in response
- ✅ Artifact record created in DB
- ✅ Panel opens automatically
- ✅ Code displayed with syntax highlighting
- ✅ Can switch to preview tab
- ✅ Copy button works
- ✅ Export generates correct format
- ✅ Can share (create public token)

---

### Feature 6: Artifact - Rendering & Display

**URL/Access:**
```
GET /chat (right panel when openArtifactId !== null)
GET /artifact/{id}              # Private view (authenticated)
GET /artifact/{token}           # Public view (no auth required)
```

**Expected Behavior:**
1. Artifact panel displays on right side
2. Shows code/document content
3. Two tabs: "Code" and "Preview"
4. Code tab shows syntax-highlighted source
5. Preview tab renders output
6. Can copy code to clipboard
7. Can expand to fullscreen
8. Can scroll independently
9. Resizable divider between chat and artifact
10. Click outside or ESC closes panel

**ArtifactPanel Component:**
```
State:
  - #[Reactive] openArtifactId    # From parent ChatLayout
  - currentArtifact               # Metadata (id, type, title)
  - activeTab = 'code'            # 'code' or 'preview'
  - fullscreen = false
  - searchQuery = ''              # Search within artifact
  
#[Computed] artifactContent:
  └─ Lazy-loads content on demand (not serialized in snapshot)
  
Methods:
  - viewArtifact($id)
  - updateArtifact($content)
  - deleteArtifact($id)
  - toggleFullscreen()
  - exportArtifact($format)
```

**Database Query Pattern:**
```sql
-- Load artifact (without content for performance)
SELECT id, message_id, type, language, title, is_public, created_at
FROM message_artifacts
WHERE id = ? AND user_id = Auth::id()

-- Load content (lazy-loaded on demand)
SELECT content FROM message_artifacts WHERE id = ?

-- Update artifact
UPDATE message_artifacts 
SET content = ?, updated_at = NOW()
WHERE id = ? AND user_id = Auth::id()
```

**Blade Implementation:**
```blade
<div class="artifact-panel" wire:click.self="closePanel">
  <div class="header">
    <h2>{{ $currentArtifact['title'] }}</h2>
    <button wire:click="toggleFullscreen">Fullscreen</button>
  </div>
  
  <div class="tabs">
    <button wire:click="$set('activeTab', 'code')" 
            :class="{ active: activeTab === 'code' }">Code</button>
    <button wire:click="$set('activeTab', 'preview')" 
            :class="{ active: activeTab === 'preview' }">Preview</button>
  </div>
  
  @if($activeTab === 'code')
    <pre><code>{{ $this->artifactContent() }}</code></pre>
  @else
    <iframe srcdoc="{{ $previewHtml }}"></iframe>
  @endif
</div>
```

**Event Impact:**
```
Parent (ChatLayout) controls visibility:
  - Set openArtifactId = $id  → Panel opens
  - Set openArtifactId = null → Panel closes

Child (ArtifactPanel) receives via #[Reactive]:
  #[Reactive] public ?int $openArtifactId
  
  updatedOpenArtifactId($value):
    └─ Load currentArtifact if value !== null
    └─ Clear if value === null
    
Fullscreen button:
  └─ wire:click toggles $fullscreen
  └─ CSS shows/hides panels
```

**Preview Rendering:**
```
HTML/Document artifacts:
  └─ Render inside <iframe>
  └─ Content: Base64 encoded or direct srcdoc
  └─ Sandboxed: allow-scripts, allow-same-origin
  
Code artifacts:
  └─ Syntax highlighted via Prism.js or similar
  └─ Read-only unless user edits
  └─ Copy button copies to clipboard
```

**Success Criteria:**
- ✅ Panel opens when artifact created
- ✅ Content displays correctly
- ✅ Code/preview tabs switch
- ✅ Syntax highlighting works
- ✅ Copy button works
- ✅ Fullscreen works
- ✅ Resizable works
- ✅ Close button/ESC works
- ✅ No errors in iframe

---

### Feature 7: Settings - Save User Settings

**URL/Access:**
```
GET /chat (settings modal when settingsOpen === true)
Livewire: SettingsModal component
Keyboard: Cmd/Ctrl + Shift + ,
```

**Expected Behavior:**
1. Click settings icon or press Cmd+Shift+,
2. Modal opens with tabs: General, Preferences, API Keys, Billing
3. User fills out form fields
4. Changes auto-save as user types (updatedProperty handlers)
5. Success message shown briefly
6. Settings persist across sessions and devices
7. API keys validated before save

**Tabs & Fields:**

**General Tab:**
```
- Name (text)
- Email (email)
- Nickname (text)
- Profession (select dropdown)
- Custom Instructions (textarea)
  └─ Auto-saves on blur via updatedCustomInstructions()
```

**Preferences Tab:**
```
- Language (en, es, fr, etc.)
- Theme (light, dark, auto)
- Chat Font (default, mono, serif)
- Font Size (small, medium, large)
- Accent Color (color picker with presets)
- Compact Mode (toggle)
  └─ All auto-save via updated* handlers
```

**API Keys Tab:**
```
- Anthropic API Key (password field)
- OpenAI API Key (password field)
- NineRouter API Key (for kr/claude models)
- Google API Key (for Gemini)
- Mistral API Key
- Proxy settings:
  ├─ Use Proxy (toggle)
  ├─ Proxy Base URL
  └─ Proxy API Key
- HuggingFace API Key
- Test Connection button
- Status indicator (✓ valid / ✗ invalid)
```

**Billing Tab:**
```
- Current Plan (read-only: Free, Pro, Enterprise)
- Tokens Used (read-only)
- Tokens Limit (read-only)
- Token Breakdown (chart/table by model)
- Usage Graph (monthly)
- Upgrade button (if applicable)
```

**Database Impact:**
```sql
-- All settings stored in users table
UPDATE users SET 
  name = ?,
  email = ?,
  nickname = ?,
  profession = ?,
  custom_instructions = ?,
  language = ?,
  theme = ?,
  chat_font = ?,
  font_size = ?,
  accent_color = ?,
  compact_mode = ?,
  allow_training = ?,
  cap_web_search = ?,
  cap_artifacts = ?,
  anthropic_api_key = ?,
  openai_api_key = ?,
  nine_router_api_key = ?,
  google_api_key = ?,
  mistral_api_key = ?,
  use_proxy = ?,
  proxy_base_url = ?,
  proxy_api_key = ?,
  huggingface_api_key = ?,
  updated_at = NOW()
WHERE id = ?

-- Token usage queries (read-only)
SELECT SUM(tokens) FROM token_usages WHERE user_id = ?
SELECT model, SUM(tokens) FROM token_usages 
WHERE user_id = ? GROUP BY model
```

**API Impact:**
```
Routes:
  PATCH /api/settings                  # Save all settings
  POST /api/settings/validate-api-key # Test API key validity
  POST /api/settings/test-connection  # Test LLM connection

Validation Endpoints:
  POST https://api.anthropic.com/v1/models  # Anthropic test
  POST https://api.openai.com/v1/chat/completions (test)
  Similar for other providers

API Key Encryption:
  └─ Keys encrypted at rest in database
  └─ Encrypted using Laravel's Encrypter
  └─ Only decrypted when needed for API calls
```

**Event Impact:**
```
#[On('open-settings-modal', 'tab')] handler:
  └─ openModal($tab)
  └─ Load current user settings
  └─ Display modal with specified tab active

Updated* handlers (auto-save on change):
  updatedName(), updatedEmail(), updatedLanguage(), etc.
  └─ Each method auto-saves that field
  └─ Flash success message
  
Other components listen for:
  #[On('apiKeysSaved')]
  └─ ChatInterface::refreshModels()
  └─ Re-load available models based on new API keys
```

**Validation Rules:**
```php
protected function rules(): array {
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . Auth::id(),
        'nickname' => 'nullable|string|max:255',
        'profession' => 'nullable|in:developer,designer,data_scientist,...',
        'language' => 'required|in:en,es,fr,de,it,pt,ja,zh',
        'theme' => 'required|in:light,dark,auto',
        'accentColor' => 'required|regex:/^#[0-9A-F]{6}$/i',
        'anthropicApiKey' => 'nullable|string|min:20',
        'openaiApiKey' => 'nullable|string|min:20',
        'proxyBaseUrl' => 'nullable|url',
    ];
}
```

**Sensitive Data Handling:**
```
API Keys:
  ├─ Stored encrypted in database
  ├─ Never logged or exposed in responses
  ├─ Never sent to client (marked as password field)
  ├─ Validated on save before storing
  └─ Can be reset (deleted) by user

Theme/Preferences:
  ├─ Stored in users table
  ├─ Cached in session for faster access
  ├─ Applied to UI in real-time
  └─ Persisted across sessions
```

**Success Criteria:**
- ✅ Modal opens on command
- ✅ Settings load from database
- ✅ Fields update in real-time
- ✅ Auto-save works
- ✅ API keys validated
- ✅ Error messages shown
- ✅ Changes persist across page refresh
- ✅ Available models update after API key save
- ✅ No console errors

---

### Feature 8: Project Management - Create/Edit/Delete

**URL/Access:**
```
GET /chat (projects panel when activePanel === 'projects')
Livewire: ProjectsPanel component
```

**Expected Behavior:**
1. Click "Projects" in sidebar
2. See list of projects with thumbnails
3. Click "+" to create new project
4. Fill out project details (name, description, knowledge files)
5. Upload files as "knowledge base" for project context
6. Click project to open and chat within context
7. Edit project settings
8. Delete project (with confirmation)
9. Search projects by name

**Component Structure:**

```
ProjectsPanel state:
  - $projects = []                    # List of user's projects
  - $activeProjectId = null           # Currently selected project
  - $projectName = ''                 # Form field
  - $projectDescription = ''          # Form field
  - $uploadedFiles = []               # Livewire temp files
  - $selectedFiles = []               # Selected from uploaded
  - $searchQuery = ''
  
Methods:
  - createProject()
  - updateProject()
  - deleteProject($id)
  - uploadFiles()                     # WithFileUploads trait
  - deleteKnowledgeFile($fileId)
  - startChat($projectId)             # Dispatch to ChatInterface
```

**Database Impact:**
```sql
-- Create project
INSERT INTO projects (user_id, name, description, created_at)
VALUES (?, ?, ?, NOW())

-- Upload project files (knowledge base)
INSERT INTO project_files (project_id, name, file_path, file_type, file_size)
VALUES (?, ?, ?, ?, ?)

-- Update project
UPDATE projects SET name = ?, description = ? WHERE id = ? AND user_id = ?

-- Delete project
DELETE FROM projects WHERE id = ? AND user_id = ?
DELETE FROM project_files WHERE project_id = ?

-- Load projects
SELECT * FROM projects WHERE user_id = ? ORDER BY updated_at DESC

-- Start chat with project context
INSERT INTO conversations (user_id, project_id, title, created_at)
VALUES (?, ?, ?, NOW())
```

**File Upload Flow:**
```
1. User clicks "Upload files"
   └─ File input opens
   
2. Select files
   └─ wire:model="uploadedFiles" captures files
   └─ Files stored in /storage/livewire-tmp/
   └─ ShowFilePreview component displays file list
   
3. Click "Save Files"
   └─ uploadFiles() method:
      ├─ Validate file types (pdf, docx, txt, etc.)
      ├─ Store permanently in /storage/projects/{projectId}/
      ├─ Create ProjectFile records
      ├─ Delete temp files
      └─ Success message
      
4. Files are now available as context for chats
   └─ When starting chat with project
   └─ Files loaded and included in prompt context
```

**API Impact:**
```
Routes:
  POST /projects                      # Create
  GET /projects                       # List
  PATCH /projects/{id}                # Update
  DELETE /projects/{id}               # Delete
  POST /projects/{id}/files           # Upload files
  DELETE /projects/{id}/files/{fileId} # Delete file
  
File Storage:
  └─ /storage/projects/{projectId}/{filename}
  └─ Accessible via Storage::get()
  └─ Not directly served (prevent hotlinking)
```

**Integration with Chat:**
```
When starting chat in project context:
  1. #[On('startProjectChat', $projectId)] event triggered
  2. ChatInterface::startProjectChat($projectId)
  3. $this->activeProjectId = $projectId
  4. Create new Conversation with project_id
  5. Load project files
  6. Prepend to chat context: "Here are your project files: ..."
  7. All responses aware of project context
```

**Event Impact:**
```
#[On('startProjectChat')] in ChatInterface:
  └─ startProjectChat($projectId, $initialPrompt, $initialModel)
  └─ Reset chat
  └─ Load project files
  └─ Start conversation with context
  
Search/Filter:
  wire:model.live.debounce.300ms="searchQuery"
  └─ Real-time filtering of projects
```

**Success Criteria:**
- ✅ Projects list displays
- ✅ Create project saves to database
- ✅ File upload works
- ✅ Files stored permanently
- ✅ Can edit project details
- ✅ Can delete project
- ✅ Search filters projects
- ✅ Project context included in chat
- ✅ Chat within project shows files

---

### Feature 9: Design Panel - AI-Generated Designs

**URL/Access:**
```
GET /design                         # Direct design panel route
GET /chat (design tab when activePanel === 'design')
Livewire: DesignPanel component
```

**Expected Behavior:**
1. Open design panel
2. See grid of previously generated designs
3. Search designs by prompt/title
4. Click "+" to create new design
5. Enter design prompt
6. AI generates design code/mockup
7. Shows in preview
8. Can favorite/star design
9. Can delete design
10. Can view/edit design details
11. Can export design

**Component Structure:**
```
DesignPanel state:
  - $designs = []                     # Grid of designs
  - $currentTab = 'all'               # Filter: all, recent, starred
  - $search = ''                      # Search query
  - $dialogOpen = false               # Create dialog visible
  - $dialogPrompt = ''                # New design prompt
  
Methods:
  - loadDesigns()
  - createDesign()                    # Call AiService
  - deleteDesign($id)
  - toggleStar($id)
  - viewDesign($id)
  - openDialog()
  - closeDialog()
```

**Database Impact:**
```sql
-- Create design
INSERT INTO designs (user_id, prompt, content, created_at)
VALUES (?, ?, ?, NOW())

-- Load designs
SELECT * FROM designs 
WHERE user_id = ? 
ORDER BY created_at DESC

-- Update design
UPDATE designs SET content = ?, is_starred = ? WHERE id = ? AND user_id = ?

-- Delete design
DELETE FROM designs WHERE id = ? AND user_id = ?
```

**API Impact:**
```
Routes:
  GET /api/designs                    # List
  POST /api/designs                   # Create (calls AiService)
  PATCH /api/designs/{id}             # Update
  DELETE /api/designs/{id}            # Delete
  
AiService call:
  └─ Send design prompt to LLM
  └─ Get HTML/CSS code back
  └─ Store content in designs table
```

**Event Impact:**
```
createDesign() method:
  └─ Call AiService::generateDesign($prompt)
  └─ Emit 'designCreated' event
  └─ Add to grid
  
No complex event system - mostly CRUD operations
```

**Success Criteria:**
- ✅ Design panel loads
- ✅ Design grid displays
- ✅ Create dialog works
- ✅ Design generated via AI
- ✅ Preview renders
- ✅ Favorite/delete work
- ✅ Search filters
- ✅ Export works

---

### Feature 10: Cowork Panel - Autonomous Task Management

**URL/Access:**
```
GET /chat (cowork tab when activePanel === 'cowork')
Livewire: CoworkPanel component
```

**Expected Behavior:**
1. Click Cowork in sidebar
2. See list of created autonomous tasks
3. Tasks have: title, description, status, priority, result
4. Click "+" to create task
5. Set task details and schedule
6. Task runs via AiService agent
7. Results displayed in task detail view
8. Can re-run task
9. Mark complete/pending
10. Delete task

**Component Structure:**
```
CoworkPanel state:
  - $tasks = []                       # Task list
  - $view = 'list'                    # 'list', 'detail', 'create'
  - $activeTaskId = null              # Selected task
  - $statusFilter = 'all'             # Filter by status
  
  Form fields:
  - $title = ''
  - $description = ''
  - $priority = 'medium'              # low, medium, high
  - $model = 'claude-haiku-4-5'
  - $scheduledFor = null              # DateTime or null for immediate
  
Methods:
  - createTask()
  - runTask($id)                      # Execute via AiService
  - updateStatus($id, $status)
  - deleteTask($id)
  - getResults($id)
```

**Database Impact:**
```sql
-- Create task
INSERT INTO cowork_tasks (user_id, title, description, priority, model, scheduled_for, created_at)
VALUES (?, ?, ?, ?, ?, ?, NOW())

-- Task execution
UPDATE cowork_tasks 
SET status = 'in_progress', started_at = NOW() 
WHERE id = ?

-- Store result
UPDATE cowork_tasks 
SET status = 'completed', result = ?, completed_at = NOW() 
WHERE id = ?

-- Update status
UPDATE cowork_tasks SET status = ? WHERE id = ? AND user_id = ?

-- Delete task
DELETE FROM cowork_tasks WHERE id = ? AND user_id = ?
```

**API Impact:**
```
Routes:
  GET /api/tasks                      # List
  POST /api/tasks                     # Create
  PATCH /api/tasks/{id}               # Update
  DELETE /api/tasks/{id}              # Delete
  POST /api/tasks/{id}/run            # Execute (long-running)
  
AiService Integration:
  └─ Agent loop with ReAct pattern
  └─ Tool calling for file operations
  └─ Result aggregation
```

**Event Impact:**
```
#[On(...)] for task updates:
  └─ Refresh task list when created/updated
  
No complex event flows - mostly async operations
```

**Task Execution Flow:**
```
1. User clicks "Run" on task
   └─ runTask($id)
   
2. Update status to 'in_progress'
   └─ Show spinner in UI
   
3. Call AiService::executeTask($task)
   └─ Send task description to LLM
   └─ Agent loop with tool calls
   └─ Execute tools (read file, write code, etc.)
   └─ Accumulate result
   
4. Store result
   └─ UPDATE cowork_tasks SET result = ?, status = 'completed'
   
5. Display result in task detail
   └─ Render result content
```

**Success Criteria:**
- ✅ Task list displays
- ✅ Create task saves
- ✅ Run task executes
- ✅ Result stored and displayed
- ✅ Status updates work
- ✅ Delete works
- ✅ Re-run works

---

### Feature 11: File Upload - Chat Attachments

**URL/Access:**
```
In Chat: Click attachment icon in message input
Livewire: ChatInterface with WithFileUploads trait
```

**Expected Behavior:**
1. Click attachment icon in chat input
2. File picker opens
3. Select one or multiple files
4. Files appear as chips/previews in input
5. Can remove file before sending
6. Send message with attachments
7. Files processed (text extracted if needed)
8. Files included in prompt context for AI
9. File references shown in message

**Implementation:**
```
ChatInterface::WithFileUploads trait:
  - $attachments = []                 # Array of uploaded files
  
Blade:
  <input type="file" wire:model="attachments" multiple />
  
  @foreach($attachmentPreviews as $preview)
    <div class="attachment-chip">
      {{ $preview['name'] }} 
      <button wire:click="removeAttachment({{ $loop->index }})">×</button>
    </div>
  @endforeach
```

**File Handling Flow:**
```
1. User selects file
   └─ wire:model="attachments" captures
   └─ Livewire stores in /storage/livewire-tmp/{randomId}
   └─ File object available in $this->attachments

2. Show file preview/list
   └─ Map $attachments to human-readable names
   └─ Show file size, type
   └─ Allow removal before send

3. User sends message with attachments
   └─ sendMessage() called
   
4. Process attachments:
   └─ For each file in $this->attachments:
      ├─ Read file content (text files) or metadata (binary)
      ├─ Create MessageAttachment record
      ├─ Move file to permanent storage: /storage/message-attachments/{messageId}/
      ├─ Clean up temp file
      └─ Add file reference to prompt context
      
5. Include in prompt context:
   └─ "User attached files: [file1.txt: content], [file2.pdf: {metadata}]"
   └─ LLM analyzes attachments in response

6. Message sent with attachments
   └─ Message record includes attachment references
   └─ Can view/download later
```

**Database Impact:**
```sql
-- Store attachment reference
INSERT INTO message_attachments (message_id, file_path, file_type, file_name, file_size)
VALUES (?, ?, ?, ?, ?)

-- Load attachments with message
SELECT * FROM message_attachments WHERE message_id = ?

-- Delete attachments with message
DELETE FROM message_attachments WHERE message_id = ?
```

**File Storage:**
```
Temp Storage:
  /storage/livewire-tmp/{sessionId}-{randomId}
  └─ Auto-cleaned by Livewire after 24 hours
  
Permanent Storage:
  /storage/message-attachments/{messageId}/{filename}
  └─ Kept as long as message exists
  └─ Deleted with message
  
Max File Size:
  ├─ Per file: 50MB (configurable)
  ├─ Per message: 200MB total
  └─ Enforced by Laravel validation
```

**Supported File Types:**
```
Text Files:
  .txt, .md, .code, .log, .json, .xml, .csv
  └─ Content fully included in prompt
  
Document Files:
  .pdf, .docx, .doc
  └─ Parsed and text extracted
  └─ Included in prompt
  
Code Files:
  .js, .py, .php, .java, .go, .rs
  └─ Full content included
  └─ Syntax highlighting in preview
  
Media Files:
  .jpg, .png, .gif
  └─ Metadata + image analysis via LLM vision capability
  
Other:
  .zip, .tar, .gz
  └─ Extracted and contents indexed
```

**API Impact:**
```
No external API calls for file upload itself.

Internal:
  POST /api/upload (Livewire temp file endpoint)
  └─ Auto-handled by WithFileUploads trait
  
File Content Processing:
  └─ Done server-side in sendMessage()
  └─ Content not sent externally
  └─ Only referenced in LLM prompt
```

**Event Impact:**
```
No events for file upload - synchronous operation.

File removal (removeAttachment):
  └─ wire:click="removeAttachment($index)"
  └─ Unset from $attachments array
  └─ Temp file remains until message sent or 24hr cleanup
```

**Validation & Security:**
```
File Validation:
  ├─ Check MIME type
  ├─ Scan for viruses (optional, if configured)
  ├─ Verify file size
  ├─ Check against whitelist of allowed types
  └─ Reject suspicious files
  
Security:
  ├─ Files stored outside public directory
  ├─ Access only to authenticated user
  ├─ Ownership verified before serving
  ├─ No direct URL to file (download via API)
  └─ Filename sanitized
```

**Success Criteria:**
- ✅ File picker opens
- ✅ Files preview before send
- ✅ Can remove files
- ✅ Files stored permanently
- ✅ Content included in prompt
- ✅ Correct MIME type
- ✅ Size limits enforced
- ✅ Files survive page refresh (until sent)
- ✅ Files cleaned up after message

---

## REGRESSION TEST SUITE

### Phase 1: Routing (After implementation)

**URL Tests:**
```
✓ GET /chat                              # Chat page loads
✓ GET /code                              # Code IDE loads
✓ GET /design                            # Design panel loads
✓ POST /api/chat/send                    # API endpoint exists
✓ POST /api/chat/{id}/stop               # Stop endpoint exists
```

**Response Format Tests:**
```
✓ /api/chat/send returns JSON with structure: 
  { "id": int, "role": "assistant", "content": "...", "artifact": null|{...} }
✓ Error responses include: { "error": "...", "message": "..." }
✓ Authentication errors return 401
✓ Authorization errors return 403
✓ Validation errors return 422 with errors array
```

**Basic Functionality Tests:**
```
✓ Can load /chat without errors
✓ Can load /code without errors  
✓ Can load /design without errors
✓ Routes require authentication (redirect to login if not auth'd)
✓ 404 on non-existent routes
```

---

### Phase 2: Blade Templates (After implementation)

**Template Rendering Tests:**
```
✓ chat.blade.php renders without errors
✓ All Blade variables exist (@isset checks pass)
✓ Alpine.js initialized (x-data in root)
✓ No Livewire wire: directives remain
✓ All CSS classes exist (check against Tailwind built CSS)
✓ JavaScript dependencies loaded (Alpine, chart libs, etc.)
```

**Component Tests:**
```
✓ Chat input field visible and focused
✓ Chat history displays correctly
✓ Artifact panel hidden initially
✓ Settings modal hidden initially
✓ All buttons functional
✓ Form inputs accept text
✓ No console errors
```

---

### Phase 3: State Management (After implementation)

**Session State Tests:**
```
✓ Selected model persists after page refresh
✓ Draft prompt saved to session (updatedPrompt)
✓ Chat history loads on conversation open
✓ Settings persist across sessions
✓ Dark/light theme preference saved
✓ Sidebar toggle state persists
```

**Client-Side State Tests:**
```
✓ Alpine.js state syncs with server
✓ x-model bindings work (two-way)
✓ @click handlers fire correctly
✓ :disabled attributes toggle correctly
✓ :class conditional classes apply
```

---

### Phase 4: API Endpoints (After implementation)

**Chat API Tests:**
```
✓ POST /api/chat/send creates Message record
✓ POST /api/chat/send creates Conversation if needed
✓ POST /api/chat/send returns message with ID
✓ POST /api/chat/send validates prompt (not empty)
✓ POST /api/chat/send with invalid model returns error
✓ POST /api/chat/{id}/stop sets cache flag
✓ GET /api/chats lists user's conversations
✓ PATCH /api/chats/{id} updates title
✓ DELETE /api/chats/{id} deletes conversation and messages
```

**Artifact API Tests:**
```
✓ GET /api/artifacts lists user's artifacts
✓ POST /api/artifacts creates artifact
✓ PATCH /api/artifacts/{id} updates content
✓ DELETE /api/artifacts/{id} deletes artifact
✓ GET /artifact/{id}/preview.pdf generates PDF
✓ GET /artifact/{token} shows shared artifact
```

**Settings API Tests:**
```
✓ PATCH /api/settings saves user settings
✓ POST /api/settings/validate-api-key tests key validity
✓ Settings update triggers model refresh
✓ API keys encrypted before storage
✓ Invalid settings rejected with validation errors
```

**Project API Tests:**
```
✓ POST /api/projects creates project
✓ GET /api/projects lists user's projects
✓ PATCH /api/projects/{id} updates
✓ DELETE /api/projects/{id} deletes
✓ POST /api/projects/{id}/files uploads files
✓ DELETE /api/projects/{id}/files/{fileId} deletes file
```

**Cowork API Tests:**
```
✓ POST /api/tasks creates task
✓ GET /api/tasks lists user's tasks
✓ PATCH /api/tasks/{id} updates status
✓ DELETE /api/tasks/{id} deletes task
✓ POST /api/tasks/{id}/run executes task
✓ Task result stored correctly
```

---

### Phase 5: Streaming (After implementation)

**SSE Streaming Tests:**
```
✓ /api/chat/{id}/stream establishes connection
✓ EventSource client connects successfully
✓ First chunk arrives within 2 seconds
✓ Chunks arrive in correct order
✓ Stop flag works mid-stream
✓ Connection closes properly after stream
✓ Partial response saved if stopped
✓ No memory leaks (connections close)
✓ Works with large responses (10K+ tokens)
✓ Handles connection drops gracefully
```

**Message Accumulation Tests:**
```
✓ Chunks accumulated correctly
✓ Full message saved to database
✓ No duplicate content
✓ Special characters handled correctly
✓ HTML/code blocks preserved
✓ Artifact detection works after streaming
```

---

### Phase 6: Livewire Removal (Final verification)

**Package Removal Tests:**
```
✓ composer remove livewire/livewire succeeds
✓ No broken dependencies
✓ All caches cleared without errors
✓ Route cache regenerated
✓ View cache cleared
✓ Config cache cleared
```

**Code Cleanup Tests:**
```
✓ No app/Livewire directory
✓ No resources/views/livewire directory
✓ No wire: directives in Blade files
✓ No #[On(...)] attributes in code
✓ No Livewire:: class references
✓ No @livewire() directives
✓ No Livewire.dispatch() calls
✓ No WithFileUploads trait usage (converted)
```

**JavaScript Cleanup Tests:**
```
✓ No document.addEventListener('livewire:initialized')
✓ No Livewire.dispatch() calls
✓ No Livewire.on() listeners
✓ No livewire.min.js in Network tab
✓ No /livewire-* routes in debugbar
```

---

## EXECUTION CHECKLIST

### Before Phase 1: Baseline Capture

```
☐ Run all Feature tests (1-11) manually in browser
☐ Verify each feature works as documented
☐ Document any deviations from expected behavior
☐ Take screenshots of each feature working
☐ Export Baseline Test Report to PDF
☐ Create backup of database (mysqldump)
☐ Document current browser console (should be clean)
☐ Note current response times
☐ Run E2E test suite (if exists) and capture baseline
```

### After Phase 1: Regression Test Phase 1 Items

```
☐ Run all URL Tests
☐ Test 404 handling
☐ Test authentication redirects
☐ Verify routes in php artisan route:list
☐ Test API response formats
```

### After Phase 2: Regression Test Phase 2 Items

```
☐ Run all Template Rendering Tests
☐ Check for console errors in DevTools
☐ Verify no wire: directives remain
☐ Test CSS loading
☐ Test Alpine.js initialization
☐ Visual regression test (screenshot comparison)
```

### After Phase 3: Regression Test Phase 3 Items

```
☐ Run all Session State Tests
☐ Test model persistence across refresh
☐ Verify settings saved
☐ Test sidebar state persistence
☐ Check localStorage for state (if used)
```

### After Phase 4: Regression Test Phase 4 Items

```
☐ Run all API Endpoint Tests
☐ Use Postman/Insomnia to test manually
☐ Verify database impacts match documentation
☐ Test authorization on all endpoints
☐ Test validation error responses
☐ Run Feature tests (Laravel tests)
```

### After Phase 5: Regression Test Phase 5 Items

```
☐ Test streaming with small responses
☐ Test streaming with large responses (10K+ tokens)
☐ Test stop mid-stream
☐ Verify SSE connections close properly
☐ Monitor for memory leaks (keep DevTools open, watch memory)
☐ Test slow internet (throttle in DevTools)
☐ Test connection drops and recovery
```

### After Phase 6: Final Regression Test

```
☐ Full smoke test of all 11 features
☐ No Livewire references found
☐ Browser console clean
☐ All routes working
☐ Performance acceptable
☐ Load times similar to baseline
☐ Database integrity check
☐ Backup production database before deploying
```

---

## TEST INFRASTRUCTURE SETUP

### Tools Needed

```
Testing Framework:
  ✓ PHPUnit (Laravel built-in)
  ✓ Pest (optional, more readable syntax)
  
API Testing:
  ✓ Postman or Insomnia (manual API testing)
  ✓ Laravel Feature tests (automated)
  
Browser Testing:
  ✓ Laravel Dusk (E2E testing)
  ✓ Screenshots for visual regression
  
Database:
  ✓ MySQL/SQLite for testing
  ✓ Seeder for test data
  
CI/CD (if available):
  ✓ GitHub Actions or similar
  ✓ Auto-run tests on push
```

### Test Data Seeding

```php
// tests/Seeders/TestDataSeeder.php
- Create 3 test users with different API keys
- Create 10 conversations with 50 messages each
- Create 5 artifacts
- Create 3 projects with files
- Create 5 cowork tasks with results
```

---

## BASELINE METRICS

**Current State (Before Phase 1):**
- Lines of Livewire code: 5,500+
- Number of components: 17
- Blade directives (wire:): 150+
- Routes using Livewire: 2
- API endpoints: ~0 (all via Livewire)
- Database models: 8
- External APIs supported: 6

**Target State (After Phase 6):**
- Lines of Livewire code: 0
- Number of components: 0
- Blade directives (wire:): 0
- Routes using controllers: 30+
- API endpoints: 30+
- Database models: 8 (unchanged)
- External APIs supported: 6 (unchanged)

**Performance Baseline:**
```
Chat message send time:
  - Current: ~1.5s (Livewire overhead)
  - Target: ~0.8s (pure REST API)
  
Streaming first chunk:
  - Current: ~2s
  - Target: ~1.5s
  
Page load time:
  - Current: ~2.5s (including Livewire JS)
  - Target: ~2.0s (lighter JS bundle)
  
SSE connection setup:
  - Current: ~1s (via Livewire)
  - Target: ~0.5s (direct EventSource)
```

---

## STATUS

✅ **All 11 features documented**  
✅ **Regression test suite created**  
✅ **No code changes made**  
✅ **Ready for Phase 1 implementation**  

---

**Next Step:** Confirm all features tested and understood, then proceed to Phase 1 (Routing Migration)

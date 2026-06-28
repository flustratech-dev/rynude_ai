# Baseline Testing Checklist

**Before Phase 1 Implementation - Manual Testing of All Features**

---

## QUICK TEST GUIDE (30 minutes)

### Test 1: Chat - Send Message (5 min)
```
□ Open /chat
□ Type prompt: "Hello, what is 2+2?"
□ Click Send
□ ✓ Verify: Message appears as "user" role
□ ✓ Verify: AI response streams in real-time
□ ✓ Verify: Full response completes
□ ✓ Verify: No browser console errors
□ ✓ Verify: Message saved in database (check conversations & messages tables)
□ Screenshot: Save as baseline-chat-message.png
```

### Test 2: Chat - Streaming Response (3 min)
```
□ From previous chat
□ Type prompt: "Write a poem about coding"
□ Watch response stream in real-time
□ ✓ Verify: Text appears character-by-character
□ ✓ Verify: Completes without timeout
□ ✓ Verify: No stuttering or chunks missing
□ Click "Stop" button mid-response
□ ✓ Verify: Streaming stops immediately
□ ✓ Verify: Partial response saved
```

### Test 3: Recent Chat List (2 min)
```
□ Click sidebar "Chats" panel
□ ✓ Verify: Lists recent conversations
□ ✓ Verify: Sorted by newest first
□ Type in search box: "poem"
□ ✓ Verify: Filters to "poetry" conversation (live debounce)
□ Click filter "Today"
□ ✓ Verify: Shows only today's chats
□ Click previous chat
□ ✓ Verify: Chat content loads
```

### Test 4: New Conversation (2 min)
```
□ Click "New Chat" button (or Cmd+K)
□ ✓ Verify: Chat clears
□ ✓ Verify: Empty state shown
□ ✓ Verify: Input focused
□ Send first message
□ ✓ Verify: New conversation created in sidebar
□ ✓ Verify: Title auto-generated from first message
```

### Test 5: Artifact Generation (3 min)
```
□ Send prompt: "Write a React button component"
□ Wait for response with code block
□ ✓ Verify: Artifact panel opens on right
□ ✓ Verify: Shows code with syntax highlighting
□ Click "Preview" tab
□ ✓ Verify: Code renders as output
□ Click "Copy" button
□ ✓ Verify: Code copied to clipboard
□ Screenshot: Save as baseline-artifact.png
```

### Test 6: Artifact Rendering (2 min)
```
□ From artifact still open
□ Click fullscreen button
□ ✓ Verify: Panel expands full width
□ ✓ Verify: Can scroll independently
□ Click fullscreen again to minimize
□ ✓ Verify: Returns to split view
□ Drag divider between chat and artifact
□ ✓ Verify: Resizable works
```

### Test 7: Settings - Save (3 min)
```
□ Press Cmd/Ctrl + Shift + ,
□ ✓ Verify: Settings modal opens
□ Click "Preferences" tab
□ Change theme to "Dark"
□ ✓ Verify: Auto-saves (no button needed)
□ ✓ Verify: UI immediately switches to dark
□ Refresh page
□ ✓ Verify: Dark theme persists
□ Go back to settings
□ Click "API Keys" tab
□ ✓ Verify: Empty fields (safe)
```

### Test 8: Project Management (3 min)
```
□ Click sidebar "Projects" panel
□ Click "+" to create project
□ Enter name: "My Python Project"
□ Enter description
□ Click "Save"
□ ✓ Verify: Project created and shown in list
□ Click project
□ ✓ Verify: Chat loads with project context
□ Type prompt about the project
□ ✓ Verify: AI references project context
□ Delete project (confirm on modal)
□ ✓ Verify: Project removed from list
```

### Test 9: Design Panel (2 min)
```
□ Click sidebar "Design" panel
□ Click "+" to create design
□ Enter prompt: "Create a landing page header"
□ Click "Generate"
□ Wait for design generation
□ ✓ Verify: Design appears in grid
□ Click design to view
□ ✓ Verify: Preview renders
□ Click star icon
□ ✓ Verify: Favorite toggle works
□ Delete design
□ ✓ Verify: Removed from grid
```

### Test 10: Cowork Panel (2 min)
```
□ Click sidebar "Cowork" panel
□ Click "Get Started"
□ Click "+" to create task
□ Enter title: "Analyze error logs"
□ Enter description
□ Set priority: "High"
□ Click "Create"
□ ✓ Verify: Task appears in list
□ Click task
□ ✓ Verify: Task detail opens
□ Click "Run"
□ Wait for execution
□ ✓ Verify: Task runs and shows result
```

### Test 11: File Upload (2 min)
```
□ Back in chat
□ Click attachment icon in input
□ Select a text file (e.g., .txt or .json)
□ ✓ Verify: File preview shows in input
□ ✓ Verify: Can remove before send
□ Add another file
□ Send message with attachments
□ ✓ Verify: Files mentioned in response
□ ✓ Verify: Attachments saved to database
□ Refresh page and open chat
□ ✓ Verify: Attachments still visible
```

---

## DATABASE VERIFICATION

After completing all feature tests, verify database was modified correctly:

```bash
# Check conversations table
mysql> SELECT COUNT(*) FROM conversations WHERE user_id = 1;
# Should be > 0

# Check messages table
mysql> SELECT COUNT(*) FROM messages;
# Should have entries from all chats

# Check artifacts table
mysql> SELECT COUNT(*) FROM message_artifacts;
# Should have artifact from test 5

# Check projects table
mysql> SELECT COUNT(*) FROM projects;
# Should have entry from test 8 (before deletion)

# Check token_usages (if tracked)
mysql> SELECT SUM(tokens) FROM token_usages WHERE user_id = 1;
# Should show accumulated tokens from chat responses
```

---

## BROWSER CONSOLE VERIFICATION

After all tests, browser console should be clean:

```
✓ No red errors
✓ No yellow warnings related to missing elements
✓ No 404s for resources
✓ No CORS errors
✓ No Livewire warnings (expected before Phase 1)
```

Check DevTools:
```
□ Application tab:
  □ Cookies present (session cookie)
  □ localStorage has app settings
  □ sessionStorage clean or minimal
  
□ Network tab:
  □ All XHR/fetch requests successful (200 status)
  □ No failed requests
  □ File upload requests show success
  □ Streaming requests show proper Content-Type: text/event-stream
  
□ Performance:
  □ Document interactive: < 3 seconds
  □ Largest Contentful Paint: < 2 seconds
```

---

## FEATURE MATRIX VERIFICATION

| Feature | URL | Status | Notes |
|---------|-----|--------|-------|
| Chat - Send Message | GET /chat | ☐ | Check message appears |
| Chat - Streaming | GET /chat | ☐ | Check real-time text flow |
| Recent Chat List | GET /chat | ☐ | Check sidebar loads |
| New Conversation | GET /chat | ☐ | Check empty state |
| Artifact Generation | GET /chat | ☐ | Check code detected |
| Artifact Rendering | GET /chat | ☐ | Check preview works |
| Settings Save | GET /chat (modal) | ☐ | Check auto-save |
| Project Management | GET /chat (panel) | ☐ | Check CRUD works |
| Design Panel | GET /chat (panel) | ☐ | Check generation |
| Cowork Panel | GET /chat (panel) | ☐ | Check task execution |
| File Upload | GET /chat | ☐ | Check attachments |

---

## SIGN-OFF

**Baseline Testing Completed By:** _______________  
**Date:** _______________  
**All Tests Passed:** ☐ Yes ☐ No (if No, list issues below)

**Issues Found:**
```
1. _________________________________________________________________
2. _________________________________________________________________
3. _________________________________________________________________
```

**Screenshots Captured:**
- ☐ baseline-chat-message.png
- ☐ baseline-artifact.png
- ☐ baseline-settings.png
- ☐ baseline-console-clean.png

**Database Backup Created:**
- ☐ mysqldump -u user -p database > baseline-backup.sql

**Ready for Phase 1:** ☐ Yes ☐ No

---

**Once all tests pass and database verified, proceed to Phase 1: Routing Migration**

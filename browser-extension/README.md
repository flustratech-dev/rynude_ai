# Rynude Connector - Browser Extension

Connect your ChatGPT, Gemini, and Claude free tier accounts to Rynude without manual API keys.

## 🎯 What This Does

This browser extension extracts authentication tokens from your browser sessions when you login to ChatGPT, Gemini, or Claude. These tokens allow Rynude to access the same APIs that the web apps use, letting you chat with your free tier accounts directly in Rynude.

**No API keys needed. No payment required. Just login and connect.**

---

## 📦 Installation

### Chrome / Edge / Brave

1. **Download the extension**
   - Clone or download this repository
   - Navigate to the `browser-extension/` folder

2. **Load the extension**
   - Open Chrome and go to `chrome://extensions/`
   - Enable "Developer mode" (toggle in top-right corner)
   - Click "Load unpacked"
   - Select the `browser-extension/` folder
   - The extension icon should appear in your toolbar

### Firefox

1. **Download the extension**
   - Same as above

2. **Load the extension**
   - Open Firefox and go to `about:debugging#/runtime/this-firefox`
   - Click "Load Temporary Add-on"
   - Navigate to `browser-extension/` folder
   - Select `manifest.json`
   - Extension loaded (note: temporary - reloads on browser restart)

---

## 🚀 How to Use

### Step 1: Install the Extension
Follow installation instructions above.

### Step 2: Login to Providers
Open these websites in new tabs and login with your accounts:
- [chatgpt.com](https://chatgpt.com) - ChatGPT/OpenAI
- [gemini.google.com](https://gemini.google.com) - Google Gemini
- [claude.ai](https://claude.ai) - Anthropic Claude

The extension will **automatically detect** your session tokens in the background.

### Step 3: Connect in Rynude
1. Start your Rynude server: `php artisan serve`
2. Open [http://localhost:8080/api-keys](http://localhost:8080/api-keys)
3. Click the **"Connect Account"** tab (4th tab)
4. Follow the on-screen instructions:
   - Mark extension as installed
   - Click "Connect [Provider] Account" buttons
   - Tokens are automatically sent to Rynude

### Step 4: Start Chatting
Go to the main chat interface and select your connected provider models. You're now using your free tier accounts through Rynude!

---

## 🔍 How It Works

```
┌─────────────────────────────────────────────────┐
│ 1. User Login (chatgpt.com/gemini/claude)      │
│    Browser stores authentication cookies        │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│ 2. Extension Background Script                  │
│    Monitors cookie changes                      │
│    Extracts authentication tokens               │
│    Stores locally (encrypted)                   │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│ 3. Content Script (injected into localhost)    │
│    Exposes API to Rynude page                   │
│    Sends tokens to Rynude backend               │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│ 4. Rynude Backend                               │
│    Stores tokens securely                       │
│    Uses tokens for API requests                 │
│    Forward responses to user                    │
└─────────────────────────────────────────────────┘
```

### Technical Details

**Monitored Cookies:**
- **ChatGPT:** `__Secure-next-auth.session-token`
- **Gemini:** `__Secure-1PSID`, `HSID`, `SSID`
- **Claude:** `sessionKey`

**Storage:**
- Tokens stored in `chrome.storage.local` (encrypted by browser)
- Never sent to third-party servers
- Only sent to localhost:8080 (your local Rynude instance)

---

## ⚙️ Extension API

The extension exposes a global API at `window.rynudeExtension` on localhost:8080 pages.

### Available Methods

```javascript
// Check extension installation
if (window.rynudeExtension) {
  console.log('Extension installed');
}

// Get all provider status
const status = await window.rynudeExtension.getStatus();
// Returns: { chatgpt: {...}, gemini: {...}, claude: {...} }

// Check specific provider
const isConnected = await window.rynudeExtension.checkChatGPT();

// Connect a provider
const success = await window.rynudeExtension.connectProvider('chatgpt');

// Disconnect a provider
await window.rynudeExtension.disconnectProvider('chatgpt');
```

### Events

```javascript
// Extension ready
window.addEventListener('rynude-extension-ready', (e) => {
  console.log('Extension version:', e.detail.version);
});

// Provider connected
window.addEventListener('rynude-provider-connected', (e) => {
  console.log('Connected:', e.detail.provider);
});

// Token updated
window.addEventListener('rynude-token-update', (e) => {
  console.log('Update:', e.detail.provider, e.detail.connected);
});
```

---

## 🔒 Security & Privacy

### What We Access
- Authentication cookies from ChatGPT, Gemini, and Claude
- Only when you visit those sites while logged in

### What We DON'T Do
- ❌ No tracking or analytics
- ❌ No data sent to external servers
- ❌ No credential storage in plain text
- ❌ No access to your passwords

### Data Storage
- Tokens stored locally in browser (encrypted by Chrome/Firefox)
- Only sent to localhost:8080 (your local Rynude instance)
- You can view stored data: Chrome DevTools → Application → Storage → Extensions

### Permissions Explained
- **cookies** - Read authentication cookies from provider sites
- **storage** - Store tokens locally in browser
- **webRequest** - Monitor requests to provider sites (for token refresh)
- **host_permissions** - Access to chatgpt.com, gemini.google.com, claude.ai, localhost

---

## ⚠️ Important Warnings

### Terms of Service
This extension uses **reverse-engineered APIs** and may violate provider Terms of Service:
- ChatGPT/OpenAI: May be against ToS
- Google Gemini: May be against ToS
- Anthropic Claude: May be against ToS

**Your account could be banned if detected by providers.**

### Recommendations
- ✅ Use separate test accounts (not your main accounts)
- ✅ Use for local/personal projects only
- ✅ Respect rate limits (don't spam requests)
- ❌ Don't use for production applications
- ❌ Don't use for commercial purposes
- ❌ Don't share your tokens with others

### Session Limitations
- Sessions may expire and require re-login
- Free tier rate limits still apply
- Tokens are tied to your IP address
- Some features may not work (file uploads, etc)

---

## 🐛 Troubleshooting

### Extension not detecting tokens
1. Make sure you're logged in to the provider website
2. Refresh the provider page (chatgpt.com, gemini.google.com, claude.ai)
3. Check extension popup (click icon in toolbar) to see status
4. Check browser console for errors: F12 → Console tab

### "No session found" error
1. Login to the provider website first
2. Wait 5-10 seconds for extension to detect cookies
3. Try refreshing the provider page
4. Check if cookies are enabled in your browser

### Tokens not sent to Rynude
1. Make sure Rynude is running: `php artisan serve`
2. Check backend API endpoint exists: `/api/provider-tokens`
3. Check browser console on localhost:8080 for errors
4. Make sure CSRF protection allows localhost

### Connection immediately disconnects
1. Token may have expired - re-login to provider
2. Provider may have invalidated session
3. Check Rynude backend logs for API errors

### Extension popup shows "Error loading status"
1. Extension background script may have crashed
2. Reload extension: chrome://extensions → click reload
3. Check background script console: Extensions page → Details → Inspect views: service worker

---

## 🔧 Development

### File Structure
```
browser-extension/
├── manifest.json         # Extension configuration
├── background.js         # Service worker (cookie monitoring)
├── content-script.js     # Injected into Rynude page
├── popup.html           # Extension popup UI
├── popup.js             # Popup logic
└── icons/               # Extension icons
```

### Testing Locally
1. Make changes to extension files
2. Go to chrome://extensions
3. Click "Reload" button under Rynude Connector
4. Test changes in Rynude

### Debugging
- **Background script:** chrome://extensions → Details → Inspect views: service worker
- **Content script:** F12 on localhost:8080 → Console tab
- **Storage:** F12 → Application → Storage → Extensions

### Add New Provider
1. Edit `PROVIDERS` object in [background.js](background.js)
2. Add cookie monitoring logic
3. Update UI in [popup.html](popup.html) and Rynude frontend
4. Create backend provider service in Rynude

---

## 📋 Next Steps (Backend Integration)

The extension is ready, but Rynude backend needs to be updated:

### Phase 2: Backend API
1. **Create migration:** `provider_web_tokens` table
2. **Create model:** `ProviderWebToken`
3. **Create controller:** `ProviderTokenController` with endpoint `/api/provider-tokens`
4. **Create providers:** `ChatGPTWebProvider`, `GeminiWebProvider`, `ClaudeWebProvider`
5. **Update routing:** Add web token fallback to existing provider services

See main project README for full integration guide.

---

## 📄 License

Use at your own risk. This extension is for educational and personal use only.

---

## 🤝 Contributing

This is a local development tool. If you improve it:
1. Test thoroughly with test accounts
2. Document changes
3. Update this README
4. Share responsibly

---

**Questions or Issues?**  
Check Rynude main project documentation or extension console logs for debugging.

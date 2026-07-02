/**
 * Rynude Connector - Background Service Worker
 *
 * Monitors cookies from ChatGPT, Gemini, and Claude
 * Extracts authentication tokens and stores them locally
 */

// Provider configurations
const PROVIDERS = {
  chatgpt: {
    domain: 'chatgpt.com',
    cookieName: '__Secure-next-auth.session-token',
    url: 'https://chatgpt.com'
  },
  gemini: {
    domain: 'gemini.google.com',
    cookieNames: ['__Secure-1PSID', 'HSID', 'SSID'],
    url: 'https://gemini.google.com'
  },
  claude: {
    domain: 'claude.ai',
    cookieName: 'sessionKey',
    url: 'https://claude.ai'
  }
};

/**
 * Initialize extension
 */
chrome.runtime.onInstalled.addListener(() => {
  console.log('Rynude Connector installed');

  // Check for existing tokens on install
  checkAllProviderTokens();
});

/**
 * Monitor cookie changes
 */
chrome.cookies.onChanged.addListener((changeInfo) => {
  const { cookie, removed } = changeInfo;

  if (removed) return; // Ignore removed cookies

  // Check if cookie is from one of our providers
  if (cookie.domain.includes('chatgpt.com')) {
    handleChatGPTCookie(cookie);
  } else if (cookie.domain.includes('gemini.google.com') || cookie.domain.includes('google.com')) {
    handleGeminiCookie(cookie);
  } else if (cookie.domain.includes('claude.ai')) {
    handleClaudeCookie(cookie);
  }
});

/**
 * Handle ChatGPT cookies
 */
async function handleChatGPTCookie(cookie) {
  if (cookie.name === PROVIDERS.chatgpt.cookieName) {
    console.log('ChatGPT session token detected');

    await chrome.storage.local.set({
      'chatgpt_token': cookie.value,
      'chatgpt_expires': cookie.expirationDate,
      'chatgpt_connected': true,
      'chatgpt_last_sync': Date.now()
    });

    // Notify content script
    notifyContentScript('chatgpt', true);
  }
}

/**
 * Handle Gemini cookies
 */
async function handleGeminiCookie(cookie) {
  if (PROVIDERS.gemini.cookieNames.includes(cookie.name)) {
    console.log(`Gemini cookie detected: ${cookie.name}`);

    // Get all Gemini cookies
    const cookies = {};
    for (const cookieName of PROVIDERS.gemini.cookieNames) {
      const c = await chrome.cookies.get({
        url: PROVIDERS.gemini.url,
        name: cookieName
      });
      if (c) {
        cookies[cookieName] = c.value;
      }
    }

    // Store if we have at least one cookie
    if (Object.keys(cookies).length > 0) {
      await chrome.storage.local.set({
        'gemini_cookies': cookies,
        'gemini_connected': true,
        'gemini_last_sync': Date.now()
      });

      // Notify content script
      notifyContentScript('gemini', true);
    }
  }
}

/**
 * Handle Claude cookies
 */
async function handleClaudeCookie(cookie) {
  if (cookie.name === PROVIDERS.claude.cookieName) {
    console.log('Claude session token detected');

    await chrome.storage.local.set({
      'claude_token': cookie.value,
      'claude_expires': cookie.expirationDate,
      'claude_connected': true,
      'claude_last_sync': Date.now()
    });

    // Notify content script
    notifyContentScript('claude', true);
  }
}

/**
 * Check all providers for existing tokens
 */
async function checkAllProviderTokens() {
  // Check ChatGPT
  const chatgptCookie = await chrome.cookies.get({
    url: PROVIDERS.chatgpt.url,
    name: PROVIDERS.chatgpt.cookieName
  });
  if (chatgptCookie) {
    handleChatGPTCookie(chatgptCookie);
  }

  // Check Gemini
  for (const cookieName of PROVIDERS.gemini.cookieNames) {
    const geminiCookie = await chrome.cookies.get({
      url: PROVIDERS.gemini.url,
      name: cookieName
    });
    if (geminiCookie) {
      handleGeminiCookie(geminiCookie);
      break; // Only need to trigger once
    }
  }

  // Check Claude
  const claudeCookie = await chrome.cookies.get({
    url: PROVIDERS.claude.url,
    name: PROVIDERS.claude.cookieName
  });
  if (claudeCookie) {
    handleClaudeCookie(claudeCookie);
  }
}

/**
 * Notify content script of token changes
 */
function notifyContentScript(provider, connected) {
  chrome.tabs.query({ url: 'http://localhost:8080/*' }, (tabs) => {
    tabs.forEach(tab => {
      chrome.tabs.sendMessage(tab.id, {
        type: 'TOKEN_UPDATE',
        provider: provider,
        connected: connected
      }).catch(() => {
        // Tab might not have content script loaded yet
      });
    });
  });
}

/**
 * Listen for messages from content script
 */
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.type === 'GET_TOKENS') {
    // Get all stored tokens
    chrome.storage.local.get(null, (data) => {
      sendResponse({
        chatgpt: {
          connected: data.chatgpt_connected || false,
          token: data.chatgpt_token,
          last_sync: data.chatgpt_last_sync
        },
        gemini: {
          connected: data.gemini_connected || false,
          cookies: data.gemini_cookies,
          last_sync: data.gemini_last_sync
        },
        claude: {
          connected: data.claude_connected || false,
          token: data.claude_token,
          last_sync: data.claude_last_sync
        }
      });
    });
    return true; // Keep channel open for async response
  }

  if (request.type === 'CONNECT_PROVIDER') {
    // Handle connection request from Rynude
    const { provider } = request;
    connectProvider(provider).then(success => {
      sendResponse({ success });
    });
    return true;
  }

  if (request.type === 'DISCONNECT_PROVIDER') {
    // Handle disconnection request
    const { provider } = request;
    disconnectProvider(provider).then(success => {
      sendResponse({ success });
    });
    return true;
  }

});

/**
 * Connect provider - check if token exists
 */
async function connectProvider(provider) {
  const data = await chrome.storage.local.get([`${provider}_token`, `${provider}_cookies`, `${provider}_connected`]);

  if (data[`${provider}_connected`]) {
    return true;
  }

  // Check for fresh cookies
  await checkAllProviderTokens();

  const updatedData = await chrome.storage.local.get([`${provider}_connected`]);
  return updatedData[`${provider}_connected`] || false;
}

/**
 * Disconnect provider
 */
async function disconnectProvider(provider) {
  await chrome.storage.local.remove([
    `${provider}_token`,
    `${provider}_cookies`,
    `${provider}_connected`,
    `${provider}_expires`,
    `${provider}_last_sync`
  ]);

  notifyContentScript(provider, false);
  return true;
}

/**
 * Periodic token refresh check (every 5 minutes)
 */
setInterval(() => {
  console.log('Checking for token updates...');
  checkAllProviderTokens();
}, 5 * 60 * 1000);

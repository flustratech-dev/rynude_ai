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

  if (request.type === 'WEB_COMPLETE' || request.type === 'CLAUDE_COMPLETE') {
    // Run a completion from INSIDE the provider's own tab (same-origin), so it
    // carries the real cookies + browser TLS and passes Cloudflare/bot defenses —
    // something a server-side request never can.
    webComplete(request.provider || 'claude', request.prompt || '')
      .then(content => sendResponse({ success: true, content }))
      .catch(err => sendResponse({ success: false, error: String(err && err.message || err) }));
    return true; // async
  }

});

/**
 * Find a logged-in claude.ai tab, or open one in the background, then run the
 * completion flow inside it via chrome.scripting (MAIN world = real page ctx).
 */
// Provider registry: which tab to use and which in-page flow to run.
const WEB_PROVIDERS = {
  claude:  { match: '*://claude.ai/*',         open: 'https://claude.ai/new',        flow: claudeApiFlow },
  chatgpt: { match: '*://chatgpt.com/*',       open: 'https://chatgpt.com/',         flow: chatgptApiFlow },
  gemini:  { match: '*://gemini.google.com/*', open: 'https://gemini.google.com/app', flow: geminiApiFlow },
};

async function webComplete(provider, prompt) {
  const cfg = WEB_PROVIDERS[provider];
  if (!cfg) {
    throw new Error('Provider tidak didukung: ' + provider);
  }

  // A freshly opened background tab may still be loading (or clearing a bot
  // challenge) when we first reach in, so retry until the app answers.
  // Re-acquire the tab each attempt so closing it mid-request just reopens a
  // fresh one instead of failing permanently.
  let lastErr = provider + ' completion failed';
  for (let attempt = 0; attempt < 8; attempt++) {
    let result = null;
    try {
      const tab = await getOrCreateTab(cfg.match, cfg.open);
      console.log('[Rynude]', provider, 'attempt', attempt + 1, 'via tab', tab.id, tab.url);
      const injection = await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        world: 'MAIN',
        args: [prompt],
        func: cfg.flow,
      });
      result = injection && injection[0] ? injection[0].result : null;
      console.log('[Rynude]', provider, 'result:', result);
    } catch (e) {
      // Tab closed / mid-navigation / not injectable yet — treat as transient.
      lastErr = String((e && e.message) || e);
      console.log('[Rynude]', provider, 'executeScript error:', lastErr);
    }

    if (result && result.ok === true) {
      return result.text;
    }
    if (result && result.error) {
      lastErr = result.error;
      // Hard errors (login/verification) won't fix themselves on retry — stop early.
      if (/login|verifikasi|proof-of-work|Arkose|tidak ditemukan/i.test(lastErr)) {
        throw new Error(lastErr);
      }
    }

    await new Promise((r) => setTimeout(r, 2500));
  }

  throw new Error(lastErr);
}

/**
 * Return an existing provider tab matching `match`, or open `open` in the
 * background. We do NOT wait on tabs.onUpdated (a bot interstitial can fire
 * "complete" before the real app is ready); webComplete's retry loop handles it.
 */
async function getOrCreateTab(match, open) {
  const existing = await chrome.tabs.query({ url: match });
  if (existing && existing.length) {
    return existing[0];
  }

  const tab = await chrome.tabs.create({ url: open, active: false });
  await new Promise((r) => setTimeout(r, 3000));
  return tab;
}

/**
 * Runs in the claude.ai page (MAIN world). Same-origin calls, so cookies +
 * Cloudflare clearance apply automatically. Returns { ok, text } or { ok:false, error }.
 */
async function claudeApiFlow(prompt) {
  try {
    const j = async (r) => {
      if (r.status === 403) throw new Error('Cloudflare masih menantang tab claude.ai — buka claude.ai sekali secara manual lalu coba lagi.');
      if (r.status === 401) throw new Error('Belum login di claude.ai (401).');
      if (!r.ok) throw new Error('claude.ai ' + r.status);
      return r.json();
    };

    const orgs = await j(await fetch('/api/organizations', { headers: { accept: 'application/json' }, credentials: 'include' }));
    const org = (orgs.find(o => (o.capabilities || []).includes('chat')) || orgs[0] || {}).uuid;
    if (!org) throw new Error('Tidak ada organization di sesi claude.ai ini.');

    const conv = await j(await fetch('/api/organizations/' + org + '/chat_conversations', {
      method: 'POST',
      headers: { 'content-type': 'application/json', accept: 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ uuid: crypto.randomUUID(), name: '' }),
    }));

    const res = await fetch('/api/organizations/' + org + '/chat_conversations/' + conv.uuid + '/completion', {
      method: 'POST',
      headers: { 'content-type': 'application/json', accept: 'text/event-stream' },
      credentials: 'include',
      body: JSON.stringify({
        prompt: prompt,
        parent_message_uuid: '00000000-0000-4000-8000-000000000000',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
        attachments: [],
        files: [],
        rendering_mode: 'messages',
      }),
    });
    if (res.status === 403) throw new Error('Cloudflare memblokir completion — buka claude.ai manual lalu ulangi.');
    if (!res.ok) throw new Error('completion ' + res.status);

    const body = await res.text();
    let text = '';
    body.split(/\r\n|\r|\n/).forEach((line) => {
      line = line.trim();
      if (!line.startsWith('data:')) return;
      const payload = line.slice(5).trim();
      if (!payload || payload === '[DONE]') return;
      try {
        const d = JSON.parse(payload);
        if (typeof d.completion === 'string') text += d.completion;
        else if (d.delta && typeof d.delta.text === 'string') text += d.delta.text;
      } catch (e) { /* ignore non-JSON keepalives */ }
    });

    if (!text) throw new Error('Jawaban kosong (format claude.ai mungkin berubah).');
    return { ok: true, text };
  } catch (e) {
    return { ok: false, error: String(e && e.message || e) };
  }
}

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
 * Runs in the chatgpt.com page (MAIN world). Best-effort: OpenAI now guards the
 * web API with a proof-of-work / Arkose challenge generated by their own bundled
 * code, which we can't reproduce — so we succeed only when it isn't required and
 * return a clear message otherwise.
 */
async function chatgptApiFlow(prompt) {
  try {
    const s = await fetch('/api/auth/session', { credentials: 'include', headers: { accept: 'application/json' } });
    if (s.status === 403) return { ok: false, error: 'Cloudflare/verifikasi di chatgpt.com — buka chatgpt.com manual lalu ulangi.' };
    const auth = await s.json().catch(() => null);
    const token = auth && auth.accessToken;
    if (!token) return { ok: false, error: 'Belum login di chatgpt.com.' };

    const rq = await fetch('/backend-api/sentinel/chat-requirements', {
      method: 'POST',
      credentials: 'include',
      headers: { 'content-type': 'application/json', authorization: 'Bearer ' + token },
      body: JSON.stringify({ p: '' }),
    });
    if (!rq.ok) return { ok: false, error: 'ChatGPT chat-requirements ' + rq.status + ' (verifikasi diperketat).' };
    const req = await rq.json().catch(() => ({}));

    if (req.proofofwork && req.proofofwork.required) {
      return { ok: false, error: 'ChatGPT butuh proof-of-work yang tidak bisa dihasilkan dari extension. Pakai Claude/Gemini atau API key resmi.' };
    }
    if (req.arkose && req.arkose.required) {
      return { ok: false, error: 'ChatGPT butuh Arkose captcha — tidak didukung.' };
    }

    const headers = { 'content-type': 'application/json', authorization: 'Bearer ' + token };
    if (req.token) headers['openai-sentinel-chat-requirements-token'] = req.token;

    const conv = await fetch('/backend-api/conversation', {
      method: 'POST',
      credentials: 'include',
      headers,
      body: JSON.stringify({
        action: 'next',
        messages: [{ id: crypto.randomUUID(), author: { role: 'user' }, content: { content_type: 'text', parts: [prompt] } }],
        parent_message_id: crypto.randomUUID(),
        model: 'auto',
        timezone_offset_min: new Date().getTimezoneOffset(),
        history_and_training_disabled: false,
      }),
    });
    if (conv.status === 403) return { ok: false, error: 'chatgpt.com menolak (403) — kemungkinan verifikasi bot.' };
    if (!conv.ok) return { ok: false, error: 'ChatGPT conversation ' + conv.status };

    const body = await conv.text();
    let text = '';
    body.split(/\r\n|\r|\n/).forEach((line) => {
      line = line.trim();
      if (!line.startsWith('data:')) return;
      const p = line.slice(5).trim();
      if (!p || p === '[DONE]') return;
      try {
        const d = JSON.parse(p);
        const parts = d && d.message && d.message.content && d.message.content.parts;
        if (Array.isArray(parts) && typeof parts[0] === 'string') text = parts.join('');
      } catch (e) { /* keepalive */ }
    });
    if (!text) return { ok: false, error: 'Jawaban ChatGPT kosong.' };
    return { ok: true, text };
  } catch (e) {
    return { ok: false, error: String((e && e.message) || e) };
  }
}

/**
 * Runs in the gemini.google.com page (MAIN world). Best-effort: uses the page's
 * SNlM0e token + the BardFrontendService batchexecute endpoint. The nested-array
 * response format is brittle and changes often.
 */
async function geminiApiFlow(prompt) {
  try {
    let at = (window.WIZ_global_data && window.WIZ_global_data.SNlM0e) || null;
    if (!at) {
      const m = document.documentElement.innerHTML.match(/"SNlM0e":"(.*?)"/);
      at = m ? m[1] : null;
    }
    if (!at) return { ok: false, error: 'Token Gemini (SNlM0e) tak ditemukan — buka gemini.google.com (login) lalu ulangi.' };

    const params = new URLSearchParams({
      bl: 'boq_assistant-bard-web-server',
      _reqid: String(Math.floor(Math.random() * 900000) + 100000),
      rt: 'c',
    });
    const form = new URLSearchParams();
    form.set('f.req', JSON.stringify([null, JSON.stringify([[prompt], null, null])]));
    form.set('at', at);

    const res = await fetch('/_/BardChatUi/data/assistant.lamda.BardFrontendService/StreamGenerate?' + params.toString(), {
      method: 'POST',
      credentials: 'include',
      headers: { 'content-type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: form.toString(),
    });
    if (res.status === 401 || res.status === 403) return { ok: false, error: 'Gemini menolak (' + res.status + ') — login ulang di gemini.google.com.' };
    if (!res.ok) return { ok: false, error: 'Gemini ' + res.status };

    const raw = await res.text();
    let text = '';
    raw.split('\n').forEach((line) => {
      const t = line.trim();
      if (!t.startsWith('[[')) return;
      try {
        const outer = JSON.parse(t);
        outer.forEach((item) => {
          if (item && item[0] === 'wrb.fr' && typeof item[2] === 'string') {
            const payload = JSON.parse(item[2]);
            const cand = payload && payload[4] && payload[4][0] && payload[4][0][1] && payload[4][0][1][0];
            if (typeof cand === 'string' && cand.length > text.length) text = cand;
          }
        });
      } catch (e) { /* not the payload line */ }
    });
    if (!text) return { ok: false, error: 'Jawaban Gemini kosong (format batchexecute mungkin berubah).' };
    return { ok: true, text };
  } catch (e) {
    return { ok: false, error: String((e && e.message) || e) };
  }
}

/**
 * Periodic token refresh check (every 5 minutes)
 */
setInterval(() => {
  console.log('Checking for token updates...');
  checkAllProviderTokens();
}, 5 * 60 * 1000);

/**
 * Rynude Connector - Content Script
 *
 * Injected into localhost:8080 (Rynude)
 * Bridges between the page and background script.
 * Since content scripts run in an isolated world, we inject a <script>
 * element into the MAIN world so Alpine.js can see window.rynudeExtension.
 */

(function() {
  'use strict';

  console.log('Rynude Connector: Content script loaded');

  // ── Bridge: forward requests from injected script to background ──────
  window.addEventListener('message', (event) => {
    if (!event.data || !event.data.rynudeRequest) return;
    const { id, type, data } = event.data;

    console.log('[Rynude] Forwarding request to background:', type);
    chrome.runtime.sendMessage({ type, ...data }, (result) => {
      console.log('[Rynude] Response from background:', type, result);
      window.postMessage({ rynudeResponse: true, id, result }, '*');
    });
  });

  // ── Inject API into the page's MAIN world ───────────────────────────
  const script = document.createElement('script');
  script.textContent = `
(function() {
  'use strict';

  // Promise-based request to the content script via postMessage
  function _rq(type, data) {
    return new Promise(function(resolve) {
      var id = Date.now() + '_' + Math.random();
      function handler(e) {
        if (e.data && e.data.rynudeResponse && e.data.id === id) {
          window.removeEventListener('message', handler);
          resolve(e.data.result);
        }
      }
      window.addEventListener('message', handler);
      window.postMessage({ rynudeRequest: true, id: id, type: type, data: data || {} }, '*');
    });
  }

  function getCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|;\\\\s*)' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  }

  window.rynudeExtension = {
    version: '1.0.0',
    installed: true,

    async getStatus() {
      console.log('[Rynude] Getting extension status...');
      var result = await _rq('GET_TOKENS');
      console.log('[Rynude] Extension status:', result);
      return result;
    },

    async checkProvider(provider) {
      var status = await this.getStatus();
      return status ? (status[provider] && status[provider].connected) || false : false;
    },

    async connectProvider(provider) {
      try {
        console.log('[Rynude] Connecting provider:', provider);

        // Step 1: Check if extension has tokens
        var resp = await _rq('CONNECT_PROVIDER', { provider: provider });
        console.log('[Rynude] CONNECT_PROVIDER response:', resp);

        if (!resp || !resp.success) {
          throw new Error('No ' + provider + ' session found. Please login to ' + provider + ' in another tab first, then try again.');
        }

        // Step 2: Get the tokens
        var status = await this.getStatus();
        var pd = status[provider];
        console.log('[Rynude] Provider data:', pd);

        if (!pd || !pd.connected) {
          throw new Error('Failed to retrieve ' + provider + ' tokens from extension');
        }

        // Step 3: Send tokens to Rynude API
        console.log('[Rynude] Sending tokens to API...');
        var apiResp = await fetch(window.location.origin + '/api/provider-tokens', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          credentials: 'include',
          body: JSON.stringify({
            provider: provider,
            token: pd.token,
            cookies: pd.cookies
          })
        });

        console.log('[Rynude] API response status:', apiResp.status);

        if (!apiResp.ok) {
          var errBody = '';
          try {
            var errJson = await apiResp.json();
            errBody = errJson.message || JSON.stringify(errJson);
          } catch(e) {
            errBody = await apiResp.text();
          }
          throw new Error('API Error (' + apiResp.status + '): ' + errBody);
        }

        var apiData = await apiResp.json();
        console.log('[Rynude] API response data:', apiData);

        window.dispatchEvent(new CustomEvent('rynude-provider-connected', {
          detail: { provider: provider, timestamp: Date.now() }
        }));

        return true;
      } catch (error) {
        console.error('[Rynude] Failed to connect ' + provider + ':', error);
        alert('Failed to connect ' + provider + ':\\n\\n' + error.message);
        return false;
      }
    },

    async disconnectProvider(provider) {
      try {
        var delResp = await fetch(window.location.origin + '/api/provider-tokens/' + provider, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json' },
          credentials: 'include'
        });

        await _rq('DISCONNECT_PROVIDER', { provider: provider });

        if (delResp.ok) {
          window.dispatchEvent(new CustomEvent('rynude-provider-disconnected', {
            detail: { provider: provider, timestamp: Date.now() }
          }));
          return true;
        }
        return false;
      } catch (error) {
        console.error('Failed to disconnect ' + provider + ':', error);
        return false;
      }
    },

    async checkChatGPT(callback) {
      var c = await this.checkProvider('chatgpt');
      if (callback) callback(c);
      return c;
    },

    async checkGemini(callback) {
      var c = await this.checkProvider('gemini');
      if (callback) callback(c);
      return c;
    },

    async checkClaude(callback) {
      var c = await this.checkProvider('claude');
      if (callback) callback(c);
      return c;
    }
  };

  // Check initial status on load
  (async function() {
    try {
      var status = await window.rynudeExtension.getStatus();
      if (status) {
        Object.keys(status).forEach(function(provider) {
          if (status[provider] && status[provider].connected) {
            window.dispatchEvent(new CustomEvent('rynude-provider-connected', {
              detail: { provider: provider, initial: true }
            }));
          }
        });
      }
    } catch(e) {
      console.error('Rynude: initial status check failed', e);
    }
  })();

  window.dispatchEvent(new CustomEvent('rynude-extension-ready', {
    detail: { version: '1.0.0' }
  }));

  console.log('Rynude Connector: API exposed at window.rynudeExtension');
})();
`;
  document.documentElement.appendChild(script);
  script.remove();

  // ── Forward TOKEN_UPDATE from background to page ────────────────────
  chrome.runtime.onMessage.addListener((message) => {
    if (message.type === 'TOKEN_UPDATE') {
      window.dispatchEvent(new CustomEvent('rynude-token-update', {
        detail: { provider: message.provider, connected: message.connected }
      }));
    }
  });

  console.log('Rynude Connector: Bridge active');
})();

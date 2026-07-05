/**
 * Rynude Connector - MAIN-world API
 *
 * Declared in manifest.json as a content script with "world": "MAIN", so the
 * browser injects it directly into the page's JavaScript context. Unlike a
 * <script> element appended to the DOM, a manifest-declared content script is
 * NOT subject to the page's Content-Security-Policy — which is exactly what
 * blocked the old inline-injection approach ("Executing inline script violates
 * ... CSP directive 'script-src ...'").
 *
 * It cannot use chrome.* APIs (MAIN world has no extension access), so it talks
 * to the ISOLATED-world bridge (content-script.js) via window.postMessage.
 */
(function () {
  'use strict';

  // Promise-based request to the isolated content script.
  function _rq(type, data) {
    return new Promise(function (resolve) {
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

  window.rynudeExtension = {
    version: '1.1.0',
    installed: true,

    async getStatus() {
      return await _rq('GET_TOKENS');
    },

    async checkProvider(provider) {
      var status = await this.getStatus();
      return status ? (status[provider] && status[provider].connected) || false : false;
    },

    async connectProvider(provider) {
      try {
        var resp = await _rq('CONNECT_PROVIDER', { provider: provider });
        if (!resp || !resp.success) {
          throw new Error('No ' + provider + ' session found. Please login to ' + provider + ' in another tab first, then try again.');
        }

        var status = await this.getStatus();
        var pd = status[provider];
        if (!pd || !pd.connected) {
          throw new Error('Failed to retrieve ' + provider + ' tokens from extension');
        }

        var apiResp = await fetch(window.location.origin + '/api/provider-tokens', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ provider: provider, token: pd.token, cookies: pd.cookies })
        });

        if (!apiResp.ok) {
          var errBody = '';
          try {
            var errJson = await apiResp.json();
            errBody = errJson.message || JSON.stringify(errJson);
          } catch (e) {
            errBody = await apiResp.text();
          }
          throw new Error('API Error (' + apiResp.status + '): ' + errBody);
        }

        window.dispatchEvent(new CustomEvent('rynude-provider-connected', {
          detail: { provider: provider, timestamp: Date.now() }
        }));
        return true;
      } catch (error) {
        console.error('[Rynude] Failed to connect ' + provider + ':', error);
        alert('Failed to connect ' + provider + ':\n\n' + error.message);
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

    async checkChatGPT(cb) { var c = await this.checkProvider('chatgpt'); if (cb) cb(c); return c; },
    async checkGemini(cb) { var c = await this.checkProvider('gemini'); if (cb) cb(c); return c; },
    async checkClaude(cb) { var c = await this.checkProvider('claude'); if (cb) cb(c); return c; },

    // Run a completion through a real provider tab (claude.ai / chatgpt.com /
    // gemini.google.com), bypassing Cloudflare/bot defenses via same-origin.
    // Returns { success: true, content } or { success: false, error }.
    async webComplete(provider, prompt) {
      return await _rq('WEB_COMPLETE', { provider: provider || 'claude', prompt: prompt });
    },

    // Stream completion through a provider tab, firing onChunk(chunk) as tokens arrive.
    async webCompleteStream(provider, prompt, onChunk) {
      var handler = function(e) {
        if (e.detail && e.detail.chunk && (!provider || e.detail.provider === provider)) {
          if (onChunk) onChunk(e.detail.chunk);
        }
      };
      window.addEventListener('rynude-web-stream-chunk', handler);
      try {
        var res = await this.webComplete(provider, prompt);
        return res;
      } finally {
        window.removeEventListener('rynude-web-stream-chunk', handler);
      }
    },

    // Backwards-compatible alias.
    async claudeComplete(prompt) {
      return await this.webComplete('claude', prompt);
    }
  };

  // Announce initial status so the page can reflect already-connected providers.
  (async function () {
    try {
      var status = await window.rynudeExtension.getStatus();
      if (status) {
        Object.keys(status).forEach(function (provider) {
          if (status[provider] && status[provider].connected) {
            window.dispatchEvent(new CustomEvent('rynude-provider-connected', {
              detail: { provider: provider, initial: true }
            }));
          }
        });
      }
    } catch (e) {
      console.error('Rynude: initial status check failed', e);
    }
  })();

  window.dispatchEvent(new CustomEvent('rynude-extension-ready', { detail: { version: '1.1.0' } }));
  console.log('Rynude Connector: API exposed at window.rynudeExtension (MAIN world)');
})();

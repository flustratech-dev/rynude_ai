/**
 * Rynude Connector - Content Script (ISOLATED-world bridge)
 *
 * Injected into localhost:8080 (Rynude) in the isolated world so it can use the
 * chrome.* APIs. The page-facing API (window.rynudeExtension) lives in
 * injected.js, which runs in the MAIN world (see manifest.json "world": "MAIN").
 * This file only bridges messages between the two:
 *   - page/injected.js  --window.postMessage-->  here  --chrome.runtime-->  background
 *   - background        --chrome.tabs message -->  here  --CustomEvent-->  page
 *
 * The MAIN-world script is used (instead of appending an inline <script>)
 * because the app serves a Content-Security-Policy that blocks inline scripts.
 */
(function () {
  'use strict';

  console.log('Rynude Connector: Content script bridge loaded');

  // Forward requests from the MAIN-world API to the background service worker.
  window.addEventListener('message', (event) => {
    if (!event.data || !event.data.rynudeRequest) return;
    const { id, type, data } = event.data;

    const reply = (result) => window.postMessage({ rynudeResponse: true, id, result }, '*');

    // After the extension is reloaded/updated, this old content script's
    // chrome.runtime handle is dead ("Extension context invalidated"). Reply
    // with an error instead of throwing an uncaught exception (and hanging the
    // caller), so the page can tell the user to refresh.
    try {
      if (!chrome.runtime || !chrome.runtime.id) {
        reply({ success: false, error: 'Extension di-reload — refresh halaman ini (Ctrl+R) untuk menyambung ulang.' });
        return;
      }
      chrome.runtime.sendMessage({ type, ...data }, (result) => {
        if (chrome.runtime.lastError) {
          reply({ success: false, error: chrome.runtime.lastError.message });
          return;
        }
        reply(result);
      });
    } catch (e) {
      reply({ success: false, error: String((e && e.message) || e) });
    }
  });

  // Forward TOKEN_UPDATE pushes from the background to the page.
  chrome.runtime.onMessage.addListener((message) => {
    if (message.type === 'TOKEN_UPDATE') {
      window.dispatchEvent(new CustomEvent('rynude-token-update', {
        detail: { provider: message.provider, connected: message.connected }
      }));
    }
  });
})();

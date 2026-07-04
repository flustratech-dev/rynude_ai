// Minimal service worker: exists to satisfy PWA installability.
// All requests pass through to the network untouched.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});

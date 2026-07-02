/**
 * Rynude Connector - Popup Script
 *
 * Displays connection status for each provider
 */

document.addEventListener('DOMContentLoaded', async () => {
  const loading = document.getElementById('loading');
  const providers = document.getElementById('providers');

  try {
    // Get status from background script
    const response = await chrome.runtime.sendMessage({
      type: 'GET_TOKENS'
    });

    // Hide loading, show providers
    loading.style.display = 'none';
    providers.style.display = 'block';

    // Update ChatGPT status
    updateProviderStatus('chatgpt', response.chatgpt);

    // Update Gemini status
    updateProviderStatus('gemini', response.gemini);

    // Update Claude status
    updateProviderStatus('claude', response.claude);

  } catch (error) {
    loading.textContent = 'Error loading status';
    console.error('Error:', error);
  }
});

/**
 * Update provider status display
 */
function updateProviderStatus(provider, data) {
  const statusEl = document.getElementById(`${provider}-status`);

  if (!statusEl) return;

  if (data && data.connected) {
    statusEl.className = 'status connected';
    statusEl.innerHTML = '<div class="status-dot"></div><span>Connected</span>';
  } else {
    statusEl.className = 'status disconnected';
    statusEl.innerHTML = '<div class="status-dot"></div><span>Not Connected</span>';
  }
}

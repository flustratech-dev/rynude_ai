import './bootstrap';
import Alpine from 'alpinejs';
import mermaid from 'mermaid';

// Initialize Mermaid.js
mermaid.initialize({
    startOnLoad: false,
    theme: 'default',
    securityLevel: 'loose',
    fontFamily: 'inherit',
});

// Make mermaid globally available
window.mermaid = mermaid;

// Initialize Alpine.js
window.Alpine = Alpine;

// Reusable speech-to-text dictation for the chat input. Appends the recognised
// transcript to the parent Alpine component's `prompt` property via Alpine's
// $dispatch event system. Gracefully no-ops on browsers without the Web Speech API.
Alpine.data('voiceInput', () => ({
    listening: false,
        supported: 'SpeechRecognition' in window || 'webkitSpeechRecognition' in window,
        recognition: null,

        toggle() {
            if (!this.supported) return;
            if (this.listening) {
                this.recognition && this.recognition.stop();
                return;
            }
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            const rec = new SR();
            rec.lang = document.documentElement.lang || 'en-US';
            rec.interimResults = false;
            rec.continuous = false;

            rec.onstart = () => { this.listening = true; };
            rec.onend = () => { this.listening = false; };
            rec.onerror = () => { this.listening = false; };
            rec.onresult = (e) => {
                let transcript = '';
                for (let i = e.resultIndex; i < e.results.length; i++) {
                    transcript += e.results[i][0].transcript;
                }
                transcript = transcript.trim();
                if (transcript) {
                    // Use Alpine's magic properties to access parent component
                    // Find the closest Alpine component with a 'prompt' property
                    const parentEl = this.$el.closest('[x-data]');
                    if (parentEl && parentEl._x_dataStack) {
                        const parentData = parentEl._x_dataStack[0];
                        if (parentData && 'prompt' in parentData) {
                            const current = (parentData.prompt || '').trim();
                            parentData.prompt = current ? current + ' ' + transcript : transcript;
                        } else if (parentData && 'projectChatPrompt' in parentData) {
                            // For project panel's chat input
                            const current = (parentData.projectChatPrompt || '').trim();
                            parentData.projectChatPrompt = current ? current + ' ' + transcript : transcript;
                        }
                    }
                }
            };

            this.recognition = rec;
            rec.start();
        },
    }));

Alpine.start();

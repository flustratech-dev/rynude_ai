import './bootstrap';

// NOTE: Alpine is provided and started by Livewire — do not import/start it here
// or you'll get "multiple instances of Alpine" errors. We only register a data
// component on the `alpine:init` event that Livewire's Alpine dispatches.

// Reusable speech-to-text dictation for the chat input. Appends the recognised
// transcript to the Livewire `prompt` property. Gracefully no-ops on browsers
// without the Web Speech API.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('voiceInput', () => ({
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
                if (transcript && this.$wire) {
                    const current = (this.$wire.prompt || '').trim();
                    this.$wire.set('prompt', current ? current + ' ' + transcript : transcript);
                }
            };

            this.recognition = rec;
            rec.start();
        },
    }));
});

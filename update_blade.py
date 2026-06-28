import sys
import re

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

chatApp_new = '''function chatApp(initialMessages = []) {
        return {
            messages: initialMessages,
            prompt: '',
            isDropping: false,
            workflowId: @entangle('currentWorkflowId'),
            conversationId: @entangle('conversationId'),
            projectId: @entangle('activeProjectId'),
            selectedModel: @entangle('selectedModel'),
            es: null,
            streamingMessage: '',
            seenSequences: new Set(),
            buffer: {},
            expectedSeq: 0,
            csrfToken: "{{ csrf_token() }}",

            async submitMessage() {
                if (!this.prompt.trim()) return;

                const text = this.prompt;
                this.prompt = '';

                // Add to UI immediately
                this.messages.push({
                    role: 'user',
                    content: text,
                    attachments: []
                });
                
                // Auto scroll
                setTimeout(() => {
                    const container = document.getElementById('chat-scroll-container');
                    if (container) container.scrollTop = container.scrollHeight;
                }, 50);

                if (!this.workflowId) {
                    this.workflowId = crypto.randomUUID();
                }

                const formData = new FormData();
                formData.append('message', text);
                formData.append('workflow_id', this.workflowId);
                // Need to send the updated messages array to the backend for context
                const filteredMessages = this.messages.filter(m => m.role !== 'system');
                for (let i = 0; i < filteredMessages.length; i++) {
                    formData.append(`messages[${i}][role]`, filteredMessages[i].role);
                    formData.append(`messages[${i}][content]`, filteredMessages[i].content);
                }
                
                formData.append('_token', this.csrfToken);
                
                if (this.conversationId) formData.append('conversation_id', this.conversationId);
                if (this.projectId) formData.append('project_id', this.projectId);
                if (this.selectedModel) formData.append('model', this.selectedModel);
                
                // Add attachments if needed
                const fileInput = document.getElementById('file-upload');
                if (fileInput && fileInput.files.length > 0) {
                    for (let i = 0; i < fileInput.files.length; i++) {
                        formData.append('attachments[]', fileInput.files[i]);
                    }
                    fileInput.value = ''; // clear input
                }

                try {
                    const response = await fetch('/chat/send', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.conversation_id && !this.conversationId) {
                        this.conversationId = result.conversation_id;
                    }
                    
                    // Initialize SSE
                    this.initSSE();
                } catch (error) {
                    console.error('Failed to send message', error);
                }
            },'''

# Replace from function chatApp(initialMessages = []) { to init() {
content = re.sub(r'function chatApp\(initialMessages = \[\]\) \{[\s\S]*?init\(\) \{', chatApp_new + '\n            init() {', content)

# 2. Update forms
content = content.replace('<form wire:submit.prevent="sendMessage"', '<form @submit.prevent="submitMessage"')
content = content.replace('wire:model="prompt"', 'x-model="prompt"')
content = content.replace('wire:model="attachments"', '') 
content = content.replace('wire:keydown.enter.prevent="sendMessage"', '@keydown.enter.prevent="submitMessage()"')
content = content.replace('wire:click="sendMessage"', '@click="submitMessage()"')
content = content.replace('wire:target="sendMessage"', 'wire:target="dummy"') 

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
print('Updated chat-interface.blade.php')

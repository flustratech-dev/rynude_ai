import sys

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('<div wire:stream="agent-stream-target" class="hidden"></div>', '<div wire:stream="agent-stream-target" class="hidden" wire:ignore></div>')
content = content.replace('wire:stream="message-stream"', 'wire:stream="message-stream" wire:ignore')

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

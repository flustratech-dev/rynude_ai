import sys

def patch_timeline():
    with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-timeline.blade.php', 'r', encoding='utf-8') as f:
        content = f.read()
    content = content.replace('<div x-data=', '<div wire:ignore x-data=')
    with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-timeline.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)

def patch_feed():
    with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-feed.blade.php', 'r', encoding='utf-8') as f:
        content = f.read()
    content = content.replace('<div x-data=', '<div wire:ignore x-data=')
    with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-feed.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)

def patch_chat():
    with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. stream targets
    content = content.replace('<div wire:stream="agent-stream-target" class="hidden"></div>', '<div wire:stream="agent-stream-target" class="hidden" wire:ignore></div>')
    content = content.replace('wire:stream="message-stream"', 'wire:stream="message-stream" wire:ignore')
    
    # 2. Add wire:ignore to the assistant message prose div so enhanceCodeBlocks survives
    target_prose = 'class="text-[#0B0B0B] dark:text-stone-200 text-[16px] leading-[1.6] max-w-[90%] prose prose-stone'
    
    lines = content.split('\\n')
    for i, line in enumerate(lines):
        if target_prose in line and i + 1 < len(lines) and 'Illuminate\\Support\\Str::markdown' in lines[i+1]:
            lines[i] = line.replace('class="text-[#0B', 'wire:ignore class="text-[#0B')
    
    # 3. Add wire:ignore to the user message prose div for consistency
    for i, line in enumerate(lines):
        if 'bg-stone-100 dark:bg-stone-800 border border-transparent' in line and i + 1 < len(lines) and '{{ $msg[' in lines[i+1] and 'content' in lines[i+1]:
            lines[i] = line.replace('class="bg-stone-100', 'wire:ignore class="bg-stone-100')
            
    content = '\\n'.join(lines)
    
    with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)

patch_timeline()
patch_feed()
patch_chat()
print("Patched all files successfully")

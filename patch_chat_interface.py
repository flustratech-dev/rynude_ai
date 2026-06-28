with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Clean duplicate wire:ignore on message-stream
content = content.replace('wire:stream="message-stream" wire:ignore wire:ignore', 'wire:stream="message-stream" wire:ignore')

# 2. Add wire:ignore to the assistant message content div to protect code blocks
target_assistant_prose = 'prose prose-stone dark:prose-invert max-w-none w-full font-claude-response'
# We find where this class is used under the assistant message template
# Line 448 starts with: <div class="text-[#0B0B0B] dark:text-stone-200 text-[16px] leading-[1.6] max-w-[90%] prose prose-stone...
lines = content.split('\n')
patched_prose = False
for i, line in enumerate(lines):
    if target_assistant_prose in line and 'style="font-family:' in line and 'wire:ignore' not in line:
        # Check if it is inside the assistant message block (around line 448)
        # We can insert wire:ignore
        lines[i] = line.replace('<div class="text-[#0B0B0B]', '<div wire:ignore class="text-[#0B0B0B]')
        patched_prose = True
        print(f"Patched assistant message prose at line {i+1}")
        break

if not patched_prose:
    print("WARNING: Assistant message prose was not patched!")

content = '\n'.join(lines)

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\chat-interface.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

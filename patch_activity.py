with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-feed.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

if 'wire:ignore' not in lines[0]:
    lines[0] = lines[0].replace('<div ', '<div wire:ignore ')

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-feed.blade.php', 'w', encoding='utf-8') as f:
    f.writelines(lines)

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-timeline.blade.php', 'r', encoding='utf-8') as f:
    lines2 = f.readlines()

if 'wire:ignore' not in lines2[0]:
    lines2[0] = lines2[0].replace('<div ', '<div wire:ignore ')

with open('d:\\claude-ui-clone\\resources\\views\\livewire\\activity-timeline.blade.php', 'w', encoding='utf-8') as f:
    f.writelines(lines2)

print("Applied wire:ignore to activity-feed and activity-timeline")

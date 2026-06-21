<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class ChatsPanel extends Component
{
    public string $searchQuery = '';
    public array $conversations = [];
    public bool $isSelectMode = false;
    public array $selectedChats = [];
    public string $filterType = 'all';
    public bool $showArchived = false;
    public ?int $renamingId = null;
    public string $renameTitle = '';


    public function mount()
    {
        $this->loadConversations();
    }

    #[On('chatCreated')]
    #[On('messageAdded')]
    public function loadConversations()
    {
        $userId = auth()->id();
        $query = \App\Models\Conversation::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($this->showArchived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        $this->conversations = $query->orderByDesc('updated_at')->take(100)->get()->map(function($c) {
            $lastMessage = $c->messages()->latest('id')->first();
            $preview = $lastMessage ? \Illuminate\Support\Str::limit(strip_tags($lastMessage->content), 80) : null;

            return [
                'id' => $c->id,
                'title' => $c->title ?? 'New Chat',
                'preview' => $preview,
                'updated_at' => $c->updated_at->format('Y-m-d'),
                'archived' => $c->archived_at !== null,
                'group' => $this->determineGroup($c->updated_at)
            ];
        })->toArray();
    }

    public function toggleShowArchived()
    {
        $this->showArchived = !$this->showArchived;
        $this->isSelectMode = false;
        $this->selectedChats = [];
        $this->loadConversations();
    }

    private function determineGroup($date)
    {
        if ($date->isToday()) return 'Today';
        if ($date->isYesterday()) return 'Yesterday';
        if ($date->greaterThanOrEqualTo(now()->subDays(7))) return 'Previous 7 days';
        return 'Older';
    }

    public function getFilteredConversations(): array
    {
        $filtered = $this->conversations;

        if ($this->filterType !== 'all') {
            $filtered = array_filter($filtered, function ($c) {
                if ($this->filterType === 'today') {
                    return $c['group'] === 'Today';
                }
                if ($this->filterType === 'week') {
                    return in_array($c['group'], ['Today', 'Yesterday', 'Previous 7 days']);
                }
                return true;
            });
        }

        if (!empty($this->searchQuery)) {
            $filtered = array_filter($filtered, function ($c) {
                return str_contains(strtolower($c['title']), strtolower($this->searchQuery));
            });
        }

        return array_values($filtered);
    }

    public function getGroupedConversations(): array
    {
        $filtered = $this->getFilteredConversations();
        $grouped = [];

        foreach ($filtered as $conversation) {
            $group = $conversation['group'];
            $grouped[$group][] = $conversation;
        }

        return $grouped;
    }

    public function selectConversation($id)
    {
        $this->dispatch('close-panel');
        $this->dispatch('selectConversation', conversationId: $id);
    }

    public function deleteConversation($id)
    {
        $conversation = \App\Models\Conversation::where('user_id', auth()->id())->find($id);
        if ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
            $this->loadConversations();
        }
    }

    public function startRename($id)
    {
        $conversation = \App\Models\Conversation::where('user_id', auth()->id())->find($id);
        $this->renamingId = $id;
        $this->renameTitle = $conversation->title ?? '';
    }

    public function cancelRename()
    {
        $this->renamingId = null;
        $this->renameTitle = '';
    }

    public function renameConversation($id, $newTitle = null)
    {
        $newTitle = $newTitle ?? $this->renameTitle;
        $title = trim((string) $newTitle);

        if ($title === '') {
            $this->cancelRename();
            return;
        }

        $conversation = \App\Models\Conversation::where('user_id', auth()->id())->find($id);
        if ($conversation) {
            $conversation->title = \Illuminate\Support\Str::limit($title, 255, '');
            $conversation->save();
        }

        $this->cancelRename();
        $this->loadConversations();
        $this->dispatch('chatCreated'); // refresh sidebar/other panels
    }

    public function archiveConversation($id)
    {
        $conversation = \App\Models\Conversation::where('user_id', auth()->id())->find($id);
        if ($conversation) {
            $conversation->archived_at = $conversation->archived_at ? null : now();
            $conversation->save();
            $this->loadConversations();
        }
    }

    public function exportConversation($id, $format = 'md')
    {
        $conversation = \App\Models\Conversation::with('messages')
            ->where('user_id', auth()->id())
            ->find($id);

        if (!$conversation) {
            return;
        }

        $title = $conversation->title ?: 'chat';
        $slug = \Illuminate\Support\Str::slug($title) ?: 'chat';

        if ($format === 'json') {
            $payload = [
                'title' => $conversation->title,
                'created_at' => optional($conversation->created_at)->toIso8601String(),
                'messages' => $conversation->messages->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                ])->toArray(),
            ];
            $data = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return response()->streamDownload(function () use ($data) {
                echo $data;
            }, $slug . '.json', ['Content-Type' => 'application/json']);
        }

        // Default: Markdown
        $lines = ["# {$title}", ''];
        foreach ($conversation->messages as $m) {
            $who = $m->role === 'user' ? 'You' : 'Rynude';
            $lines[] = "## {$who}";
            $lines[] = '';
            $lines[] = $m->content;
            $lines[] = '';
        }
        $md = implode("\n", $lines);

        return response()->streamDownload(function () use ($md) {
            echo $md;
        }, $slug . '.md', ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    public function toggleSelectMode()
    {
        $this->isSelectMode = !$this->isSelectMode;
        if (!$this->isSelectMode) {
            $this->selectedChats = [];
        }
    }

    public function toggleChatSelection($id)
    {
        if (in_array($id, $this->selectedChats)) {
            $this->selectedChats = array_values(array_diff($this->selectedChats, [$id]));
        } else {
            $this->selectedChats[] = $id;
        }
    }

    public function setFilter($type)
    {
        $this->filterType = $type;
    }

    public function deleteSelectedChats()
    {
        if (empty($this->selectedChats)) return;
        
        $conversations = \App\Models\Conversation::whereIn('id', $this->selectedChats)
            ->where('user_id', auth()->id())
            ->get();
            
        foreach ($conversations as $c) {
            $c->messages()->delete();
            $c->delete();
        }
        
        $this->loadConversations();
        $this->isSelectMode = false;
        $this->selectedChats = [];
    }

    public function archiveSelectedChats()
    {
        if (empty($this->selectedChats)) return;

        \App\Models\Conversation::whereIn('id', $this->selectedChats)
            ->where('user_id', auth()->id())
            ->update(['archived_at' => $this->showArchived ? null : now()]);

        $this->loadConversations();
        $this->isSelectMode = false;
        $this->selectedChats = [];
    }

    public function closePanel()
    {
        $this->dispatch('close-panel');
    }

    public function startNewChat()
    {
        $this->dispatch('close-panel');
        $this->dispatch('newChat');
    }

    public function render()
    {
        return view('livewire.chats-panel');
    }
}

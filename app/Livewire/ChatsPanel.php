<?php

namespace App\Livewire;

use Livewire\Component;

class ChatsPanel extends Component
{
    public string $searchQuery = '';
    public array $conversations = [];
    public bool $isSelectMode = false;
    public array $selectedChats = [];
    public string $filterType = 'all';


    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $userId = auth()->id();
        $query = \App\Models\Conversation::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $this->conversations = $query->orderByDesc('updated_at')->get()->map(function($c) {
            return [
                'id' => $c->id,
                'title' => $c->title ?? 'New Chat',
                'updated_at' => $c->updated_at->format('Y-m-d'),
                'group' => $this->determineGroup($c->updated_at)
            ];
        })->toArray();
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

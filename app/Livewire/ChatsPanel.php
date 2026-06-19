<?php

namespace App\Livewire;

use Livewire\Component;

class ChatsPanel extends Component
{
    public string $searchQuery = '';
    public array $conversations = [];

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
        if (empty($this->searchQuery)) {
            return $this->conversations;
        }

        return array_values(array_filter($this->conversations, function ($c) {
            return str_contains(strtolower($c['title']), strtolower($this->searchQuery));
        }));
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
        $this->conversations = array_values(array_filter($this->conversations, fn($c) => $c['id'] !== $id));
    }

    public function closePanel()
    {
        $this->dispatch('close-panel');
    }

    public function render()
    {
        return view('livewire.chats-panel');
    }
}

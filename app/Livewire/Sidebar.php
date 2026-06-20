<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public $conversations = [];
    public $selectedConversation = null;
    public $searchQuery = '';
    public $groups = [];

    public ?string $activePanel = null;
    public bool $artifactPanelOpen = false;

    public function mount(?string $activePanel = null, bool $artifactPanelOpen = false)
    {
        $this->activePanel = $activePanel;
        $this->artifactPanelOpen = $artifactPanelOpen;
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
        
        $conversations = $query->orderByDesc('updated_at')->get()->map(function($c) {
            return [
                'id' => $c->id,
                'title' => $c->title ?? 'New Chat',
                'updated_at' => $c->updated_at->format('Y-m-d'),
                'group' => $this->determineGroup($c->updated_at)
            ];
        });

        $this->groups = [];
        foreach ($conversations as $c) {
            $this->groups[$c['group']][] = $c;
        }
        
        $this->flattenConversations();
    }

    private function determineGroup($date)
    {
        if ($date->isToday()) return 'Today';
        if ($date->isYesterday()) return 'Yesterday';
        if ($date->greaterThanOrEqualTo(now()->subDays(7))) return 'Previous 7 days';
        return 'Older';
    }

    public function flattenConversations()
    {
        $this->conversations = collect($this->groups)->flatten(1)->toArray();
    }

    public function selectConversation($id)
    {
        $this->selectedConversation = $id;
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
            
            if ($this->selectedConversation === $id) {
                $this->startNewChat();
            }
        }
    }

    public function startNewChat()
    {
        $this->selectedConversation = null;
        $this->dispatch('close-panel');
        $this->dispatch('newChat');
    }

    public function openPanel($panel)
    {
        \Illuminate\Support\Facades\Log::info('Sidebar openPanel called: ' . $panel);
        $this->dispatch('open-panel', panel: $panel);
    }

    public function openSettingsModal($tab = 'general')
    {
        $this->dispatch('open-settings-modal', tab: $tab);
    }

    public function openHelpModal($tab = 'help')
    {
        $this->dispatch('open-help-modal', tab: $tab);
    }

    public function render()
    {
        return view('livewire.sidebar');
    }
}

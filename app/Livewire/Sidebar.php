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
    public bool $hasUpdate = false;
    public bool $sidebarOpen = true;

    public function mount(?string $activePanel = null, bool $artifactPanelOpen = false, bool $sidebarOpen = true)
    {
        $this->activePanel = $activePanel;
        $this->artifactPanelOpen = $artifactPanelOpen;
        $this->sidebarOpen = $sidebarOpen;
        $this->loadConversations();
        $this->checkForUpdates();
    }

    public function checkForUpdates()
    {
        try {
            // Check every 5 minutes (300 seconds)
            $this->hasUpdate = \Illuminate\Support\Facades\Cache::remember('system_update_available', 300, function () {
                if (!function_exists('shell_exec')) return false;
                
                $localHash = trim(shell_exec('git rev-parse HEAD 2> nul'));
                $remoteOutput = trim(shell_exec('git ls-remote origin -h refs/heads/main 2> nul'));
                $remoteHash = explode("\t", $remoteOutput)[0] ?? null;
                
                if ($localHash && $remoteHash && $localHash !== $remoteHash) {
                    return true;
                }
                return false;
            });
        } catch (\Exception $e) {
            $this->hasUpdate = false;
        }
    }

    #[On('chatCreated')]
    #[On('messageAdded')]
    public function loadConversations()
    {
        $userId = auth()->id();
        $query = \App\Models\Conversation::query()->whereNull('archived_at');
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $search = trim($this->searchQuery);
        if ($search !== '') {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $conversations = $query->orderByDesc('updated_at')->take($search !== '' ? 100 : 30)->get()->map(function($c) {
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

    public function updatedSearchQuery()
    {
        $this->loadConversations();
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

<?php

namespace App\Livewire;

use Livewire\Component;

class ChatLayout extends Component
{
    public bool $sidebarOpen = true;

    public $artifactPanelOpen = false;
    public $settingsOpen = false;

    public ?string $activePanel = null; // null | 'chats' | 'projects' | 'code'

    public function toggleArtifactPanel()
    {
        $this->artifactPanelOpen = !$this->artifactPanelOpen;
    }

    public function openSettingsModal()
    {
        $this->settingsOpen = true;
    }

    public function closeSettingsModal()
    {
        $this->settingsOpen = false;
    }

    #[\Livewire\Attributes\On('open-panel')]
    public function togglePanel($panel)
    {
        \Illuminate\Support\Facades\Log::info('ChatLayout togglePanel received: ' . json_encode($panel));
        // Handle if Alpine passes an array through dispatch detail
        $panelName = is_array($panel) && isset($panel['panel']) ? $panel['panel'] : (is_string($panel) ? $panel : null);
        
        \Illuminate\Support\Facades\Log::info('ChatLayout panelName resolved to: ' . $panelName);

        if (!$panelName) return;

        if ($panelName === 'artifacts') {
            $this->artifactPanelOpen = !$this->artifactPanelOpen;
        } else {
            $this->activePanel = ($this->activePanel === $panelName) ? null : $panelName;
        }
        
        \Illuminate\Support\Facades\Log::info('ChatLayout activePanel is now: ' . $this->activePanel);
    }

    #[\Livewire\Attributes\On('close-panel')]
    public function closePanel()
    {
        $this->activePanel = null;
    }

    #[\Livewire\Attributes\On('closeArtifactPanel')]
    public function hideArtifactPanel()
    {
        $this->artifactPanelOpen = false;
        if ($this->activePanel === 'artifacts') {
            $this->activePanel = null;
        }
    }

    #[\Livewire\Attributes\On('openArtifact')]
    #[\Livewire\Attributes\On('showArtifactPanel')]
    public function showArtifactPanel()
    {
        $this->artifactPanelOpen = true;
        if ($this->activePanel === 'artifacts') {
            $this->activePanel = null; // Switch out of full screen list to show split screen
        }
    }

    public function render()
    {
        return view('livewire.chat-layout');
    }
}

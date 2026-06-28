<?php

namespace App\Livewire;

use Livewire\Component;

class ActivityFeed extends Component
{
    public string $sessionId;
    public ?string $workflowId = null;

    public array $events = [];

    public function mount(string $sessionId, ?string $workflowId = null)
    {
        $this->sessionId = $sessionId;
        $this->workflowId = $workflowId;
        $this->events = [];
    }

    public function render()
    {
        return view('livewire.activity-feed');
    }
}

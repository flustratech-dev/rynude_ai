<?php

namespace App\Livewire;

use Livewire\Component;
use App\Contracts\EventHistoryServiceInterface;

class ActivityTimeline extends Component
{
    public string $sessionId;
    public ?string $workflowId = null;
    
    // We can pre-load historical events if they exist
    public array $initialEvents = [];

    public function mount(string $sessionId, ?string $workflowId = null)
    {
        $this->sessionId = $sessionId;
        $this->workflowId = $workflowId;
        
        $historyService = app(EventHistoryServiceInterface::class);
        $events = $historyService->getHistory($sessionId);
        
        $this->initialEvents = array_map(fn($e) => $e->toArray(), $events);
    }

    public function render()
    {
        \Illuminate\Support\Facades\Log::info('ActivityTimeline re-rendered');
        return view('livewire.activity-timeline');
    }
}

<?php

namespace App\Services;

use App\Contracts\StreamProviderInterface;
use App\Models\AgentEvent;

class NullStreamProvider implements StreamProviderInterface
{
    public function connect(): void
    {
        // No-op
    }

    public function disconnect(): void
    {
        // No-op
    }

    public function publish(AgentEvent $event): void
    {
        // No-op, streaming is handled via Livewire polling/events in the frontend directly
    }
}

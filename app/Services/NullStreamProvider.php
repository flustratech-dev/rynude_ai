<?php

namespace App\Services;

use App\Contracts\StreamProviderInterface;
use App\Domain\AgentEvent;

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

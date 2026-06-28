<?php

namespace App\Services;

use App\Contracts\EventEmitterInterface;
use App\Contracts\EventHistoryServiceInterface;
use App\Contracts\StreamProviderInterface;
use App\Domain\AgentEvent;

class ActivityStreamService
{
    private EventHistoryServiceInterface $historyService;
    private StreamProviderInterface $streamProvider;
    private \App\Repositories\AgentEventRepositoryInterface $eventStore;

    public function __construct(
        EventHistoryServiceInterface $historyService,
        StreamProviderInterface $streamProvider,
        \App\Repositories\AgentEventRepositoryInterface $eventStore
    ) {
        $this->historyService = $historyService;
        $this->streamProvider = $streamProvider;
        $this->eventStore = $eventStore;
    }

    public function emit(AgentEvent $event): void
    {
        // 1. Save to DB (Single Source of Truth)
        $this->eventStore->save($event);
        
        // Dispatch to Laravel Event Bus (Phase 2 foundation)
        event(new \App\Events\AgentEventDispatched($event));

        // Publish to legacy Livewire stream provider (Phase 1 shadow mode)
        $this->streamProvider->publish($event);
    }

    public function emitBatch(array $events): void
    {
        foreach ($events as $event) {
            $this->emit($event);
        }
    }

    public function getHistory(string $sessionId, int $page = 1, int $perPage = 50): array
    {
        return $this->historyService->getHistory($sessionId, $page, $perPage);
    }

    public function getLatest(string $sessionId): ?AgentEvent
    {
        $history = $this->historyService->getHistory($sessionId, 1, 1);
        return !empty($history) ? $history[0] : null;
    }
}

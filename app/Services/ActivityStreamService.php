<?php

namespace App\Services;

use App\Contracts\StreamProviderInterface;
use App\Models\AgentEvent;

class ActivityStreamService
{
    private EventEmitter $emitter;
    private EventHistoryService $historyService;
    private StreamProviderInterface $streamProvider;

    public function __construct(
        EventEmitter $emitter,
        EventHistoryService $historyService,
        StreamProviderInterface $streamProvider
    ) {
        $this->emitter = $emitter;
        $this->historyService = $historyService;
        $this->streamProvider = $streamProvider;
    }

    public function emit(AgentEvent $event): void
    {
        $this->emitter->dispatch($event);
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

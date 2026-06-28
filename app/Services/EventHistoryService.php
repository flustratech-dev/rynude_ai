<?php

namespace App\Services;

use App\Repositories\AgentEventRepositoryInterface;
use App\Domain\AgentEvent;
use App\Contracts\EventHistoryServiceInterface;

class EventHistoryService implements EventHistoryServiceInterface
{
    private AgentEventRepositoryInterface $store;

    public function __construct(AgentEventRepositoryInterface $store)
    {
        $this->store = $store;
    }

    public function getHistory(string $sessionId, int $page = 1, int $perPage = 50): array
    {
        $allEvents = $this->store->findBySession($sessionId);
        
        // Sort newest first
        usort($allEvents, fn(AgentEvent $a, AgentEvent $b) => $b->timestamp <=> $a->timestamp);

        $offset = ($page - 1) * $perPage;
        return array_slice($allEvents, $offset, $perPage);
    }

    public function filterHistory(string $sessionId, array $criteria): array
    {
        $allEvents = $this->store->findBySession($sessionId);
        
        return array_values(array_filter($allEvents, function(AgentEvent $event) use ($criteria) {
            if (isset($criteria['eventType']) && $event->eventType->value !== $criteria['eventType']) {
                return false;
            }
            return true;
        }));
    }
}

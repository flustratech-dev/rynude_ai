<?php

namespace App\Repositories;

use App\Models\AgentEvent;
use DateTimeImmutable;

class EventStore implements AgentEventRepositoryInterface
{
    /** @var AgentEvent[] */
    private array $events = [];



    public function save(AgentEvent $event): void
    {
        $this->events[] = $event;
    }

    public function findBySession(string $sessionId): array
    {
        return array_values(array_filter($this->events, fn(AgentEvent $e) => $e->sessionId === $sessionId));
    }

    public function findLatest(string $sessionId, int $limit = 50): array
    {
        $sessionEvents = $this->findBySession($sessionId);
        usort($sessionEvents, fn(AgentEvent $a, AgentEvent $b) => $b->timestamp <=> $a->timestamp);
        return array_slice($sessionEvents, 0, $limit);
    }

    public function findByTimeRange(string $sessionId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $sessionEvents = $this->findBySession($sessionId);
        return array_values(array_filter($sessionEvents, function(AgentEvent $e) use ($start, $end) {
            return $e->timestamp >= $start && $e->timestamp <= $end;
        }));
    }
}

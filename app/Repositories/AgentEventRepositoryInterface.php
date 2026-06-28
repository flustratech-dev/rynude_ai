<?php

namespace App\Repositories;

use App\Models\AgentEvent;
use DateTimeImmutable;

interface AgentEventRepositoryInterface
{
    /**
     * Save an agent event.
     *
     * @param AgentEvent $event
     * @return void
     */
    public function save(AgentEvent $event): void;

    /**
     * Get all events for a specific session.
     *
     * @param string $sessionId
     * @return AgentEvent[]
     */
    public function findBySession(string $sessionId): array;

    /**
     * Get the latest events for a specific session.
     *
     * @param string $sessionId
     * @param int $limit
     * @return AgentEvent[]
     */
    public function findLatest(string $sessionId, int $limit = 50): array;

    /**
     * Get events for a specific session within a time range.
     *
     * @param string $sessionId
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @return AgentEvent[]
     */
    public function findByTimeRange(string $sessionId, DateTimeImmutable $start, DateTimeImmutable $end): array;
}

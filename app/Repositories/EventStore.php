<?php

namespace App\Repositories;

use App\Domain\AgentEvent;
use App\Domain\Enums\AgentEventType;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use DateTimeZone;

class EventStore implements AgentEventRepositoryInterface
{
    public function save(AgentEvent $event): void
    {
        DB::table('agent_events')->insert([
            'id' => $event->id,
            'workflow_id' => $event->workflowId,
            'session_id' => $event->sessionId,
            'agent_id' => $event->agentId,
            'event_type' => $event->eventType->value,
            'stage' => $event->stage,
            'message' => $event->message,
            'metadata' => json_encode($event->metadata),
            'sequence_number' => $event->sequenceNumber,
            'created_at' => $event->createdAt->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function mapToAgentEvent(object $row): AgentEvent
    {
        return new AgentEvent(
            $row->id,
            new DateTimeImmutable($row->created_at, new DateTimeZone('UTC')),
            $row->session_id,
            $row->agent_id,
            $row->workflow_id,
            AgentEventType::from($row->event_type),
            $row->stage,
            $row->message,
            json_decode($row->metadata ?? '{}', true) ?? [],
            (int) $row->sequence_number
        );
    }

    public function findBySession(string $sessionId): array
    {
        $rows = DB::table('agent_events')
            ->where('session_id', $sessionId)
            ->orderBy('sequence_number', 'asc')
            ->get();
            
        return $rows->map(fn($row) => $this->mapToAgentEvent($row))->toArray();
    }

    public function findLatest(string $sessionId, int $limit = 50): array
    {
        $rows = DB::table('agent_events')
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $rows->map(fn($row) => $this->mapToAgentEvent($row))->toArray();
    }

    public function findByTimeRange(string $sessionId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $rows = DB::table('agent_events')
            ->where('session_id', $sessionId)
            ->whereBetween('created_at', [$start->format('Y-m-d H:i:s.u'), $end->format('Y-m-d H:i:s.u')])
            ->orderBy('sequence_number', 'asc')
            ->get();

        return $rows->map(fn($row) => $this->mapToAgentEvent($row))->toArray();
    }
}

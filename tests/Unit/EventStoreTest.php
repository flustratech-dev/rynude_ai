<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Repositories\EventStore;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use DateTimeImmutable;

class EventStoreTest extends TestCase
{
    public function test_can_persist_and_retrieve_events()
    {
        $store = new EventStore();
        
        $event = new AgentEvent(
            'evt_1',
            new DateTimeImmutable(),
            'sess_1',
            'agent_1',
            'wf_1',
            AgentEventType::THINKING,
            null,
            'Message'
        );
        
        $store->save($event);
        
        $events = $store->findBySession('sess_1');
        $this->assertCount(1, $events);
        $this->assertEquals('evt_1', $events[0]->id);
    }

    public function test_can_query_by_time_range()
    {
        $store = new EventStore();
        
        $event1 = new AgentEvent('evt_1', new DateTimeImmutable('2023-10-10T10:00:00Z'), 'sess_1', 'agent_1', 'wf_1', AgentEventType::THINKING, null, 'M1');
        $event2 = new AgentEvent('evt_2', new DateTimeImmutable('2023-10-10T11:00:00Z'), 'sess_1', 'agent_1', 'wf_1', AgentEventType::PLANNING, null, 'M2');
        $event3 = new AgentEvent('evt_3', new DateTimeImmutable('2023-10-10T12:00:00Z'), 'sess_1', 'agent_1', 'wf_1', AgentEventType::WRITING, null, 'M3');
        
        $store->save($event1);
        $store->save($event2);
        $store->save($event3);
        
        $results = $store->findByTimeRange(
            'sess_1',
            new DateTimeImmutable('2023-10-10T10:30:00Z'),
            new DateTimeImmutable('2023-10-10T11:30:00Z')
        );
        
        $this->assertCount(1, $results);
        $this->assertEquals('evt_2', $results[0]->id);
    }
}

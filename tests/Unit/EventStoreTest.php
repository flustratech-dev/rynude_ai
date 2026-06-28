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
            AgentEventType::THINKING,
            'Message'
        );
        
        $store->persist($event);
        
        $events = $store->retrieve('sess_1');
        $this->assertCount(1, $events);
        $this->assertEquals('evt_1', $events[0]->id);
    }

    public function test_can_query_by_time_range()
    {
        $store = new EventStore();
        
        $event1 = new AgentEvent('evt_1', new DateTimeImmutable('2023-10-10T10:00:00Z'), 'sess_1', AgentEventType::THINKING, 'M1');
        $event2 = new AgentEvent('evt_2', new DateTimeImmutable('2023-10-10T11:00:00Z'), 'sess_1', AgentEventType::PLANNING, 'M2');
        $event3 = new AgentEvent('evt_3', new DateTimeImmutable('2023-10-10T12:00:00Z'), 'sess_1', AgentEventType::WRITING, 'M3');
        
        $store->persist($event1);
        $store->persist($event2);
        $store->persist($event3);
        
        $results = $store->queryByTimeRange(
            'sess_1',
            new DateTimeImmutable('2023-10-10T10:30:00Z'),
            new DateTimeImmutable('2023-10-10T11:30:00Z')
        );
        
        $this->assertCount(1, $results);
        $this->assertEquals('evt_2', $results[0]->id);
    }
}

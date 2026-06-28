<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\EventHistoryService;
use App\Repositories\EventStore;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use DateTimeImmutable;

class EventHistoryServiceTest extends TestCase
{
    public function test_can_paginate_history()
    {
        $store = new EventStore();
        
        $event1 = new AgentEvent('evt_1', new DateTimeImmutable('2023-10-10T10:00:00Z'), 'sess_1', 'agent_1', 'wf_1', AgentEventType::THINKING, null, 'M1');
        $event2 = new AgentEvent('evt_2', new DateTimeImmutable('2023-10-10T11:00:00Z'), 'sess_1', 'agent_1', 'wf_1', AgentEventType::PLANNING, null, 'M2');
        $event3 = new AgentEvent('evt_3', new DateTimeImmutable('2023-10-10T12:00:00Z'), 'sess_1', 'agent_1', 'wf_1', AgentEventType::WRITING, null, 'M3');
        
        $store->save($event1);
        $store->save($event2);
        $store->save($event3);
        
        $service = new EventHistoryService($store);
        
        // Page 1, limit 2. Should return newest first: evt_3, evt_2
        $history = $service->getHistory('sess_1', 1, 2);
        $this->assertCount(2, $history);
        $this->assertEquals('evt_3', $history[0]->id);
        $this->assertEquals('evt_2', $history[1]->id);
        
        // Page 2, limit 2. Should return evt_1
        $historyPage2 = $service->getHistory('sess_1', 2, 2);
        $this->assertCount(1, $historyPage2);
        $this->assertEquals('evt_1', $historyPage2[0]->id);
    }

    public function test_can_filter_history()
    {
        $store = new EventStore();
        
        $event1 = new AgentEvent('evt_1', new DateTimeImmutable(), 'sess_1', 'agent_1', 'wf_1', AgentEventType::THINKING, null, 'M1');
        $event2 = new AgentEvent('evt_2', new DateTimeImmutable(), 'sess_1', 'agent_1', 'wf_1', AgentEventType::PLANNING, null, 'M2');
        
        $store->save($event1);
        $store->save($event2);
        
        $service = new EventHistoryService($store);
        
        $filtered = $service->filterHistory('sess_1', ['eventType' => AgentEventType::PLANNING->value]);
        $this->assertCount(1, $filtered);
        $this->assertEquals('evt_2', $filtered[0]->id);
    }
}

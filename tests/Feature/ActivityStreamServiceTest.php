<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ActivityStreamService;
use App\Services\EventEmitter;
use App\Services\EventHistoryService;
use App\Repositories\EventStore;
use App\Contracts\StreamProviderInterface;
use App\Domain\AgentEvent;
use App\Domain\Enums\AgentEventType;
use DateTimeImmutable;

class FakeStreamProvider implements StreamProviderInterface
{
    public array $published = [];

    public function connect(): void {}
    public function disconnect(): void {}
    
    public function publish(AgentEvent $event): void
    {
        $this->published[] = $event;
    }
}

class ActivityStreamServiceTest extends TestCase
{
    public function test_emit_dispatches_event_and_publishes_to_stream()
    {
        $store = new EventStore();
        $historyService = new EventHistoryService($store);
        $emitter = new EventEmitter();
        $streamProvider = new FakeStreamProvider();
        
        $notified = false;
        $emitter->subscribe(function (AgentEvent $event) use (&$notified, $store) {
            $notified = true;
            $store->save($event);
        });

        $service = new ActivityStreamService($emitter, $historyService, $streamProvider);
        
        $event = new AgentEvent(
            'evt_1',
            new DateTimeImmutable(),
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            AgentEventType::THINKING,
            null,
            'Message'
        );

        $service->emit($event);

        // Verify subscriber was notified (which persists it in our setup)
        $this->assertTrue($notified);
        
        // Verify stream provider published the event
        $this->assertCount(1, $streamProvider->published);
        $this->assertEquals('evt_1', $streamProvider->published[0]->id);
        
        // Verify we can retrieve history
        $history = $service->getHistory('11111111-1111-1111-1111-111111111111');
        $this->assertCount(1, $history);
        
        // Verify we can get latest
        $latest = $service->getLatest('11111111-1111-1111-1111-111111111111');
        $this->assertNotNull($latest);
        $this->assertEquals('evt_1', $latest->id);
    }

    public function test_emit_batch_processes_multiple_events()
    {
        $store = new EventStore();
        $historyService = new EventHistoryService($store);
        $emitter = new EventEmitter();
        $streamProvider = new FakeStreamProvider();
        
        $emitter->subscribe(function (AgentEvent $event) use ($store) {
            $store->save($event);
        });

        $service = new ActivityStreamService($emitter, $historyService, $streamProvider);
        
        $events = [
            new AgentEvent('evt_1', new DateTimeImmutable('2023-10-10T10:00:00Z'), '11111111-1111-1111-1111-111111111111', '22222222-2222-2222-2222-222222222222', '33333333-3333-3333-3333-333333333333', AgentEventType::THINKING, null, 'M1'),
            new AgentEvent('evt_2', new DateTimeImmutable('2023-10-10T11:00:00Z'), '11111111-1111-1111-1111-111111111111', '22222222-2222-2222-2222-222222222222', '33333333-3333-3333-3333-333333333333', AgentEventType::PLANNING, null, 'M2'),
        ];

        $service->emitBatch($events);

        // Verify stream provider published both
        $this->assertCount(2, $streamProvider->published);
        
        // Verify history has both
        $history = $service->getHistory('11111111-1111-1111-1111-111111111111');
        $this->assertCount(2, $history);
        
        // Verify latest is evt_2 because it's newer
        $latest = $service->getLatest('11111111-1111-1111-1111-111111111111');
        $this->assertEquals('evt_2', $latest->id);
    }
}

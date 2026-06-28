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

use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityStreamServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_emit_dispatches_event_and_publishes_to_stream()
    {
        $store = new EventStore();
        $historyService = new EventHistoryService($store);
        $streamProvider = new FakeStreamProvider();
        $notified = false;
        // In the new setup, ActivityStreamService saves directly to the DB.
        
        $service = new ActivityStreamService($historyService, $streamProvider, $store);
        
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

        // Verify event was saved to the DB
        $history = $service->getHistory('11111111-1111-1111-1111-111111111111');
        $this->assertCount(1, $history);
        
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
        $streamProvider = new FakeStreamProvider();
        $service = new ActivityStreamService($historyService, $streamProvider, $store);
        
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

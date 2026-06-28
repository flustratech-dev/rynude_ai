<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\EventEmitter;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use DateTimeImmutable;

class EventEmitterTest extends TestCase
{
    public function test_can_subscribe_and_notify_listeners()
    {
        $emitter = new EventEmitter();
        $notified = false;
        
        $emitter->subscribe(function(AgentEvent $event) use (&$notified) {
            $notified = true;
            $this->assertEquals('evt_1', $event->id);
        });

        $event = new AgentEvent(
            'evt_1',
            new DateTimeImmutable(),
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            AgentEventType::THINKING,
            null,
            'Thinking'
        );

        $emitter->dispatch($event);
        
        $this->assertTrue($notified);
    }
}

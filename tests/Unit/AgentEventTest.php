<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\AgentEvent;
use App\Domain\Enums\AgentEventType;
use InvalidArgumentException;
use DateTimeImmutable;
use DateTimeZone;

class AgentEventTest extends TestCase
{
    public function test_can_create_valid_agent_event()
    {
        $createdAt = new DateTimeImmutable('2023-10-10T10:00:00Z');
        
        $event = new AgentEvent(
            'evt_123',
            $createdAt,
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            AgentEventType::THINKING,
            null,
            'Thinking about the request...',
            ['tool' => 'none'],
            1
        );

        $this->assertEquals('evt_123', $event->id);
        $this->assertEquals('UTC', $event->createdAt->getTimezone()->getName());
        $this->assertEquals('2023-10-10T10:00:00.000000Z', $event->createdAt->format('Y-m-d\TH:i:s.u\Z'));
        $this->assertEquals(1, $event->sequenceNumber);
        $this->assertEquals('11111111-1111-1111-1111-111111111111', $event->sessionId);
        $this->assertEquals('22222222-2222-2222-2222-222222222222', $event->agentId);
        $this->assertEquals('33333333-3333-3333-3333-333333333333', $event->workflowId);
        $this->assertEquals(AgentEventType::THINKING, $event->eventType);
        $this->assertNull($event->stage);
        $this->assertEquals('Thinking about the request...', $event->message);
        $this->assertEquals(['tool' => 'none'], $event->metadata);
    }

    public function test_can_create_event_from_strings()
    {
        $event = new AgentEvent(
            'evt_123',
            '2023-10-10T10:00:00Z',
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            'thinking',
            'UNDERSTAND',
            'Thinking about the request...',
            ['tool' => 'none']
        );
        
        $this->assertEquals(AgentEventType::THINKING, $event->eventType);
        $this->assertEquals('UNDERSTAND', $event->stage);
        $this->assertEquals('UTC', $event->createdAt->getTimezone()->getName());
        $this->assertEquals(0, $event->sequenceNumber);
    }

    public function test_invalid_event_type_throws_exception()
    {
        $this->expectException(\ValueError::class);

        new AgentEvent(
            'evt_123',
            '2023-10-10T10:00:00Z',
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            'invalid_type',
            null,
            'Message'
        );
    }

    public function test_missing_required_fields_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        
        // Passing empty string for id to trigger validation failure
        new AgentEvent(
            '',
            '2023-10-10T10:00:00Z',
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            AgentEventType::PLANNING,
            null,
            'Message'
        );
    }

    public function test_serialization()
    {
        $event = new AgentEvent(
            'evt_123',
            '2023-10-10T10:00:00Z',
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
            AgentEventType::COMPLETED,
            null,
            'Done',
            ['foo' => 'bar']
        );

        $json = json_encode($event);
        
        $this->assertStringContainsString('evt_123', $json);
        $this->assertStringContainsString('completed', $json);
        $this->assertStringContainsString('Done', $json);
        $this->assertStringContainsString('foo', $json);
        $this->assertStringContainsString('bar', $json);
        
        $array = json_decode($json, true);
        $this->assertEquals('evt_123', $array['id']);
        $this->assertEquals('bar', $array['metadata']['foo']);
    }
}

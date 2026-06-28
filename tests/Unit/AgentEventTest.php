<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use InvalidArgumentException;
use DateTimeImmutable;
use DateTimeZone;

class AgentEventTest extends TestCase
{
    public function test_can_create_valid_agent_event()
    {
        $timestamp = new DateTimeImmutable('2023-10-10T10:00:00Z');
        
        $event = new AgentEvent(
            'evt_123',
            $timestamp,
            'sess_456',
            'agent_789',
            AgentEventType::THINKING,
            'Thinking about the request...',
            ['tool' => 'none']
        );

        $this->assertEquals('evt_123', $event->id);
        $this->assertEquals('UTC', $event->timestamp->getTimezone()->getName());
        $this->assertEquals('2023-10-10T10:00:00.000000Z', $event->timestamp->format('Y-m-d\TH:i:s.u\Z'));
        $this->assertEquals('sess_456', $event->sessionId);
        $this->assertEquals('agent_789', $event->agentId);
        $this->assertEquals(AgentEventType::THINKING, $event->eventType);
        $this->assertEquals('Thinking about the request...', $event->message);
        $this->assertEquals(['tool' => 'none'], $event->metadata);
    }

    public function test_can_create_event_from_strings()
    {
        $event = new AgentEvent(
            'evt_123',
            '2023-10-10T10:00:00Z',
            'sess_456',
            'agent_789',
            'thinking',
            'Thinking about the request...',
            ['tool' => 'none']
        );
        
        $this->assertEquals(AgentEventType::THINKING, $event->eventType);
        $this->assertEquals('UTC', $event->timestamp->getTimezone()->getName());
    }

    public function test_invalid_event_type_throws_exception()
    {
        $this->expectException(\ValueError::class);

        new AgentEvent(
            'evt_123',
            '2023-10-10T10:00:00Z',
            'sess_456',
            'agent_789',
            'invalid_type',
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
            'sess_456',
            'agent_789',
            AgentEventType::PLANNING,
            'Message'
        );
    }

    public function test_serialization()
    {
        $event = new AgentEvent(
            'evt_123',
            '2023-10-10T10:00:00Z',
            'sess_456',
            'agent_789',
            AgentEventType::COMPLETED,
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

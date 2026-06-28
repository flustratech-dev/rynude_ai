<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Orchestrator\ToolExecutionTracker;
use App\Services\ActivityStreamService;
use App\Contracts\EventEmitterInterface;
use App\Contracts\EventHistoryServiceInterface;
use App\Contracts\StreamProviderInterface;
use App\Domain\AgentEvent;
use App\Domain\Enums\AgentEventType;
use App\Domain\Enums\ToolCategory;
use App\Domain\Enums\ToolStatus;
use Illuminate\Support\Str;

class ToolExecutionTrackerTest extends TestCase
{
    private $streamProvider;
    private $activityStream;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->streamProvider = new class implements StreamProviderInterface {
            public array $published = [];
            public function connect(): void {}
            public function disconnect(): void {}
            public function publish(AgentEvent $event): void {
                $this->published[] = $event;
            }
        };

        $historyService = new class implements EventHistoryServiceInterface {
            public function getHistory(string $sessionId, int $page = 1, int $limit = 50, array $filters = []): array { return []; }
            public function filterHistory(string $sessionId, array $filters): array { return []; }
        };

        $emitter = new class implements EventEmitterInterface {
            public function dispatch(AgentEvent $event): void {}
            public function subscribe(callable $listener): void {}
            public function notifyListeners(AgentEvent $event): void {}
        };

        $this->activityStream = new ActivityStreamService($emitter, $historyService, $this->streamProvider);
    }

    public function test_lifecycle_emits_correct_events()
    {
        $tracker = new ToolExecutionTracker($this->activityStream);
        
        $sessionId = Str::uuid()->toString();
        $workflowId = Str::uuid()->toString();
        $agentId = Str::uuid()->toString();

        // 1. Start Tool
        $toolId = $tracker->startTool($sessionId, $workflowId, $agentId, 'TestTool', ToolCategory::RESEARCH, 'RESEARCH', ['input' => 'test']);
        
        $this->assertCount(1, $this->streamProvider->published);
        $startEvent = $this->streamProvider->published[0];
        $this->assertEquals(AgentEventType::START, $startEvent->eventType);
        $this->assertEquals('RESEARCH', $startEvent->stage);
        $this->assertEquals('started', $startEvent->metadata['toolStatus']);
        $this->assertEquals(0, $startEvent->metadata['progressPercent']);

        // 2. Update Status
        $tracker->updateStatus($toolId, ['progress_detail' => 'running...'], 45);
        $this->assertCount(2, $this->streamProvider->published);
        $updateEvent = $this->streamProvider->published[1];
        $this->assertEquals(AgentEventType::THINKING, $updateEvent->eventType);
        $this->assertEquals('running', $updateEvent->metadata['toolStatus']);
        $this->assertEquals(45, $updateEvent->metadata['progressPercent']);
        $this->assertEquals('running...', $updateEvent->metadata['progress_detail']);

        // 3. Complete Tool
        $tracker->completeTool($toolId, ['result' => 'done']);
        $this->assertCount(3, $this->streamProvider->published);
        $completeEvent = $this->streamProvider->published[2];
        $this->assertEquals(AgentEventType::COMPLETED, $completeEvent->eventType);
        $this->assertEquals('completed', $completeEvent->metadata['toolStatus']);
        $this->assertEquals(100, $completeEvent->metadata['progressPercent']);
        $this->assertEquals('done', $completeEvent->metadata['result']);
        $this->assertNotNull($completeEvent->metadata['durationMs']);
    }

    public function test_fail_tool_emits_error_event()
    {
        $tracker = new ToolExecutionTracker($this->activityStream);
        
        $sessionId = Str::uuid()->toString();
        $workflowId = Str::uuid()->toString();
        $agentId = Str::uuid()->toString();

        $toolId = $tracker->startTool($sessionId, $workflowId, $agentId, 'TestTool', ToolCategory::RESEARCH, 'RESEARCH');
        
        $tracker->failTool($toolId, "Something went wrong", ['code' => 500]);
        
        $this->assertCount(2, $this->streamProvider->published);
        $failEvent = $this->streamProvider->published[1];
        
        $this->assertEquals(AgentEventType::ERROR, $failEvent->eventType);
        $this->assertEquals('failed', $failEvent->metadata['toolStatus']);
        $this->assertEquals('Something went wrong', $failEvent->metadata['error']);
        $this->assertEquals(500, $failEvent->metadata['code']);
    }
}

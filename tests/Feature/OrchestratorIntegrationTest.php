<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Orchestrator\AgentOrchestrator;
use App\Services\ActivityStreamService;
use App\Contracts\EventEmitterInterface;
use App\Contracts\EventHistoryServiceInterface;
use App\Contracts\StreamProviderInterface;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use Exception;
use Mockery;
use Illuminate\Support\Str;

class OrchestratorIntegrationTest extends TestCase
{
    private $streamProvider;
    private $historyService;
    private $emitter;
    private $activityStream;
    private $toolTracker;

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

        $this->historyService = new class implements EventHistoryServiceInterface {
            public array $events = [];
            public function getHistory(string $sessionId, int $page = 1, int $limit = 50, array $filters = []): array {
                return $this->events;
            }
            public function filterHistory(string $sessionId, array $filters): array {
                return $this->events;
            }
        };

        $this->emitter = new class implements EventEmitterInterface {
            public array $dispatched = [];
            public function dispatch(AgentEvent $event): void {
                $this->dispatched[] = $event;
            }
            public function subscribe(callable $listener): void {}
            public function notifyListeners(AgentEvent $event): void {}
        };

        $this->activityStream = new ActivityStreamService(
            $this->emitter,
            $this->historyService,
            $this->streamProvider
        );

        $this->toolTracker = new \App\Services\Orchestrator\ToolExecutionTracker($this->activityStream);
    }

    public function test_orchestrator_emits_pipeline_events_in_order()
    {
        $orchestrator = new AgentOrchestrator($this->activityStream, $this->toolTracker);
        
        $sessionId = Str::uuid()->toString();
        $agentId = Str::uuid()->toString();
        $workflowId = Str::uuid()->toString();
        
        $orchestrator->execute($sessionId, $agentId, $workflowId);

        $events = $this->streamProvider->published;
        
        $this->assertCount(11, $events); // 5 stages * 2 (START, COMPLETED) + 1 final COMPLETED

        // Verify ordering and stages
        $expectedStages = ['UNDERSTAND', 'PLAN', 'RESEARCH', 'WRITE', 'REVIEW'];
        
        for ($i = 0; $i < 5; $i++) {
            $startIndex = $i * 2;
            $completeIndex = $i * 2 + 1;
            
            $startEvent = $events[$startIndex];
            $completeEvent = $events[$completeIndex];
            
            $this->assertEquals(AgentEventType::START, $startEvent->eventType);
            $this->assertEquals($expectedStages[$i], $startEvent->stage);
            
            $this->assertEquals(AgentEventType::COMPLETED, $completeEvent->eventType);
            $this->assertEquals($expectedStages[$i], $completeEvent->stage);
        }
        
        $finalEvent = $events[10];
        $this->assertEquals(AgentEventType::COMPLETED, $finalEvent->eventType);
        $this->assertNull($finalEvent->stage);
        $this->assertEquals('Workflow completed successfully', $finalEvent->message);
    }

    public function test_orchestrator_emits_error_event_on_failure()
    {
        $failingOrchestrator = new class($this->activityStream, $this->toolTracker) extends AgentOrchestrator {
            protected function plan(string $sessionId, string $agentId, string $workflowId, string $stage): void {
                throw new Exception("Simulated failure in PLAN");
            }
            // Need to expose protected for test
            public function runStage(string $sessionId, string $agentId, string $workflowId, string $stage, callable $action): void
            {
                if ($stage === 'PLAN') {
                    $action = fn() => $this->plan($sessionId, $agentId, $workflowId, 'PLAN');
                }
                parent::runStage($sessionId, $agentId, $workflowId, $stage, $action);
            }
        };

        $sessionId = Str::uuid()->toString();
        $agentId = Str::uuid()->toString();
        $workflowId = Str::uuid()->toString();
        
        $exceptionThrown = false;
        try {
            $failingOrchestrator->execute($sessionId, $agentId, $workflowId);
        } catch (Exception $e) {
            $exceptionThrown = true;
            $this->assertEquals("Simulated failure in PLAN", $e->getMessage());
        }

        $this->assertTrue($exceptionThrown);

        $events = $this->streamProvider->published;
        
        // Expected events: UNDERSTAND START, UNDERSTAND COMPLETE, PLAN START, PLAN ERROR, FINAL ERROR
        $this->assertCount(5, $events);
        
        $this->assertEquals(AgentEventType::START, $events[0]->eventType);
        $this->assertEquals('UNDERSTAND', $events[0]->stage);
        
        $this->assertEquals(AgentEventType::COMPLETED, $events[1]->eventType);
        $this->assertEquals('UNDERSTAND', $events[1]->stage);
        
        $this->assertEquals(AgentEventType::START, $events[2]->eventType);
        $this->assertEquals('PLAN', $events[2]->stage);
        
        $this->assertEquals(AgentEventType::ERROR, $events[3]->eventType);
        $this->assertEquals('PLAN', $events[3]->stage);
        $this->assertStringContainsString('Failed PLAN stage', $events[3]->message);
        
        $this->assertEquals(AgentEventType::ERROR, $events[4]->eventType);
        $this->assertNull($events[4]->stage);
        $this->assertStringContainsString('Workflow failed', $events[4]->message);
    }
}

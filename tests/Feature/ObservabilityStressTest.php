<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Enums\AgentEventType;
use App\Models\AgentEvent;
use App\Contracts\EventEmitterInterface;
use App\Services\ActivityStreamService;
use App\Services\Orchestrator\AgentOrchestrator;
use DateTimeImmutable;

class ObservabilityStressTest extends TestCase
{
    public function test_concurrent_workflow_isolation()
    {
        $orchestrator = app(AgentOrchestrator::class);
        $emitter = app(EventEmitterInterface::class);
        
        $events = [];
        $emitter->subscribe(function (AgentEvent $event) use (&$events) {
            $events[] = $event;
        });

        // Simulate 5 concurrent workflows emitting events
        $workflows = [];
        for ($i = 0; $i < 5; $i++) {
            $workflows[] = [
                'sessionId' => (string) Str::uuid(),
                'agentId' => (string) Str::uuid(),
                'workflowId' => (string) Str::uuid()
            ];
        }

        // Run them sequentially but track events globally
        foreach ($workflows as $wf) {
            $orchestrator->execute($wf['sessionId'], $wf['agentId'], $wf['workflowId']);
        }

        // Each workflow emits multiple events. Ensure isolation.
        $this->assertGreaterThan(0, count($events));

        // Group events by workflowId
        $groupedEvents = [];
        foreach ($events as $event) {
            $groupedEvents[$event->workflowId][] = $event;
        }

        // Verify each workflow has the correct events
        $this->assertCount(5, $groupedEvents);
        foreach ($groupedEvents as $workflowId => $workflowEvents) {
            // Verify ordering inside each workflow
            $stages = array_column($workflowEvents, 'stage');
            $this->assertContains('UNDERSTAND', $stages);
            $this->assertContains('PLAN', $stages);
            $this->assertContains('RESEARCH', $stages);
            
            // Validate timestamps are monotonically increasing or equal within a workflow
            for ($i = 1; $i < count($workflowEvents); $i++) {
                $prevTime = $workflowEvents[$i - 1]->timestamp;
                $currTime = $workflowEvents[$i]->timestamp;
                $this->assertGreaterThanOrEqual($prevTime, $currTime);
            }
        }
    }
}

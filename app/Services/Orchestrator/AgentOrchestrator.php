<?php

namespace App\Services\Orchestrator;

use App\Contracts\ToolExecutionTrackerInterface;
use App\Enums\ToolCategory;
use App\Services\ActivityStreamService;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use DateTimeImmutable;
use Illuminate\Support\Str;

class AgentOrchestrator
{
    public function __construct(
        private readonly ActivityStreamService $activityStream,
        private readonly ToolExecutionTrackerInterface $toolTracker
    ) {}

    public function execute(string $sessionId, string $agentId, string $workflowId): void
    {
        try {
            $this->runStage($sessionId, $agentId, $workflowId, 'UNDERSTAND', fn() => $this->understand($sessionId, $agentId, $workflowId, 'UNDERSTAND'));
            $this->runStage($sessionId, $agentId, $workflowId, 'PLAN', fn() => $this->plan($sessionId, $agentId, $workflowId, 'PLAN'));
            $this->runStage($sessionId, $agentId, $workflowId, 'RESEARCH', fn() => $this->research($sessionId, $agentId, $workflowId, 'RESEARCH'));
            $this->runStage($sessionId, $agentId, $workflowId, 'WRITE', fn() => $this->write($sessionId, $agentId, $workflowId, 'WRITE'));
            $this->runStage($sessionId, $agentId, $workflowId, 'REVIEW', fn() => $this->review($sessionId, $agentId, $workflowId, 'REVIEW'));
            
            // Workflow completed
            $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::COMPLETED, null, 'Workflow completed successfully');
        } catch (\Throwable $e) {
            $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::ERROR, null, 'Workflow failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function runStage(string $sessionId, string $agentId, string $workflowId, string $stage, callable $action): void
    {
        $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::START, $stage, "Starting $stage stage");
        
        try {
            $action();
            $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::COMPLETED, $stage, "Completed $stage stage");
        } catch (\Throwable $e) {
            $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::ERROR, $stage, "Failed $stage stage: " . $e->getMessage());
            throw $e;
        }
    }

    private function emitEvent(string $sessionId, string $agentId, string $workflowId, AgentEventType $type, ?string $stage, string $message): void
    {
        $event = new AgentEvent(
            (string) Str::uuid(),
            new DateTimeImmutable(),
            $sessionId,
            $agentId,
            $workflowId,
            $type,
            $stage,
            $message
        );
        $this->activityStream->emit($event);
    }

    protected function understand(string $sessionId, string $agentId, string $workflowId, string $stage): void
    {
        $toolId = $this->toolTracker->startTool($sessionId, $workflowId, $agentId, 'AnalyzeContextTool', ToolCategory::RESEARCH, $stage);
        $this->toolTracker->updateStatus($toolId, [], 50);
        $this->toolTracker->completeTool($toolId, ['result' => 'Context analyzed']);
    }

    protected function plan(string $sessionId, string $agentId, string $workflowId, string $stage): void
    {
        $toolId = $this->toolTracker->startTool($sessionId, $workflowId, $agentId, 'PlanningTool', ToolCategory::SYSTEM, $stage);
        $this->toolTracker->updateStatus($toolId, [], 100);
        $this->toolTracker->completeTool($toolId, ['plan' => 'Step 1, Step 2']);
    }

    protected function research(string $sessionId, string $agentId, string $workflowId, string $stage): void
    {
        $toolId = $this->toolTracker->startTool($sessionId, $workflowId, $agentId, 'WebSearchTool', ToolCategory::RESEARCH, $stage);
        $this->toolTracker->completeTool($toolId, ['query' => 'observability']);
    }

    protected function write(string $sessionId, string $agentId, string $workflowId, string $stage): void
    {
        $toolId = $this->toolTracker->startTool($sessionId, $workflowId, $agentId, 'CodeEditorTool', ToolCategory::WRITING, $stage);
        $this->toolTracker->completeTool($toolId, ['files' => 1]);
    }

    protected function review(string $sessionId, string $agentId, string $workflowId, string $stage): void
    {
        $toolId = $this->toolTracker->startTool($sessionId, $workflowId, $agentId, 'ReviewTool', ToolCategory::REVIEW, $stage);
        $this->toolTracker->completeTool($toolId, ['status' => 'approved']);
    }
}

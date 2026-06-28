<?php

namespace App\Services\Orchestrator;

use App\Services\ActivityStreamService;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use DateTimeImmutable;
use Illuminate\Support\Str;

class AgentOrchestrator
{
    public function __construct(
        private readonly ActivityStreamService $activityStream
    ) {}

    public function execute(string $sessionId, string $agentId, string $workflowId): void
    {
        try {
            $this->runStage($sessionId, $agentId, $workflowId, 'UNDERSTAND', fn() => $this->understand());
            $this->runStage($sessionId, $agentId, $workflowId, 'PLAN', fn() => $this->plan());
            $this->runStage($sessionId, $agentId, $workflowId, 'RESEARCH', fn() => $this->research());
            $this->runStage($sessionId, $agentId, $workflowId, 'WRITE', fn() => $this->write());
            $this->runStage($sessionId, $agentId, $workflowId, 'REVIEW', fn() => $this->review());
            
            // Workflow completed
            $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::COMPLETED, null, 'Workflow completed successfully');
        } catch (\Throwable $e) {
            $this->emitEvent($sessionId, $agentId, $workflowId, AgentEventType::ERROR, null, 'Workflow failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function runStage(string $sessionId, string $agentId, string $workflowId, string $stage, callable $action): void
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

    private function understand(): void
    {
        // Dummy implementation
    }

    private function plan(): void
    {
        // Dummy implementation
    }

    private function research(): void
    {
        // Dummy implementation
    }

    private function write(): void
    {
        // Dummy implementation
    }

    private function review(): void
    {
        // Dummy implementation
    }
}

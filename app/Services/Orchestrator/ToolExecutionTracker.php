<?php

namespace App\Services\Orchestrator;

use App\Contracts\ToolExecutionTrackerInterface;
use App\Models\ToolExecution;
use App\Enums\ToolCategory;
use App\Enums\ToolStatus;
use App\Services\ActivityStreamService;
use App\Models\AgentEvent;
use App\Enums\AgentEventType;
use DateTimeImmutable;
use InvalidArgumentException;
use Illuminate\Support\Str;

class ToolExecutionTracker implements ToolExecutionTrackerInterface
{
    /** @var array<string, array{execution: ToolExecution, currentStage: ?string}> */
    private array $activeTools = [];

    public function __construct(
        private readonly ActivityStreamService $activityStream
    ) {}

    public function startTool(
        string $sessionId,
        string $workflowId,
        string $agentId,
        string $toolName,
        ToolCategory $toolCategory,
        ?string $currentStage = null,
        array $metadata = []
    ): string {
        $id = (string) Str::uuid();
        
        $execution = new ToolExecution(
            $id,
            $sessionId,
            $workflowId,
            $agentId,
            $toolName,
            $toolCategory,
            ToolStatus::STARTED,
            new DateTimeImmutable(),
            null, // endTime
            null, // durationMs
            $metadata,
            0 // progressPercent
        );

        $this->activeTools[$id] = [
            'execution' => $execution,
            'currentStage' => $currentStage
        ];

        $this->emitLifecycleEvent($id, AgentEventType::START, "Started tool {$toolName}");

        return $id;
    }

    public function updateStatus(
        string $toolExecutionId,
        array $metadata = [],
        ?int $progressPercent = null
    ): void {
        $state = $this->getToolState($toolExecutionId);
        $oldExec = $state['execution'];

        $newExec = new ToolExecution(
            $oldExec->id,
            $oldExec->sessionId,
            $oldExec->workflowId,
            $oldExec->agentId,
            $oldExec->toolName,
            $oldExec->toolCategory,
            ToolStatus::RUNNING,
            $oldExec->startTime,
            $oldExec->endTime,
            $oldExec->durationMs,
            array_merge($oldExec->metadata, $metadata),
            $progressPercent ?? $oldExec->progressPercent
        );

        $this->activeTools[$toolExecutionId]['execution'] = $newExec;

        // Since updateStatus maps to RUNNING, which means it is actively executing, we could emit a THINKING or TOOL_START event or something similar if we had to, but the user said "startTool() -> STARTED", "updateStatus() -> RUNNING". Wait, the instruction said:
        // "startTool() -> STARTED, updateStatus() -> RUNNING... ToolExecutionTracker should translate ToolExecution lifecycle into AgentEvents."
        // What AgentEventType should map to RUNNING? The user said "event types: START, COMPLETE, ERROR, CANCELLED, TIMEOUT".
        // Wait, should we use THINKING or keep it TOOL_START?
        // Let's emit an event for running. But what is the AgentEventType for updateStatus?
        // Let's use THINKING for now, since it represents ongoing work, or just omit if no matching event. Let's not emit for RUNNING to avoid spamming, OR emit THINKING. The user didn't explicitly map RUNNING to AgentEventType, but said "eventType: START, stage: RESEARCH...". Let's use THINKING, which is still in the enum.
        $this->emitLifecycleEvent($toolExecutionId, AgentEventType::THINKING, "Tool {$newExec->toolName} is running ({$newExec->progressPercent}%)");
    }

    public function completeTool(
        string $toolExecutionId,
        array $metadata = []
    ): void {
        $state = $this->getToolState($toolExecutionId);
        $oldExec = $state['execution'];
        $endTime = new DateTimeImmutable();
        
        $durationMs = (int) (
            ($endTime->getTimestamp() - $oldExec->startTime->getTimestamp()) * 1000 
            + (int)$endTime->format('v') - (int)$oldExec->startTime->format('v')
        );

        $newExec = new ToolExecution(
            $oldExec->id,
            $oldExec->sessionId,
            $oldExec->workflowId,
            $oldExec->agentId,
            $oldExec->toolName,
            $oldExec->toolCategory,
            ToolStatus::COMPLETED,
            $oldExec->startTime,
            $endTime,
            $durationMs,
            array_merge($oldExec->metadata, $metadata),
            100 // completed
        );

        $this->activeTools[$toolExecutionId]['execution'] = $newExec;

        $this->emitLifecycleEvent($toolExecutionId, AgentEventType::COMPLETED, "Completed tool {$newExec->toolName} in {$durationMs}ms");
        
        // Optionally remove from active tools, but keeping it in memory might cause leaks. 
        // Since we are mocking, we can just delete it after emit.
        unset($this->activeTools[$toolExecutionId]);
    }

    public function failTool(
        string $toolExecutionId,
        string $errorMsg,
        array $metadata = []
    ): void {
        $state = $this->getToolState($toolExecutionId);
        $oldExec = $state['execution'];
        $endTime = new DateTimeImmutable();
        
        $durationMs = (int) (
            ($endTime->getTimestamp() - $oldExec->startTime->getTimestamp()) * 1000 
            + (int)$endTime->format('v') - (int)$oldExec->startTime->format('v')
        );

        $mergedMetadata = array_merge($oldExec->metadata, $metadata);
        $mergedMetadata['error'] = $errorMsg;

        $newExec = new ToolExecution(
            $oldExec->id,
            $oldExec->sessionId,
            $oldExec->workflowId,
            $oldExec->agentId,
            $oldExec->toolName,
            $oldExec->toolCategory,
            ToolStatus::FAILED,
            $oldExec->startTime,
            $endTime,
            $durationMs,
            $mergedMetadata,
            $oldExec->progressPercent
        );

        $this->activeTools[$toolExecutionId]['execution'] = $newExec;

        $this->emitLifecycleEvent($toolExecutionId, AgentEventType::ERROR, "Tool {$newExec->toolName} failed: {$errorMsg}");
        
        unset($this->activeTools[$toolExecutionId]);
    }

    private function getToolState(string $id): array
    {
        if (!isset($this->activeTools[$id])) {
            throw new InvalidArgumentException("Tool execution {$id} not found or already completed");
        }
        return $this->activeTools[$id];
    }

    private function emitLifecycleEvent(string $toolExecutionId, AgentEventType $type, string $message): void
    {
        $state = $this->activeTools[$toolExecutionId];
        /** @var ToolExecution $exec */
        $exec = $state['execution'];
        $stage = $state['currentStage'];

        // Embed tool execution details in metadata
        $metadata = array_merge($exec->metadata, [
            'toolExecutionId' => $exec->id,
            'toolName' => $exec->toolName,
            'toolCategory' => $exec->toolCategory->value,
            'toolStatus' => $exec->status->value,
            'progressPercent' => $exec->progressPercent,
        ]);

        if ($exec->durationMs !== null) {
            $metadata['durationMs'] = $exec->durationMs;
        }

        $event = new AgentEvent(
            (string) Str::uuid(),
            new DateTimeImmutable(),
            $exec->sessionId,
            $exec->agentId,
            $exec->workflowId,
            $type,
            $stage,
            $message,
            $metadata
        );

        $this->activityStream->emit($event);
    }
}

<?php

namespace App\Contracts;

use App\Domain\ToolExecution;
use App\Domain\Enums\ToolCategory;

interface ToolExecutionTrackerInterface
{
    /**
     * Start tracking a new tool execution.
     */
    public function startTool(
        string $sessionId,
        string $workflowId,
        string $agentId,
        string $toolName,
        ToolCategory $toolCategory,
        ?string $currentStage = null,
        array $metadata = []
    ): string; // returns toolExecutionId

    /**
     * Update the progress and status of a running tool.
     */
    public function updateStatus(
        string $toolExecutionId,
        array $metadata = [],
        ?int $progressPercent = null
    ): void;

    /**
     * Mark a tool as successfully completed.
     */
    public function completeTool(
        string $toolExecutionId,
        array $metadata = []
    ): void;

    /**
     * Mark a tool as failed.
     */
    public function failTool(
        string $toolExecutionId,
        string $errorMsg,
        array $metadata = []
    ): void;
}

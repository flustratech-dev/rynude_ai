<?php

namespace App\Services\AI\Prompting;

final class PromptContext
{
    public function __construct(
        public readonly string $mode, // 'fast', 'chat', 'research'
        public readonly array $availableTools = [],
        public readonly ?string $taskBrief = null,
        public readonly ?array $executionPlan = null,
        public readonly ?string $provider = null,
        public readonly ?string $workflowType = null,
        public readonly ?int $qualityThreshold = null,
        public readonly bool $artifactRequired = false,
        public readonly bool $citationRequired = false,
    ) {}
}

<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class ExecutionPlanSection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        $planItems = array_map(fn($step) => "- {$step}", $context->executionPlan);
        $planString = implode("\n", $planItems);

        return <<<MD
# Execution Plan
Follow this step-by-step execution plan:
{$planString}
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        return !empty($context->executionPlan);
    }
}

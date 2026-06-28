<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class PlanningInstructionsSection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        return <<<MD
# Planning Before Generation
Before writing any code or generating final output, you MUST plan your approach.
Break down complex tasks into component-level steps. Identify edge cases, dependencies, and requirements.
Evaluate multiple potential approaches before settling on an implementation.
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        // Applicable in all modes except fast
        return $context->mode !== 'fast';
    }
}

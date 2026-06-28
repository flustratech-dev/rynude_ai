<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class ReflectionInstructionsSection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        return <<<MD
# Reflection Before Publishing
Before finalizing your output, review your work against the initial prompt and the execution plan.
Check for edge cases, logical errors, or formatting issues. Refine your output to ensure maximum quality and alignment with the user's intent.
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        // Applicable in all modes except fast
        return $context->mode !== 'fast';
    }
}

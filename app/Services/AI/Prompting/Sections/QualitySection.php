<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class QualitySection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        $threshold = $context->qualityThreshold ?? 80;
        
        return <<<MD
# Quality Scoring Before Delivery
You must meet or exceed a quality threshold of {$threshold}%. Ensure your output adheres to the following standards:
- Accuracy: All facts, logic, and code must be correct and verified.
- Completeness: All parts of the prompt must be addressed. Do not leave placeholder comments like "add code here".
- Consistency: Maintain a consistent tone, style, and structure throughout.
- Formatting: Follow output policies exactly.
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        // Applicable if a quality threshold is defined or mode is not fast
        return $context->qualityThreshold !== null || $context->mode !== 'fast';
    }
}

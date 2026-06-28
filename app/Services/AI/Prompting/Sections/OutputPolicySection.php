<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class OutputPolicySection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        $rules = [];

        if ($context->artifactRequired) {
            $rules[] = "Artifact Requirements: You MUST wrap substantial content (code blocks, extensive reports, etc.) inside `<antigravity-artifact identifier=\"...\" type=\"...\" language=\"...\" title=\"...\">...</antigravity-artifact>` tags.";
        }
        
        if ($context->citationRequired) {
            $rules[] = "Citation Requirements: You MUST wrap all external references or factual claims in `<cite url=\"...\" title=\"...\">text</cite>` tags.";
        }

        $rules[] = "Output Formatting Rules: Ensure markdown is valid and properly escaped.";
        $rules[] = "Mode-specific Behavior: You are operating in '{$context->mode}' mode. Adjust your verbosity and detail accordingly.";

        $rulesList = implode("\n", array_map(fn($rule) => "- {$rule}", $rules));

        return <<<MD
# Output Policy
Follow these output policies strictly:
{$rulesList}
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        return true;
    }
}

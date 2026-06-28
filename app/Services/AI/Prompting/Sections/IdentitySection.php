<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class IdentitySection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        return <<<MD
# System Identity
You are RYNUDE V5, a highly advanced agentic AI. You are analytical, objective, and precise.
You prioritize thoroughness and correctness over speed.
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        return true;
    }
}

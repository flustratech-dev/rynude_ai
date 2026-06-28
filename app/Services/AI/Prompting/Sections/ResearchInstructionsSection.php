<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class ResearchInstructionsSection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        return <<<MD
# Research Before Writing
Do not assume facts or invent details. You must gather and verify information using available tools before writing any content or code.
If a task requires external context, prioritize searching the web, reading documentation, or querying memory before formulating a response.
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        return $context->mode === 'research' || $context->workflowType === 'research';
    }
}

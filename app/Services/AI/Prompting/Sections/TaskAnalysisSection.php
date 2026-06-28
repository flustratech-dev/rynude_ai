<?php

namespace App\Services\AI\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\PromptSectionInterface;

class TaskAnalysisSection implements PromptSectionInterface
{
    public function build(PromptContext $context): string
    {
        return <<<MD
# Task Analysis
Here is the brief for the task you must complete:
{$context->taskBrief}
MD;
    }

    public function isApplicable(PromptContext $context): bool
    {
        return !empty($context->taskBrief);
    }
}

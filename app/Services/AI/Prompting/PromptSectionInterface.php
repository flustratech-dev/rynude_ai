<?php

namespace App\Services\AI\Prompting;

interface PromptSectionInterface
{
    /**
     * Builds the specific section of the prompt based on context.
     */
    public function build(PromptContext $context): string;

    /**
     * Determines whether this section should be included in the prompt.
     */
    public function isApplicable(PromptContext $context): bool;
}

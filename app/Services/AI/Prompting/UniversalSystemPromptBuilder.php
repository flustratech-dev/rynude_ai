<?php

namespace App\Services\AI\Prompting;

class UniversalSystemPromptBuilder
{
    /** @var PromptSectionInterface[] */
    private array $sections;

    public function __construct(array $sections = [])
    {
        if (empty($sections)) {
            $this->sections = [
                new Sections\IdentitySection(),
                new Sections\TaskAnalysisSection(),
                new Sections\ExecutionPlanSection(),
                new Sections\PlanningInstructionsSection(),
                new Sections\ResearchInstructionsSection(),
                new Sections\ReflectionInstructionsSection(),
                new Sections\QualitySection(),
                new Sections\OutputPolicySection(),
            ];
        } else {
            $this->sections = $sections;
        }
    }

    public function build(PromptContext $context): string
    {
        $prompt = [];
        
        foreach ($this->sections as $section) {
            if ($section->isApplicable($context)) {
                $prompt[] = $section->build($context);
            }
        }
        
        return implode("\n\n", $prompt);
    }
}

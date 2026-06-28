<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\PlanningInstructionsSection;
use PHPUnit\Framework\TestCase;

class PlanningInstructionsSectionTest extends TestCase
{
    public function test_is_applicable_except_in_fast_mode(): void
    {
        $section = new PlanningInstructionsSection();
        
        $contextFast = new PromptContext(mode: 'fast');
        $this->assertFalse($section->isApplicable($contextFast));
        
        $contextChat = new PromptContext(mode: 'chat');
        $this->assertTrue($section->isApplicable($contextChat));
    }

    public function test_builds_planning_instructions(): void
    {
        $section = new PlanningInstructionsSection();
        $context = new PromptContext(mode: 'chat');
        $prompt = $section->build($context);
        
        $this->assertStringContainsString('Before writing any code or generating final output, you MUST plan', $prompt);
    }
}

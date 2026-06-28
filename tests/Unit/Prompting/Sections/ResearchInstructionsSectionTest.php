<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\ResearchInstructionsSection;
use PHPUnit\Framework\TestCase;

class ResearchInstructionsSectionTest extends TestCase
{
    public function test_is_applicable_in_research_mode_or_workflow(): void
    {
        $section = new ResearchInstructionsSection();
        
        $contextChat = new PromptContext(mode: 'chat');
        $this->assertFalse($section->isApplicable($contextChat));
        
        $contextResearchMode = new PromptContext(mode: 'research');
        $this->assertTrue($section->isApplicable($contextResearchMode));
        
        $contextResearchWorkflow = new PromptContext(mode: 'chat', workflowType: 'research');
        $this->assertTrue($section->isApplicable($contextResearchWorkflow));
    }

    public function test_builds_research_instructions(): void
    {
        $section = new ResearchInstructionsSection();
        $context = new PromptContext(mode: 'research');
        $prompt = $section->build($context);
        
        $this->assertStringContainsString('Research Before Writing', $prompt);
    }
}

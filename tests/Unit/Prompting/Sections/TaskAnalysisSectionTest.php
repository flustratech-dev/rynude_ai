<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\TaskAnalysisSection;
use PHPUnit\Framework\TestCase;

class TaskAnalysisSectionTest extends TestCase
{
    public function test_is_applicable_when_task_brief_is_present(): void
    {
        $section = new TaskAnalysisSection();
        
        $contextWithoutBrief = new PromptContext(mode: 'chat');
        $this->assertFalse($section->isApplicable($contextWithoutBrief));
        
        $contextWithBrief = new PromptContext(mode: 'chat', taskBrief: 'Fix the bug.');
        $this->assertTrue($section->isApplicable($contextWithBrief));
    }

    public function test_builds_task_brief(): void
    {
        $section = new TaskAnalysisSection();
        $context = new PromptContext(mode: 'chat', taskBrief: 'Fix the bug.');
        $prompt = $section->build($context);
        $this->assertStringContainsString('Fix the bug.', $prompt);
    }
}

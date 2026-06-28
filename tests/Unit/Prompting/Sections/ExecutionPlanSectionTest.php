<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\ExecutionPlanSection;
use PHPUnit\Framework\TestCase;

class ExecutionPlanSectionTest extends TestCase
{
    public function test_is_applicable_when_execution_plan_is_present(): void
    {
        $section = new ExecutionPlanSection();
        
        $contextWithoutPlan = new PromptContext(mode: 'chat');
        $this->assertFalse($section->isApplicable($contextWithoutPlan));
        
        $contextWithPlan = new PromptContext(mode: 'chat', executionPlan: ['Step 1']);
        $this->assertTrue($section->isApplicable($contextWithPlan));
    }

    public function test_builds_execution_plan(): void
    {
        $section = new ExecutionPlanSection();
        $context = new PromptContext(mode: 'chat', executionPlan: ['Step 1', 'Step 2']);
        $prompt = $section->build($context);
        
        $this->assertStringContainsString('- Step 1', $prompt);
        $this->assertStringContainsString('- Step 2', $prompt);
    }
}

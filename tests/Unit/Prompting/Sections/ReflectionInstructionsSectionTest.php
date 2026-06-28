<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\ReflectionInstructionsSection;
use PHPUnit\Framework\TestCase;

class ReflectionInstructionsSectionTest extends TestCase
{
    public function test_is_applicable_except_in_fast_mode(): void
    {
        $section = new ReflectionInstructionsSection();
        
        $contextFast = new PromptContext(mode: 'fast');
        $this->assertFalse($section->isApplicable($contextFast));
        
        $contextChat = new PromptContext(mode: 'chat');
        $this->assertTrue($section->isApplicable($contextChat));
    }

    public function test_builds_reflection_instructions(): void
    {
        $section = new ReflectionInstructionsSection();
        $context = new PromptContext(mode: 'chat');
        $prompt = $section->build($context);
        
        $this->assertStringContainsString('Reflection Before Publishing', $prompt);
    }
}

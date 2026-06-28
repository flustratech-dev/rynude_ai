<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\QualitySection;
use PHPUnit\Framework\TestCase;

class QualitySectionTest extends TestCase
{
    public function test_is_applicable_if_threshold_set_or_not_fast_mode(): void
    {
        $section = new QualitySection();
        
        // Fast mode without threshold should be false
        $contextFast = new PromptContext(mode: 'fast');
        $this->assertFalse($section->isApplicable($contextFast));
        
        // Fast mode with threshold should be true
        $contextFastThreshold = new PromptContext(mode: 'fast', qualityThreshold: 90);
        $this->assertTrue($section->isApplicable($contextFastThreshold));
        
        // Chat mode should be true even without threshold
        $contextChat = new PromptContext(mode: 'chat');
        $this->assertTrue($section->isApplicable($contextChat));
    }

    public function test_builds_quality_instructions_with_threshold(): void
    {
        $section = new QualitySection();
        $context = new PromptContext(mode: 'chat', qualityThreshold: 95);
        $prompt = $section->build($context);
        
        $this->assertStringContainsString('95%', $prompt);
        $this->assertStringContainsString('Accuracy', $prompt);
        $this->assertStringContainsString('Completeness', $prompt);
    }
}

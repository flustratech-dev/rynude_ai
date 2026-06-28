<?php

namespace Tests\Unit\Prompting;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\UniversalSystemPromptBuilder;
use PHPUnit\Framework\TestCase;

class UniversalSystemPromptBuilderTest extends TestCase
{
    public function test_builds_complete_prompt(): void
    {
        $builder = new UniversalSystemPromptBuilder();
        
        $context = new PromptContext(
            mode: 'chat',
            taskBrief: 'Write a poem',
            executionPlan: ['Think', 'Write'],
            qualityThreshold: 90,
            artifactRequired: true
        );
        
        $prompt = $builder->build($context);
        
        $this->assertStringContainsString('System Identity', $prompt);
        $this->assertStringContainsString('Task Analysis', $prompt);
        $this->assertStringContainsString('Write a poem', $prompt);
        $this->assertStringContainsString('Execution Plan', $prompt);
        $this->assertStringContainsString('Think', $prompt);
        $this->assertStringContainsString('Planning Before Generation', $prompt);
        $this->assertStringContainsString('Reflection Before Publishing', $prompt);
        $this->assertStringContainsString('Quality Scoring Before Delivery', $prompt);
        $this->assertStringContainsString('90%', $prompt);
        $this->assertStringContainsString('Output Policy', $prompt);
        $this->assertStringContainsString('Artifact Requirements', $prompt);
        
        // Research should not be included
        $this->assertStringNotContainsString('Research Before Writing', $prompt);
    }
}

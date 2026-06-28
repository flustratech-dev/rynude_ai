<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\OutputPolicySection;
use PHPUnit\Framework\TestCase;

class OutputPolicySectionTest extends TestCase
{
    public function test_is_always_applicable(): void
    {
        $section = new OutputPolicySection();
        $context = new PromptContext(mode: 'chat');
        $this->assertTrue($section->isApplicable($context));
    }

    public function test_includes_artifact_and_citation_rules_when_required(): void
    {
        $section = new OutputPolicySection();
        $context = new PromptContext(mode: 'chat', artifactRequired: true, citationRequired: true);
        $prompt = $section->build($context);
        
        $this->assertStringContainsString('Artifact Requirements:', $prompt);
        $this->assertStringContainsString('<antigravity-artifact', $prompt);
        $this->assertStringContainsString('Citation Requirements:', $prompt);
        $this->assertStringContainsString('<cite', $prompt);
        $this->assertStringContainsString('chat', $prompt);
    }

    public function test_excludes_optional_rules(): void
    {
        $section = new OutputPolicySection();
        $context = new PromptContext(mode: 'research', artifactRequired: false, citationRequired: false);
        $prompt = $section->build($context);
        
        $this->assertStringNotContainsString('Artifact Requirements:', $prompt);
        $this->assertStringNotContainsString('Citation Requirements:', $prompt);
        $this->assertStringContainsString('research', $prompt);
    }
}

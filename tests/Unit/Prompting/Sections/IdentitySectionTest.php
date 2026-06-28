<?php

namespace Tests\Unit\Prompting\Sections;

use App\Services\AI\Prompting\PromptContext;
use App\Services\AI\Prompting\Sections\IdentitySection;
use PHPUnit\Framework\TestCase;

class IdentitySectionTest extends TestCase
{
    public function test_is_always_applicable(): void
    {
        $section = new IdentitySection();
        $context = new PromptContext(mode: 'chat');
        $this->assertTrue($section->isApplicable($context));
    }

    public function test_builds_identity(): void
    {
        $section = new IdentitySection();
        $context = new PromptContext(mode: 'chat');
        $prompt = $section->build($context);
        $this->assertStringContainsString('You are RYNUDE V5', $prompt);
    }
}

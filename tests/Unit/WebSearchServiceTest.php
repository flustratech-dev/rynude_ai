<?php

namespace Tests\Unit;

use App\Services\WebSearchService;
use PHPUnit\Framework\TestCase;

class WebSearchServiceTest extends TestCase
{
    public function test_format_for_prompt_returns_empty_string_for_no_results(): void
    {
        $service = new WebSearchService();
        $this->assertSame('', $service->formatForPrompt([]));
    }

    public function test_format_for_prompt_includes_titles_and_urls(): void
    {
        $service = new WebSearchService();
        $output = $service->formatForPrompt([
            ['title' => 'Example Result', 'url' => 'https://example.com', 'snippet' => 'A snippet'],
        ]);

        $this->assertStringContainsString('Example Result', $output);
        $this->assertStringContainsString('https://example.com', $output);
        $this->assertStringContainsString('cite the URLs', $output);
    }
}

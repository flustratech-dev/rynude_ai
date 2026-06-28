<?php

namespace Tests\Unit\Normalization\Extractors;

use App\Services\AI\Normalization\Extractors\CitationExtractor;
use PHPUnit\Framework\TestCase;

class CitationExtractorTest extends TestCase
{
    public function test_extracts_citations_and_keeps_inner_text(): void
    {
        $extractor = new CitationExtractor();
        
        $text = 'According to <cite url="https://example.com" title="Example">this source</cite>, the sky is blue.';
        
        $result = $extractor->extract($text);
        
        $this->assertCount(1, $result['citations']);
        $this->assertEquals('this source', $result['citations'][0]->text);
        $this->assertEquals('https://example.com', $result['citations'][0]->url);
        $this->assertEquals('Example', $result['citations'][0]->title);
        
        $this->assertEquals('According to this source, the sky is blue.', $result['cleanText']);
    }

    public function test_returns_empty_when_no_citations(): void
    {
        $extractor = new CitationExtractor();
        
        $text = 'This is just some text.';
        
        $result = $extractor->extract($text);
        
        $this->assertEmpty($result['citations']);
        $this->assertEquals($text, $result['cleanText']);
    }
}

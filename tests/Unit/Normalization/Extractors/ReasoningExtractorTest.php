<?php

namespace Tests\Unit\Normalization\Extractors;

use App\Services\AI\Normalization\Extractors\ReasoningExtractor;
use PHPUnit\Framework\TestCase;

class ReasoningExtractorTest extends TestCase
{
    public function test_extracts_reasoning_and_removes_from_text(): void
    {
        $extractor = new ReasoningExtractor();
        
        $text = '<thinking>I need to think about this.</thinking> Here is the answer.';
        
        $result = $extractor->extract($text);
        
        $this->assertNotNull($result['reasoning']);
        $this->assertEquals('I need to think about this.', $result['reasoning']->text);
        $this->assertEquals('Here is the answer.', $result['cleanText']);
    }

    public function test_returns_null_when_no_reasoning(): void
    {
        $extractor = new ReasoningExtractor();
        
        $text = 'This is just some text.';
        
        $result = $extractor->extract($text);
        
        $this->assertNull($result['reasoning']);
        $this->assertEquals($text, $result['cleanText']);
    }
}

<?php

namespace Tests\Unit\Normalization;

use App\Services\AI\Normalization\Extractors\ArtifactExtractor;
use App\Services\AI\Normalization\Extractors\CitationExtractor;
use App\Services\AI\Normalization\Extractors\ReasoningExtractor;
use App\Services\AI\Normalization\Extractors\RefusalDetector;
use App\Services\AI\Normalization\OutputNormalizer;
use PHPUnit\Framework\TestCase;

class OutputNormalizerTest extends TestCase
{
    public function test_normalizes_output_correctly(): void
    {
        $normalizer = new OutputNormalizer(
            new ArtifactExtractor(),
            new ReasoningExtractor(),
            new CitationExtractor(),
            new RefusalDetector()
        );
        
        $text = '<thinking>I think therefore I am</thinking>
According to <cite url="https://example.com" title="Example">this source</cite>, here is the file:
<antigravity-artifact identifier="test" type="text/plain" language="text" title="Test">
Hello World
</antigravity-artifact>';
        
        $result = $normalizer->normalize($text);
        
        $this->assertFalse($result->flags['is_refusal']);
        
        $this->assertNotNull($result->reasoning);
        $this->assertEquals('I think therefore I am', $result->reasoning->text);
        
        $this->assertCount(1, $result->citations);
        $this->assertEquals('this source', $result->citations[0]->text);
        
        $this->assertNotNull($result->artifact);
        $this->assertEquals('Hello World', $result->artifact->content);
        
        $this->assertEquals('According to this source, here is the file:', trim($result->visibleText));
    }
}

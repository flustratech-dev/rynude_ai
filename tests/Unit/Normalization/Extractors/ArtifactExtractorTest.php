<?php

namespace Tests\Unit\Normalization\Extractors;

use App\Services\AI\Normalization\Extractors\ArtifactExtractor;
use PHPUnit\Framework\TestCase;

class ArtifactExtractorTest extends TestCase
{
    public function test_extracts_artifact_and_removes_from_text(): void
    {
        $extractor = new ArtifactExtractor();
        
        $text = 'Here is your file:
<antigravity-artifact identifier="test" type="application/json" language="json" title="Test File">
{
  "key": "value"
}
</antigravity-artifact>
Hope this helps!';
        
        $result = $extractor->extract($text);
        
        $this->assertNotNull($result['artifact']);
        $this->assertEquals('test', $result['artifact']->identifier);
        $this->assertEquals('application/json', $result['artifact']->type);
        $this->assertEquals('json', $result['artifact']->language);
        $this->assertEquals('Test File', $result['artifact']->title);
        $this->assertEquals("{\n  \"key\": \"value\"\n}", $result['artifact']->content);
        
        $this->assertEquals("Here is your file:\n\nHope this helps!", $result['cleanText']);
    }

    public function test_returns_null_when_no_artifact(): void
    {
        $extractor = new ArtifactExtractor();
        
        $text = 'This is just some text without an artifact.';
        
        $result = $extractor->extract($text);
        
        $this->assertNull($result['artifact']);
        $this->assertEquals($text, $result['cleanText']);
    }
}

<?php

namespace Tests\Unit\Normalization\Extractors;

use App\Services\AI\Normalization\Extractors\RefusalDetector;
use PHPUnit\Framework\TestCase;

class RefusalDetectorTest extends TestCase
{
    public function test_detects_refusal(): void
    {
        $detector = new RefusalDetector();
        
        $text = "I'm sorry, but I can't do that Dave.";
        
        $this->assertTrue($detector->detect($text));
    }

    public function test_returns_false_when_not_refusal(): void
    {
        $detector = new RefusalDetector();
        
        $text = 'This is just some text.';
        
        $this->assertFalse($detector->detect($text));
    }
}

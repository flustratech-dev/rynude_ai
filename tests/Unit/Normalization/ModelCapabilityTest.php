<?php

namespace Tests\Unit\Normalization;

use App\Services\AI\Normalization\ModelCapability;
use PHPUnit\Framework\TestCase;

class ModelCapabilityTest extends TestCase
{
    public function test_holds_all_flags(): void
    {
        $cap = new ModelCapability(
            thinking: true,
            nativeTools: true,
            jsonMode: false,
            vision: true,
            maxContextTokens: 200000,
        );

        $this->assertTrue($cap->thinking);
        $this->assertTrue($cap->nativeTools);
        $this->assertFalse($cap->jsonMode);
        $this->assertTrue($cap->vision);
        $this->assertSame(200000, $cap->maxContextTokens);
    }

    public function test_to_array_uses_canonical_keys(): void
    {
        $cap = new ModelCapability(
            thinking: false,
            nativeTools: true,
            jsonMode: true,
            vision: false,
            maxContextTokens: 128000,
        );

        $this->assertSame([
            'thinking' => false,
            'native_tools' => true,
            'json_mode' => true,
            'vision' => false,
            'max_ctx' => 128000,
        ], $cap->toArray());
    }
}

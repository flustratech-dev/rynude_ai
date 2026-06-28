<?php

namespace Tests\Feature\Normalization;

use App\Services\AI\Normalization\Adapters\AnthropicAdapter;
use App\Services\AI\Normalization\ModelAdapterRegistry;
use Tests\TestCase;

class ModelAdapterRegistryTest extends TestCase
{
    public function test_resolves_anthropic_sonnet(): void
    {
        $registry = new ModelAdapterRegistry();
        $adapter = $registry->for('claude-sonnet-4-6');

        $this->assertInstanceOf(AnthropicAdapter::class, $adapter);
        $this->assertSame('claude-sonnet-4-6', $adapter->model);
    }

    public function test_resolves_anthropic_haiku(): void
    {
        $registry = new ModelAdapterRegistry();
        $adapter = $registry->for('claude-haiku-4-5');

        $this->assertInstanceOf(AnthropicAdapter::class, $adapter);
    }

    public function test_resolves_legacy_anthropic_model_codes(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertInstanceOf(AnthropicAdapter::class, $registry->for('claude-3-5-sonnet-20241022'));
        $this->assertInstanceOf(AnthropicAdapter::class, $registry->for('claude-3-opus-20240229'));
    }

    public function test_kr_claude_routes_are_not_anthropic_in_step_3(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertFalse($registry->supports('kr/claude-sonnet'));
        $this->expectException(\InvalidArgumentException::class);
        $registry->for('kr/claude-sonnet');
    }

    public function test_unknown_model_throws(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No ModelAdapter registered for model: gpt-4o');
        $registry->for('gpt-4o');
    }

    public function test_non_anthropic_models_are_not_yet_supported(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertFalse($registry->supports('gpt-4o'));
        $this->assertFalse($registry->supports('gemini-1.5-pro'));
        $this->assertFalse($registry->supports('mistral-large-latest'));
        $this->assertTrue($registry->supports('claude-sonnet-4-6'));
    }
}

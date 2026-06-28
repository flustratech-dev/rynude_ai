<?php

namespace Tests\Feature\Normalization;

use App\Services\AI\Normalization\Adapters\AnthropicAdapter;
use App\Services\AI\Normalization\Adapters\GoogleAdapter;
use App\Services\AI\Normalization\Adapters\MistralAdapter;
use App\Services\AI\Normalization\Adapters\OpenAIAdapter;
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

    public function test_resolves_openai_gpt(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('gpt-4o'));
        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('gpt-5'));
        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('gpt-4.1'));
        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('gpt-3.5-turbo'));
    }

    public function test_resolves_openai_reasoning_models(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('o1-preview'));
        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('o3-mini'));
        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('o4-mini'));
    }

    public function test_kr_claude_routes_through_openai_adapter(): void
    {
        // 9Router exposes claude via an OpenAI-compatible endpoint, so the
        // adapter for these codes must be OpenAIAdapter, not AnthropicAdapter.
        $registry = new ModelAdapterRegistry();
        $adapter = $registry->for('kr/claude-sonnet');

        $this->assertInstanceOf(OpenAIAdapter::class, $adapter);
        $this->assertSame('kr/claude-sonnet', $adapter->model);
    }

    public function test_mmf_mimo_routes_through_openai_adapter(): void
    {
        $registry = new ModelAdapterRegistry();
        $this->assertInstanceOf(OpenAIAdapter::class, $registry->for('mmf/mimo-7b'));
    }

    public function test_resolves_google_gemini(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertInstanceOf(GoogleAdapter::class, $registry->for('gemini-1.5-pro-latest'));
        $this->assertInstanceOf(GoogleAdapter::class, $registry->for('gemini-1.5-flash'));
        $this->assertInstanceOf(GoogleAdapter::class, $registry->for('gemini-2.0-flash'));
        $this->assertInstanceOf(GoogleAdapter::class, $registry->for('gemini-2.5-pro'));
    }

    public function test_resolves_mistral_family(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertInstanceOf(MistralAdapter::class, $registry->for('mistral-large-latest'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('mistral-small-latest'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('magistral-medium-latest'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('ministral-8b-latest'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('open-mistral-7b'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('open-mixtral-8x22b'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('codestral-latest'));
        $this->assertInstanceOf(MistralAdapter::class, $registry->for('pixtral-large-latest'));
    }

    public function test_unknown_model_falls_back_to_openai_adapter(): void
    {
        // Mirrors AiService::resolveProvider, which defaults to OpenAIProvider
        // for any code that doesn't match a known prefix. OpenAIProvider in turn
        // handles HuggingFace / Ollama / proxy endpoints based on user settings.
        $registry = new ModelAdapterRegistry();
        $adapter = $registry->for('some-future-unknown-model');

        $this->assertInstanceOf(OpenAIAdapter::class, $adapter);
        $this->assertSame('some-future-unknown-model', $adapter->model);
    }

    public function test_supports_reports_true_for_known_prefixes(): void
    {
        $registry = new ModelAdapterRegistry();

        $this->assertTrue($registry->supports('claude-sonnet-4-6'));
        $this->assertTrue($registry->supports('gpt-4o'));
        $this->assertTrue($registry->supports('o3-mini'));
        $this->assertTrue($registry->supports('gemini-1.5-pro'));
        $this->assertTrue($registry->supports('mistral-large-latest'));
        $this->assertTrue($registry->supports('codestral-latest'));
        $this->assertTrue($registry->supports('pixtral-large-latest'));
        $this->assertTrue($registry->supports('kr/claude-sonnet'));
        $this->assertTrue($registry->supports('mmf/mimo-7b'));
    }

    public function test_supports_reports_false_for_unknown_codes(): void
    {
        $registry = new ModelAdapterRegistry();

        // Fallback still resolves these via for(), but supports() reports the
        // honest answer so callers can detect non-routable codes if needed.
        $this->assertFalse($registry->supports('some-future-unknown-model'));
        $this->assertFalse($registry->supports('llama-3.1-70b'));
    }
}

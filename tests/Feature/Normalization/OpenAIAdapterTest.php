<?php

namespace Tests\Feature\Normalization;

use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Adapters\OpenAIAdapter;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use Tests\Support\FakeLLMProvider;
use Tests\TestCase;

class OpenAIAdapterTest extends TestCase
{
    public function test_capabilities_for_gpt_4o(): void
    {
        $adapter = new OpenAIAdapter('gpt-4o');
        $caps = $adapter->capabilities();

        $this->assertFalse($caps->thinking);
        $this->assertTrue($caps->nativeTools);
        $this->assertTrue($caps->jsonMode);
        $this->assertTrue($caps->vision);
        $this->assertSame(128000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_gpt_5_has_extended_context(): void
    {
        $caps = (new OpenAIAdapter('gpt-5'))->capabilities();
        $this->assertSame(200000, $caps->maxContextTokens);
        $this->assertTrue($caps->vision);
    }

    public function test_capabilities_for_gpt_4_1_has_one_million_context(): void
    {
        $caps = (new OpenAIAdapter('gpt-4.1'))->capabilities();
        $this->assertSame(1000000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_o3_reasoning_model(): void
    {
        $caps = (new OpenAIAdapter('o3-mini'))->capabilities();
        $this->assertFalse($caps->thinking, 'provider does not surface reasoning deltas');
        $this->assertTrue($caps->vision);
        $this->assertSame(200000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_gpt_3_5_no_vision(): void
    {
        $caps = (new OpenAIAdapter('gpt-3.5-turbo'))->capabilities();
        $this->assertFalse($caps->vision);
        $this->assertSame(16385, $caps->maxContextTokens);
    }

    public function test_capabilities_for_kr_claude_router_disables_native_tools_and_json(): void
    {
        $caps = (new OpenAIAdapter('kr/claude-sonnet'))->capabilities();
        $this->assertFalse($caps->nativeTools);
        $this->assertFalse($caps->jsonMode);
        $this->assertFalse($caps->vision);
        $this->assertSame(200000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_mmf_mimo_router(): void
    {
        $caps = (new OpenAIAdapter('mmf/mimo-7b'))->capabilities();
        $this->assertFalse($caps->nativeTools);
        $this->assertFalse($caps->jsonMode);
    }

    public function test_stream_completion_yields_text_events(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['Hello, ', 'world!'];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'be brief',
            messages: [['role' => 'user', 'content' => 'hi']],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(2, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('Hello, ', $events[0]->payload['text']);
        $this->assertSame('world!', $events[1]->payload['text']);
    }

    public function test_thinking_prefix_is_not_special_for_openai(): void
    {
        // Unlike Anthropic, OpenAIProvider does not use a "[Thinking] " convention.
        // The adapter must pass through such a chunk verbatim as text.
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['[Thinking] just text here'];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest('s', [['role' => 'user', 'content' => 'x']]);

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(1, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('[Thinking] just text here', $events[0]->payload['text']);
    }

    public function test_stream_completion_skips_non_string_chunks(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['ok'];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'x']]);

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(1, $events);
        $this->assertSame(NormalizedEvent::class, get_class($events[0]));
    }

    public function test_stream_completion_prepends_system_prompt_as_system_message(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'YOU ARE RYNUDE',
            messages: [['role' => 'user', 'content' => 'hi']],
        );

        iterator_to_array($adapter->streamCompletion($req), false);

        $call = $provider->lastCall();
        $this->assertNotNull($call);
        $this->assertSame('system', $call['messages'][0]['role']);
        $this->assertSame('YOU ARE RYNUDE', $call['messages'][0]['content']);
        $this->assertSame('user', $call['messages'][1]['role']);
        $this->assertSame('gpt-4o', $call['model']);
    }

    public function test_empty_system_prompt_is_omitted_from_messages(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'hi']]);

        iterator_to_array($adapter->streamCompletion($req), false);

        $call = $provider->lastCall();
        $this->assertCount(1, $call['messages']);
        $this->assertSame('user', $call['messages'][0]['role']);
    }

    public function test_tools_route_to_agent_turn_and_translate_events(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'text', 'text' => 'Reading file...'],
            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'read_file', 'input' => ['path' => 'a.txt']],
        ];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'read a.txt']],
            tools: [['name' => 'read_file', 'description' => 'read', 'input_schema' => ['type' => 'object']]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(2, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('tool_use', $events[1]->type);
        $this->assertSame('call_1', $events[1]->payload['id']);
        $this->assertSame('read_file', $events[1]->payload['name']);
        $this->assertSame(['path' => 'a.txt'], $events[1]->payload['input']);

        $call = $provider->lastCall();
        $this->assertArrayHasKey('tools', $call);
        $this->assertSame('read_file', $call['tools'][0]['name']);
    }

    public function test_tool_use_with_non_array_input_is_normalized_to_empty(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'no_args'],
        ];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 's',
            messages: [['role' => 'user', 'content' => 'go']],
            tools: [['name' => 'no_args', 'description' => '', 'input_schema' => ['type' => 'object']]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);
        $this->assertSame('tool_use', $events[0]->type);
        $this->assertSame([], $events[0]->payload['input']);
    }

    public function test_no_tools_does_not_call_agent_turn(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['hi'];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest('s', [['role' => 'user', 'content' => 'x']]);

        iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(1, $provider->calls);
        $this->assertArrayNotHasKey('tools', $provider->calls[0]);
    }

    public function test_agent_events_with_missing_fields_are_skipped(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            'not-an-array',
            ['no_type_key' => true],
            ['type' => 'thinking', 'text' => 'ignored — openai has no thinking'],
            ['type' => 'text', 'text' => 'ok'],
        ];

        $adapter = new OpenAIAdapter('gpt-4o', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 's',
            messages: [['role' => 'user', 'content' => 'x']],
            tools: [['name' => 't', 'description' => '', 'input_schema' => []]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);
        $this->assertCount(1, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('ok', $events[0]->payload['text']);
    }
}

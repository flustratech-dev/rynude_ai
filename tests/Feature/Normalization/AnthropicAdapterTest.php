<?php

namespace Tests\Feature\Normalization;

use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Adapters\AnthropicAdapter;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use Tests\Support\FakeLLMProvider;
use Tests\TestCase;

class AnthropicAdapterTest extends TestCase
{
    public function test_capabilities_for_sonnet_enables_thinking(): void
    {
        $adapter = new AnthropicAdapter('claude-sonnet-4-6');
        $caps = $adapter->capabilities();

        $this->assertTrue($caps->thinking);
        $this->assertTrue($caps->nativeTools);
        $this->assertTrue($caps->vision);
        $this->assertSame(200000, $caps->maxContextTokens);
        $this->assertFalse($caps->jsonMode);
    }

    public function test_capabilities_for_opus_enables_thinking(): void
    {
        $adapter = new AnthropicAdapter('claude-opus-4-8');
        $this->assertTrue($adapter->capabilities()->thinking);
    }

    public function test_capabilities_for_haiku_disables_thinking(): void
    {
        $adapter = new AnthropicAdapter('claude-haiku-4-5');
        $this->assertFalse($adapter->capabilities()->thinking);
    }

    public function test_capabilities_for_legacy_claude_3_5_enables_thinking(): void
    {
        $adapter = new AnthropicAdapter('claude-3-5-sonnet-20241022');
        $this->assertTrue($adapter->capabilities()->thinking);
    }

    public function test_stream_completion_yields_text_events(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['Hello, ', 'world!'];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
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

    public function test_stream_completion_translates_thinking_prefix(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = [
            '[Thinking] planning the answer',
            'final text',
        ];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
        $req = new NormalizedRequest('sys', [['role' => 'user', 'content' => 'go']]);

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertSame('thinking', $events[0]->type);
        $this->assertSame('planning the answer', $events[0]->payload['text']);
        $this->assertSame('text', $events[1]->type);
        $this->assertSame('final text', $events[1]->payload['text']);
    }

    public function test_stream_completion_skips_non_string_chunks(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['ok'];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'x']]);

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(1, $events);
        $this->assertSame(NormalizedEvent::class, get_class($events[0]));
    }

    public function test_stream_completion_prepends_system_prompt_as_system_message(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
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
        $this->assertSame('claude-sonnet-4-6', $call['model']);
    }

    public function test_empty_system_prompt_is_omitted_from_messages(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
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
            ['type' => 'thinking', 'text' => 'I will use a tool'],
            ['type' => 'text', 'text' => 'Reading file...'],
            ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'read_file', 'input' => ['path' => 'a.txt']],
        ];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'read a.txt']],
            tools: [['name' => 'read_file', 'description' => 'read', 'input_schema' => ['type' => 'object']]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(3, $events);
        $this->assertSame('thinking', $events[0]->type);
        $this->assertSame('I will use a tool', $events[0]->payload['text']);
        $this->assertSame('text', $events[1]->type);
        $this->assertSame('tool_use', $events[2]->type);
        $this->assertSame('tu_1', $events[2]->payload['id']);
        $this->assertSame('read_file', $events[2]->payload['name']);
        $this->assertSame(['path' => 'a.txt'], $events[2]->payload['input']);

        $call = $provider->lastCall();
        $this->assertArrayHasKey('tools', $call);
        $this->assertSame('read_file', $call['tools'][0]['name']);
    }

    public function test_tool_use_with_non_array_input_is_normalized_to_empty(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'no_args'],
        ];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
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

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
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
            ['type' => 'text', 'text' => 'ok'],
        ];

        $adapter = new AnthropicAdapter('claude-sonnet-4-6', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 's',
            messages: [['role' => 'user', 'content' => 'x']],
            tools: [['name' => 't', 'description' => '', 'input_schema' => []]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);
        $this->assertCount(1, $events);
        $this->assertSame('ok', $events[0]->payload['text']);
    }
}

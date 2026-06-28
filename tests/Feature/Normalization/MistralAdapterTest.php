<?php

namespace Tests\Feature\Normalization;

use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Adapters\MistralAdapter;
use Tests\Support\FakeLLMProvider;
use Tests\TestCase;

class MistralAdapterTest extends TestCase
{
    public function test_capabilities_for_mistral_large(): void
    {
        $caps = (new MistralAdapter('mistral-large-latest'))->capabilities();

        $this->assertFalse($caps->thinking);
        $this->assertTrue($caps->nativeTools);
        $this->assertTrue($caps->jsonMode);
        $this->assertFalse($caps->vision);
        $this->assertSame(128000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_mistral_small(): void
    {
        $caps = (new MistralAdapter('mistral-small-latest'))->capabilities();
        $this->assertSame(32000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_codestral_has_extended_context(): void
    {
        $caps = (new MistralAdapter('codestral-latest'))->capabilities();
        $this->assertSame(256000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_pixtral_enables_vision(): void
    {
        $caps = (new MistralAdapter('pixtral-large-latest'))->capabilities();
        $this->assertTrue($caps->vision);
        $this->assertSame(128000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_open_mixtral(): void
    {
        $caps = (new MistralAdapter('open-mixtral-8x22b'))->capabilities();
        $this->assertSame(64000, $caps->maxContextTokens);
        $this->assertFalse($caps->vision);
    }

    public function test_capabilities_for_open_mistral(): void
    {
        $caps = (new MistralAdapter('open-mistral-7b'))->capabilities();
        $this->assertSame(32000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_magistral(): void
    {
        $caps = (new MistralAdapter('magistral-medium-latest'))->capabilities();
        $this->assertSame(128000, $caps->maxContextTokens);
    }

    public function test_stream_completion_yields_text_events(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['Bonjour ', 'monde!'];

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'hello']],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(2, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('Bonjour ', $events[0]->payload['text']);
        $this->assertSame('monde!', $events[1]->payload['text']);
    }

    public function test_stream_completion_skips_non_string_chunks(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['ok'];

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'x']]);

        $events = iterator_to_array($adapter->streamCompletion($req), false);
        $this->assertCount(1, $events);
    }

    public function test_stream_completion_prepends_system_prompt(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'YOU ARE RYNUDE',
            messages: [['role' => 'user', 'content' => 'hi']],
        );

        iterator_to_array($adapter->streamCompletion($req), false);

        $call = $provider->lastCall();
        $this->assertSame('system', $call['messages'][0]['role']);
        $this->assertSame('YOU ARE RYNUDE', $call['messages'][0]['content']);
        $this->assertSame('mistral-large-latest', $call['model']);
    }

    public function test_empty_system_prompt_is_omitted(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'hi']]);

        iterator_to_array($adapter->streamCompletion($req), false);

        $call = $provider->lastCall();
        $this->assertCount(1, $call['messages']);
        $this->assertSame('user', $call['messages'][0]['role']);
    }

    public function test_tools_route_to_agent_turn(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'text', 'text' => 'Using the tool'],
            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'do_thing', 'input' => ['k' => 'v']],
        ];

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'go']],
            tools: [['name' => 'do_thing', 'description' => 'd', 'input_schema' => ['type' => 'object']]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(2, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('tool_use', $events[1]->type);
        $this->assertSame('call_1', $events[1]->payload['id']);
        $this->assertSame('do_thing', $events[1]->payload['name']);
        $this->assertSame(['k' => 'v'], $events[1]->payload['input']);

        $call = $provider->lastCall();
        $this->assertArrayHasKey('tools', $call);
    }

    public function test_tool_use_with_non_array_input_is_normalized_to_empty(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'no_args'],
        ];

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
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

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
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

        $adapter = new MistralAdapter('mistral-large-latest', $provider);
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

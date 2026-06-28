<?php

namespace Tests\Feature\Normalization;

use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Adapters\GoogleAdapter;
use Tests\Support\FakeLLMProvider;
use Tests\TestCase;

class GoogleAdapterTest extends TestCase
{
    public function test_capabilities_for_gemini_1_5_pro(): void
    {
        $caps = (new GoogleAdapter('gemini-1.5-pro-latest'))->capabilities();

        $this->assertFalse($caps->thinking);
        $this->assertTrue($caps->nativeTools);
        $this->assertTrue($caps->jsonMode);
        $this->assertTrue($caps->vision);
        $this->assertSame(2000000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_gemini_1_5_flash(): void
    {
        $caps = (new GoogleAdapter('gemini-1.5-flash'))->capabilities();
        $this->assertSame(1000000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_gemini_2_5_pro(): void
    {
        $caps = (new GoogleAdapter('gemini-2.5-pro'))->capabilities();
        $this->assertSame(2000000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_gemini_2_0_flash(): void
    {
        $caps = (new GoogleAdapter('gemini-2.0-flash'))->capabilities();
        $this->assertSame(1000000, $caps->maxContextTokens);
    }

    public function test_capabilities_for_unknown_gemini_falls_back(): void
    {
        $caps = (new GoogleAdapter('gemini-experimental-1206'))->capabilities();
        $this->assertSame(128000, $caps->maxContextTokens);
    }

    public function test_stream_completion_yields_text_events(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['Hi ', 'there!'];

        $adapter = new GoogleAdapter('gemini-1.5-flash', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'hello']],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(2, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('Hi ', $events[0]->payload['text']);
        $this->assertSame('there!', $events[1]->payload['text']);
    }

    public function test_stream_completion_skips_non_string_chunks(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['ok'];

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'x']]);

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(1, $events);
    }

    public function test_stream_completion_prepends_system_prompt(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'RYNUDE INSTRUCTIONS',
            messages: [['role' => 'user', 'content' => 'hi']],
        );

        iterator_to_array($adapter->streamCompletion($req), false);

        $call = $provider->lastCall();
        $this->assertSame('system', $call['messages'][0]['role']);
        $this->assertSame('RYNUDE INSTRUCTIONS', $call['messages'][0]['content']);
        $this->assertSame('gemini-1.5-pro', $call['model']);
    }

    public function test_empty_system_prompt_is_omitted(): void
    {
        $provider = new FakeLLMProvider();
        $provider->streamScript = ['x'];

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
        $req = new NormalizedRequest('', [['role' => 'user', 'content' => 'hi']]);

        iterator_to_array($adapter->streamCompletion($req), false);

        $call = $provider->lastCall();
        $this->assertCount(1, $call['messages']);
        $this->assertSame('user', $call['messages'][0]['role']);
    }

    public function test_tools_route_to_agent_turn_and_translate_function_calls(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'text', 'text' => 'Calling the search tool'],
            ['type' => 'tool_use', 'id' => 'gcall_0', 'name' => 'search', 'input' => ['q' => 'php']],
        ];

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'search php']],
            tools: [['name' => 'search', 'description' => 's', 'input_schema' => ['type' => 'object']]],
        );

        $events = iterator_to_array($adapter->streamCompletion($req), false);

        $this->assertCount(2, $events);
        $this->assertSame('text', $events[0]->type);
        $this->assertSame('tool_use', $events[1]->type);
        $this->assertSame('gcall_0', $events[1]->payload['id']);
        $this->assertSame('search', $events[1]->payload['name']);
        $this->assertSame(['q' => 'php'], $events[1]->payload['input']);

        $call = $provider->lastCall();
        $this->assertArrayHasKey('tools', $call);
    }

    public function test_tool_use_with_non_array_input_is_normalized_to_empty(): void
    {
        $provider = new FakeLLMProvider();
        $provider->agentScript = [
            ['type' => 'tool_use', 'id' => 'gcall_0', 'name' => 'no_args'],
        ];

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
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

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
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

        $adapter = new GoogleAdapter('gemini-1.5-pro', $provider);
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

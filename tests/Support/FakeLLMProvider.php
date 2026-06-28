<?php

namespace Tests\Support;

use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;

/**
 * Test double for LLMProviderInterface + SupportsToolUse. Records every call
 * and replays a scripted event sequence — used by adapter tests so they can
 * assert on translation behaviour without real HTTP traffic.
 *
 * Scripts are PHP arrays that match each provider's native yield shape:
 *   - streamResponse(): array of strings (with optional "[Thinking] " prefix)
 *   - streamAgentTurn(): array of ['type' => ..., ...] event arrays
 */
class FakeLLMProvider implements LLMProviderInterface, SupportsToolUse
{
    /** @var array<int, array{messages: array, model: string, tools?: array}> */
    public array $calls = [];

    /** @var array<int, string> */
    public array $streamScript = [];

    /** @var array<int, array<string, mixed>> */
    public array $agentScript = [];

    public array $agentReturn = ['stop_reason' => 'end'];

    public function streamResponse(array $messages, string $model): \Generator
    {
        $this->calls[] = ['messages' => $messages, 'model' => $model];
        foreach ($this->streamScript as $chunk) {
            yield $chunk;
        }
    }

    public function streamAgentTurn(array $messages, string $model, array $tools): \Generator
    {
        $this->calls[] = ['messages' => $messages, 'model' => $model, 'tools' => $tools];
        foreach ($this->agentScript as $event) {
            yield $event;
        }
        return $this->agentReturn;
    }

    public function lastCall(): ?array
    {
        if (empty($this->calls)) {
            return null;
        }
        return $this->calls[array_key_last($this->calls)];
    }
}

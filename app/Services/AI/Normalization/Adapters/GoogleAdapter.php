<?php

namespace App\Services\AI\Normalization\Adapters;

use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\GoogleProvider;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use App\Services\AI\Normalization\ModelAdapter;
use App\Services\AI\Normalization\ModelCapability;

/**
 * Gemini-family adapter. Wraps the existing GoogleProvider untouched.
 *
 * Provider yield shapes:
 *   - streamResponse(): strings (no thinking convention; Gemini's reasoning
 *     trace is not surfaced through the v1beta streamGenerateContent endpoint)
 *   - streamAgentTurn(): ['type' => 'text'|'tool_use', ...] arrays mapped from
 *     functionCall / text parts
 *
 * GoogleProvider builds its own systemInstruction from any role=='system'
 * messages in the unified history, so this adapter prepends the system prompt
 * the same way AnthropicAdapter does and lets the provider extract it.
 */
final class GoogleAdapter extends ModelAdapter
{
    private LLMProviderInterface $provider;

    public function __construct(string $model, ?LLMProviderInterface $provider = null)
    {
        parent::__construct($model);
        $this->provider = $provider ?? new GoogleProvider();
    }

    public function adaptSystemPrompt(string $prompt): string
    {
        // Gemini Pro follows the shared prompt; Flash/Flash-Lite drift on the
        // artifact format without the strict preamble.
        $isPro = str_contains($this->model, 'pro');

        return $isPro ? $prompt : $this->strictOutputRules() . $prompt;
    }

    public function capabilities(): ModelCapability
    {
        $model = $this->model;

        // Context windows by Gemini family. Conservative for unknown gemini-* codes.
        $maxCtx = match (true) {
            str_starts_with($model, 'gemini-1.5-pro')     => 2000000,
            str_starts_with($model, 'gemini-1.5-flash')   => 1000000,
            str_starts_with($model, 'gemini-2.0-flash')   => 1000000,
            str_starts_with($model, 'gemini-2.5-pro')     => 2000000,
            str_starts_with($model, 'gemini-2.5-flash')   => 1000000,
            str_starts_with($model, 'gemini-2')           => 1000000,
            str_starts_with($model, 'gemini-1')           => 1000000,
            str_starts_with($model, 'gemini')             => 128000,
            default                                        => 128000,
        };

        return new ModelCapability(
            thinking: false,
            nativeTools: true,
            jsonMode: true,
            vision: true,
            maxContextTokens: $maxCtx,
        );
    }

    public function streamCompletion(NormalizedRequest $req): \Generator
    {
        $messages = $this->buildMessages($req);

        if (!empty($req->tools)) {
            if (!$this->provider instanceof SupportsToolUse) {
                yield NormalizedEvent::error('Provider does not support tool use');
                return;
            }
            yield from $this->streamAgentTurn($messages, $req->tools);
            return;
        }

        yield from $this->streamText($messages);
    }

    /**
     * @return \Generator<int, NormalizedEvent>
     */
    private function streamText(array $messages): \Generator
    {
        foreach ($this->provider->streamResponse($messages, $this->model) as $chunk) {
            if (!is_string($chunk)) {
                continue;
            }
            yield NormalizedEvent::text($chunk);
        }
    }

    /**
     * @return \Generator<int, NormalizedEvent>
     */
    private function streamAgentTurn(array $messages, array $tools): \Generator
    {
        /** @var SupportsToolUse $provider */
        $provider = $this->provider;

        foreach ($provider->streamAgentTurn($messages, $this->model, $tools) as $event) {
            if (!is_array($event) || !isset($event['type'])) {
                continue;
            }
            switch ($event['type']) {
                case 'text':
                    yield NormalizedEvent::text((string) ($event['text'] ?? ''));
                    break;
                case 'tool_use':
                    yield NormalizedEvent::toolUse(
                        (string) ($event['id'] ?? ''),
                        (string) ($event['name'] ?? ''),
                        is_array($event['input'] ?? null) ? $event['input'] : [],
                    );
                    break;
            }
        }
    }

    private function buildMessages(NormalizedRequest $req): array
    {
        $messages = [];
        if ($req->systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $req->systemPrompt];
        }
        foreach ($req->messages as $msg) {
            $messages[] = $msg;
        }
        return $messages;
    }
}

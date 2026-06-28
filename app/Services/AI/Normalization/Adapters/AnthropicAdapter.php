<?php

namespace App\Services\AI\Normalization\Adapters;

use App\Services\AI\AnthropicProvider;
use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use App\Services\AI\Normalization\ModelAdapter;
use App\Services\AI\Normalization\ModelCapability;

/**
 * Anthropic-family adapter. Wraps the existing AnthropicProvider untouched
 * and translates its two native event surfaces into NormalizedEvent objects:
 *
 *   - streamResponse() yields strings; a "[Thinking] " prefix marks an
 *     extended-thinking delta and is rewritten into NormalizedEvent::thinking.
 *   - streamAgentTurn() yields structured ['type' => ...] arrays that map
 *     directly to text / thinking / tool_use events.
 *
 * The choice between the two is driven by whether the request carries tools.
 */
final class AnthropicAdapter extends ModelAdapter
{
    private LLMProviderInterface $provider;

    public function __construct(string $model, ?LLMProviderInterface $provider = null)
    {
        parent::__construct($model);
        $this->provider = $provider ?? new AnthropicProvider();
    }

    public function capabilities(): ModelCapability
    {
        $model = $this->model;

        $isHaiku = str_contains($model, 'haiku');
        $thinking = !$isHaiku && (
            str_contains($model, 'sonnet') ||
            str_contains($model, 'opus') ||
            str_contains($model, 'thinking') ||
            str_contains($model, 'claude-3-5') ||
            str_contains($model, 'claude-3-7') ||
            str_contains($model, 'claude-4') ||
            str_contains($model, 'claude-fable')
        );

        return new ModelCapability(
            thinking: $thinking,
            nativeTools: true,
            jsonMode: false,
            vision: true,
            maxContextTokens: 200000,
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
            if (str_starts_with($chunk, '[Thinking] ')) {
                yield NormalizedEvent::thinking(substr($chunk, strlen('[Thinking] ')));
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

        $gen = $provider->streamAgentTurn($messages, $this->model, $tools);
        foreach ($gen as $event) {
            if (!is_array($event) || !isset($event['type'])) {
                continue;
            }
            switch ($event['type']) {
                case 'text':
                    yield NormalizedEvent::text((string) ($event['text'] ?? ''));
                    break;
                case 'thinking':
                    yield NormalizedEvent::thinking((string) ($event['text'] ?? ''));
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

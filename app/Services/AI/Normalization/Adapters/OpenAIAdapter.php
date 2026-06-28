<?php

namespace App\Services\AI\Normalization\Adapters;

use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use App\Services\AI\Normalization\ModelAdapter;
use App\Services\AI\Normalization\ModelCapability;
use App\Services\AI\OpenAIProvider;

/**
 * OpenAI-family adapter. Wraps the existing OpenAIProvider untouched.
 *
 * Handles three families that all route through OpenAIProvider:
 *   - Genuine OpenAI (gpt-*, o1/o3/o4 reasoning models): full native tools + JSON mode
 *   - 9Router proxies (kr/claude-*, mmf/mimo-*): no native tools, no JSON mode
 *   - HuggingFace / Ollama / generic OpenAI-compatible proxies: handled per-user at
 *     OpenAIProvider::resolveConfig; from the adapter's perspective they look like
 *     generic OpenAI calls with potentially weaker tool support.
 *
 * Provider yield shapes:
 *   - streamResponse(): strings (no thinking convention — OpenAI does not surface
 *     reasoning deltas through the standard chat-completions stream)
 *   - streamAgentTurn(): ['type' => 'text'|'tool_use', ...] arrays via the shared
 *     OpenAiCompatToolStream trait
 */
final class OpenAIAdapter extends ModelAdapter
{
    private LLMProviderInterface $provider;

    public function __construct(string $model, ?LLMProviderInterface $provider = null)
    {
        parent::__construct($model);
        $this->provider = $provider ?? new OpenAIProvider();
    }

    public function capabilities(): ModelCapability
    {
        $model = $this->model;
        $isRouter = $this->isRouterRoute($model);

        $nativeTools = !$isRouter;
        $jsonMode = !$isRouter;

        $vision = !$isRouter && (
            str_starts_with($model, 'gpt-4') ||
            str_starts_with($model, 'gpt-5') ||
            str_starts_with($model, 'o3') ||
            str_starts_with($model, 'o4')
        );

        $maxCtx = match (true) {
            str_starts_with($model, 'gpt-5')   => 200000,
            str_starts_with($model, 'gpt-4.1') => 1000000,
            str_starts_with($model, 'gpt-4o')  => 128000,
            str_starts_with($model, 'gpt-4')   => 128000,
            str_starts_with($model, 'gpt-3.5') => 16385,
            str_starts_with($model, 'o1'),
            str_starts_with($model, 'o3'),
            str_starts_with($model, 'o4')      => 200000,
            str_starts_with($model, 'kr/claude') => 200000,
            default                            => 128000,
        };

        return new ModelCapability(
            thinking: false,
            nativeTools: $nativeTools,
            jsonMode: $jsonMode,
            vision: $vision,
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

    private function isRouterRoute(string $model): bool
    {
        return str_starts_with($model, 'kr/') || str_starts_with($model, 'mmf/');
    }
}

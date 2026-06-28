<?php

namespace App\Services\AI\Normalization\Adapters;

use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\MistralProvider;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use App\Services\AI\Normalization\ModelAdapter;
use App\Services\AI\Normalization\ModelCapability;

/**
 * Mistral-family adapter. Wraps the existing MistralProvider untouched.
 *
 * MistralProvider speaks the OpenAI-compatible chat-completions protocol so its
 * yield shapes match OpenAIProvider:
 *   - streamResponse(): strings (no thinking)
 *   - streamAgentTurn(): ['type' => 'text'|'tool_use', ...] arrays via the
 *     shared OpenAiCompatToolStream trait
 *
 * Covers: mistral-*, magistral-*, ministral-*, open-mistral-*, open-mixtral-*,
 * codestral-*, pixtral-* (vision).
 */
final class MistralAdapter extends ModelAdapter
{
    private LLMProviderInterface $provider;

    public function __construct(string $model, ?LLMProviderInterface $provider = null)
    {
        parent::__construct($model);
        $this->provider = $provider ?? new MistralProvider();
    }

    public function capabilities(): ModelCapability
    {
        $model = $this->model;

        // Pixtral is the only currently vision-capable Mistral family.
        $vision = str_starts_with($model, 'pixtral');

        $maxCtx = match (true) {
            str_starts_with($model, 'codestral')      => 256000,
            str_starts_with($model, 'mistral-large')  => 128000,
            str_starts_with($model, 'mistral-medium') => 128000,
            str_starts_with($model, 'mistral-small')  => 32000,
            str_starts_with($model, 'open-mixtral')   => 64000,
            str_starts_with($model, 'open-mistral')   => 32000,
            str_starts_with($model, 'pixtral')        => 128000,
            str_starts_with($model, 'magistral')      => 128000,
            str_starts_with($model, 'ministral')      => 128000,
            default                                    => 32000,
        };

        return new ModelCapability(
            thinking: false,
            nativeTools: true,
            jsonMode: true,
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
}

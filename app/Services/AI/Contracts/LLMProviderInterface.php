<?php

namespace App\Services\AI\Contracts;

interface LLMProviderInterface
{
    /**
     * Stream a response from the LLM provider.
     *
     * @param array $messages Array of message history, e.g., [['role' => 'user', 'content' => 'hello']]
     * @param string $model The model identifier to use.
     * @param array $options Optional per-request generation options. Currently
     *                       understood by OpenAIProvider for local GGUF models:
     *                       'grammar' (GBNF string constraining the output) and
     *                       'max_tokens'. Other providers may ignore them.
     * @return \Generator Yields chunks of the response.
     */
    public function streamResponse(array $messages, string $model, array $options = []): \Generator;
}

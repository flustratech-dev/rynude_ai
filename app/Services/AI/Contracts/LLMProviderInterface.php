<?php

namespace App\Services\AI\Contracts;

interface LLMProviderInterface
{
    /**
     * Stream a response from the LLM provider.
     *
     * @param array $messages Array of message history, e.g., [['role' => 'user', 'content' => 'hello']]
     * @param string $model The model identifier to use.
     * @return \Generator Yields chunks of the response.
     */
    public function streamResponse(array $messages, string $model): \Generator;
}

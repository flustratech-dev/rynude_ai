<?php

namespace Tests\Support;

use App\Services\AI\AiService;

/**
 * Test double for AiService. Records the messages it was asked to stream so
 * tests can assert on the system prompt, and yields a canned response without
 * making any network calls.
 */
class FakeAiService extends AiService
{
    /** @var array<int, array{messages: array, model: string}> */
    public array $calls = [];

    public string $cannedResponse = 'This is a fake assistant response.';

    public function streamResponse(array $messages, string $model): \Generator
    {
        $this->calls[] = ['messages' => $messages, 'model' => $model];

        yield $this->cannedResponse;
    }

    /**
     * The combined system prompt content from the most recent call that actually
     * carried one. Auxiliary calls (e.g. the title-generation prompt) have no
     * system message, so we skip them and report the real generation prompt.
     */
    public function lastSystemPrompt(): string
    {
        foreach (array_reverse($this->calls) as $call) {
            $system = '';
            foreach ($call['messages'] as $m) {
                if (($m['role'] ?? '') === 'system') {
                    $system .= $m['content'] . "\n";
                }
            }
            if ($system !== '') {
                return $system;
            }
        }
        return '';
    }
}

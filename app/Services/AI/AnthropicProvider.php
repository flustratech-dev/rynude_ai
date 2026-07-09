<?php

namespace App\Services\AI;

use App\Services\AI\Concerns\ResolvesAttachments;
use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use Illuminate\Support\Facades\Http;
use App\Services\AI\CostTracker;
use App\Services\AI\RtkTracker;

class AnthropicProvider implements LLMProviderInterface, SupportsToolUse
{
    use ResolvesAttachments;

    /**
     * Determine if the model supports extended thinking.
     */
    protected function modelSupportsThinking(string $model): bool
    {
        if (str_contains($model, 'haiku')) {
            return false;
        }
        return str_contains($model, 'sonnet') || 
               str_contains($model, 'opus') || 
               str_contains($model, 'fable') || 
               str_contains($model, 'thinking') || 
               str_contains($model, 'claude-3-5') ||
               str_contains($model, 'claude-3-7');
    }

    public function streamResponse(array $messages, string $model): \Generator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user ? $user->anthropic_api_key : null;
        
        if (empty($apiKey)) {
            $apiKey = config('services.anthropic.key');
        }
        
        if (empty($apiKey)) {
            yield "Anthropic API key is not configured. Please add it in your Settings.";
            return;
        }

        $modelMap = [
            'claude-sonnet-4-6' => 'claude-3-5-sonnet-20241022',
            'claude-haiku-4-6' => 'claude-3-5-haiku-20241022',
            'claude-haiku-4-5' => 'claude-3-5-haiku-20241022',
            'claude-opus-4-8' => 'claude-3-opus-20240229',
            'fable-5' => 'claude-3-5-sonnet-20241022',
            'claude-sonnet-5' => 'claude-3-5-sonnet-20241022',
        ];
        $apiModel = $modelMap[$model] ?? $model;

        [$anthropicMessages, $systemPrompt] = $this->mapMessagesToAnthropic($messages);

        // Optimize message list prompt caching: find the last user message and cache it
        if (count($anthropicMessages) >= 3) {
            for ($i = count($anthropicMessages) - 1; $i >= 0; $i--) {
                if ($anthropicMessages[$i]['role'] === 'user') {
                    if (is_array($anthropicMessages[$i]['content'])) {
                        $lastBlockIdx = count($anthropicMessages[$i]['content']) - 1;
                        $anthropicMessages[$i]['content'][$lastBlockIdx]['cache_control'] = ['type' => 'ephemeral'];
                    } else {
                        $anthropicMessages[$i]['content'] = [
                            [
                                'type' => 'text',
                                'text' => $anthropicMessages[$i]['content'],
                                'cache_control' => ['type' => 'ephemeral']
                            ]
                        ];
                    }
                    break;
                }
            }
        }

        $supportsThinking = $this->modelSupportsThinking($apiModel);
        
        $payload = array_filter([
            'model' => $apiModel,
            'messages' => $anthropicMessages,
            'system' => $systemPrompt ? [
                [
                    'type' => 'text',
                    'text' => $systemPrompt,
                    'cache_control' => ['type' => 'ephemeral']
                ]
            ] : null,
            // 8192 (non-thinking) leaves room for long documents such as a full
            // skripsi/laporan without truncating mid-chapter; thinking models get
            // a larger ceiling that also has to cover the reasoning budget.
            'max_tokens' => $supportsThinking ? 16384 : 8192,
            'stream' => true,
        ]);

        if ($supportsThinking) {
            $payload['thinking'] = [
                'type' => 'enabled',
                'budget_tokens' => 4000,
            ];
        }

        $client = $this->getClient();
        $inputTokens = 0;
        $outputTokens = 0;
        $cacheReadTokens = 0;
        $cacheWriteTokens = 0;
        
        $maxRetries = 3;
        $retryDelay = 1;
        $response = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $client->post(config('services.anthropic.base_url', 'https://api.anthropic.com/v1/messages'), [
                    'headers' => [
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'anthropic-beta' => 'prompt-caching-2024-07-31,extended-thinking-2025-04-11',
                        'content-type' => 'application/json',
                    ],
                    'json' => $payload,
                    'stream' => true,
                    'http_errors' => false,
                    'timeout' => 300,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    break;
                }

                if (($statusCode === 429 || $statusCode >= 500) && $attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }

                if ($statusCode === 429) {
                    yield "\n[Error: Rate limit exceeded. Please try again later.]";
                    return;
                } else {
                    $errorBody = json_decode($response->getBody()->getContents(), true);
                    yield "\n[Error: API returned status code " . $statusCode . " - " . ($errorBody['error']['message'] ?? 'Unknown error') . "]";
                    return;
                }

            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                yield "\n[Error: Connection timeout. Please check your network and try again.]";
                return;
            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                yield "\n[Error communicating with Anthropic API: " . $e->getMessage() . "]";
                return;
            }
        }

        try {
            $body = $response->getBody();
            $buffer = '';
            $wasTruncated = false;
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    $line = trim($line);
                    if (empty($line)) continue;

                    if (str_starts_with($line, 'data: ')) {
                        $jsonStr = trim(substr($line, 6));
                        if (empty($jsonStr) || $jsonStr === '[DONE]') continue;

                        $data = json_decode($jsonStr, true);
                        if ($data && isset($data['type'])) {
                            if ($data['type'] === 'message_delta' && (($data['delta']['stop_reason'] ?? null) === 'max_tokens')) {
                                $wasTruncated = true;
                            }
                            if ($data['type'] === 'message_start' && isset($data['message']['usage']['input_tokens'])) {
                                $usage = $data['message']['usage'] ?? [];
                                $inputTokens += $usage['input_tokens'] ?? 0;
                                $cacheReadTokens += $usage['cache_read_input_tokens'] ?? 0;
                                $cacheWriteTokens += $usage['cache_creation_input_tokens'] ?? 0;
                            } elseif ($data['type'] === 'message_delta' && isset($data['usage']['output_tokens'])) {
                                $usage = $data['usage'] ?? [];
                                $outputTokens += $usage['output_tokens'] ?? 0;
                                $cacheReadTokens += $usage['cache_read_input_tokens'] ?? 0;
                                $cacheWriteTokens += $usage['cache_creation_input_tokens'] ?? 0;
                            } elseif ($data['type'] === 'content_block_delta' && isset($data['delta']['text'])) {
                                yield $data['delta']['text'];
                            } elseif ($data['type'] === 'content_block_delta' && isset($data['delta']['thinking'])) {
                                // Structured chunk so the UI can render thinking live
                                // without it leaking into the saved answer text.
                                yield ['type' => 'thinking', 'text' => $data['delta']['thinking']];
                            } elseif ($data['type'] === 'error') {
                                yield "\n[Error from API: " . ($data['error']['message'] ?? 'Unknown error') . "]";
                            }
                        }
                    }
                }
            }
            
            // Answer hit the max_tokens ceiling: signal it so the UI can offer
            // a "Continue" action.
            if ($wasTruncated) {
                yield ['type' => 'truncated'];
            }

            // Deduct tokens and record usage
            if ($user && ($inputTokens > 0 || $outputTokens > 0)) {
                [$rtkSaved, $rtkOriginal] = RtkTracker::flushAndGet();
                \App\Models\TokenUsage::record($user->id, $model, 'anthropic', $inputTokens, $outputTokens, $rtkSaved, $rtkOriginal);
                $user->decrement('token_balance', $inputTokens + $outputTokens);
                
                CostTracker::track($model, $inputTokens, $outputTokens, $cacheReadTokens, $cacheWriteTokens);
            }
            
        } catch (\Exception $e) {
            yield "\n[Error communicating with Anthropic API: " . $e->getMessage() . "]";
        }
    }

    /**
     * One agentic turn with native Anthropic tool use. See SupportsToolUse.
     */
    public function streamAgentTurn(array $messages, string $model, array $tools): \Generator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user?->anthropic_api_key ?: config('services.anthropic.key');

        if (empty($apiKey)) {
            yield ['type' => 'text', 'text' => 'Anthropic API key is not configured. Please add it in your Settings.'];
            return ['stop_reason' => 'error', 'error' => 'missing_key'];
        }

        $modelMap = [
            'claude-sonnet-4-6' => 'claude-3-5-sonnet-20241022',
            'claude-haiku-4-6' => 'claude-3-5-haiku-20241022',
            'claude-haiku-4-5' => 'claude-3-5-haiku-20241022',
            'claude-opus-4-8' => 'claude-3-opus-20240229',
            'fable-5' => 'claude-3-5-sonnet-20241022',
            'claude-sonnet-5' => 'claude-3-5-sonnet-20241022',
        ];
        $apiModel = $modelMap[$model] ?? $model;

        [$anthropicMessages, $systemPrompt] = $this->mapMessagesToAnthropic($messages);

        // Optimize message list prompt caching: find the last user message and cache it
        if (count($anthropicMessages) >= 3) {
            for ($i = count($anthropicMessages) - 1; $i >= 0; $i--) {
                if ($anthropicMessages[$i]['role'] === 'user') {
                    if (is_array($anthropicMessages[$i]['content'])) {
                        $lastBlockIdx = count($anthropicMessages[$i]['content']) - 1;
                        $anthropicMessages[$i]['content'][$lastBlockIdx]['cache_control'] = ['type' => 'ephemeral'];
                    } else {
                        $anthropicMessages[$i]['content'] = [
                            [
                                'type' => 'text',
                                'text' => $anthropicMessages[$i]['content'],
                                'cache_control' => ['type' => 'ephemeral']
                            ]
                        ];
                    }
                    break;
                }
            }
        }

        $supportsThinking = $this->modelSupportsThinking($apiModel);

        $payload = array_filter([
            'model' => $apiModel,
            'messages' => $anthropicMessages,
            'system' => $systemPrompt ? [[
                'type' => 'text',
                'text' => $systemPrompt,
                'cache_control' => ['type' => 'ephemeral'],
            ]] : null,
            'tools' => !empty($tools) ? array_map(function ($t, $index) use ($tools) {
                $toolData = [
                    'name' => $t['name'],
                    'description' => $t['description'] ?? '',
                    'input_schema' => $t['input_schema'] ?? ['type' => 'object', 'properties' => (object) []],
                ];
                if ($index === count($tools) - 1) {
                    $toolData['cache_control'] = ['type' => 'ephemeral'];
                }
                return $toolData;
            }, $tools, array_keys($tools)) : null,
            'max_tokens' => $supportsThinking ? 16384 : 8192,
            'stream' => true,
        ]);

        if ($supportsThinking) {
            $payload['thinking'] = [
                'type' => 'enabled',
                'budget_tokens' => 10000,
            ];
            $payload['max_tokens'] = 32768;
        }

        $client = $this->getClient();
        $inputTokens = 0;
        $outputTokens = 0;
        $cacheReadTokens = 0;
        $cacheWriteTokens = 0;
        $stopReason = 'end';
        $blocks = [];

        $maxRetries = 3;
        $retryDelay = 1;
        $response = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $client->post(config('services.anthropic.base_url', 'https://api.anthropic.com/v1/messages'), [
                    'headers' => [
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'anthropic-beta' => 'prompt-caching-2024-07-31,extended-thinking-2025-04-11',
                        'content-type' => 'application/json',
                    ],
                    'json' => $payload,
                    'stream' => true,
                    'http_errors' => false,
                    'timeout' => 300,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    break;
                }

                if (($statusCode === 429 || $statusCode >= 500) && $attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }

                if ($statusCode === 429) {
                    yield ['type' => 'text', 'text' => "\n[Error: Rate limit exceeded. Please try again later.]"];
                    return ['stop_reason' => 'error', 'error' => 'rate_limit'];
                } else {
                    $errorBody = json_decode($response->getBody()->getContents(), true);
                    $msg = $errorBody['error']['message'] ?? ('HTTP ' . $statusCode);
                    yield ['type' => 'text', 'text' => "\n[Error: {$msg}]"];
                    return ['stop_reason' => 'error', 'error' => $msg];
                }

            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                yield ['type' => 'text', 'text' => "\n[Error: Connection timeout. Please check your network and try again.]"];
                return ['stop_reason' => 'error', 'error' => $e->getMessage()];
            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                yield ['type' => 'text', 'text' => "\n[Error communicating with Anthropic API: " . $e->getMessage() . "]"];
                return ['stop_reason' => 'error', 'error' => $e->getMessage()];
            }
        }

        try {
            $body = $response->getBody();
            $buffer = '';
            while (!$body->eof()) {
                $buffer .= $body->read(1024);

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);
                    if ($line === '' || !str_starts_with($line, 'data: ')) {
                        continue;
                    }
                    $jsonStr = trim(substr($line, 6));
                    if ($jsonStr === '' || $jsonStr === '[DONE]') {
                        continue;
                    }
                    $data = json_decode($jsonStr, true);
                    if (!$data || !isset($data['type'])) {
                        continue;
                    }

                    switch ($data['type']) {
                        case 'message_start':
                            $usage = $data['message']['usage'] ?? [];
                            $inputTokens += $usage['input_tokens'] ?? 0;
                            $cacheReadTokens += $usage['cache_read_input_tokens'] ?? 0;
                            $cacheWriteTokens += $usage['cache_creation_input_tokens'] ?? 0;
                            break;

                        case 'content_block_start':
                            $idx = $data['index'] ?? 0;
                            $block = $data['content_block'] ?? [];
                            $blocks[$idx] = [
                                'type' => $block['type'] ?? 'text',
                                'id' => $block['id'] ?? '',
                                'name' => $block['name'] ?? '',
                                'json' => '',
                                'thinking' => '',
                                'signature' => '',
                            ];
                            break;

                        case 'content_block_delta':
                            $idx = $data['index'] ?? 0;
                            $delta = $data['delta'] ?? [];
                            if (($delta['type'] ?? '') === 'text_delta' && isset($delta['text'])) {
                                yield ['type' => 'text', 'text' => $delta['text']];
                            } elseif (($delta['type'] ?? '') === 'thinking_delta' && isset($delta['thinking'])) {
                                $blocks[$idx]['thinking'] = ($blocks[$idx]['thinking'] ?? '') . $delta['thinking'];
                                yield ['type' => 'thinking', 'text' => $delta['thinking']];
                            } elseif (($delta['type'] ?? '') === 'signature_delta' && isset($delta['signature'])) {
                                $blocks[$idx]['signature'] = ($blocks[$idx]['signature'] ?? '') . $delta['signature'];
                            } elseif (($delta['type'] ?? '') === 'input_json_delta' && isset($delta['partial_json'])) {
                                $blocks[$idx]['json'] = ($blocks[$idx]['json'] ?? '') . $delta['partial_json'];
                            }
                            break;

                        case 'content_block_stop':
                            $idx = $data['index'] ?? 0;
                            $b = $blocks[$idx] ?? null;
                            if ($b && $b['type'] === 'tool_use') {
                                $input = json_decode($b['json'] !== '' ? $b['json'] : '{}', true);
                                yield [
                                    'type' => 'tool_use',
                                    'id' => $b['id'],
                                    'name' => $b['name'],
                                    'input' => is_array($input) ? $input : [],
                                ];
                            }
                            break;

                        case 'message_delta':
                            $usage = $data['usage'] ?? [];
                            $outputTokens += $usage['output_tokens'] ?? 0;
                            $cacheReadTokens += $usage['cache_read_input_tokens'] ?? 0;
                            $cacheWriteTokens += $usage['cache_creation_input_tokens'] ?? 0;
                            if (isset($data['delta']['stop_reason'])) {
                                $stopReason = $data['delta']['stop_reason'];
                            }
                            break;

                        case 'error':
                            $msg = $data['error']['message'] ?? 'Unknown error';
                            yield ['type' => 'text', 'text' => "\n[Error from API: {$msg}]"];
                            break;
                    }
                }
            }

            if ($user && ($inputTokens > 0 || $outputTokens > 0)) {
                [$rtkSaved, $rtkOriginal] = RtkTracker::flushAndGet();
                \App\Models\TokenUsage::record($user->id, $model, 'anthropic', $inputTokens, $outputTokens, $rtkSaved, $rtkOriginal);
                $user->decrement('token_balance', $inputTokens + $outputTokens);
                
                CostTracker::track($model, $inputTokens, $outputTokens, $cacheReadTokens, $cacheWriteTokens);
            }
        } catch (\Exception $e) {
            yield ['type' => 'text', 'text' => "\n[Error communicating with Anthropic API: " . $e->getMessage() . "]"];
            return ['stop_reason' => 'error', 'error' => $e->getMessage()];
        }

        // Find thinking text and signature from content blocks
        $thinkingText = '';
        $signatureText = '';
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'thinking') {
                $thinkingText = $b['thinking'] ?? '';
                $signatureText = $b['signature'] ?? '';
                break;
            }
        }

        return [
            'stop_reason' => $stopReason === 'tool_use' ? 'tool_use' : 'end',
            'thinking' => $thinkingText,
            'signature' => $signatureText,
        ];
    }

    /**
     * Map the unified message history to Anthropic [messages, systemPrompt].
     * Tool results are merged into the preceding user message so roles alternate.
     */
    private function mapMessagesToAnthropic(array $messages): array
    {
        $out = [];
        $systemPrompt = '';
        $ragQuery = $this->ragQueryFrom($messages);

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';

            if ($role === 'system') {
                $systemPrompt .= $msg['content'] . "\n";
                continue;
            }

            // Structured Content Array passthrough
            if (is_array($msg['content'])) {
                $out[] = [
                    'role' => $role,
                    'content' => $msg['content']
                ];
                continue;
            }

            if ($role === 'tool') {
                $toolResult = [
                    'type' => 'tool_result',
                    'tool_use_id' => $msg['tool_call_id'] ?? '',
                    'content' => (string) ($msg['content'] ?? ''),
                ];
                $last = count($out) - 1;
                if ($last >= 0 && $out[$last]['role'] === 'user' && is_array($out[$last]['content'])) {
                    $out[$last]['content'][] = $toolResult;
                } else {
                    $out[] = ['role' => 'user', 'content' => [$toolResult]];
                }
                continue;
            }

            if ($role === 'assistant' && !empty($msg['tool_calls'])) {
                $content = [];
                if (!empty($msg['content'])) {
                    $content[] = ['type' => 'text', 'text' => (string) $msg['content']];
                }
                foreach ($msg['tool_calls'] as $call) {
                    $content[] = [
                        'type' => 'tool_use',
                        'id' => $call['id'],
                        'name' => $call['name'],
                        'input' => (object) ($call['input'] ?? []),
                    ];
                }
                $out[] = ['role' => 'assistant', 'content' => $content];
                continue;
            }

            // Plain user / assistant — text + attachments
            $content = [];
            if (!empty($msg['content'])) {
                $content[] = ['type' => 'text', 'text' => (string) $msg['content']];
            }
            foreach ($this->resolveAttachmentParts($msg['attachments'] ?? [], $ragQuery) as $part) {
                $content[] = $part['kind'] === 'image'
                    ? ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $part['mime'], 'data' => $part['base64']]]
                    : ['type' => 'text', 'text' => $part['text']];
            }

            $out[] = [
                'role' => $role,
                'content' => (count($content) === 1 && $content[0]['type'] === 'text')
                    ? $content[0]['text']
                    : (count($content) > 0 ? $content : ''),
            ];
        }

        return [$out, $systemPrompt];
    }

    /**
     * Get a GuzzleHttp\Client instance.
     */
    protected function getClient(array $config = []): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client($config);
    }
}

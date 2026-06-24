<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements LLMProviderInterface, SupportsToolUse
{
    public function streamResponse(array $messages, string $model): \Generator
    {
        // Get the API key from the currently authenticated user, or fallback to config
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user ? $user->anthropic_api_key : null;
        
        if (empty($apiKey)) {
            $apiKey = config('services.anthropic.key');
        }
        
        if (empty($apiKey)) {
            yield "Anthropic API key is not configured. Please add it in your Settings.";
            return;
        }

        // Map standard messages array to Anthropic format
        $anthropicMessages = [];
        $systemPrompt = "";
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt .= $msg['content'] . "\n";
            } else {
                $content = [];
                
                // Handle text content
                if (!empty($msg['content'])) {
                    $content[] = [
                        'type' => 'text',
                        'text' => $msg['content']
                    ];
                }
                
                // Handle attachments
                if (!empty($msg['attachments'])) {
                    foreach ($msg['attachments'] as $att) {
                        $filePath = storage_path('app/public/' . $att['file_path']);
                        if (file_exists($filePath)) {
                            $mimeType = $att['file_type'];
                            
                            if (str_starts_with($mimeType, 'image/')) {
                                $processedImage = \App\Helpers\ImageHelper::resizeAndEncode($filePath, $mimeType, 4000);
                                $content[] = [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $processedImage['mime_type'],
                                        'data' => $processedImage['data']
                                    ]
                                ];
                            } elseif ($mimeType === 'application/pdf' || $mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || str_ends_with($att['file_name'], '.docx')) {
                                $text = \App\Helpers\DocumentParser::parseText($att['file_path'], $mimeType, $att['file_name']);
                                $content[] = [
                                    'type' => 'text',
                                    'text' => "\n\n[Isi Dokumen lampiran: {$att['file_name']}]\n" . trim($text) . "\n[Akhir Isi Dokumen]"
                                ];
                            }
                        }
                    }
                }
                
                $anthropicMessages[] = [
                    'role' => $msg['role'],
                    'content' => count($content) === 1 && $content[0]['type'] === 'text' ? $content[0]['text'] : (count($content) > 0 ? $content : '')
                ];
            }
        }

        $client = new \GuzzleHttp\Client();
        $inputTokens = 0;
        $outputTokens = 0;
        
        try {
            $response = $client->post(config('services.anthropic.base_url', 'https://api.anthropic.com/v1/messages'), [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'anthropic-beta' => 'prompt-caching-2024-07-31',
                    'content-type' => 'application/json',
                ],
                'json' => array_filter([
                    'model' => $model,
                    'messages' => $anthropicMessages,
                    'system' => $systemPrompt ? [
                        [
                            'type' => 'text',
                            'text' => $systemPrompt,
                            'cache_control' => ['type' => 'ephemeral']
                        ]
                    ] : null,
                    'max_tokens' => 4096,
                    'stream' => true,
                ]),
                'stream' => true,
                'http_errors' => false,
                'timeout' => 300,
            ]);

            if ($response->getStatusCode() === 429) {
                yield "\n[Error: Rate limit exceeded. Please try again later.]";
                return;
            } elseif ($response->getStatusCode() !== 200) {
                $errorBody = json_decode($response->getBody()->getContents(), true);
                yield "\n[Error: API returned status code " . $response->getStatusCode() . " - " . ($errorBody['error']['message'] ?? 'Unknown error') . "]";
                return;
            }

            $body = $response->getBody();
            $buffer = '';
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
                            if ($data['type'] === 'message_start' && isset($data['message']['usage']['input_tokens'])) {
                                $inputTokens += $data['message']['usage']['input_tokens'];
                            } elseif ($data['type'] === 'message_delta' && isset($data['usage']['output_tokens'])) {
                                $outputTokens += $data['usage']['output_tokens'];
                            } elseif ($data['type'] === 'content_block_delta' && isset($data['delta']['text'])) {
                                yield $data['delta']['text'];
                            } elseif ($data['type'] === 'error') {
                                yield "\n[Error from API: " . ($data['error']['message'] ?? 'Unknown error') . "]";
                            }
                        }
                    }
                }
            }
            
            // Deduct tokens and record usage
            if ($user && ($inputTokens > 0 || $outputTokens > 0)) {
                \App\Models\TokenUsage::record($user->id, $model, 'anthropic', $inputTokens, $outputTokens);
                $user->decrement('token_balance', $inputTokens + $outputTokens);
            }
            
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            yield "\n[Error: Connection timeout. Please check your network and try again.]";
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

        [$anthropicMessages, $systemPrompt] = $this->mapMessagesToAnthropic($messages);

        $payload = array_filter([
            'model' => $model,
            'messages' => $anthropicMessages,
            'system' => $systemPrompt ? [[
                'type' => 'text',
                'text' => $systemPrompt,
                'cache_control' => ['type' => 'ephemeral'],
            ]] : null,
            'tools' => !empty($tools) ? array_map(fn ($t) => [
                'name' => $t['name'],
                'description' => $t['description'] ?? '',
                'input_schema' => $t['input_schema'] ?? ['type' => 'object', 'properties' => (object) []],
            ], $tools) : null,
            'max_tokens' => 4096,
            'stream' => true,
        ]);

        $client = new \GuzzleHttp\Client();
        $inputTokens = 0;
        $outputTokens = 0;
        $stopReason = 'end';
        $blocks = []; // index => ['type', 'id', 'name', 'json']

        try {
            $response = $client->post(config('services.anthropic.base_url', 'https://api.anthropic.com/v1/messages'), [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'anthropic-beta' => 'prompt-caching-2024-07-31',
                    'content-type' => 'application/json',
                ],
                'json' => $payload,
                'stream' => true,
                'http_errors' => false,
                'timeout' => 300,
            ]);

            if ($response->getStatusCode() !== 200) {
                $errorBody = json_decode($response->getBody()->getContents(), true);
                $msg = $errorBody['error']['message'] ?? ('HTTP ' . $response->getStatusCode());
                yield ['type' => 'text', 'text' => "\n[Error: {$msg}]"];
                return ['stop_reason' => 'error', 'error' => $msg];
            }

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
                            $inputTokens += $data['message']['usage']['input_tokens'] ?? 0;
                            break;

                        case 'content_block_start':
                            $idx = $data['index'] ?? 0;
                            $block = $data['content_block'] ?? [];
                            $blocks[$idx] = [
                                'type' => $block['type'] ?? 'text',
                                'id' => $block['id'] ?? '',
                                'name' => $block['name'] ?? '',
                                'json' => '',
                            ];
                            break;

                        case 'content_block_delta':
                            $idx = $data['index'] ?? 0;
                            $delta = $data['delta'] ?? [];
                            if (($delta['type'] ?? '') === 'text_delta' && isset($delta['text'])) {
                                yield ['type' => 'text', 'text' => $delta['text']];
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
                            $outputTokens += $data['usage']['output_tokens'] ?? 0;
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
                \App\Models\TokenUsage::record($user->id, $model, 'anthropic', $inputTokens, $outputTokens);
                $user->decrement('token_balance', $inputTokens + $outputTokens);
            }
        } catch (\Exception $e) {
            yield ['type' => 'text', 'text' => "\n[Error communicating with Anthropic API: " . $e->getMessage() . "]"];
            return ['stop_reason' => 'error', 'error' => $e->getMessage()];
        }

        return ['stop_reason' => $stopReason === 'tool_use' ? 'tool_use' : 'end'];
    }

    /**
     * Map the unified message history to Anthropic [messages, systemPrompt].
     * Tool results are merged into the preceding user message so roles alternate.
     */
    private function mapMessagesToAnthropic(array $messages): array
    {
        $out = [];
        $systemPrompt = '';

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';

            if ($role === 'system') {
                $systemPrompt .= $msg['content'] . "\n";
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

            // Plain user / assistant — text + attachments (images, parsed docs).
            $content = [];
            if (!empty($msg['content'])) {
                $content[] = ['type' => 'text', 'text' => (string) $msg['content']];
            }
            foreach ($msg['attachments'] ?? [] as $att) {
                $filePath = storage_path('app/public/' . $att['file_path']);
                if (!file_exists($filePath)) {
                    continue;
                }
                $mime = $att['file_type'] ?? '';
                if (str_starts_with($mime, 'image/')) {
                    $img = \App\Helpers\ImageHelper::resizeAndEncode($filePath, $mime, 4000);
                    $content[] = [
                        'type' => 'image',
                        'source' => ['type' => 'base64', 'media_type' => $img['mime_type'], 'data' => $img['data']],
                    ];
                } elseif ($mime === 'application/pdf' || str_ends_with($att['file_name'] ?? '', '.docx')) {
                    $text = \App\Helpers\DocumentParser::parseText($att['file_path'], $mime, $att['file_name'] ?? '');
                    $content[] = ['type' => 'text', 'text' => "\n\n[Attachment: {$att['file_name']}]\n" . trim($text)];
                }
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
}

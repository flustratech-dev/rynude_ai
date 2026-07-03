<?php

namespace App\Services\AI;

use App\Services\AI\Concerns\ResolvesAttachments;
use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;

class GoogleProvider implements LLMProviderInterface, SupportsToolUse
{
    use ResolvesAttachments;

    /**
     * One agentic turn with Gemini function calling. See SupportsToolUse.
     */
    public function streamAgentTurn(array $messages, string $model, array $tools): \Generator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user?->google_api_key ?: config('services.google.key');

        if (empty($apiKey)) {
            yield ['type' => 'text', 'text' => 'Google AI API key is not configured. Please add it in your Settings.'];
            return ['stop_reason' => 'error', 'error' => 'missing_key'];
        }

        [$contents, $systemPrompt] = $this->mapMessagesToGemini($messages);

        $payload = ['contents' => $contents];
        if (!empty(trim($systemPrompt))) {
            $payload['systemInstruction'] = ['parts' => [['text' => trim($systemPrompt)]]];
        }
        if (!empty($tools)) {
            $payload['tools'] = [[
                'functionDeclarations' => array_map(fn ($t) => array_filter([
                    'name' => $t['name'],
                    'description' => $t['description'] ?? '',
                    'parameters' => !empty($t['input_schema']['properties']) ? $t['input_schema'] : null,
                ]), $tools),
            ]];
        }

        $baseUrl = rtrim(config('services.google.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $url = $baseUrl . '/models/' . $model . ':streamGenerateContent?alt=sse&key=' . $apiKey;

        $client = new \GuzzleHttp\Client();
        $inputTokens = 0;
        $outputTokens = 0;
        $sawFunctionCall = false;

        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
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
            $callIndex = 0;
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
                    if (!$data) {
                        continue;
                    }

                    foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
                        if (isset($part['text'])) {
                            yield ['type' => 'text', 'text' => $part['text']];
                        } elseif (isset($part['functionCall'])) {
                            $sawFunctionCall = true;
                            yield [
                                'type' => 'tool_use',
                                'id' => 'gcall_' . $callIndex++,
                                'name' => $part['functionCall']['name'] ?? '',
                                'input' => $part['functionCall']['args'] ?? [],
                            ];
                        }
                    }
                    if (isset($data['usageMetadata'])) {
                        $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? $inputTokens;
                        $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? $outputTokens;
                    }
                    if (isset($data['error'])) {
                        yield ['type' => 'text', 'text' => "\n[Error from API: " . ($data['error']['message'] ?? 'Unknown error') . "]"];
                    }
                }
            }

            if ($user && ($inputTokens > 0 || $outputTokens > 0)) {
                \App\Models\TokenUsage::record($user->id, $model, 'google', $inputTokens, $outputTokens);
                $user->decrement('token_balance', $inputTokens + $outputTokens);
            }
        } catch (\Exception $e) {
            yield ['type' => 'text', 'text' => "\n[Error communicating with Google AI API: " . $e->getMessage() . "]"];
            return ['stop_reason' => 'error', 'error' => $e->getMessage()];
        }

        return ['stop_reason' => $sawFunctionCall ? 'tool_use' : 'end'];
    }

    /**
     * Map the unified message history to Gemini [contents, systemPrompt].
     * Function responses are merged into one user content so turns stay valid.
     */
    private function mapMessagesToGemini(array $messages): array
    {
        $contents = [];
        $systemPrompt = '';

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';

            if ($role === 'system') {
                $systemPrompt .= $msg['content'] . "\n";
                continue;
            }

            if ($role === 'tool') {
                $part = [
                    'functionResponse' => [
                        'name' => $msg['name'] ?? '',
                        'response' => ['result' => (string) ($msg['content'] ?? '')],
                    ],
                ];
                $last = count($contents) - 1;
                if ($last >= 0 && $contents[$last]['role'] === 'user') {
                    $contents[$last]['parts'][] = $part;
                } else {
                    $contents[] = ['role' => 'user', 'parts' => [$part]];
                }
                continue;
            }

            if ($role === 'assistant' && !empty($msg['tool_calls'])) {
                $parts = [];
                if (!empty($msg['content'])) {
                    $parts[] = ['text' => (string) $msg['content']];
                }
                foreach ($msg['tool_calls'] as $call) {
                    $parts[] = ['functionCall' => ['name' => $call['name'], 'args' => (object) ($call['input'] ?? [])]];
                }
                $contents[] = ['role' => 'model', 'parts' => $parts];
                continue;
            }

            $parts = [];
            if (!empty($msg['content'])) {
                $parts[] = ['text' => (string) $msg['content']];
            }
            foreach ($this->resolveAttachmentParts($msg['attachments'] ?? []) as $part) {
                $parts[] = $part['kind'] === 'image'
                    ? ['inline_data' => ['mime_type' => $part['mime'], 'data' => $part['base64']]]
                    : ['text' => $part['text']];
            }
            if (empty($parts)) {
                continue;
            }
            $contents[] = ['role' => $role === 'assistant' ? 'model' : 'user', 'parts' => $parts];
        }

        return [$contents, $systemPrompt];
    }

    public function streamResponse(array $messages, string $model): \Generator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user ? $user->google_api_key : null;

        if (empty($apiKey)) {
            $apiKey = config('services.google.key');
        }

        if (empty($apiKey)) {
            yield "Google AI API key is not configured. Please add it in your Settings.";
            return;
        }

        // Map standard messages to Gemini's contents/systemInstruction format.
        $contents = [];
        $systemPrompt = '';

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt .= $msg['content'] . "\n";
                continue;
            }

            $parts = [];

            if (!empty($msg['content'])) {
                $parts[] = ['text' => $msg['content']];
            }

            // Handle attachments (images inline, documents as extracted text).
            foreach ($this->resolveAttachmentParts($msg['attachments'] ?? []) as $part) {
                $parts[] = $part['kind'] === 'image'
                    ? ['inline_data' => ['mime_type' => $part['mime'], 'data' => $part['base64']]]
                    : ['text' => $part['text']];
            }

            if (empty($parts)) {
                continue;
            }

            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => $parts,
            ];
        }

        $baseUrl = rtrim(config('services.google.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $url = $baseUrl . '/models/' . $model . ':streamGenerateContent?alt=sse&key=' . $apiKey;

        $payload = ['contents' => $contents];
        if (!empty(trim($systemPrompt))) {
            $payload['systemInstruction'] = ['parts' => [['text' => trim($systemPrompt)]]];
        }

        $client = new \GuzzleHttp\Client();
        $inputTokens = 0;
        $outputTokens = 0;

        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $payload,
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
            $wasTruncated = false;
            while (!$body->eof()) {
                $buffer .= $body->read(1024);

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if (empty($line) || !str_starts_with($line, 'data: ')) continue;

                    $jsonStr = trim(substr($line, 6));
                    if (empty($jsonStr) || $jsonStr === '[DONE]') continue;

                    $data = json_decode($jsonStr, true);
                    if (!$data) continue;

                    if (isset($data['candidates'][0]['content']['parts'])) {
                        foreach ($data['candidates'][0]['content']['parts'] as $part) {
                            if (isset($part['text'])) {
                                yield $part['text'];
                            }
                        }
                    }
                    if (($data['candidates'][0]['finishReason'] ?? null) === 'MAX_TOKENS') {
                        $wasTruncated = true;
                    }
                    if (isset($data['usageMetadata'])) {
                        $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? $inputTokens;
                        $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? $outputTokens;
                    }
                    if (isset($data['error'])) {
                        yield "\n[Error from API: " . ($data['error']['message'] ?? 'Unknown error') . "]";
                    }
                }
            }

            if ($wasTruncated) {
                yield ['type' => 'truncated'];
            }

            if ($user) {
                \App\Models\TokenUsage::record($user->id, $model, 'google', $inputTokens, $outputTokens);
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $user->decrement('token_balance', $inputTokens + $outputTokens);
                }
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            yield "\n[Error: Connection timeout. Please check your network and try again.]";
        } catch (\Exception $e) {
            yield "\n[Error communicating with Google AI API: " . $e->getMessage() . "]";
        }
    }
}

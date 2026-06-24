<?php

namespace App\Services\AI\Concerns;

/**
 * Shared OpenAI-compatible tool-use plumbing for OpenAIProvider and MistralProvider.
 *
 * Handles: mapping the unified message/tool format to the OpenAI chat shape, and
 * parsing a streaming /chat/completions response into unified agent events.
 */
trait OpenAiCompatToolStream
{
    protected function mapToolsToOpenAi(array $tools): array
    {
        return array_map(fn ($t) => [
            'type' => 'function',
            'function' => [
                'name' => $t['name'],
                'description' => $t['description'] ?? '',
                'parameters' => $t['input_schema'] ?? ['type' => 'object', 'properties' => (object) []],
            ],
        ], $tools);
    }

    /**
     * Translate the unified message history into OpenAI chat messages, including
     * assistant tool_calls, tool results, and (for the user turns) attachments.
     */
    protected function mapMessagesToOpenAi(array $messages): array
    {
        $out = [];

        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';

            if ($role === 'tool') {
                $out[] = [
                    'role' => 'tool',
                    'tool_call_id' => $m['tool_call_id'] ?? '',
                    'content' => (string) ($m['content'] ?? ''),
                ];
                continue;
            }

            if ($role === 'assistant' && !empty($m['tool_calls'])) {
                $calls = [];
                foreach ($m['tool_calls'] as $call) {
                    $calls[] = [
                        'id' => $call['id'],
                        'type' => 'function',
                        'function' => [
                            'name' => $call['name'],
                            'arguments' => json_encode((object) ($call['input'] ?? [])),
                        ],
                    ];
                }
                $out[] = [
                    'role' => 'assistant',
                    'content' => $m['content'] ?? '',
                    'tool_calls' => $calls,
                ];
                continue;
            }

            if ($role === 'system') {
                $out[] = ['role' => 'system', 'content' => (string) ($m['content'] ?? '')];
                continue;
            }

            // user / plain assistant — fold in attachments (images + parsed docs).
            $content = [];
            if (!empty($m['content'])) {
                $content[] = ['type' => 'text', 'text' => (string) $m['content']];
            }
            foreach ($m['attachments'] ?? [] as $att) {
                $filePath = storage_path('app/public/' . $att['file_path']);
                if (!file_exists($filePath)) {
                    continue;
                }
                $mime = $att['file_type'] ?? '';
                if (str_starts_with($mime, 'image/')) {
                    $img = \App\Helpers\ImageHelper::resizeAndEncode($filePath, $mime, 4000);
                    $content[] = [
                        'type' => 'image_url',
                        'image_url' => ['url' => 'data:' . $img['mime_type'] . ';base64,' . $img['data']],
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

        return $out;
    }

    /**
     * POST to an OpenAI-compatible chat-completions endpoint and stream the result
     * as unified agent events. Returns ['stop_reason' => 'tool_use'|'end'|'error'].
     */
    protected function streamOpenAiCompat(
        \GuzzleHttp\Client $client,
        string $url,
        array $headers,
        string $model,
        array $messages,
        array $tools
    ): \Generator {
        $payload = [
            'model' => $model,
            'messages' => $this->mapMessagesToOpenAi($messages),
            'stream' => true,
            'max_tokens' => 4096,
        ];
        if (!empty($tools)) {
            $payload['tools'] = $this->mapToolsToOpenAi($tools);
        }

        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $payload,
            'stream' => true,
            'http_errors' => false,
            'verify' => false,
            'timeout' => 300,
        ]);

        if ($response->getStatusCode() !== 200) {
            $err = json_decode($response->getBody()->getContents(), true);
            $msg = is_array($err)
                ? ($err['error']['message'] ?? ($err['message'] ?? ('HTTP ' . $response->getStatusCode())))
                : ('HTTP ' . $response->getStatusCode());
            yield ['type' => 'text', 'text' => "\n[Error: {$msg}]"];
            return ['stop_reason' => 'error', 'error' => (string) $msg];
        }

        $body = $response->getBody();
        $buffer = '';
        $toolAcc = []; // index => ['id', 'name', 'args']

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
                $choice = $data['choices'][0] ?? null;
                if (!$choice) {
                    continue;
                }

                $delta = $choice['delta'] ?? [];
                if (isset($delta['content']) && $delta['content'] !== null && $delta['content'] !== '') {
                    yield ['type' => 'text', 'text' => $delta['content']];
                }
                foreach ($delta['tool_calls'] ?? [] as $call) {
                    $idx = $call['index'] ?? 0;
                    $toolAcc[$idx] ??= ['id' => '', 'name' => '', 'args' => ''];
                    if (!empty($call['id'])) {
                        $toolAcc[$idx]['id'] = $call['id'];
                    }
                    if (isset($call['function']['name'])) {
                        $toolAcc[$idx]['name'] .= $call['function']['name'];
                    }
                    if (isset($call['function']['arguments'])) {
                        $toolAcc[$idx]['args'] .= $call['function']['arguments'];
                    }
                }
            }
        }

        if (!empty($toolAcc)) {
            ksort($toolAcc);
            foreach ($toolAcc as $i => $t) {
                $input = json_decode($t['args'] !== '' ? $t['args'] : '{}', true);
                yield [
                    'type' => 'tool_use',
                    'id' => $t['id'] !== '' ? $t['id'] : ('call_' . $i),
                    'name' => $t['name'],
                    'input' => is_array($input) ? $input : [],
                ];
            }
            return ['stop_reason' => 'tool_use'];
        }

        return ['stop_reason' => 'end'];
    }
}

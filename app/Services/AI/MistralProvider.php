<?php

namespace App\Services\AI;

use App\Services\AI\Concerns\OpenAiCompatToolStream;
use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use App\Services\AI\RtkTracker;

class MistralProvider implements LLMProviderInterface, SupportsToolUse
{
    use OpenAiCompatToolStream;

    /**
     * One agentic turn over Mistral's OpenAI-compatible endpoint. See SupportsToolUse.
     */
    public function streamAgentTurn(array $messages, string $model, array $tools): \Generator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user?->mistral_api_key ?: config('services.mistral.key');

        if (empty($apiKey)) {
            yield ['type' => 'text', 'text' => 'Mistral API key is not configured. Please add it in your Settings.'];
            return ['stop_reason' => 'error', 'error' => 'missing_key'];
        }

        $baseUrl = rtrim(config('services.mistral.base_url', 'https://api.mistral.ai/v1'), '/');

        return yield from $this->streamOpenAiCompat(
            new \GuzzleHttp\Client(),
            $baseUrl . '/chat/completions',
            [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            $model,
            $messages,
            $tools
        );
    }

    public function streamResponse(array $messages, string $model): \Generator
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $apiKey = $user ? $user->mistral_api_key : null;

        if (empty($apiKey)) {
            $apiKey = config('services.mistral.key');
        }

        if (empty($apiKey)) {
            yield "Mistral API key is not configured. Please add it in your Settings.";
            return;
        }

        $baseUrl = rtrim(config('services.mistral.base_url', 'https://api.mistral.ai/v1'), '/');

        // Mistral uses the OpenAI-compatible chat format. Only vision-capable
        // models accept image parts; the rest reject them with a 400, so images
        // degrade to a text notice there.
        $supportsVision = (bool) preg_match('/pixtral|vision/i', $model);

        $mistralMessages = [];
        $ragQuery = $this->ragQueryFrom($messages);
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $mistralMessages[] = ['role' => 'system', 'content' => (string) ($msg['content'] ?? '')];
                continue;
            }

            $content = [];
            if (!empty($msg['content'])) {
                $content[] = ['type' => 'text', 'text' => (string) $msg['content']];
            }
            foreach ($this->resolveAttachmentParts($msg['attachments'] ?? [], $ragQuery) as $part) {
                if ($part['kind'] === 'image') {
                    $content[] = $supportsVision
                        ? ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $part['mime'] . ';base64,' . $part['base64']]]
                        : ['type' => 'text', 'text' => "\n\n[Gambar terlampir, tetapi model ini tidak mendukung input gambar]"];
                } else {
                    $content[] = ['type' => 'text', 'text' => $part['text']];
                }
            }

            $mistralMessages[] = [
                'role' => $msg['role'],
                'content' => (count($content) === 1 && $content[0]['type'] === 'text')
                    ? $content[0]['text']
                    : (count($content) > 0 ? $content : ''),
            ];
        }

        $client = new \GuzzleHttp\Client();
        $inputTokens = 0;
        $outputTokens = 0;

        try {
            // Retry transient failures with backoff, same pattern as the other
            // providers.
            $response = null;
            $retryDelay = 1;
            for ($attempt = 0; $attempt <= 2; $attempt++) {
                $response = $client->post($baseUrl . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => $mistralMessages,
                        'stream' => true,
                    ],
                    'stream' => true,
                    'http_errors' => false,
                    'timeout' => 300,
                ]);

                $status = $response->getStatusCode();
                if (($status === 429 || $status >= 500) && $attempt < 2) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
                break;
            }

            if ($response->getStatusCode() === 429) {
                yield "\n[Error: Rate limit exceeded. Please try again later.]";
                return;
            } elseif ($response->getStatusCode() !== 200) {
                $errorBody = json_decode($response->getBody()->getContents(), true);
                $errorMsg = $errorBody['error']['message'] ?? ($errorBody['message'] ?? 'Unknown error');
                yield "\n[Error: API returned status code " . $response->getStatusCode() . " - " . $errorMsg . "]";
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

                    if (isset($data['choices'][0]['delta']['content'])) {
                        yield $data['choices'][0]['delta']['content'];
                    }
                    if (isset($data['usage'])) {
                        $inputTokens = $data['usage']['prompt_tokens'] ?? $inputTokens;
                        $outputTokens = $data['usage']['completion_tokens'] ?? $outputTokens;
                    }
                    if (($data['choices'][0]['finish_reason'] ?? null) === 'length') {
                        $wasTruncated = true;
                    }
                }
            }

            if ($wasTruncated) {
                yield ['type' => 'truncated'];
            }

            if ($user) {
                [$rtkSaved, $rtkOriginal] = RtkTracker::flushAndGet();
                \App\Models\TokenUsage::record($user->id, $model, 'mistral', $inputTokens, $outputTokens, $rtkSaved, $rtkOriginal);
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $user->decrement('token_balance', $inputTokens + $outputTokens);
                }
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            yield "\n[Error: Connection timeout. Please check your network and try again.]";
        } catch (\Exception $e) {
            yield "\n[Error communicating with Mistral API: " . $e->getMessage() . "]";
        }
    }
}

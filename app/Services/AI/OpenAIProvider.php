<?php

namespace App\Services\AI;

use App\Services\AI\Concerns\OpenAiCompatToolStream;
use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use Illuminate\Support\Facades\Http;
use App\Services\AI\RtkTracker;

class OpenAIProvider implements LLMProviderInterface, SupportsToolUse
{
    use OpenAiCompatToolStream;

    /**
     * Resolve [apiKey, baseUrl, label] for the current user/model. Shared by the
     * plain chat path and the agentic tool path.
     */
    private function resolveConfig(string $model): array
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $isProxy = $user && $user->use_proxy;

        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        // STRICT engine separation:
        //  - 'ollama'          => Ollama daemon (11434)
        //  - 'local' / 'gguf'  => dedicated local GGUF engine (LlamaServerService), NEVER Ollama
        //  - 'nine_router'     => 9router gateway (20128)
        // GGUF is detected by CODE (LlamaServerService catalog), not just the DB
        // provider column, so a mistagged row can't leak a local model to cloud.
        $isGguf = app(\App\Services\LlamaServerService::class)->isGgufModel($model)
            || ($aiModel && in_array($aiModel->provider, ['local', 'gguf']));
        $isOllama = !$isGguf && $aiModel && $aiModel->provider === 'ollama';
        $isLocalEngine = $isGguf || str_starts_with($model, 'kr/claude') || str_starts_with($model, 'mmf/mimo') || ($aiModel && in_array($aiModel->provider, ['nine_router', 'ollama']));

        $isHuggingFace = $aiModel && $aiModel->provider === 'huggingface';
        $isGlm = ($aiModel && $aiModel->provider === 'glm') || (!$isLocalEngine && str_starts_with($model, 'glm'));
        $isKimi = ($aiModel && $aiModel->provider === 'kimi') || (!$isLocalEngine && (str_starts_with($model, 'kimi') || str_starts_with($model, 'moonshot')));
        $isQwen = ($aiModel && $aiModel->provider === 'qwen') || (!$isLocalEngine && (str_starts_with($model, 'qwen') || str_starts_with($model, 'qwq')));

        if ($isLocalEngine) {
            if ($isGguf) {
                // Model Hub GGUF: served STRICTLY by the dedicated local GGUF
                // engine (LlamaServerService) on its own port. This path must
                // never touch Ollama — no ollama serve, no ollama create, no
                // 11434. The engine is launched with an explicit large context
                // window so large prompts don't overflow the old 4096 default.
                $apiKey = 'sk-dummy-key-for-local-gguf';
                $llama = app(\App\Services\LlamaServerService::class);
                $baseUrl = $llama->ensureRunning($model) ?? $llama->baseUrl();
            } elseif ($isOllama) {
                // Native Ollama models ONLY.
                $apiKey = 'sk-dummy-key-for-ollama';
                $baseUrl = 'http://127.0.0.1:11434/v1';

                // Pastikan server lokal berjalan
                $connection = @fsockopen('127.0.0.1', 11434, $errno, $errstr, 1);
                if (is_resource($connection)) {
                    fclose($connection);
                } else {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        @pclose(@popen('start /B ollama serve > NUL 2>&1', 'r'));
                        sleep(2);
                    } else {
                        @exec('nohup ollama serve > /dev/null 2>&1 &');
                        sleep(2);
                    }
                }
            } else {
                $apiKey = ($user && !empty($user->nine_router_api_key)) ? $user->nine_router_api_key : 'sk-dummy-key-for-local-engine';
                $baseUrl = ($user && !empty($user->nine_router_base_url)) ? rtrim($user->nine_router_base_url, '/') : 'http://127.0.0.1:20128/v1';
            }
        } elseif ($isHuggingFace) {
            $apiKey = ($user && !empty($user->huggingface_api_key)) ? trim($user->huggingface_api_key) : 'sk-dummy-key-for-huggingface';

            $savedUrl = $user->huggingface_base_url;
            if (!empty($savedUrl) && str_contains($savedUrl, 'api-inference.huggingface.co')) {
                $savedUrl = str_replace('api-inference.huggingface.co', 'router.huggingface.co', $savedUrl);
            }

            $hfBaseUrl = !empty($savedUrl) ? rtrim(trim($savedUrl), '/') : '';

            if (empty($hfBaseUrl)) {
                $baseUrl = "https://router.huggingface.co/v1";
            } else {
                $baseUrl = $hfBaseUrl;
                if (!preg_match('~^https?://~i', $baseUrl)) {
                    $baseUrl = "https://" . $baseUrl;
                }
                if (!str_ends_with($baseUrl, '/v1')) {
                    $baseUrl .= '/v1';
                }
            }
        } elseif ($isProxy) {
            $apiKey = ($user && !empty($user->proxy_api_key)) ? $user->proxy_api_key : 'sk-dummy-key-for-local-proxy';
            if (!empty($user->proxy_base_url)) {
                $baseUrl = rtrim($user->proxy_base_url, '/');
            } else {
                $baseUrl = 'http://127.0.0.1:20128/v1';
            }
        } elseif ($isGlm) {
            $apiKey = ($user && !empty($user->glm_api_key)) ? trim($user->glm_api_key) : '';
            $baseUrl = 'https://api.z.ai/api/paas/v4';
        } elseif ($isKimi) {
            $apiKey = ($user && !empty($user->kimi_api_key)) ? trim($user->kimi_api_key) : '';
            $baseUrl = 'https://api.moonshot.ai/v1';
        } elseif ($isQwen) {
            $apiKey = ($user && !empty($user->qwen_api_key)) ? trim($user->qwen_api_key) : '';
            $baseUrl = 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1';
        } else {
            $apiKey = $user ? $user->openai_api_key : null;
            if (empty($apiKey)) {
                $apiKey = config('services.openai.key');
            }
            $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        }

        if (empty($apiKey) && ($isProxy || $isLocalEngine)) {
            $apiKey = 'sk-dummy-key-for-local-engine';
        }

        $label = $isHuggingFace ? 'huggingface' : ($isGlm ? 'glm' : ($isKimi ? 'kimi' : ($isQwen ? 'qwen' : ($isLocalEngine ? 'local' : ($isProxy ? 'proxy' : 'openai')))));

        $nativeTools = !($isProxy || $isLocalEngine || $isHuggingFace || $isGlm || $isKimi || $isQwen);

        return [$apiKey, $baseUrl, $label, $nativeTools];
    }

    /**
     * Guard against "ghost model" answers: if the selected model is a Model Hub
     * GGUF whose .gguf file has NOT been downloaded, there is nothing to run it
     * locally — we must block, never fall through to any other engine. Returns a
     * user-facing error string when blocked, or null when the model is fine.
     */
    private function ggufNotInstalledError(string $model): ?string
    {
        $llama = app(\App\Services\LlamaServerService::class);
        if (!$llama->isGgufModel($model)) {
            return null; // not a Model Hub GGUF — nothing to check here
        }
        if ($llama->ggufPath($model) !== null) {
            return null; // downloaded and present
        }

        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        $name = $aiModel->name ?? $model;
        return "Model \"{$name}\" is not installed. Please download it from the Model Hub first.";
    }

    private function guzzle(): \GuzzleHttp\Client
    {
        // CurlHandler buffers the ENTIRE response body before returning, which
        // silently breaks 'stream' => true (tokens only arrive after the whole
        // generation finishes). Route streaming requests to StreamHandler —
        // exactly what Guzzle's default handler chain does — while keeping
        // curl (with the IPv4 pin) for regular requests.
        $curl = new \GuzzleHttp\Handler\CurlHandler();
        $handler = ini_get('allow_url_fopen')
            ? \GuzzleHttp\Handler\Proxy::wrapStreaming($curl, new \GuzzleHttp\Handler\StreamHandler())
            : $curl;
        $stack = \GuzzleHttp\HandlerStack::create($handler);
        return new \GuzzleHttp\Client([
            'handler' => $stack,
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ]);
    }

    /**
     * One agentic turn over an OpenAI-compatible endpoint. See SupportsToolUse.
     */
    public function streamAgentTurn(array $messages, string $model, array $tools): \Generator
    {
        if ($installError = $this->ggufNotInstalledError($model)) {
            yield ['type' => 'text', 'text' => $installError];
            return ['stop_reason' => 'error', 'error' => 'model_not_installed'];
        }

        [$apiKey, $baseUrl, , $nativeTools] = $this->resolveConfig($model);

        // Endpoint can't be trusted with native tools — signal AgentRunner to use
        // its text-protocol fallback (no HTTP call wasted here).
        if (!empty($tools) && !$nativeTools) {
            return ['stop_reason' => 'error', 'error' => 'native_tools_unsupported'];
        }

        if (empty($apiKey)) {
            $providerName = strtoupper($label === 'openai' ? 'OpenAI' : $label);
            yield ['type' => 'text', 'text' => $providerName . ' API key is not configured. Please add it in your Settings.'];
            return ['stop_reason' => 'error', 'error' => 'missing_key'];
        }

        return yield from $this->streamOpenAiCompat(
            $this->guzzle(),
            $baseUrl . '/chat/completions',
            ['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'],
            $model,
            $messages,
            $tools
        );
    }

    public function streamResponse(array $messages, string $model, array $options = []): \Generator
    {
        // Block uninstalled Model Hub GGUF models before any dispatch, so an
        // uninstalled local model can NEVER produce an answer.
        if ($installError = $this->ggufNotInstalledError($model)) {
            yield "\n[" . $installError . "]";
            return;
        }

        [$apiKey, $baseUrl, $label, $nativeTools] = $this->resolveConfig($model);
        $user = \Illuminate\Support\Facades\Auth::user();

        if (empty($apiKey)) {
            $providerName = strtoupper($label === 'openai' ? 'OpenAI' : $label);
            yield $providerName . " API key is not configured. Please add it in your Settings.";
            return;
        }

        // RAG parameters for document attachments: retrieval query is the latest
        // user message; the budget scales with the local model's real context
        // (now 32K, up from 16K) so document continuation (#6) has enough of the
        // source to ground on, and is generous for cloud models.
        $ragQuery = $this->ragQueryFrom($messages);
        $llama = app(\App\Services\LlamaServerService::class);
        $isLocalGguf = $llama->isGgufModel($model);
        $ragBudget = $isLocalGguf
            ? ($llama->tierFor($model) === 'large' ? 20_000 : 14_000)
            : 48_000;

        // Filter messages (OpenAI only supports system, user, assistant)
        $openAiMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $openAiMessages[] = [
                    'role' => 'system',
                    'content' => $msg['content'],
                ];
                continue;
            }

            $content = [];
            
            // Handle text content
            if (!empty($msg['content'])) {
                $content[] = [
                    'type' => 'text',
                    'text' => $msg['content']
                ];
            }
            
            // Handle attachments
            foreach ($this->resolveAttachmentParts($msg['attachments'] ?? [], $ragQuery, $ragBudget) as $part) {
                $content[] = $part['kind'] === 'image'
                    ? ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $part['mime'] . ';base64,' . $part['base64']]]
                    : ['type' => 'text', 'text' => $part['text']];
            }
            
            $openAiMessages[] = [
                'role' => $msg['role'],
                'content' => count($content) === 1 && $content[0]['type'] === 'text' ? $content[0]['text'] : (count($content) > 0 ? $content : '')
            ];
        }

        // See guzzle(): CurlHandler alone would buffer the whole SSE body and
        // defeat streaming — wrap it so 'stream' => true uses StreamHandler.
        $curl = new \GuzzleHttp\Handler\CurlHandler();
        $handler = ini_get('allow_url_fopen')
            ? \GuzzleHttp\Handler\Proxy::wrapStreaming($curl, new \GuzzleHttp\Handler\StreamHandler())
            : $curl;
        $stack = \GuzzleHttp\HandlerStack::create($handler);

        $client = new \GuzzleHttp\Client([
            'handler' => $stack,
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]
        ]);

        // Auto-detect Ollama model if using the generic 'rynude-ollama'
        if ($model === 'rynude-ollama') {
            try {
                $tagsResponse = $client->get('http://127.0.0.1:11434/api/tags', ['timeout' => 2]);
                if ($tagsResponse->getStatusCode() === 200) {
                    $tagsBody = json_decode($tagsResponse->getBody()->getContents(), true);
                    if (!empty($tagsBody['models'])) {
                        // Use the first available model automatically
                        $model = $tagsBody['models'][0]['name'];
                    } else {
                        $model = 'llama3.1'; // Fallback if no models found
                    }
                }
            } catch (\Exception $e) {
                // If Ollama is down or error, fallback to a standard name
                $model = 'llama3.1';
            }
        }
        
        try {
            // Transient failures (rate limit / provider overload) get a couple
            // of retries with backoff before surfacing an error, mirroring the
            // pattern already proven in AnthropicProvider.
            $response = null;
            $retryDelay = 1;
            for ($attempt = 0; $attempt <= 2; $attempt++) {
                $response = $client->post($baseUrl . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => array_filter([
                        'model' => $model,
                        'messages' => $openAiMessages,
                        'stream' => true,
                        // Large ceiling so long documents (full skripsi/laporan) aren't
                        // truncated mid-chapter. Most OpenAI-compatible / proxy models
                        // accept 8192 output tokens.
                        'max_tokens' => (int) ($options['max_tokens'] ?? 8192),
                        // Constrained output (local GGUF engine only): a GBNF
                        // grammar that llama-server.mjs compiles and enforces
                        // during sampling. Cloud endpoints never receive it.
                        'grammar' => $isLocalGguf ? ($options['grammar'] ?? null) : null,
                    ], fn ($v) => $v !== null),
                    'stream' => true,
                    'verify' => false,
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
                $errorMsg = 'Unknown error';
                if ($errorBody && isset($errorBody['error'])) {
                    $errorMsg = is_string($errorBody['error']) ? $errorBody['error'] : ($errorBody['error']['message'] ?? 'Unknown error');
                } elseif ($errorBody && isset($errorBody['message'])) {
                    $errorMsg = $errorBody['message'];
                }
                yield "\n[Error: API returned status code " . $response->getStatusCode() . " - " . $errorMsg . "]";
                return;
            }

            $body = $response->getBody();
            $buffer = '';
            $fullBody = '';
            $hasDataChunks = false;
            $wasTruncated = false;
            $inputTokens = 0;
            $outputTokens = 0;
            $generatedContent = '';

            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;
                $fullBody .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    $line = trim($line);
                    if (empty($line)) continue;

                    if (str_starts_with($line, 'data: ')) {
                        $hasDataChunks = true;
                        $jsonStr = trim(substr($line, 6));
                        if (empty($jsonStr) || $jsonStr === '[DONE]') continue;

                        $data = json_decode($jsonStr, true);
                        $delta = $data['choices'][0]['delta'] ?? [];

                        // Reasoning models stream their thinking as a separate delta
                        // field (`reasoning_content` on DeepSeek-style APIs, `reasoning`
                        // on some proxies). Forward it as a structured chunk so the UI
                        // can show it live without mixing it into the final answer.
                        $reasoning = $delta['reasoning_content'] ?? $delta['reasoning'] ?? null;
                        if (is_string($reasoning) && $reasoning !== '') {
                            $generatedContent .= $reasoning;
                            yield ['type' => 'thinking', 'text' => $reasoning];
                        }

                        if (isset($delta['content']) && $delta['content'] !== '') {
                            $generatedContent .= $delta['content'];
                            yield $delta['content'];
                        } elseif ($data && isset($data['error'])) {
                            $errorMsg = is_string($data['error']) ? $data['error'] : ($data['error']['message'] ?? 'Unknown error');
                            yield "\n[Error from API: " . $errorMsg . "]";
                        }
                        // Some OpenAI-compatible endpoints include a final usage object.
                        if ($data && isset($data['usage'])) {
                            $inputTokens = $data['usage']['prompt_tokens'] ?? $inputTokens;
                            $outputTokens = $data['usage']['completion_tokens'] ?? $outputTokens;
                        }
                        if (($data['choices'][0]['finish_reason'] ?? null) === 'length') {
                            $wasTruncated = true;
                        }
                    }
                }
            }

            // Answer hit the max_tokens ceiling: signal it so the UI can offer
            // a "Continue" action.
            if ($wasTruncated) {
                yield ['type' => 'truncated'];
            }

            // Record any usage the endpoint reported or estimate if missing.
            if ($user) {
                if ($inputTokens <= 0 && $outputTokens <= 0) {
                    $inputChars = 0;
                    foreach ($openAiMessages as $m) {
                        $contentStr = is_string($m['content'] ?? '') ? $m['content'] : json_encode($m['content'] ?? '');
                        $inputChars += strlen($contentStr);
                    }
                    $inputTokens = max(1, intdiv($inputChars, 4));
                    $outputTokens = max(1, intdiv(strlen($generatedContent), 4));
                }
                if ($inputTokens > 0 || $outputTokens > 0) {
                    $providerLabel = $label;
                    [$rtkSaved, $rtkOriginal] = RtkTracker::flushAndGet();
                    \App\Models\TokenUsage::record($user->id, $model, $providerLabel, $inputTokens, $outputTokens, $rtkSaved, $rtkOriginal);
                }
            }

            // Fallback: If no streaming chunks were received, maybe the API returned a flat JSON object
            if (!$hasDataChunks && !empty(trim($fullBody))) {
                $data = json_decode(trim($fullBody), true);
                if ($data && isset($data['choices'][0]['message']['content'])) {
                    yield $data['choices'][0]['message']['content'];
                } elseif ($data && isset($data['error'])) {
                    $errorMsg = is_string($data['error']) ? $data['error'] : ($data['error']['message'] ?? 'Unknown error');
                    yield "\n[Error from API: " . $errorMsg . "]";
                } elseif (!$data) {
                    yield "\n[API Error: " . substr(strip_tags(trim($fullBody)), 0, 200) . "]";
                }
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            yield "\n[Error: Network Connection Failed. Details: " . $e->getMessage() . "]";
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $errorBody = json_decode($response->getBody()->getContents(), true);
            yield "\n[API Error: " . ($errorBody['error']['message'] ?? $e->getMessage()) . "]";
        } catch (\Exception $e) {
            yield "\n[Error: " . $e->getMessage() . "]";
        }
    }
}

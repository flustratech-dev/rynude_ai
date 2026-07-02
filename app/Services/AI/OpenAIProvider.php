<?php

namespace App\Services\AI;

use App\Services\AI\Concerns\OpenAiCompatToolStream;
use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\Contracts\SupportsToolUse;
use Illuminate\Support\Facades\Http;

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
        $is9RouterAuto = str_starts_with($model, 'kr/claude') || str_starts_with($model, 'mmf/mimo');

        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        $isHuggingFace = $aiModel && $aiModel->provider === 'huggingface';
        $isOllama = $aiModel && $aiModel->provider === 'ollama';

        if ($isHuggingFace) {
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
        } elseif ($is9RouterAuto) {
            $apiKey = ($user && !empty($user->nine_router_api_key)) ? $user->nine_router_api_key : 'sk-dummy-key-for-9router';
            $baseUrl = 'http://127.0.0.1:20128/v1';
        } elseif ($isOllama) {
            $apiKey = 'sk-dummy-key-for-ollama';
            $baseUrl = 'http://127.0.0.1:11434/v1';
        } elseif ($isProxy) {
            $apiKey = ($user && !empty($user->proxy_api_key)) ? $user->proxy_api_key : 'sk-dummy-key-for-local-proxy';
            if (!empty($user->proxy_base_url)) {
                $baseUrl = rtrim($user->proxy_base_url, '/');
            } else {
                $baseUrl = 'http://127.0.0.1:20128/v1';
            }
        } else {
            $apiKey = $user ? $user->openai_api_key : null;
            if (empty($apiKey)) {
                $apiKey = config('services.openai.key');
            }
            $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        }

        if (empty($apiKey) && $isProxy) {
            $apiKey = 'sk-dummy-key-for-local-proxy';
        }

        $label = $isHuggingFace ? 'huggingface' : ($isProxy ? 'proxy' : 'openai');

        // Native OpenAI function-calling is only reliable on the genuine OpenAI
        // endpoint. Local proxies, 9Router (kr/*) and HuggingFace routers either
        // reject the `tools` param or can't round-trip tool messages — those fall
        // back to AgentRunner's text-protocol (ReAct) loop instead.
        $nativeTools = !($isProxy || $is9RouterAuto || $isHuggingFace);

        return [$apiKey, $baseUrl, $label, $nativeTools];
    }

    private function guzzle(): \GuzzleHttp\Client
    {
        $stack = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\CurlHandler());
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
        [$apiKey, $baseUrl, , $nativeTools] = $this->resolveConfig($model);

        // Endpoint can't be trusted with native tools — signal AgentRunner to use
        // its text-protocol fallback (no HTTP call wasted here).
        if (!empty($tools) && !$nativeTools) {
            return ['stop_reason' => 'error', 'error' => 'native_tools_unsupported'];
        }

        if (empty($apiKey)) {
            yield ['type' => 'text', 'text' => 'OpenAI API key is not configured. Please add it in your Settings.'];
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

    public function streamResponse(array $messages, string $model): \Generator
    {
        // Get the API key from the currently authenticated user, or fallback to config
        $user = \Illuminate\Support\Facades\Auth::user();

        $isProxy = $user && $user->use_proxy;
        $is9RouterAuto = str_starts_with($model, 'kr/claude') || str_starts_with($model, 'mmf/mimo');
        
        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        $isHuggingFace = $aiModel && $aiModel->provider === 'huggingface';
        $isOllama = $aiModel && $aiModel->provider === 'ollama';

        if ($isHuggingFace) {
            $apiKey = ($user && !empty($user->huggingface_api_key)) ? trim($user->huggingface_api_key) : 'sk-dummy-key-for-huggingface';
            
            // Auto-migrate the deprecated api-inference domain to the new router domain
            $savedUrl = $user->huggingface_base_url;
            if (!empty($savedUrl) && str_contains($savedUrl, 'api-inference.huggingface.co')) {
                $savedUrl = str_replace('api-inference.huggingface.co', 'router.huggingface.co', $savedUrl);
            }
            
            $hfBaseUrl = !empty($savedUrl) ? rtrim(trim($savedUrl), '/') : '';
            
            if (empty($hfBaseUrl)) {
                $baseUrl = "https://router.huggingface.co/v1";
            } else {
                $baseUrl = $hfBaseUrl;
                // Prepend https:// if no scheme is provided
                if (!preg_match('~^https?://~i', $baseUrl)) {
                    $baseUrl = "https://" . $baseUrl;
                }
                // Ensure OpenAI compatibility path is present
                if (!str_ends_with($baseUrl, '/v1')) {
                    $baseUrl .= '/v1';
                }
            }
        } elseif ($is9RouterAuto) {
            $apiKey = ($user && !empty($user->nine_router_api_key)) ? $user->nine_router_api_key : 'sk-dummy-key-for-9router';
            $baseUrl = 'http://127.0.0.1:20128/v1';
        } elseif ($isOllama) {
            $apiKey = 'sk-dummy-key-for-ollama';
            $baseUrl = 'http://127.0.0.1:11434/v1';
        } elseif ($isProxy) {
            // Always try to use the proxy key if provided, otherwise fallback to dummy
            $apiKey = ($user && !empty($user->proxy_api_key)) ? $user->proxy_api_key : 'sk-dummy-key-for-local-proxy';
            
            // If proxy base url is set by user AND proxy is enabled, use it.
            if (!empty($user->proxy_base_url)) {
                $baseUrl = rtrim($user->proxy_base_url, '/');
            } else {
                $baseUrl = 'http://127.0.0.1:20128/v1';
            }
        } else {
            $apiKey = $user ? $user->openai_api_key : null;
            if (empty($apiKey)) {
                $apiKey = config('services.openai.key');
            }
            $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        }

        if (empty($apiKey)) {
            if ($isProxy) {
                // Local proxies like LM Studio or 9Router might not require an API key
                $apiKey = 'sk-dummy-key-for-local-proxy';
            } else {
                yield "OpenAI API key is not configured. Please add it in your Settings.";
                return;
            }
        }

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
            if (!empty($msg['attachments'])) {
                foreach ($msg['attachments'] as $att) {
                    $filePath = storage_path('app/public/' . $att['file_path']);
                    if (file_exists($filePath)) {
                        $mimeType = $att['file_type'];
                        
                        if (str_starts_with($mimeType, 'image/')) {
                            $processedImage = \App\Helpers\ImageHelper::resizeAndEncode($filePath, $mimeType, 4000);
                            $content[] = [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:' . $processedImage['mime_type'] . ';base64,' . $processedImage['data']
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
            
            $openAiMessages[] = [
                'role' => $msg['role'],
                'content' => count($content) === 1 && $content[0]['type'] === 'text' ? $content[0]['text'] : (count($content) > 0 ? $content : '')
            ];
        }

        $handler = new \GuzzleHttp\Handler\CurlHandler();
        $stack = \GuzzleHttp\HandlerStack::create($handler);
        
        $client = new \GuzzleHttp\Client([
            'handler' => $stack,
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]
        ]);

        // Auto-detect Ollama model if using the generic 'rynude-ollama'
        if ($isOllama && $model === 'rynude-ollama') {
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
            $response = $client->post($baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $openAiMessages,
                    'stream' => true,
                    // Large ceiling so long documents (full skripsi/laporan) aren't
                    // truncated mid-chapter. Most OpenAI-compatible / proxy models
                    // accept 8192 output tokens.
                    'max_tokens' => 8192,
                ],
                'stream' => true,
                'verify' => false,
                'http_errors' => false,
                'timeout' => 300,
            ]);

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
            $inputTokens = 0;
            $outputTokens = 0;

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
                            yield ['type' => 'thinking', 'text' => $reasoning];
                        }

                        if (isset($delta['content']) && $delta['content'] !== '') {
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
                    }
                }
            }

            // Record any usage the endpoint reported.
            if ($user && ($inputTokens > 0 || $outputTokens > 0)) {
                $providerLabel = $isHuggingFace ? 'huggingface' : ($isProxy ? 'proxy' : 'openai');
                \App\Models\TokenUsage::record($user->id, $model, $providerLabel, $inputTokens, $outputTokens);
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

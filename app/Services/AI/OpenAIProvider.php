<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements LLMProviderInterface
{
    public function streamResponse(array $messages, string $model): \Generator
    {
        // Get the API key from the currently authenticated user, or fallback to config
        $user = \Illuminate\Support\Facades\Auth::user();
        
        $isProxy = $user && $user->use_proxy;
        $is9RouterAuto = str_starts_with($model, 'kr/claude');
        
        if ($isProxy || $is9RouterAuto) {
            $apiKey = $isProxy && !empty($user->proxy_api_key) ? $user->proxy_api_key : 'sk-dummy-key-for-local-proxy';
            
            // If proxy base url is set by user, use it. Otherwise if it's a 9router model, fallback to 127.0.0.1:20128
            if ($isProxy && !empty($user->proxy_base_url)) {
                $baseUrl = rtrim($user->proxy_base_url, '/');
            } else {
                $baseUrl = 'http://127.0.0.1:20128/v1';
            }
        } else {
            $apiKey = $user ? $user->openai_api_key : null;
            if (empty($apiKey)) {
                $apiKey = config('services.openai.key');
            }
            $baseUrl = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
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
            $openAiMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $client = new \GuzzleHttp\Client();
        
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
                ],
                'stream' => true,
                'verify' => false,
                'http_errors' => false,
                'timeout' => 60,
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
                        if ($data && isset($data['choices'][0]['delta']['content'])) {
                            yield $data['choices'][0]['delta']['content'];
                        } elseif ($data && isset($data['error'])) {
                            $errorMsg = is_string($data['error']) ? $data['error'] : ($data['error']['message'] ?? 'Unknown error');
                            yield "\n[Error from API: " . $errorMsg . "]";
                        }
                    }
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
            yield "\n[Error: Connection timeout. Please check your network and try again.]";
        } catch (\Exception $e) {
            yield "\n[Error communicating with API: " . $e->getMessage() . "]";
        }
    }
}

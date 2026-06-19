<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements LLMProviderInterface
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
                $anthropicMessages[] = [
                    'role' => $msg['role'], // 'user' or 'assistant'
                    'content' => $msg['content'],
                ];
            }
        }

        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->post(env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/messages'), [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => array_filter([
                    'model' => $model,
                    'messages' => $anthropicMessages,
                    'system' => $systemPrompt ?: null,
                    'max_tokens' => 4096,
                    'stream' => true,
                ]),
                'stream' => true,
                'http_errors' => false,
                'timeout' => 60,
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
                            if ($data['type'] === 'content_block_delta' && isset($data['delta']['text'])) {
                                yield $data['delta']['text'];
                            } elseif ($data['type'] === 'error') {
                                yield "\n[Error from API: " . ($data['error']['message'] ?? 'Unknown error') . "]";
                            }
                        }
                    }
                }
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            yield "\n[Error: Connection timeout. Please check your network and try again.]";
        } catch (\Exception $e) {
            yield "\n[Error communicating with Anthropic API: " . $e->getMessage() . "]";
        }
    }
}

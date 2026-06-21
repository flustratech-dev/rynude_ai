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
        $is9RouterAuto = str_starts_with($model, 'kr/claude') || str_starts_with($model, 'mmf/mimo');
        
        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        $isHuggingFace = $aiModel && $aiModel->provider === 'huggingface';

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
            
            // Handle attachment
            if (!empty($msg['attachment']) && isset($msg['attachment']['file_path'])) {
                $filePath = storage_path('app/public/' . $msg['attachment']['file_path']);
                if (file_exists($filePath)) {
                    $mimeType = $msg['attachment']['file_type'];
                    
                    if (str_starts_with($mimeType, 'image/')) {
                        $processedImage = \App\Helpers\ImageHelper::resizeAndEncode($filePath, $mimeType, 4000);
                        $content[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $processedImage['mime_type'] . ';base64,' . $processedImage['data']
                            ]
                        ];
                    } elseif ($mimeType === 'application/pdf') {
                        try {
                            $parser = new \Smalot\PdfParser\Parser();
                            $pdf = $parser->parseFile($filePath);
                            $text = $pdf->getText();
                            $content[] = [
                                'type' => 'text',
                                'text' => "\n\n[Isi Dokumen PDF: {$msg['attachment']['file_name']}]\n" . $text . "\n[Akhir Isi Dokumen]"
                            ];
                        } catch (\Exception $e) {
                            $content[] = [
                                'type' => 'text',
                                'text' => "\n\n[Gagal membaca file PDF: " . $e->getMessage() . "]"
                            ];
                        }
                    } elseif ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || str_ends_with($msg['attachment']['file_name'], '.docx')) {
                        try {
                            $zip = new \ZipArchive();
                            $text = "";
                            if ($zip->open($filePath) === true) {
                                if (($index = $zip->locateName('word/document.xml')) !== false) {
                                    $contentXml = $zip->getFromIndex($index);
                                    $text = strip_tags(str_replace('</w:p>', "\n", $contentXml));
                                }
                                $zip->close();
                            }
                            $content[] = [
                                'type' => 'text',
                                'text' => "\n\n[Isi Dokumen Word: {$msg['attachment']['file_name']}]\n" . trim($text) . "\n[Akhir Isi Dokumen]"
                            ];
                        } catch (\Exception $e) {
                            $content[] = [
                                'type' => 'text',
                                'text' => "\n\n[Gagal membaca file Word: " . $e->getMessage() . "]"
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

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
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $processedImage['mime_type'],
                                    'data' => $processedImage['data']
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
}

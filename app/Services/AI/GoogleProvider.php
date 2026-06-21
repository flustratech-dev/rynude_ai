<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;

class GoogleProvider implements LLMProviderInterface
{
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
            if (!empty($msg['attachment']) && isset($msg['attachment']['file_path'])) {
                $filePath = storage_path('app/public/' . $msg['attachment']['file_path']);
                if (file_exists($filePath)) {
                    $mimeType = $msg['attachment']['file_type'];

                    if (str_starts_with($mimeType, 'image/')) {
                        $processedImage = \App\Helpers\ImageHelper::resizeAndEncode($filePath, $mimeType, 4000);
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => $processedImage['mime_type'],
                                'data' => $processedImage['data'],
                            ],
                        ];
                    } elseif ($mimeType === 'application/pdf') {
                        try {
                            $parser = new \Smalot\PdfParser\Parser();
                            $pdf = $parser->parseFile($filePath);
                            $parts[] = ['text' => "\n\n[Isi Dokumen PDF: {$msg['attachment']['file_name']}]\n" . $pdf->getText() . "\n[Akhir Isi Dokumen]"];
                        } catch (\Exception $e) {
                            $parts[] = ['text' => "\n\n[Gagal membaca file PDF: " . $e->getMessage() . "]"];
                        }
                    }
                }
            }

            if (empty($parts)) {
                continue;
            }

            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => $parts,
            ];
        }

        $baseUrl = rtrim(env('GOOGLE_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/');
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
                    if (isset($data['usageMetadata'])) {
                        $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? $inputTokens;
                        $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? $outputTokens;
                    }
                    if (isset($data['error'])) {
                        yield "\n[Error from API: " . ($data['error']['message'] ?? 'Unknown error') . "]";
                    }
                }
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

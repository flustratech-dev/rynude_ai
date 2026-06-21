<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;

class MistralProvider implements LLMProviderInterface
{
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

        // Mistral uses the OpenAI-compatible chat format.
        $mistralMessages = [];
        foreach ($messages as $msg) {
            $text = $msg['content'] ?? '';

            // Documents are flattened into text; Mistral chat endpoint is text-first.
            if (!empty($msg['attachment']) && isset($msg['attachment']['file_path'])) {
                $filePath = storage_path('app/public/' . $msg['attachment']['file_path']);
                if (file_exists($filePath) && ($msg['attachment']['file_type'] ?? '') === 'application/pdf') {
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($filePath);
                        $text .= "\n\n[Isi Dokumen PDF: {$msg['attachment']['file_name']}]\n" . $pdf->getText() . "\n[Akhir Isi Dokumen]";
                    } catch (\Exception $e) {
                        $text .= "\n\n[Gagal membaca file PDF: " . $e->getMessage() . "]";
                    }
                }
            }

            $mistralMessages[] = [
                'role' => $msg['role'],
                'content' => $text,
            ];
        }

        $client = new \GuzzleHttp\Client();
        $inputTokens = 0;
        $outputTokens = 0;

        try {
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
                }
            }

            if ($user) {
                \App\Models\TokenUsage::record($user->id, $model, 'mistral', $inputTokens, $outputTokens);
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

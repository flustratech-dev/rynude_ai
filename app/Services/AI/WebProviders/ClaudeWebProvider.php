<?php

namespace App\Services\AI\WebProviders;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Talks to the *unofficial* claude.ai web API using the `sessionKey` cookie that
 * the browser extension captured after the user logged in to claude.ai.
 *
 * The real flow (unlike the old stub) is:
 *   1. GET  /api/organizations                                   → organization uuid
 *   2. POST /api/organizations/{org}/chat_conversations          → fresh conversation uuid
 *   3. POST /api/organizations/{org}/chat_conversations/{c}/completion (SSE) → answer
 *
 * IMPORTANT: claude.ai authenticates with the `sessionKey` COOKIE (not a Bearer
 * header) and sits behind Cloudflare. Requests coming from a server IP may be
 * challenged (HTTP 403 "Just a moment"); when that happens the only reliable fix
 * is to proxy the calls through the extension inside the real browser.
 */
class ClaudeWebProvider extends BaseWebProvider
{
    protected string $provider = 'claude';

    private const BASE = 'https://claude.ai';
    private const ROOT_PARENT = '00000000-0000-4000-8000-000000000000';

    public function sendMessage(array $messages, array $options = []): array
    {
        $org = $this->organizationUuid();

        // Create a fresh conversation for this exchange.
        $convUuid = (string) Str::uuid();
        $create = $this->web(['Accept' => 'application/json'])->post(
            self::BASE . "/api/organizations/{$org}/chat_conversations",
            ['uuid' => $convUuid, 'name' => '']
        );
        $this->guard($create);
        $convUuid = $create->json('uuid', $convUuid);

        // Ask for the completion. The response is a Server-Sent-Events stream.
        $resp = $this->web([
            'Accept' => 'text/event-stream',
            'Content-Type' => 'application/json',
        ])->post(
            self::BASE . "/api/organizations/{$org}/chat_conversations/{$convUuid}/completion",
            [
                'prompt' => $this->flatten($messages),
                'parent_message_uuid' => self::ROOT_PARENT,
                'timezone' => config('app.timezone') ?: 'UTC',
                'attachments' => [],
                'files' => [],
                'rendering_mode' => 'messages',
                // `model` is intentionally omitted so claude.ai uses the account
                // default — passing an unknown web slug returns "invalid model".
            ]
        );
        $this->guard($resp);

        $text = $this->parseSse($resp->body());

        if ($text === '') {
            throw new \RuntimeException('Claude returned an empty response — the claude.ai stream format may have changed.');
        }

        $this->token->markActive();

        return ['content' => $text];
    }

    public function validate(): bool
    {
        try {
            $resp = $this->web(['Accept' => 'application/json'])->get(self::BASE . '/api/organizations');
            $valid = $resp->successful() && is_array($resp->json());
        } catch (\Throwable $e) {
            $valid = false;
        }

        $this->token->update([
            'status' => $valid ? 'active' : 'expired',
            'last_validated_at' => now(),
            'last_error' => $valid ? null : 'Validation failed',
        ]);

        return $valid;
    }

    /**
     * Browser-like, cookie-authenticated HTTP client.
     */
    private function web(array $extra = []): PendingRequest
    {
        return Http::withHeaders(array_merge([
            'Cookie' => 'sessionKey=' . $this->token->access_token,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Origin' => self::BASE,
            'Referer' => self::BASE . '/chats',
            'Accept-Language' => 'en-US,en;q=0.9',
            'anthropic-client-platform' => 'web_claude_ai',
        ], $extra))->timeout(120);
    }

    /**
     * Resolve the organization uuid tied to this session.
     */
    private function organizationUuid(): string
    {
        $resp = $this->web(['Accept' => 'application/json'])->get(self::BASE . '/api/organizations');
        $this->guard($resp);

        $orgs = (array) $resp->json();

        // Prefer an org that actually has the chat capability.
        foreach ($orgs as $org) {
            if (in_array('chat', (array) ($org['capabilities'] ?? []), true) && !empty($org['uuid'])) {
                return $org['uuid'];
            }
        }

        if (!empty($orgs[0]['uuid'])) {
            return $orgs[0]['uuid'];
        }

        throw new \RuntimeException('No Claude organization found for this session. Re-login to claude.ai and reconnect.');
    }

    /**
     * Turn our internal message array into a single prompt string. Each call
     * creates a brand-new server-side conversation, so prior turns are inlined
     * as plain text to preserve context.
     */
    private function flatten(array $messages): string
    {
        $parts = [];

        foreach ($messages as $m) {
            $content = $m['content'] ?? '';
            if (is_array($content)) {
                $content = collect($content)->pluck('text')->filter()->implode("\n");
            }
            $content = trim((string) $content);
            if ($content === '') {
                continue;
            }

            $role = $m['role'] ?? 'user';
            $label = match ($role) {
                'assistant' => 'Assistant',
                'system' => 'System',
                default => 'Human',
            };
            $parts[] = "{$label}: {$content}";
        }

        if (count($parts) <= 1) {
            // Clean single prompt — drop the role label.
            return preg_replace('/^(Human|System|Assistant):\s*/', '', $parts[0] ?? '');
        }

        return implode("\n\n", $parts);
    }

    /**
     * Collect the assistant text out of the SSE body. claude.ai has shipped a
     * couple of event shapes over time, so we accept all of the known ones.
     */
    private function parseSse(string $body): string
    {
        $text = '';

        foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
            $line = trim($line);
            if ($line === '' || !str_starts_with($line, 'data:')) {
                continue;
            }

            $json = trim(substr($line, 5));
            if ($json === '' || $json === '[DONE]') {
                continue;
            }

            $data = json_decode($json, true);
            if (!is_array($data)) {
                continue;
            }

            if (isset($data['completion']) && is_string($data['completion'])) {
                $text .= $data['completion'];
            } elseif (isset($data['delta']['text']) && is_string($data['delta']['text'])) {
                $text .= $data['delta']['text'];
            }
        }

        return $text;
    }

    /**
     * Translate transport/auth failures into clear, actionable messages.
     */
    private function guard(Response $resp): void
    {
        if ($resp->successful()) {
            return;
        }

        $status = $resp->status();
        $body = $resp->body();

        // Cloudflare challenge — the request never reached Claude.
        if (in_array($status, [403, 503], true)
            && (stripos($body, 'just a moment') !== false
                || stripos($body, 'cloudflare') !== false
                || stripos($body, 'cf-chl') !== false)) {
            throw new \RuntimeException(
                'Diblokir Cloudflare: request server ke claude.ai ditantang. '
                . 'Session-nya perlu dijalankan lewat browser extension, bukan dari server.'
            );
        }

        if (in_array($status, [401, 403], true)) {
            $this->markExpired("Session rejected ($status)");
            throw new \RuntimeException('Sesi Claude kadaluarsa/ditolak. Login ulang ke claude.ai lalu reconnect.');
        }

        throw new \RuntimeException('Claude API error (' . $status . '): ' . mb_substr($body, 0, 500));
    }
}

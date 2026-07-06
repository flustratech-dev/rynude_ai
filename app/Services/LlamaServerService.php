<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Dedicated local GGUF inference engine (Phase 4 of the local-engine plan).
 *
 * This is the ONLY thing that serves Model Hub `.gguf` files. It launches an
 * OpenAI-compatible `node-llama-cpp serve` process on its own port — completely
 * separate from Ollama (11434) and 9router (20128). GGUF models must NEVER be
 * routed through Ollama; keeping the engines physically separate (different
 * process, different port, different manager) is what enforces that rule.
 *
 * It also owns the context-window size: the server is always started with an
 * explicit `--contextSize`, which is the fix for the 4096-token overflow — the
 * engine no longer inherits a hardcoded 4096 default.
 */
class LlamaServerService
{
    /**
     * Maps a Model Hub model `code` to the on-disk `.gguf` filename.
     * Kept in sync with ModelHubController::getCatalog().
     */
    private const CATALOG = [
        'qwen-2.5-0.5b'   => 'qwen2.5-0.5b-instruct-q8_0.gguf',
        'qwen-2.5-1.5b'   => 'qwen2.5-1.5b-instruct-q5_k_m.gguf',
        'llama-3.2-3b'    => 'Llama-3.2-3B-Instruct-Q4_K_M.gguf',
        'mistral-7b-v0.3' => 'Mistral-7B-Instruct-v0.3-Q4_K_M.gguf',
        'llama-3.1-8b'    => 'Meta-Llama-3.1-8B-Instruct-Q4_K_M.gguf',
        'qwen-2.5-14b'    => 'qwen2.5-14b-instruct-q4_k_m.gguf',
    ];

    /**
     * Per-model context window (`n_ctx`). We serve every Model Hub model with
     * 16384 tokens: the chat path requests up to 8192 output tokens, so the
     * window must hold prompt + output. The reported failure was a 6625-token
     * prompt against a 4096 window; 16384 comfortably fits an ~8K prompt plus an
     * ~8K reply. All these models have a >=32K native context, so 16384 is well
     * within range; the ceiling is KV-cache RAM, which the Model Hub already
     * gates on (required_ram_gb). Tunable per-model here if a machine is tight.
     */
    private const CONTEXT_SIZES = [
        'qwen-2.5-0.5b'   => 16384,
        'qwen-2.5-1.5b'   => 16384,
        'llama-3.2-3b'    => 16384,
        'mistral-7b-v0.3' => 16384,
        'llama-3.1-8b'    => 16384,
        'qwen-2.5-14b'    => 16384,
    ];

    private const DEFAULT_CONTEXT = 16384;

    /** Cache key holding the model code currently loaded into the running server. */
    private const CURRENT_KEY = 'local_gguf_current_model';

    public function port(): int
    {
        return (int) config('services.local_gguf.port', 8091);
    }

    public function baseUrl(): string
    {
        $configured = config('services.local_gguf.base_url');
        $url = !empty($configured) ? $configured : 'http://127.0.0.1:' . $this->port() . '/v1';
        return rtrim($url, '/');
    }

    /** True when this model code is a Model Hub GGUF we know how to serve. */
    public function isGgufModel(string $modelCode): bool
    {
        return isset(self::CATALOG[$modelCode]);
    }

    public function contextSizeFor(string $modelCode): int
    {
        return self::CONTEXT_SIZES[$modelCode] ?? self::DEFAULT_CONTEXT;
    }

    public function ggufPath(string $modelCode): ?string
    {
        if (!isset(self::CATALOG[$modelCode])) {
            return null;
        }
        $path = storage_path('app/models' . DIRECTORY_SEPARATOR . self::CATALOG[$modelCode]);
        return file_exists($path) && filesize($path) > 0 ? $path : null;
    }

    private function isPortOpen(): bool
    {
        $conn = @fsockopen('127.0.0.1', $this->port(), $errno, $errstr, 1);
        if (is_resource($conn)) {
            fclose($conn);
            return true;
        }
        return false;
    }

    /**
     * Ensure the GGUF engine is running and is serving the requested model.
     *
     * If a different GGUF model is currently loaded, the old server is stopped
     * and a new one is started with the correct file + context size. Returns the
     * base URL to send OpenAI-compatible chat requests to, or null if the model
     * file isn't downloaded (caller should surface a helpful error).
     */
    public function ensureRunning(string $modelCode): ?string
    {
        $ggufPath = $this->ggufPath($modelCode);
        if ($ggufPath === null) {
            return null;
        }

        $current = Cache::get(self::CURRENT_KEY);
        $alreadyServing = $current === $modelCode && $this->isPortOpen();

        if ($alreadyServing) {
            return $this->baseUrl();
        }

        // Switching models (or nothing is up): free the port, then (re)start.
        if ($this->isPortOpen()) {
            $this->killPort();
        }

        $this->startServer($modelCode, $ggufPath);

        // Wait briefly for the server to bind the port + load weights.
        for ($i = 0; $i < 30; $i++) {
            if ($this->isPortOpen()) {
                Cache::put(self::CURRENT_KEY, $modelCode, 3600);
                return $this->baseUrl();
            }
            usleep(500000); // 0.5s
        }

        Log::warning("Local GGUF engine did not come up for model {$modelCode} on port {$this->port()}.");
        // Return the URL anyway; the provider's connection error is more informative than a silent null.
        return $this->baseUrl();
    }

    private function startServer(string $modelCode, string $ggufPath): void
    {
        $node = config('services.local_gguf.node', 'node');
        $script = base_path('scripts/llama-server.mjs');
        $ctx = $this->contextSizeFor($modelCode);
        $port = $this->port();

        // node-llama-cpp (v3) ships no OpenAI `serve` command, so we run our own
        // thin OpenAI-compatible server script. The explicit --ctx is the Bug 1 fix.
        $cmd = sprintf(
            '%s "%s" --model "%s" --port %d --ctx %d --id "%s"',
            $node,
            $script,
            $ggufPath,
            $port,
            $ctx,
            $modelCode
        );

        Log::info("Starting local GGUF engine: {$cmd}");

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @pclose(@popen('start /B "" ' . $cmd . ' > NUL 2>&1', 'r'));
        } else {
            @exec('nohup ' . $cmd . ' > /dev/null 2>&1 &');
        }
    }

    /** Kill whatever process currently holds the GGUF engine port. */
    private function killPort(): void
    {
        $port = $this->port();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $out = [];
            @exec('netstat -ano | findstr :' . $port, $out);
            foreach ($out as $line) {
                if (preg_match('/LISTENING\s+(\d+)/', $line, $m)) {
                    @exec('taskkill /F /PID ' . (int) $m[1] . ' > NUL 2>&1');
                }
            }
        } else {
            @exec('fuser -k ' . $port . '/tcp > /dev/null 2>&1');
        }

        Cache::forget(self::CURRENT_KEY);
        sleep(1);
    }
}

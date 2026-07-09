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
        // Generation upgrade (perubahan.md #1): codes are stable keys, the
        // weights behind them are now Qwen3 (native <think>, 32K context).
        'qwen-2.5-0.5b'       => 'Qwen3-0.6B-Q8_0.gguf',
        'qwen-2.5-1.5b'       => 'Qwen3-1.7B-Q8_0.gguf',
        'rynude-lyric-plus-1' => 'Qwen3-1.7B-Lyric-Plus-Q8_0.gguf',
        'llama-3.2-3b'        => 'Qwen3-4B-Q4_K_M.gguf',
        'mistral-7b-v0.3' => 'Qwen3-8B-Q4_K_M.gguf',
        'llama-3.1-8b'    => 'Qwen3-14B-Q4_K_M.gguf',
        'qwen-2.5-14b'    => 'Qwen3-30B-A3B-Q4_K_M.gguf',
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
        'qwen-2.5-0.5b'       => 16384,
        'qwen-2.5-1.5b'       => 16384,
        'rynude-lyric-plus-1' => 16384,
        'llama-3.2-3b'        => 16384,
        'mistral-7b-v0.3' => 16384,
        'llama-3.1-8b'    => 16384,
        'qwen-2.5-14b'    => 16384,
    ];

    private const DEFAULT_CONTEXT = 16384;

    /**
     * Capability tier per model. Generation params and the system prompt are
     * tuned per-tier instead of one-size-fits-all: the old single profile was
     * tuned for Vignette 0.5B, which artificially capped the 7B–14B models.
     *
     *  - small: 0.5B–3B  — need low temperature + strict repeat penalties or
     *    they loop/parrot; get the slim system prompt.
     *  - large: 7B–14B  — follow instructions reliably; get a near-cloud
     *    system prompt and more natural sampling for deeper, richer output.
     */
    private const TIERS = [
        'qwen-2.5-0.5b'       => 'small',
        'qwen-2.5-1.5b'       => 'small',
        'rynude-lyric-plus-1' => 'small',
        // Qwen3-4B follows long instructions reliably — promoted to 'large'
        // so it gets the near-cloud prompt instead of the slim guardrail one.
        'llama-3.2-3b'    => 'large',
        'mistral-7b-v0.3' => 'large',
        'llama-3.1-8b'    => 'large',
        'qwen-2.5-14b'    => 'large',
    ];

    /**
     * Per-tier sampling profiles passed to llama-server.mjs as CLI args
     * (they override the env/default values inside the script).
     * Values follow the official Qwen3 recommendations (temp 0.6–0.7,
     * topP 0.95, topK 20) with a mild repeat penalty kept for the tiny
     * quantized models that still loop without it.
     */
    private const GEN_PROFILES = [
        'small' => [
            'temp' => 0.6, 'top-p' => 0.95, 'top-k' => 20,
            'repeat-penalty' => 1.1, 'freq-penalty' => 0.05, 'pres-penalty' => 0.1,
            'max-tokens' => 8192,
        ],
        'large' => [
            'temp' => 0.7, 'top-p' => 0.95, 'top-k' => 20,
            'repeat-penalty' => 1.05, 'freq-penalty' => 0.0, 'pres-penalty' => 0.05,
            'max-tokens' => 8192,
        ],
    ];

    /**
     * Optional local embedding model (semantic RAG, perubahan.md #6). This is
     * NOT a chat model — deliberately kept out of CATALOG so it can never be
     * selected/served for chat. When the file exists, llama-server.mjs loads
     * it alongside the chat model and serves POST /v1/embeddings.
     */
    public const EMBED_FILENAME = 'Qwen3-Embedding-0.6B-Q8_0.gguf';

    public function embedModelPath(): ?string
    {
        $path = storage_path('app/models' . DIRECTORY_SEPARATOR . self::EMBED_FILENAME);
        return file_exists($path) && filesize($path) > 0 ? $path : null;
    }

    /** Capability tier ('small'|'large') for a Model Hub GGUF code. */
    public function tierFor(string $modelCode): string
    {
        return self::TIERS[$modelCode] ?? 'small';
    }

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

        // Wait for the server to bind the port. The port only opens AFTER the
        // weights finish loading, and load time scales with file size: a 1.7B
        // model takes seconds, a 9GB 14B model can take minutes (disk read +
        // RAM/VRAM allocation). The old fixed 15s window made big models fail
        // with "Connection refused" while they were still loading.
        $sizeGb = max(1, (int) ceil((@filesize($ggufPath) ?: 0) / 1_000_000_000));
        $waitSeconds = min(420, 45 + $sizeGb * 30)
            + ($this->embedModelPath() !== null ? 30 : 0); // embedding model adds load time
        $deadline = time() + $waitSeconds;

        while (time() < $deadline) {
            if ($this->isPortOpen()) {
                Cache::put(self::CURRENT_KEY, $modelCode, 3600);
                return $this->baseUrl();
            }
            usleep(1000000); // 1s
        }

        Log::warning("Local GGUF engine did not come up for model {$modelCode} on port {$this->port()} after {$waitSeconds}s (file {$sizeGb}GB).");
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
        // Sampling args come from the model's capability tier so a 14B model is
        // no longer clamped to the guardrails a 0.5B model needs.
        $gen = self::GEN_PROFILES[$this->tierFor($modelCode)];
        $cmd = sprintf(
            '%s "%s" --model "%s" --port %d --ctx %d --id "%s"'
                . ' --temp %s --top-p %s --top-k %d --repeat-penalty %s'
                . ' --freq-penalty %s --pres-penalty %s --max-tokens %d',
            $node,
            $script,
            $ggufPath,
            $port,
            $ctx,
            $modelCode,
            $gen['temp'],
            $gen['top-p'],
            $gen['top-k'],
            $gen['repeat-penalty'],
            $gen['freq-penalty'],
            $gen['pres-penalty'],
            $gen['max-tokens']
        );

        // GPU offload (perubahan.md #7): 'auto' lets node-llama-cpp pick
        // CUDA/Vulkan/Metal and offload every layer that fits in VRAM.
        $gpu = config('services.local_gguf.gpu', 'auto');
        if (!empty($gpu)) {
            $cmd .= ' --gpu ' . escapeshellarg((string) $gpu);
        }
        $gpuLayers = config('services.local_gguf.gpu_layers');
        if (!empty($gpuLayers)) {
            $cmd .= ' --gpu-layers ' . escapeshellarg((string) $gpuLayers);
        }

        // Semantic RAG (perubahan.md #6): serve embeddings when the optional
        // embedding model has been downloaded from the Model Hub.
        if (($embedPath = $this->embedModelPath()) !== null) {
            $cmd .= ' --embed-model ' . escapeshellarg($embedPath);
        }

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

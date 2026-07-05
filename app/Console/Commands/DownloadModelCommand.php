<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class DownloadModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rynude:download-model {url} {filename} {model_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download a GGUF model file asynchronously in the background with progress tracking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $filename = $this->argument('filename');
        $modelId = $this->argument('model_id');

        $modelsDir = storage_path('app/models');
        if (!is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }

        $finalPath = $modelsDir . DIRECTORY_SEPARATOR . $filename;
        $partPath = $finalPath . '.part';

        // Check if already downloaded
        if (file_exists($finalPath) && filesize($finalPath) > 0) {
            try {
                $modelfilePath = $modelsDir . DIRECTORY_SEPARATOR . 'Modelfile.' . $modelId;
                @file_put_contents($modelfilePath, "FROM {$finalPath}");
                @exec("ollama create \"{$modelId}\" -f \"{$modelfilePath}\" > NUL 2>&1");
                @unlink($modelfilePath);
            } catch (\Throwable $e) {
                // Ignore if ollama not running/installed
            }

            Cache::put('model_download_' . $modelId, [
                'status' => 'completed',
                'progress' => 100,
                'downloaded_bytes' => filesize($finalPath),
                'total_bytes' => filesize($finalPath),
                'updated_at' => now()->toIso8601String(),
            ], 3600);
            $this->info("Model {$filename} already exists.");
            return 0;
        }

        // Initialize cache state
        Cache::put('model_download_' . $modelId, [
            'status' => 'downloading',
            'progress' => 0,
            'downloaded_bytes' => 0,
            'total_bytes' => 0,
            'updated_at' => now()->toIso8601String(),
        ], 3600);

        $lastUpdate = time();

        try {
            $client = new Client([
                'verify' => false, // Ensure SSL works in all local environments
            ]);

            $client->request('GET', $url, [
                'sink' => $partPath,
                'timeout' => 14400, // 4 hours max for large models
                'connect_timeout' => 30,
                'progress' => function ($totalDownload, $downloadedBytes) use ($modelId, &$lastUpdate) {
                    // Cek apakah unduhan dibatalkan/dihapus oleh pengguna
                    $currentState = Cache::get('model_download_' . $modelId);
                    if (!$currentState || (isset($currentState['status']) && $currentState['status'] === 'cancelled')) {
                        throw new \Exception('Download dibatalkan dan dihapus oleh pengguna.');
                    }

                    if ($totalDownload > 0 && (time() - $lastUpdate >= 1 || $downloadedBytes === $totalDownload)) {
                        $lastUpdate = time();
                        $progress = round(($downloadedBytes / $totalDownload) * 100, 1);
                        Cache::put('model_download_' . $modelId, [
                            'status' => 'downloading',
                            'progress' => $progress,
                            'downloaded_bytes' => $downloadedBytes,
                            'total_bytes' => $totalDownload,
                            'updated_at' => now()->toIso8601String(),
                        ], 3600);
                    }
                },
            ]);

            if (file_exists($partPath) && filesize($partPath) > 0) {
                rename($partPath, $finalPath);

                try {
                    $modelfilePath = $modelsDir . DIRECTORY_SEPARATOR . 'Modelfile.' . $modelId;
                    @file_put_contents($modelfilePath, "FROM {$finalPath}");
                    @exec("ollama create \"{$modelId}\" -f \"{$modelfilePath}\" > NUL 2>&1");
                    @unlink($modelfilePath);
                } catch (\Throwable $e) {
                    // Ignore if ollama not running/installed
                }

                Cache::put('model_download_' . $modelId, [
                    'status' => 'completed',
                    'progress' => 100,
                    'downloaded_bytes' => filesize($finalPath),
                    'total_bytes' => filesize($finalPath),
                    'updated_at' => now()->toIso8601String(),
                ], 3600);
                $this->info("Model {$filename} downloaded successfully.");
            } else {
                throw new \Exception('Koneksi terputus dari server Hugging Face (file 0 bytes). Silakan klik tombol Unduh kembali.');
            }
        } catch (\Throwable $e) {
            Log::error("Model download error for {$modelId}: " . $e->getMessage());
            if (file_exists($partPath)) {
                @unlink($partPath);
            }
            $currentState = Cache::get('model_download_' . $modelId);
            if (!($currentState && isset($currentState['status']) && $currentState['status'] === 'cancelled')) {
                Cache::put('model_download_' . $modelId, [
                    'status' => 'error',
                    'progress' => 0,
                    'message' => 'Gagal mengunduh: ' . $e->getMessage(),
                    'updated_at' => now()->toIso8601String(),
                ], 3600);
            } else {
                Cache::forget('model_download_' . $modelId);
            }
            return 1;
        }

        return 0;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ModelHubController extends Controller
{
    /**
     * Get the catalog of available GGUF models and their current local status.
     */
    public function index(): JsonResponse
    {
        $catalog = $this->getCatalog();
        $modelsDir = storage_path('app/models');

        if (!is_dir($modelsDir)) {
            @mkdir($modelsDir, 0755, true);
        }

        $freeSpaceBytes = @disk_free_space($modelsDir) ?: 0;
        $freeSpaceGb = round($freeSpaceBytes / (1024 * 1024 * 1024), 2);

        $models = array_map(function ($model) use ($modelsDir) {
            $filePath = $modelsDir . DIRECTORY_SEPARATOR . $model['filename'];
            $partPath = $filePath . '.part';

            $isDownloaded = file_exists($filePath) && filesize($filePath) > 0;
            $localSizeBytes = $isDownloaded ? filesize($filePath) : (file_exists($partPath) ? filesize($partPath) : 0);

            // Check cache for download progress
            $cacheState = Cache::get('model_download_' . $model['id']);
            
            $status = 'not_downloaded';
            $progress = 0;

            if ($isDownloaded) {
                $status = 'completed';
                $progress = 100;
            } elseif ($cacheState && isset($cacheState['status'])) {
                $status = $cacheState['status'];
                $progress = $cacheState['progress'] ?? 0;
            } elseif (file_exists($partPath) && $model['file_size_bytes'] > 0) {
                $status = 'downloading';
                $progress = round(($localSizeBytes / $model['file_size_bytes']) * 100, 1);
            }

            return array_merge($model, [
                'is_downloaded' => $isDownloaded,
                'local_size_bytes' => $localSizeBytes,
                'status' => $status,
                'progress' => $progress,
                'error_message' => $cacheState['message'] ?? null,
            ]);
        }, $catalog);

        return response()->json([
            'success' => true,
            'free_space_gb' => $freeSpaceGb,
            'models' => $models,
        ]);
    }

    /**
     * Start downloading a GGUF model in the background.
     */
    public function download(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_id' => 'required|string',
        ]);

        $modelId = $validated['model_id'];
        $catalog = collect($this->getCatalog())->keyBy('id');

        if (!$catalog->has($modelId)) {
            return response()->json([
                'success' => false,
                'message' => 'Model tidak ditemukan di katalog.',
            ], 404);
        }

        $model = $catalog->get($modelId);
        $modelsDir = storage_path('app/models');

        if (!is_dir($modelsDir)) {
            @mkdir($modelsDir, 0755, true);
        }

        // Check disk space (require model size + 500MB buffer)
        $freeSpace = @disk_free_space($modelsDir);
        if ($freeSpace !== false && $freeSpace < ($model['file_size_bytes'] + 500000000)) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang penyimpanan hardisk tidak mencukupi untuk mengunduh model ini.',
            ], 400);
        }

        // If running in unit tests, simulate immediate start
        if (app()->runningUnitTests()) {
            Cache::put('model_download_' . $modelId, [
                'status' => 'downloading',
                'progress' => 15.0,
                'downloaded_bytes' => 1000000,
                'total_bytes' => $model['file_size_bytes'],
                'updated_at' => now()->toIso8601String(),
            ], 3600);

            return response()->json([
                'success' => true,
                'message' => 'Proses unduhan dimulai (Test Simulation).',
                'status' => 'downloading',
            ]);
        }

        $finalPath = $modelsDir . DIRECTORY_SEPARATOR . $model['filename'];
        if (file_exists($finalPath) && filesize($finalPath) > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Model sudah terunduh di sistem.',
                'status' => 'completed',
            ]);
        }

        // Initialize cache state
        Cache::put('model_download_' . $modelId, [
            'status' => 'downloading',
            'progress' => 0.1,
            'downloaded_bytes' => 0,
            'total_bytes' => $model['file_size_bytes'],
            'updated_at' => now()->toIso8601String(),
        ], 3600);

        // Spawn background command
        $phpBinary = PHP_BINARY;
        $artisan = base_path('artisan');
        $url = $model['download_url'];
        $filename = $model['filename'];

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf('start /B "" "%s" "%s" rynude:download-model "%s" "%s" "%s"', $phpBinary, $artisan, $url, $filename, $modelId);
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = sprintf('nohup "%s" "%s" rynude:download-model "%s" "%s" "%s" > /dev/null 2>&1 &', $phpBinary, $artisan, $url, $filename, $modelId);
            exec($cmd);
        }

        return response()->json([
            'success' => true,
            'message' => "Unduhan model {$model['name']} telah dimulai di latar belakang.",
            'status' => 'downloading',
        ]);
    }

    /**
     * Get real-time progress for all downloads.
     */
    public function progress(): JsonResponse
    {
        $catalog = $this->getCatalog();
        $progressMap = [];

        foreach ($catalog as $model) {
            $cacheState = Cache::get('model_download_' . $model['id']);
            if ($cacheState) {
                $progressMap[$model['id']] = $cacheState;
            }
        }

        return response()->json([
            'success' => true,
            'progress' => $progressMap,
        ]);
    }

    /**
     * Delete a downloaded model file from local storage.
     */
    public function destroy(string $modelId): JsonResponse
    {
        $catalog = collect($this->getCatalog())->keyBy('id');

        if (!$catalog->has($modelId)) {
            return response()->json([
                'success' => false,
                'message' => 'Model tidak ditemukan di katalog.',
            ], 404);
        }

        $model = $catalog->get($modelId);
        $filePath = storage_path('app/models/' . $model['filename']);
        $partPath = $filePath . '.part';

        // Set status ke cancelled agar background worker Guzzle langsung berhenti
        $cacheState = Cache::get('model_download_' . $modelId);
        $wasDownloading = $cacheState && ($cacheState['status'] ?? '') === 'downloading';
        
        if ($wasDownloading) {
            Cache::put('model_download_' . $modelId, [
                'status' => 'cancelled',
                'message' => 'Unduhan dibatalkan oleh pengguna.',
            ], 60);
        } else {
            Cache::forget('model_download_' . $modelId);
        }

        $deleted = false;
        if (!app()->runningUnitTests()) {
            if (file_exists($filePath)) {
                $deleted = @unlink($filePath);
            }
            if (file_exists($partPath)) {
                @unlink($partPath);
            }
        } else {
            $deleted = true;
        }

        return response()->json([
            'success' => true,
            'message' => $wasDownloading ? "Unduhan model {$model['name']} berhasil dibatalkan dan file sementara dihapus." : "Model {$model['name']} berhasil dihapus.",
            'deleted' => $deleted || !file_exists($filePath),
        ]);
    }

    /**
     * Catalog of popular GGUF models.
     *
     * Generation upgrade (perubahan.md #1): the rynude names and catalog ids are
     * stable keys (DB rows, user model selections, LlamaServerService maps all
     * reference them), so upgrading the underlying weights only swaps the
     * filename/download_url here + the filename map in LlamaServerService.
     * Current generation: Qwen3 (native <think> reasoning, 32K context).
     * Users re-download the file; everything else keeps working.
     */
    private function getCatalog(): array
    {
        return [
            [
                'id' => 'qwen-2.5-0.5b',
                'name' => 'rynude Vignette',
                'description' => 'Arsitektur rynude-v3 Ultra-Light. Model sangat ringan dan cepat dengan kemampuan penalaran logika bawaan. Sangat cocok untuk pengujian sistem, komputer berspesifikasi rendah (< 8GB RAM), atau tugas percakapan sehari-hari.',
                'parameter_size' => '0.6B',
                'required_ram_gb' => 2.0,
                'file_size_label' => '~640 MB',
                'file_size_bytes' => 665000000,
                'filename' => 'Qwen3-0.6B-Q8_0.gguf',
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-0.6B-GGUF/resolve/main/Qwen3-0.6B-Q8_0.gguf',
                'recommended' => false,
                'recommended_for' => ['low', 'medium', 'high'],
            ],
            [
                'id' => 'qwen-2.5-1.5b',
                'name' => 'rynude Lyric 4.5',
                'description' => 'Arsitektur rynude-v3 Compact. Rekomendasi utama untuk sebagian besar pengguna: memiliki pemahaman konteks mendalam dan penalaran cerdas, namun tetap sangat ringan dijalankan pada komputer berspesifikasi terbatas.',
                'parameter_size' => '1.7B',
                'required_ram_gb' => 4.0,
                'file_size_label' => '1.8 GB',
                'file_size_bytes' => 1830000000,
                'filename' => 'Qwen3-1.7B-Q8_0.gguf',
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-1.7B-GGUF/resolve/main/Qwen3-1.7B-Q8_0.gguf',
                'recommended' => true,
                'recommended_for' => ['low', 'medium', 'high'],
            ],
            [
                'id' => 'rynude-lyric-plus-1',
                'name' => 'rynude Lyric 4.6',
                'description' => 'Edisi Khusus rynude Lyric Plus. Dilatih secara mendalam (Fine-Tuned) khusus untuk Bahasa Indonesia natural, format penulisan skripsi/akademik baku, serta kepatuhan struktur dokumen (Artifacts). Kualitas presisi tinggi (F16).',
                'parameter_size' => '1.7B (LoRA)',
                'required_ram_gb' => 6.0,
                'file_size_label' => '3.45 GB',
                'file_size_bytes' => 3447349408,
                'filename' => 'rynude-lyric-4.6.gguf',
                'download_url' => 'https://huggingface.co/flustratechcompany/rynude-lyric-4.6-gguf/resolve/main/rynude-lyric-4.6.gguf',
                'recommended' => false,
                'recommended_for' => ['low', 'medium', 'high'],
            ],
            [
                'id' => 'rynude-lyric-plus-2',
                'name' => 'rynude Lyric 4.7',
                'description' => 'Upgrade dari Lyric 4.6: mewarisi kemampuan 4.6 plus perbaikan kepatuhan dokumen (selalu keluarkan artifact + front-matter, tidak menolak membuat file), anti-halusinasi, serta matematika & diagram. Dilatih ulang (QLoRA) di atas data 4.6 + data perbaikan. Kuantisasi Q8_0, lebih ringan.',
                'parameter_size' => '1.7B (LoRA)',
                'required_ram_gb' => 4.0,
                'file_size_label' => '1.83 GB',
                'file_size_bytes' => 1834426048,
                'filename' => 'rynude-lyric-4.7.gguf',
                'download_url' => 'https://huggingface.co/flustratechcompany/rynude-lyric-4.7/resolve/main/rynude-lyric-4.7.gguf',
                'recommended' => true,
                'recommended_for' => ['low', 'medium', 'high'],
            ],
            [
                'id' => 'llama-3.2-3b',
                'name' => 'rynude Stanza',
                'description' => 'Arsitektur rynude-v3 Mid-Scale. Kualitas pemahaman setara model kelas atas — sangat efisien dan akurat untuk analisis dokumen, penalaran logis, serta asisten coding tingkat menengah.',
                'parameter_size' => '4B',
                'required_ram_gb' => 6.0,
                'file_size_label' => '2.5 GB',
                'file_size_bytes' => 2500000000,
                'filename' => 'Qwen3-4B-Q4_K_M.gguf',
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-4B-GGUF/resolve/main/Qwen3-4B-Q4_K_M.gguf',
                'recommended' => false,
                'recommended_for' => ['low', 'medium', 'high'],
            ],
            [
                'id' => 'mistral-7b-v0.3',
                'name' => 'rynude Canto',
                'description' => 'Arsitektur rynude-v3 Performance. Memiliki kemampuan penalaran logika dan pemrograman yang sangat kuat, dirancang khusus untuk efisiensi tinggi pada tugas teknis dan analitis yang kompleks.',
                'parameter_size' => '8B',
                'required_ram_gb' => 10.0,
                'file_size_label' => '5.0 GB',
                'file_size_bytes' => 5030000000,
                'filename' => 'Qwen3-8B-Q4_K_M.gguf',
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-8B-GGUF/resolve/main/Qwen3-8B-Q4_K_M.gguf',
                'recommended' => false,
                'recommended_for' => ['medium', 'high'],
            ],
            [
                'id' => 'llama-3.1-8b',
                'name' => 'rynude Symphony',
                'description' => 'Arsitektur rynude-v3 Flagship. Model serba bisa kelas atas untuk percakapan mendalam tingkat lanjut, pemrosesan dokumen panjang, serta pemecahan masalah algoritma rumit.',
                'parameter_size' => '14B',
                'required_ram_gb' => 16.0,
                'file_size_label' => '9.0 GB',
                'file_size_bytes' => 9000000000,
                'filename' => 'Qwen3-14B-Q4_K_M.gguf',
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-14B-GGUF/resolve/main/Qwen3-14B-Q4_K_M.gguf',
                'recommended' => false,
                'recommended_for' => ['medium', 'high'],
            ],
            [
                'id' => 'qwen-2.5-14b',
                'name' => 'rynude Magnum',
                'description' => 'Arsitektur rynude-v3 Ultimate (Mixture-of-Experts). Model kelas berat tingkat lanjut yang menggabungkan kecerdasan masif dengan kecepatan pemrosesan efisien berkat aktivasi parameter selektif.',
                'parameter_size' => '30B (MoE)',
                'required_ram_gb' => 24.0,
                'file_size_label' => '18.6 GB',
                'file_size_bytes' => 18600000000,
                'filename' => 'Qwen3-30B-A3B-Q4_K_M.gguf',
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-30B-A3B-GGUF/resolve/main/Qwen3-30B-A3B-Q4_K_M.gguf',
                'recommended' => false,
                'recommended_for' => ['high'],
            ],
            [
                'id' => 'rynude-embed-0.6b',
                'name' => 'rynude Sense (Modul Pemahaman Makna)',
                'description' => 'BUKAN model chat — modul tambahan RAG semantik: membuat pembacaan dokumen lampiran memahami MAKNA kalimat (mis. "dampak finansial" ≈ "pengaruh terhadap pendapatan"), bukan sekadar kecocokan kata. Aktif otomatis setelah diunduh, bekerja mendampingi model chat lokal mana pun.',
                'parameter_size' => '0.6B (embedding)',
                'required_ram_gb' => 1.5,
                'file_size_label' => '~640 MB',
                'file_size_bytes' => 665000000,
                'filename' => \App\Services\LlamaServerService::EMBED_FILENAME,
                'download_url' => 'https://huggingface.co/Qwen/Qwen3-Embedding-0.6B-GGUF/resolve/main/Qwen3-Embedding-0.6B-Q8_0.gguf',
                'recommended' => false,
                'recommended_for' => ['low', 'medium', 'high'],
            ],
        ];
    }
}

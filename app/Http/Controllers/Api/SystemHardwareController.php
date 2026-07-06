<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SystemHardwareController extends Controller
{
    /**
     * Detect system hardware specifications (RAM, OS, GPU) and provide
     * AI model parameter size recommendations.
     */
    public function detect(): JsonResponse
    {
        $fallbackUsed = false;
        $totalRamGb = null;
        $osFamily = PHP_OS_FAMILY;

        try {
            if ($osFamily === 'Windows') {
                $totalRamGb = $this->getWindowsRamGb();
            } elseif ($osFamily === 'Linux') {
                $totalRamGb = $this->getLinuxRamGb();
            } elseif ($osFamily === 'Darwin') {
                $totalRamGb = $this->getMacRamGb();
            }
        } catch (\Throwable $e) {
            Log::error('Hardware detection error: ' . $e->getMessage());
        }

        if ($totalRamGb === null || $totalRamGb <= 0) {
            $totalRamGb = 8.0; // Safe fallback to 8GB to guarantee zero-bug operation
            $fallbackUsed = true;
        }

        $gpuInfo = $this->detectGpu();

        // Calculate effective capability
        $effectiveRam = $totalRamGb;
        if ($gpuInfo['has_gpu'] && $gpuInfo['vram_gb'] > 0) {
            $effectiveRam += ($gpuInfo['vram_gb'] * 0.5); // Give bonus rating for dedicated GPU VRAM
        }

        if ($effectiveRam < 8.0) {
            $status = 'low';
            $maxParam = '3B';
            $message = 'RAM terbatas (< 8 GB). Sangat disarankan mengunduh model kecil (1.5B - 3B) agar sistem tidak lambat.';
        } elseif ($effectiveRam < 16.0) {
            $status = 'medium';
            $maxParam = '7B';
            $message = 'Spesifikasi memadai (8 - 15 GB RAM). Optimal untuk menjalankan model ukuran menengah hingga 7B - 8B parameter.';
        } else {
            $status = 'high';
            $maxParam = '32B+';
            $message = 'Spesifikasi tinggi (>= 16 GB RAM). Anda dapat menjalankan model besar (14B, 32B, atau lebih) dengan lancar.';
        }

        return response()->json([
            'success' => true,
            'os' => strtolower($osFamily),
            'total_ram_gb' => $totalRamGb,
            'has_gpu' => $gpuInfo['has_gpu'],
            'gpu_name' => $gpuInfo['gpu_name'],
            'vram_gb' => $gpuInfo['vram_gb'],
            'recommendation' => [
                'status' => $status,
                'max_parameter_size' => $maxParam,
                'message' => $message,
            ],
            'fallback_used' => $fallbackUsed,
        ]);
    }

    /**
     * Get physical RAM in GB on Windows using PowerShell or WMIC fallback.
     */
    private function getWindowsRamGb(): ?float
    {
        try {
            // Try PowerShell first (fast & reliable on modern Windows 10/11)
            $process = Process::fromShellCommandline('powershell -NoProfile -Command "(Get-CimInstance Win32_PhysicalMemory | Measure-Object -Property Capacity -Sum).Sum"');
            $process->setTimeout(3);
            $process->run();

            if ($process->isSuccessful() && !empty(trim($process->getOutput()))) {
                $bytes = (float) trim($process->getOutput());
                if ($bytes > 0) {
                    return round($bytes / (1024 * 1024 * 1024), 2);
                }
            }

            // Fallback to wmic if PowerShell failed
            $processWmic = Process::fromShellCommandline('wmic MemoryChip get Capacity');
            $processWmic->setTimeout(3);
            $processWmic->run();

            if ($processWmic->isSuccessful()) {
                $lines = explode("\n", trim($processWmic->getOutput()));
                $totalBytes = 0;
                foreach ($lines as $line) {
                    $val = (float) trim($line);
                    if ($val > 0) {
                        $totalBytes += $val;
                    }
                }
                if ($totalBytes > 0) {
                    return round($totalBytes / (1024 * 1024 * 1024), 2);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Windows RAM detection failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get physical RAM in GB on Linux using /proc/meminfo or free -b.
     */
    private function getLinuxRamGb(): ?float
    {
        try {
            if (file_exists('/proc/meminfo')) {
                $meminfo = file_get_contents('/proc/meminfo');
                if (preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $matches)) {
                    $kb = (float) $matches[1];
                    return round($kb / (1024 * 1024), 2);
                }
            }

            $process = Process::fromShellCommandline('free -b | grep Mem | awk \'{print $2}\'');
            $process->setTimeout(3);
            $process->run();
            if ($process->isSuccessful() && !empty(trim($process->getOutput()))) {
                $bytes = (float) trim($process->getOutput());
                if ($bytes > 0) {
                    return round($bytes / (1024 * 1024 * 1024), 2);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Linux RAM detection failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get physical RAM in GB on macOS using sysctl.
     */
    private function getMacRamGb(): ?float
    {
        try {
            $process = Process::fromShellCommandline('sysctl -n hw.memsize');
            $process->setTimeout(3);
            $process->run();
            if ($process->isSuccessful() && !empty(trim($process->getOutput()))) {
                $bytes = (float) trim($process->getOutput());
                if ($bytes > 0) {
                    return round($bytes / (1024 * 1024 * 1024), 2);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('macOS RAM detection failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Detect NVIDIA GPU and VRAM if available.
     */
    private function detectGpu(): array
    {
        try {
            $process = Process::fromShellCommandline('nvidia-smi --query-gpu=name,memory.total --format=csv,noheader,nounits');
            $process->setTimeout(2);
            $process->run();

            if ($process->isSuccessful() && !empty(trim($process->getOutput()))) {
                $lines = explode("\n", trim($process->getOutput()));
                $parts = explode(',', trim($lines[0]));
                if (count($parts) >= 2) {
                    $name = trim($parts[0]);
                    $vramMb = (float) trim($parts[1]);
                    return [
                        'has_gpu' => true,
                        'gpu_name' => $name,
                        'vram_gb' => round($vramMb / 1024, 2),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // nvidia-smi not available or failed, ignore silently
        }

        return [
            'has_gpu' => false,
            'gpu_name' => null,
            'vram_gb' => null,
        ];
    }
}

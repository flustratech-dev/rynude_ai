<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class LocalWorkspaceService
{
    /**
     * Directories to ignore when scanning.
     */
    protected array $ignoreDirs = [
        '.git',
        'node_modules',
        'vendor',
        'storage/framework',
        'storage/logs',
        '.vscode',
        '.idea',
    ];

    /**
     * Fetch a flat tree of files and directories in the workspace, matching the format expected by AgentTools.
     */
    public function fetchTree(string $basePath, ?int $maxDepth = null): array
    {
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        if (!is_dir($basePath)) {
            return [];
        }

        $gitignorePatterns = $this->getGitignorePatterns($basePath);

        $tree = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace('\\', '/', str_replace($basePath . '/', '', $file->getPathname()));
            
            // Check ignore patterns (explicit list + gitignore)
            if ($this->isIgnored($relativePath, $gitignorePatterns)) {
                continue;
            }

            // Depth calculation relative to workspace root
            $depth = substr_count($relativePath, '/');
            if ($maxDepth !== null && $depth > $maxDepth) {
                continue;
            }

            $type = $file->isDir() ? 'tree' : 'blob'; // 'tree' maps to 'dir', 'blob' maps to 'file' in UI logic
            $tree[] = [
                'path' => $relativePath,
                'type' => $type === 'tree' ? 'dir' : 'file',
                'size' => $file->isDir() ? 0 : $file->getSize(),
            ];
        }

        return $tree;
    }

    /**
     * Parse patterns from .gitignore and .rynudeignore
     */
    protected function getGitignorePatterns(string $basePath): array
    {
        $patterns = [];
        foreach (['.gitignore', '.rynudeignore'] as $filename) {
            $path = $basePath . '/' . $filename;
            if (file_exists($path)) {
                $lines = explode("\n", file_get_contents($path));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    $patterns[] = $line;
                }
            }
        }
        return array_unique($patterns);
    }

    /**
     * Check if path matches ignore patterns
     */
    protected function isIgnored(string $relativePath, array $patterns): bool
    {
        // Explicitly ignore .rynude folder (backups, settings) to avoid loops
        if ($relativePath === '.rynude' || str_starts_with($relativePath, '.rynude/')) {
            return true;
        }

        // 1. Check hardcoded directories
        foreach ($this->ignoreDirs as $ignoreDir) {
            if ($relativePath === $ignoreDir || str_starts_with($relativePath, $ignoreDir . '/')) {
                return true;
            }
        }

        // 2. Check gitignore / rynudeignore patterns
        foreach ($patterns as $pattern) {
            $isDirPattern = str_ends_with($pattern, '/');
            $cleanPattern = rtrim($pattern, '/');

            // Handle glob patterns with '*'
            if (str_contains($cleanPattern, '*')) {
                if (fnmatch($cleanPattern, $relativePath) || 
                    fnmatch($cleanPattern . '/*', $relativePath) || 
                    fnmatch('*/' . $cleanPattern, $relativePath)) {
                    return true;
                }
            } else {
                // Exact match or folder match
                if ($relativePath === $cleanPattern || 
                    str_starts_with($relativePath, $cleanPattern . '/') || 
                    str_contains($relativePath, '/' . $cleanPattern . '/')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Read the content of a file.
     */
    public function fetchFileContent(string $basePath, string $relativePath): ?string
    {
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        
        if (File::exists($fullPath) && File::isFile($fullPath)) {
            return File::get($fullPath);
        }

        return null;
    }

    /**
     * Write content to a file (creates or overwrites).
     */
    public function writeFile(string $basePath, string $relativePath, string $content): bool
    {
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        
        // Backup before overwrite if file already exists
        if (File::exists($fullPath) && File::isFile($fullPath)) {
            $this->backupFile($basePath, $relativePath);
        }

        $directory = dirname($fullPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return File::put($fullPath, $content) !== false;
    }

    /**
     * Edit a file by replacing a specific string (with optional line targeting).
     */
    public function replaceInFile(string $basePath, string $relativePath, string $search, string $replace, ?int $startLine = null, ?int $endLine = null): bool
    {
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        
        if (!File::exists($fullPath) || !File::isFile($fullPath)) {
            return false;
        }

        $content = File::get($fullPath);
        $newContent = '';

        if ($startLine !== null && $endLine !== null) {
            $lines = explode("\n", $content);
            $totalLines = count($lines);

            $startIdx = max(1, $startLine) - 1;
            $endIdx = min($totalLines, $endLine) - 1;

            if ($startIdx > $endIdx || $startIdx >= $totalLines) {
                return false;
            }

            $slice = array_slice($lines, $startIdx, $endIdx - $startIdx + 1);
            $sliceContent = implode("\n", $slice);

            if (!str_contains($sliceContent, $search)) {
                return false;
            }

            $newSliceContent = str_replace($search, $replace, $sliceContent);

            array_splice($lines, $startIdx, $endIdx - $startIdx + 1, explode("\n", $newSliceContent));
            $newContent = implode("\n", $lines);
        } else {
            $newContent = str_replace($search, $replace, $content);
        }
        
        if ($content !== $newContent) {
            // Backup before editing
            $this->backupFile($basePath, $relativePath);
            return File::put($fullPath, $newContent) !== false;
        }

        return false;
    }

    /**
     * Backup file before overwrite or edit.
     */
    protected function backupFile(string $basePath, string $relativePath): void
    {
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        if (!File::exists($fullPath) || !File::isFile($fullPath)) {
            return;
        }

        $backupRoot = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . '.rynude' . DIRECTORY_SEPARATOR . 'backups';
        $manifestPath = $backupRoot . DIRECTORY_SEPARATOR . 'manifest.json';

        $manifest = [];
        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true) ?: [];
        }

        $timestamp = date('Ymd_His');
        $backupFilename = $timestamp . '_' . uniqid() . '_' . str_replace(['/', '\\'], '_', $relativePath);
        $backupPath = $backupRoot . DIRECTORY_SEPARATOR . $backupFilename;

        if (!File::isDirectory($backupRoot)) {
            File::makeDirectory($backupRoot, 0755, true, true);
        }

        File::copy($fullPath, $backupPath);

        $manifest[] = [
            'timestamp' => time(),
            'relative_path' => $relativePath,
            'backup_file' => $backupFilename,
        ];
        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Restore the last backup file, popping it from the manifest stack.
     */
    public function restoreLastBackup(string $basePath): ?string
    {
        $backupRoot = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . '.rynude' . DIRECTORY_SEPARATOR . 'backups';
        $manifestPath = $backupRoot . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!File::exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode(File::get($manifestPath), true) ?: [];
        if (empty($manifest)) {
            return null;
        }

        $lastBackup = array_pop($manifest);
        $backupFile = $lastBackup['backup_file'];
        $relativePath = $lastBackup['relative_path'];

        $backupPath = $backupRoot . DIRECTORY_SEPARATOR . $backupFile;
        $originalFullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');

        if (File::exists($backupPath)) {
            $dir = dirname($originalFullPath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
            File::copy($backupPath, $originalFullPath);
            File::delete($backupPath);

            File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
            return $relativePath;
        }

        return null;
    }
}

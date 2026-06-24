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
    public function fetchTree(string $basePath): array
    {
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        if (!is_dir($basePath)) {
            return [];
        }

        $tree = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace('\\', '/', str_replace($basePath . '/', '', $file->getPathname()));
            
            // Skip ignored directories
            $skip = false;
            foreach ($this->ignoreDirs as $ignoreDir) {
                if ($relativePath === $ignoreDir || str_starts_with($relativePath, $ignoreDir . '/')) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
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
        $directory = dirname($fullPath);
        
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return File::put($fullPath, $content) !== false;
    }

    /**
     * Edit a file by replacing a specific string.
     */
    public function replaceInFile(string $basePath, string $relativePath, string $search, string $replace): bool
    {
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        
        if (!File::exists($fullPath) || !File::isFile($fullPath)) {
            return false;
        }

        $content = File::get($fullPath);
        $newContent = str_replace($search, $replace, $content);
        
        if ($content !== $newContent) {
            return File::put($fullPath, $newContent) !== false;
        }

        return false;
    }
}

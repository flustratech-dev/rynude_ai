<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\File;

class WorkspaceContext
{
    /**
     * Scan workspace directory and generate text context containing stack info and instructions.
     */
    public static function getContext(string $workspacePath): string
    {
        if (empty($workspacePath) || !is_dir($workspacePath)) {
            return '';
        }

        $context = "\n\n=== WORKSPACE CONTEXT ===";

        // 1. Tech Stack Detection
        $techStack = [];
        if (file_exists($workspacePath . '/composer.json')) {
            $techStack[] = 'PHP (Composer)';
            $composerJson = json_decode(file_get_contents($workspacePath . '/composer.json'), true);
            if (isset($composerJson['require']['laravel/framework'])) {
                $techStack[] = 'Laravel Framework (' . $composerJson['require']['laravel/framework'] . ')';
            }
        }
        if (file_exists($workspacePath . '/package.json')) {
            $techStack[] = 'Node.js (npm)';
            $packageJson = json_decode(file_get_contents($workspacePath . '/package.json'), true);
            if (isset($packageJson['dependencies']['react']) || isset($packageJson['devDependencies']['react'])) {
                $techStack[] = 'React';
            }
            if (isset($packageJson['dependencies']['next']) || isset($packageJson['devDependencies']['next'])) {
                $techStack[] = 'Next.js';
            }
            if (isset($packageJson['dependencies']['vue']) || isset($packageJson['devDependencies']['vue'])) {
                $techStack[] = 'Vue.js';
            }
        }
        if (file_exists($workspacePath . '/requirements.txt') || file_exists($workspacePath . '/Pipfile')) {
            $techStack[] = 'Python';
        }
        
        if (!empty($techStack)) {
            $context .= "\nDetected Tech Stack: " . implode(', ', array_unique($techStack));
        }

        // 2. Load instructions files (RYNUDE.md, AGENTS.md, README.md)
        foreach (['RYNUDE.md', 'AGENTS.md', 'README.md'] as $file) {
            $path = $workspacePath . '/' . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $context .= "\n\n[File: {$file}]\n" . trim($content);
            }
        }

        // 3. Load Claude settings rules from .claude/settings.json
        $settingsPath = $workspacePath . '/.claude/settings.json';
        if (file_exists($settingsPath)) {
            $content = file_get_contents($settingsPath);
            $context .= "\n\n[Claude Settings]\n" . trim($content);
        }

        return $context;
    }
}

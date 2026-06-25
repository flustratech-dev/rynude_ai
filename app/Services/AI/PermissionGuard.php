<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\File;

class PermissionGuard
{
    /**
     * Dangerous tools that require permission.
     */
    protected array $dangerousTools = [
        'bash',
        'write_file',
        'edit_file',
        'multi_edit_file',
    ];

    /**
     * Approved patterns stored for this session.
     * Format: [tool_name => [pattern1, pattern2, ...]]
     */
    protected static array $approvedPatterns = [];

    /**
     * Determine if a tool execution is approved.
     */
    public function isApproved(string $toolName, array $input): bool
    {
        if (!in_array($toolName, $this->dangerousTools)) {
            return true;
        }

        // Check if there is an environment variable or config bypassing permissions
        if (env('DANGEROUSLY_SKIP_PERMISSIONS') === true || env('RYNUDE_AUTO_APPROVE') === true) {
            return true;
        }

        // Check static approved patterns first
        $target = $this->getPermissionTarget($toolName, $input);
        if ($this->checkApprovedPatterns($toolName, $target)) {
            return true;
        }

        return false;
    }

    /**
     * Ask user for permission. Blocks in CLI, auto-approves or checks session in Web UI.
     */
    public function askPermission(string $toolName, array $input, string $workspacePath): bool
    {
        if ($this->isApproved($toolName, $input)) {
            return true;
        }

        $target = $this->getPermissionTarget($toolName, $input);

        // If running in CLI/console, ask using Laravel Prompts
        if (app()->runningInConsole()) {
            $inputStr = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            // Show diff preview if writing/editing files
            $diffPreview = '';
            if (in_array($toolName, ['write_file', 'edit_file', 'multi_edit_file']) && !empty($input['path'])) {
                $diffPreview = $this->generateDiffPreview($toolName, $input, $workspacePath);
            }

            // Format a nice prompt
            $label = "\n⚠️  RynudeCode wants to run '{$toolName}' on target:\n" .
                     "   " . trim(str_replace("\n", "\n   ", $target)) . "\n";
                     
            if ($diffPreview !== '') {
                $label .= "\n--- Proposed Changes (Diff) ---\n" . $diffPreview . "\n";
            } else {
                $label .= "   Full Input:\n" . 
                          "   " . trim(str_replace("\n", "\n   ", $inputStr)) . "\n";
            }
            
            $label .= "\n   Do you want to allow this operation?";

            // In Laravel Prompts, confirm returns true/false
            $confirmed = \Laravel\Prompts\confirm(
                label: $label,
                default: false
            );

            if ($confirmed) {
                // Cache approval for this session
                $this->approvePattern($toolName, $target);
                return true;
            }

            return false;
        }

        // If in Web UI: For now, we can check if it's explicitly approved in session,
        // otherwise default to true to keep it working
        if (session('auto_approve_web', true)) {
            return true;
        }

        return false;
    }

    /**
     * Extract a simplified target string for the operation.
     */
    public function getPermissionTarget(string $toolName, array $input): string
    {
        return match ($toolName) {
            'bash' => $input['command'] ?? '',
            'write_file', 'edit_file', 'multi_edit_file' => $input['path'] ?? '',
            default => json_encode($input),
        };
    }

    /**
     * Generate diff preview for tool call.
     */
    public function generateDiffPreview(string $toolName, array $input, string $workspacePath): string
    {
        $relativePath = $input['path'] ?? '';
        if ($relativePath === '') return '';

        $fullPath = rtrim($workspacePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        
        $oldContent = '';
        if (File::exists($fullPath) && File::isFile($fullPath)) {
            $oldContent = File::get($fullPath);
        }

        $newContent = '';
        if ($toolName === 'write_file') {
            $newContent = $input['content'] ?? '';
        } elseif ($toolName === 'edit_file') {
            $search = $input['search'] ?? ($input['old_text'] ?? '');
            $replace = $input['replace'] ?? ($input['new_text'] ?? '');
            $startLine = $input['start_line'] ?? null;
            $endLine = $input['end_line'] ?? null;

            if ($startLine !== null && $endLine !== null) {
                $lines = explode("\n", $oldContent);
                $totalLines = count($lines);
                $startIdx = max(1, $startLine) - 1;
                $endIdx = min($totalLines, $endLine) - 1;
                if ($startIdx <= $endIdx && $startIdx < $totalLines) {
                    $slice = array_slice($lines, $startIdx, $endIdx - $startIdx + 1);
                    $sliceContent = implode("\n", $slice);
                    $newSliceContent = str_replace($search, $replace, $sliceContent);
                    array_splice($lines, $startIdx, $endIdx - $startIdx + 1, explode("\n", $newSliceContent));
                    $newContent = implode("\n", $lines);
                } else {
                    $newContent = str_replace($search, $replace, $oldContent);
                }
            } else {
                $newContent = str_replace($search, $replace, $oldContent);
            }
        } elseif ($toolName === 'multi_edit_file') {
            $edits = $input['edits'] ?? [];
            $tempContent = $oldContent;
            foreach ($edits as $edit) {
                $search = $edit['search'] ?? ($edit['old_text'] ?? '');
                $replace = $edit['replace'] ?? ($edit['new_text'] ?? '');
                $startLine = $edit['start_line'] ?? null;
                $endLine = $edit['end_line'] ?? null;

                if ($startLine !== null && $endLine !== null) {
                    $lines = explode("\n", $tempContent);
                    $totalLines = count($lines);
                    $startIdx = max(1, $startLine) - 1;
                    $endIdx = min($totalLines, $endLine) - 1;
                    if ($startIdx <= $endIdx && $startIdx < $totalLines) {
                        $slice = array_slice($lines, $startIdx, $endIdx - $startIdx + 1);
                        $sliceContent = implode("\n", $slice);
                        $newSliceContent = str_replace($search, $replace, $sliceContent);
                        array_splice($lines, $startIdx, $endIdx - $startIdx + 1, explode("\n", $newSliceContent));
                        $tempContent = implode("\n", $lines);
                    } else {
                        $tempContent = str_replace($search, $replace, $tempContent);
                    }
                } else {
                    $tempContent = str_replace($search, $replace, $tempContent);
                }
            }
            $newContent = $tempContent;
        }

        if ($oldContent === $newContent) {
            return "   (No changes to apply)";
        }

        return DiffRenderer::render($oldContent, $newContent);
    }

    /**
     * Add a target pattern to approved list.
     */
    public function approvePattern(string $toolName, string $target): void
    {
        if (!isset(self::$approvedPatterns[$toolName])) {
            self::$approvedPatterns[$toolName] = [];
        }
        self::$approvedPatterns[$toolName][] = $target;
    }

    /**
     * Check if the target is already approved.
     */
    protected function checkApprovedPatterns(string $toolName, string $target): bool
    {
        if (!isset(self::$approvedPatterns[$toolName])) {
            return false;
        }

        foreach (self::$approvedPatterns[$toolName] as $approved) {
            // Exact match
            if ($approved === $target) {
                return true;
            }
            // For bash, allow simple prefix/wildcard match
            if ($toolName === 'bash') {
                if (str_starts_with($target, $approved)) {
                    return true;
                }
            }
        }

        return false;
    }
    /**
     * Get all currently approved patterns.
     */
    public static function getApprovedPatterns(): array
    {
        return self::$approvedPatterns;
    }

    /**
     * Clear all cached approved patterns.
     */
    public static function resetApprovedPatterns(): void
    {
        self::$approvedPatterns = [];
    }
}

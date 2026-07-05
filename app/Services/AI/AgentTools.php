<?php

namespace App\Services\AI;

use App\Services\GitHubService;
use App\Services\WebSearchService;
use App\Services\LocalWorkspaceService;
use App\Services\AI\PermissionGuard;
use App\Services\AI\OutputCompressor;
use App\Services\AI\RtkTracker;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
     * Tool registry the agent loop (AgentRunner) can call autonomously.
 */
class AgentTools
{
    /** Max bytes returned by read_file, to keep the context window sane. */
    private const MAX_FILE_BYTES = 60000;

    /** Files the agent opened this turn — merged back into the UI's Files panel. */
    private array $openedFiles = [];

    /** Metrics tracking. */
    private array $metrics = [
        'calls_count' => 0,
        'bytes_read' => 0,
        'bytes_written' => 0,
        'total_duration' => 0.0,
        'history' => []
    ];

    /** Current tool call ID being executed. */
    private ?string $currentToolCallId = null;

    public function setCurrentToolCallId(?string $id): void
    {
        $this->currentToolCallId = $id;
    }

    public function getCurrentToolCallId(): ?string
    {
        return $this->currentToolCallId;
    }

    /**
     * @param string $repoConnected        "owner/repo" or '' when no repo is connected
     * @param array  $repoTree             flat depth list from ClaudeCodeApp::$repoTree
     * @param array  $localFiles           ClaudeCodeApp::$localFilesTree (uploaded files)
     * @param array  $uploadedContents     [path => content] for uploaded files already read
     * @param string|null $githubToken     user's GitHub PAT for private repos / code search
     * @param string $localWorkspacePath   Local directory path for the CLI or Web UI when exploring local files
     */
    public function __construct(
        private string $repoConnected = '',
        private array $repoTree = [],
        private array $localFiles = [],
        private array $uploadedContents = [],
        private ?string $githubToken = null,
        private string $localWorkspacePath = '',
    ) {}

    public function openedFiles(): array
    {
        return $this->openedFiles;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Whether there are any tools worth offering.
     */
    public function hasTools(): bool
    {
        return count($this->schemas()) > 0;
    }

    /**
     * Unified tool schemas (mapped to each provider's native shape by the provider).
     */
    public function schemas(): array
    {
        $tools = [];

        $hasRepo  = $this->repoConnected !== '';
        $hasLocal = count($this->localFiles) > 0;
        $hasWorkspace = $this->localWorkspacePath !== '';

        if ($hasRepo || $hasLocal || $hasWorkspace) {
            $tools[] = [
                'name' => 'list_files',
                'description' => 'List files and directories available in the connected repository, local workspace, or uploaded folder. Call this first to discover what code exists before reading specific files.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Optional directory path prefix to filter by, e.g. "src/" or "app/Http". Omit to list from the root.',
                        ],
                        'max_depth' => [
                            'type' => 'integer',
                            'description' => 'Optional directory scanning depth. 1 = top-level files only, 2 = subdirectories, etc.',
                        ],
                    ],
                ],
            ];
            $tools[] = [
                'name' => 'read_file',
                'description' => 'Read the contents of a single file by its exact path. Use the paths returned by list_files, glob, or grep_search. Returns line-numbered contents.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Exact file path, e.g. "app/Models/User.php".',
                        ],
                        'start_line' => [
                            'type' => 'integer',
                            'description' => 'Optional: read starting from this line number (1-indexed, inclusive).',
                        ],
                        'end_line' => [
                            'type' => 'integer',
                            'description' => 'Optional: read until this line number (inclusive).',
                        ],
                    ],
                    'required' => ['path'],
                ],
            ];
        }

        if ($hasWorkspace) {
            $tools[] = [
                'name' => 'write_file',
                'description' => 'Create a new file or completely overwrite an existing file in the local workspace.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Exact file path, e.g. "app/Helpers/MyHelper.php".',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'The full content of the file.',
                        ],
                    ],
                    'required' => ['path', 'content'],
                ],
            ];
            $tools[] = [
                'name' => 'edit_file',
                'description' => 'Edit an existing file in the local workspace by replacing a specific string of text with a new string. Be careful to match exact indentation and characters.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Exact file path to edit.',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'The exact text to find and replace. Must match exactly including whitespace.',
                        ],
                        'replace' => [
                            'type' => 'string',
                            'description' => 'The new text to replace the searched text with.',
                        ],
                        'start_line' => [
                            'type' => 'integer',
                            'description' => 'Optional: narrow search to lines starting from this number (1-indexed)',
                        ],
                        'end_line' => [
                            'type' => 'integer',
                            'description' => 'Optional: narrow search to lines up to this number',
                        ],
                    ],
                    'required' => ['path', 'search', 'replace'],
                ],
            ];
            $tools[] = [
                'name' => 'multi_edit_file',
                'description' => 'Make multiple non-contiguous edits to a file in one call.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Exact file path to edit.',
                        ],
                        'edits' => [
                            'type' => 'array',
                            'description' => 'An array of edit operations, executed in order.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'search' => [
                                        'type' => 'string',
                                        'description' => 'The exact text to find.',
                                    ],
                                    'replace' => [
                                        'type' => 'string',
                                        'description' => 'The replacement text.',
                                    ],
                                    'start_line' => [
                                        'type' => 'integer',
                                        'description' => 'Optional: narrow search to lines starting from this number',
                                    ],
                                    'end_line' => [
                                        'type' => 'integer',
                                        'description' => 'Optional: narrow search to lines up to this number',
                                    ],
                                ],
                                'required' => ['search', 'replace'],
                            ],
                        ],
                    ],
                    'required' => ['path', 'edits'],
                ],
            ];
            $tools[] = [
                'name' => 'bash',
                'description' => 'Execute a shell command in the workspace directory. Use for running tests, installing packages, git operations, builds, etc.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'The shell command to execute.',
                        ],
                        'timeout' => [
                            'type' => 'integer',
                            'description' => 'Max seconds to wait (default: 30, max: 300).',
                        ],
                    ],
                    'required' => ['command'],
                ],
            ];
            $tools[] = [
                'name' => 'grep_search',
                'description' => 'Search the workspace files for a text pattern using ripgrep-style recursive search.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The term or regex pattern to search for.',
                        ],
                        'case_insensitive' => [
                            'type' => 'boolean',
                            'description' => 'Perform case-insensitive search (default: false).',
                        ],
                        'is_regex' => [
                            'type' => 'boolean',
                            'description' => 'Treat query as a regular expression pattern (default: false).',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ];
            $tools[] = [
                'name' => 'glob',
                'description' => 'Find files matching a glob pattern in the workspace.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'pattern' => [
                            'type' => 'string',
                            'description' => 'Glob pattern, e.g. "app/Models/*.php" or "**/*.test.js".',
                        ],
                    ],
                    'required' => ['pattern'],
                ],
            ];
            $tools[] = [
                'name' => 'git_operation',
                'description' => 'Execute basic git command (status, diff, log, show) to understand workspace state.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'subcommand' => [
                            'type' => 'string',
                            'description' => 'The git subcommand, e.g. "status", "diff", "log -n 5".',
                        ],
                    ],
                    'required' => ['subcommand'],
                ],
            ];
        }

        if ($hasRepo) {
            $tools[] = [
                'name' => 'search_code',
                'description' => 'Search the connected repository for a keyword, symbol, function or filename. Returns matching file paths (and code fragments when available). Use this to locate where something is implemented.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The term to search for, e.g. "AuthController" or "login".',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ];
        }

        $tools[] = [
            'name' => 'web_search',
            'description' => 'Search the public web for up-to-date information (library versions, docs, error messages, current facts). Cite the URLs you rely on.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'The search query.',
                    ],
                ],
                'required' => ['query'],
            ],
        ];

        return $tools;
    }

    /**
     * Execute a tool by name. Always returns a string (errors included).
     */
    public function execute(string $name, array $input): string
    {
        // 1. Permission Check
        $guard = new PermissionGuard();
        if (!$guard->isApproved($name, $input)) {
            if (app()->runningInConsole()) {
                if (!$guard->askPermission($name, $input, $this->localWorkspacePath)) {
                    return "Error: Permission denied by user.";
                }
            } else {
                throw new \App\Exceptions\PermissionRequiredException($name, $input, $this->currentToolCallId);
            }
        }

        $start = microtime(true);

        try {
            $result = match ($name) {
                'list_files'     => $this->listFiles($input['path'] ?? '', $input['max_depth'] ?? null),
                'read_file'      => $this->readFile((string) ($input['path'] ?? ''), $input['start_line'] ?? null, $input['end_line'] ?? null),
                'write_file'     => $this->writeFile((string) ($input['path'] ?? ''), (string) ($input['content'] ?? '')),
                'edit_file'      => $this->editFile((string) ($input['path'] ?? ''), (string) ($input['search'] ?? ''), (string) ($input['replace'] ?? ''), $input['start_line'] ?? null, $input['end_line'] ?? null),
                'multi_edit_file'=> $this->runMultiEditFile((string) ($input['path'] ?? ''), $input['edits'] ?? []),
                'bash'           => $this->runBash((string) ($input['command'] ?? ''), $input['timeout'] ?? 30),
                'grep_search'    => $this->runGrepSearch((string) ($input['query'] ?? ''), (bool) ($input['case_insensitive'] ?? false), (bool) ($input['is_regex'] ?? false)),
                'glob'           => $this->runGlob((string) ($input['pattern'] ?? '')),
                'git_operation'  => $this->runGitOperation((string) ($input['subcommand'] ?? '')),
                'search_code'    => $this->searchCode((string) ($input['query'] ?? '')),
                'web_search'     => $this->webSearch((string) ($input['query'] ?? '')),
                default          => "Error: unknown tool '{$name}'.",
            };

            // 2. Metrics Accumulation
            $duration = microtime(true) - $start;
            $this->metrics['calls_count']++;
            $this->metrics['total_duration'] += $duration;
            
            $bytesRead = 0;
            $bytesWritten = 0;
            if ($name === 'read_file') {
                $bytesRead = strlen($result);
            } elseif ($name === 'write_file' || $name === 'edit_file' || $name === 'multi_edit_file') {
                $bytesWritten = strlen(json_encode($input));
            }
            
            $this->metrics['bytes_read'] += $bytesRead;
            $this->metrics['bytes_written'] += $bytesWritten;

            $this->metrics['history'][] = [
                'tool' => $name,
                'duration' => $duration,
                'bytes_read' => $bytesRead,
                'bytes_written' => $bytesWritten,
            ];

            return $result;
        } catch (\Throwable $e) {
            return "Error executing {$name}: " . $e->getMessage();
        }
    }

    /** Short human-readable summary of a tool result, for the UI activity log. */
    public function summarize(string $name, string $result): string
    {
        $lines = substr_count(rtrim($result), "\n") + 1;
        return match ($name) {
            'read_file'   => strlen($result) . ' chars',
            'write_file', 'edit_file', 'multi_edit_file' => 'success',
            'list_files', 'search_code' => $lines . ' entries',
            'web_search'  => 'web results',
            'bash', 'git_operation' => (function() use ($name, $result) {
                $exitCode = preg_match('/(?:Git )?Exit Code: (\d+)/', $result, $m) ? $m[1] : '0';
                // Extract compression stats if present
                if (preg_match('/\[compressed: (\d+)%\]/', $result, $cm)) {
                    return "exit: {$exitCode} · saved {$cm[1]}% tokens";
                }
                return "exit: {$exitCode}";
            })(),
            'grep_search', 'glob' => $lines . ' matches',
            default       => $lines . ' lines',
        };
    }

    // ── Tool implementations ──────────────────────────────────────────────

    private function listFiles(string $path, ?int $maxDepth = null): string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '.' || $path === './') {
            $path = '';
        }

        $entries = [];

        // 1. Local Workspace
        if ($this->localWorkspacePath !== '') {
            $localSvc = new LocalWorkspaceService();
            $tree = $localSvc->fetchTree($this->localWorkspacePath, $maxDepth);
            foreach ($tree as $item) {
                $p = $item['path'] ?? '';
                if ($path !== '' && !str_starts_with($p, $path)) {
                    continue;
                }
                $entries[] = ($item['type'] === 'dir' ? '[dir]  ' : '       ') . $p;
            }
        }

        // 2. Connected Repo (GitHub)
        foreach ($this->repoTree as $item) {
            $p = $item['path'] ?? '';
            if ($path !== '' && !str_starts_with($p, $path)) {
                continue;
            }
            $entries[] = ($item['type'] === 'dir' ? '[dir]  ' : '       ') . $p;
        }

        // 3. Uploaded Files
        if (empty($entries)) {
            foreach ($this->localFiles as $item) {
                $entries[] = '       ' . ($item['name'] ?? '');
            }
        }

        if (empty($entries)) {
            return $path !== ''
                ? "No files found under '{$path}'."
                : 'No files available. No repository is connected and no files were uploaded.';
        }

        // Deduplicate entries
        $entries = array_unique($entries);

        $shown = array_slice($entries, 0, 150);
        $note = count($entries) > 150 ? "\n… (" . (count($entries) - 150) . ' more, narrow with a path)' : '';
        return implode("\n", $shown) . $note;
    }

    private function readFile(string $path, ?int $startLine = null, ?int $endLine = null): string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '') {
            return 'Error: read_file requires a non-empty "path".';
        }

        $content = null;

        // Uploaded files first, then local workspace, then connected repo.
        if (isset($this->uploadedContents[$path])) {
            $content = $this->uploadedContents[$path];
        } elseif ($this->localWorkspacePath !== '') {
            $localSvc = new LocalWorkspaceService();
            $content = $localSvc->fetchFileContent($this->localWorkspacePath, $path);
        }
        
        if ($content === null && $this->repoConnected !== '') {
            [$owner, $repo] = array_pad(explode('/', $this->repoConnected, 2), 2, '');
            if ($owner === '' || $repo === '') {
                return 'Error: connected repository is misconfigured.';
            }
            $content = (new GitHubService($this->githubToken))->fetchFileContent($owner, $repo, $path);
        }

        if ($content === null) {
            return "Error: could not read '{$path}'. Check the exact path with list_files or search_code.";
        }

        // Record raw file content for Files panel before any slice/line formatting
        if (!isset($this->openedFiles[$path])) {
            $this->openedFiles[$path] = [
                'name'    => basename($path),
                'path'    => $path,
                'content' => $content,
            ];
        }

        // Extract slice if line numbers targeted
        $lines = explode("\n", $content);
        $totalLines = count($lines);

        $start = 1;
        $end = $totalLines;

        if ($startLine !== null || $endLine !== null) {
            $start = $startLine !== null ? max(1, $startLine) : 1;
            $end = $endLine !== null ? min($totalLines, $endLine) : $totalLines;
            $lines = array_slice($lines, $start - 1, $end - $start + 1);
        }

        // Format lines with numbers prefix
        $formatted = [];
        $currentLine = $start;
        foreach ($lines as $line) {
            $formatted[] = "{$currentLine}: {$line}";
            $currentLine++;
        }
        $formattedContent = implode("\n", $formatted);

        $truncated = '';
        if (strlen($formattedContent) > self::MAX_FILE_BYTES) {
            $formattedContent = substr($formattedContent, 0, self::MAX_FILE_BYTES);
            $truncated = "\n\n… [truncated at " . self::MAX_FILE_BYTES . ' bytes]';
        }

        return "File: {$path}\n```\n" . $formattedContent . "\n```" . $truncated;
    }

    private function writeFile(string $path, string $content): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to write to.';
        }
        
        $localSvc = new LocalWorkspaceService();
        if ($localSvc->writeFile($this->localWorkspacePath, $path, $content)) {
            return "Successfully wrote to '{$path}'.";
        }

        return "Error: Failed to write to '{$path}'.";
    }

    private function editFile(string $path, string $search, string $replace, ?int $startLine = null, ?int $endLine = null): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to edit.';
        }

        $localSvc = new LocalWorkspaceService();
        if ($localSvc->replaceInFile($this->localWorkspacePath, $path, $search, $replace, $startLine, $endLine)) {
            return "Successfully edited '{$path}'.";
        }

        return "Error: Failed to edit '{$path}'. This may happen if the search string was not found exactly as provided (mind whitespace and indentation).";
    }

    private function runMultiEditFile(string $path, array $edits): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to edit.';
        }
        
        $localSvc = new LocalWorkspaceService();
        $fullPath = rtrim($this->localWorkspacePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        
        if (!File::exists($fullPath)) {
            return "Error: File '{$path}' does not exist.";
        }
        
        $content = File::get($fullPath);
        $originalContent = $content;
        
        foreach ($edits as $index => $edit) {
            $search = $edit['search'] ?? '';
            $replace = $edit['replace'] ?? '';
            $startLine = $edit['start_line'] ?? null;
            $endLine = $edit['end_line'] ?? null;
            
            if ($startLine !== null && $endLine !== null) {
                $lines = explode("\n", $content);
                $totalLines = count($lines);
                
                $startIdx = max(1, $startLine) - 1;
                $endIdx = min($totalLines, $endLine) - 1;
                
                if ($startIdx > $endIdx || $startIdx >= $totalLines) {
                    return "Error in edit #{$index}: Invalid line range {$startLine}-{$endLine}.";
                }
                
                $slice = array_slice($lines, $startIdx, $endIdx - $startIdx + 1);
                $sliceContent = implode("\n", $slice);
                
                if (!str_contains($sliceContent, $search)) {
                    return "Error in edit #{$index}: Target text not found in range {$startLine}-{$endLine}.";
                }
                
                $newSliceContent = str_replace($search, $replace, $sliceContent);
                array_splice($lines, $startIdx, $endIdx - $startIdx + 1, explode("\n", $newSliceContent));
                $content = implode("\n", $lines);
            } else {
                if (!str_contains($content, $search)) {
                    return "Error in edit #{$index}: Target text not found in file.";
                }
                $content = str_replace($search, $replace, $content);
            }
        }
        
        if ($content !== $originalContent) {
            if (File::put($fullPath, $content) !== false) {
                return "Successfully applied " . count($edits) . " edits to '{$path}'.";
            }
        }
        
        return "No edits were applied to '{$path}' (content did not change).";
    }

    private function runBash(string $command, int $timeout = 30): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to execute command.';
        }
        
        $timeout = min(max($timeout, 1), 300);
        
        // Blacklist check
        $blacklist = ['sudo', 'rm -rf /', 'rm -rf *', 'mv /*', 'chmod -R', 'chown -R'];
        foreach ($blacklist as $item) {
            if (str_contains($command, $item)) {
                return "Error: Command blocked by security policy.";
            }
        }

        try {
            $process = Process::fromShellCommandline($command);
            $process->setWorkingDirectory($this->localWorkspacePath);
            $process->setTimeout($timeout);
            
            $process->run();
            
            $rawOutput = $process->getOutput() . $process->getErrorOutput();
            $exitCode  = $process->getExitCode();

            // ── Native token compression (RTK-style) ──────────────────────────
            // Compresses verbose command output (npm, git, phpunit, etc.) before
            // sending to the LLM, reducing token usage by 60–90% on noisy output.
            // No external binary required — pure PHP implementation.
            [$output, $compressionStats] = OutputCompressor::compress($rawOutput, $command);

            // Accumulate RTK savings for later recording in TokenUsage
            RtkTracker::add($compressionStats['original_chars'], $compressionStats['compressed_chars']);

            // Attach compression metadata so summarize() can display savings
            $compressionNote = '';
            if ($compressionStats['saved_pct'] >= 10) {
                $compressionNote = " [compressed: {$compressionStats['saved_pct']}%]";
            }
            // ──────────────────────────────────────────────────────────────────
            
            return "Exit Code: {$exitCode}{$compressionNote}\nOutput:\n" . $output;
        } catch (\Throwable $e) {
            return "Error executing command: " . $e->getMessage();
        }
    }

    private function runGrepSearch(string $query, bool $caseInsensitive = false, bool $isRegex = false): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to search.';
        }

        $useRg = false;
        try {
            $check = Process::fromShellCommandline('rg --version');
            $check->run();
            if ($check->isSuccessful()) {
                $useRg = true;
            }
        } catch (\Throwable $e) {}

        $output = '';
        $exitCode = 1;

        if ($useRg) {
            $flags = ['--line-number', '--no-heading', '--color=never', '--max-count=50'];
            if ($caseInsensitive) $flags[] = '-i';
            if (!$isRegex) $flags[] = '-F';
            
            $cmd = "rg " . implode(' ', $flags) . " " . escapeshellarg($query) . " .";
            
            try {
                $process = Process::fromShellCommandline($cmd);
                $process->setWorkingDirectory($this->localWorkspacePath);
                $process->run();
                $output = $process->getOutput();
                $exitCode = $process->getExitCode();
            } catch (\Throwable $e) {}
        }

        // Try grep as fallback
        if ($exitCode !== 0 && !$useRg) {
            $flags = ['-rnI'];
            if ($caseInsensitive) $flags[] = '-i';
            if (!$isRegex) $flags[] = '-F';
            
            $cmd = "grep " . implode(' ', $flags) . " --exclude-dir={node_modules,vendor,.git,storage} " . escapeshellarg($query) . " .";
            
            try {
                $process = Process::fromShellCommandline($cmd);
                $process->setWorkingDirectory($this->localWorkspacePath);
                $process->run();
                $output = $process->getOutput();
                $exitCode = $process->getExitCode();
            } catch (\Throwable $e) {}
        }

        // Fallback to PHP native search
        if ($exitCode !== 0 || empty(trim($output))) {
            $localSvc = new LocalWorkspaceService();
            $files = $localSvc->fetchTree($this->localWorkspacePath);
            $matches = [];
            $count = 0;

            foreach ($files as $file) {
                if ($file['type'] !== 'file') continue;
                
                $path = $file['path'];
                $content = $localSvc->fetchFileContent($this->localWorkspacePath, $path);
                if ($content === null) continue;

                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $lineContent) {
                    $match = false;
                    if ($isRegex) {
                        $pattern = '/' . str_replace('/', '\/', $query) . '/' . ($caseInsensitive ? 'i' : '');
                        try {
                           $match = preg_match($pattern, $lineContent) === 1;
                        } catch (\Throwable $e) {}
                    } else {
                        if ($caseInsensitive) {
                            $match = str_contains(strtolower($lineContent), strtolower($query));
                        } else {
                            $match = str_contains($lineContent, $query);
                        }
                    }

                    if ($match) {
                        $matches[] = "{$path}:" . ($lineNum + 1) . ":{$lineContent}";
                        $count++;
                        if ($count >= 50) {
                            break 2;
                        }
                    }
                }
            }
            $output = implode("\n", $matches);
        }

        $lines = explode("\n", trim($output));
        $lines = array_filter($lines);
        $totalMatches = count($lines);
        
        if ($totalMatches === 0) {
            return "No matches found for query '{$query}'.";
        }

        $lines = array_slice($lines, 0, 50);
        $result = implode("\n", $lines);
        if ($totalMatches > 50) {
            $result .= "\n... (showing first 50 matches out of {$totalMatches})";
        }
        return $result;
    }

    private function runGlob(string $pattern): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to run glob.';
        }
        
        $localSvc = new LocalWorkspaceService();
        $files = $localSvc->fetchTree($this->localWorkspacePath);
        
        $matches = [];
        foreach ($files as $file) {
            if ($file['type'] !== 'file') continue;
            
            $path = $file['path'];
            
            if (fnmatch($pattern, $path) || fnmatch('*/' . $pattern, $path)) {
                $matches[] = "{$path} (size: {$file['size']} bytes)";
            }
        }
        
        if (empty($matches)) {
            return "No files matched the pattern '{$pattern}'.";
        }
        
        return implode("\n", $matches);
    }

    private function searchCode(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return 'Error: search_code requires a non-empty "query".';
        }

        $needle = strtolower($query);
        $pathHits = [];
        foreach ($this->repoTree as $item) {
            if (($item['type'] ?? '') !== 'file') {
                continue;
            }
            $p = $item['path'] ?? '';
            if (str_contains(strtolower($p), $needle)) {
                $pathHits[] = $p;
            }
        }

        $contentHits = [];
        if ($this->repoConnected !== '') {
            [$owner, $repo] = array_pad(explode('/', $this->repoConnected, 2), 2, '');
            if ($owner !== '' && $repo !== '') {
                $contentHits = (new GitHubService($this->githubToken))->searchCode($owner, $repo, $query);
            }
        }

        $out = [];
        if (!empty($pathHits)) {
            $out[] = 'Filename/path matches:';
            foreach (array_slice($pathHits, 0, 30) as $p) {
                $out[] = '  ' . $p;
            }
        }
        if (!empty($contentHits)) {
            $out[] = "\nContent matches:";
            foreach ($contentHits as $hit) {
                $out[] = '  ' . $hit['path'];
                if ($hit['fragment'] !== '') {
                    $out[] = '    ' . str_replace("\n", "\n    ", $hit['fragment']);
                }
            }
        }

        if (empty($out)) {
            return "No matches for '{$query}'. Try list_files to browse, or a broader term.";
        }
        return implode("\n", $out);
    }

    private function webSearch(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return 'Error: web_search requires a non-empty "query".';
        }

        $results = (new WebSearchService())->search($query, 5);
        if (empty($results)) {
            return "No web results for '{$query}'.";
        }

        $out = [];
        foreach ($results as $i => $r) {
            $n = $i + 1;
            $out[] = "[{$n}] {$r['title']}\n    {$r['url']}\n    {$r['snippet']}";
        }
        return implode("\n", $out);
    }

    private function runGitOperation(string $subcommand): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to run git command.';
        }
        
        $subcommand = trim($subcommand);
        
        $allowed = ['status', 'diff', 'log', 'show', 'branch', 'config'];
        $parts = explode(' ', $subcommand);
        $baseCmd = strtolower($parts[0] ?? '');
        
        if (!in_array($baseCmd, $allowed)) {
            return "Error: Git subcommand '{$baseCmd}' is not allowed for security reasons. Allowed: " . implode(', ', $allowed);
        }
        
        try {
            $process = Process::fromShellCommandline("git " . $subcommand);
            $process->setWorkingDirectory($this->localWorkspacePath);
            $process->run();
            
            $rawOutput = $process->getOutput() . $process->getErrorOutput();
            $exitCode  = $process->getExitCode();

            // ── Native token compression (RTK-style) ──────────────────────────
            [$output, $compressionStats] = OutputCompressor::compress($rawOutput, 'git ' . $subcommand);

            // Accumulate RTK savings for later recording in TokenUsage
            RtkTracker::add($compressionStats['original_chars'], $compressionStats['compressed_chars']);

            $compressionNote = '';
            if ($compressionStats['saved_pct'] >= 10) {
                $compressionNote = " [compressed: {$compressionStats['saved_pct']}%]";
            }
            // ──────────────────────────────────────────────────────────────────

            return "Git Exit Code: {$exitCode}{$compressionNote}\nOutput:\n" . trim($output);
        } catch (\Throwable $e) {
            return "Error executing git command: " . $e->getMessage();
        }
    }
}

<?php

namespace App\Services\AI;

use App\Services\GitHubService;
use App\Services\WebSearchService;

/**
 * Read-only tool registry the agent loop (AgentRunner) can call autonomously.
 *
 * Tools are backed by services that already exist in the app:
 *   - list_files / read_file / search_code → GitHubService (+ the repo tree already
 *     fetched when the user connected the repo) and uploaded local files
 *   - web_search → WebSearchService
 *
 * execute() never throws: any failure is returned as text so the model can read
 * the error and self-correct on the next turn.
 */
class AgentTools
{
    /** Max bytes returned by read_file, to keep the context window sane. */
    private const MAX_FILE_BYTES = 60000;

    /** Files the agent opened this turn — merged back into the UI's Files panel. */
    private array $openedFiles = [];

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

    /**
     * Whether there are any tools worth offering. When false, AgentRunner falls
     * back to plain chat. web_search is always available, so this is effectively
     * always true — but kept explicit for clarity.
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
                    ],
                ],
            ];
            $tools[] = [
                'name' => 'read_file',
                'description' => 'Read the full contents of a single file by its exact path. Use the paths returned by list_files or search_code.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Exact file path, e.g. "app/Models/User.php".',
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
                    ],
                    'required' => ['path', 'search', 'replace'],
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
        try {
            return match ($name) {
                'list_files'  => $this->listFiles($input['path'] ?? ''),
                'read_file'   => $this->readFile((string) ($input['path'] ?? '')),
                'write_file'  => $this->writeFile((string) ($input['path'] ?? ''), (string) ($input['content'] ?? '')),
                'edit_file'   => $this->editFile((string) ($input['path'] ?? ''), (string) ($input['search'] ?? ''), (string) ($input['replace'] ?? '')),
                'search_code' => $this->searchCode((string) ($input['query'] ?? '')),
                'web_search'  => $this->webSearch((string) ($input['query'] ?? '')),
                default       => "Error: unknown tool '{$name}'.",
            };
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
            'write_file', 'edit_file' => 'success',
            'list_files', 'search_code' => $lines . ' entries',
            'web_search'  => 'web results',
            default       => $lines . ' lines',
        };
    }

    // ── Tool implementations ──────────────────────────────────────────────

    private function listFiles(string $path): string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '.' || $path === './') {
            $path = '';
        }

        $entries = [];

        // 1. Local Workspace
        if ($this->localWorkspacePath !== '') {
            $localSvc = new \App\Services\LocalWorkspaceService();
            $tree = $localSvc->fetchTree($this->localWorkspacePath);
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

    private function readFile(string $path): string
    {
        $path = ltrim(trim($path), '/');
        if ($path === '') {
            return 'Error: read_file requires a non-empty "path".';
        }

        $content = null;

        // Uploaded files first (already in memory), then local workspace, then connected repo.
        if (isset($this->uploadedContents[$path])) {
            $content = $this->uploadedContents[$path];
        } elseif ($this->localWorkspacePath !== '') {
            $localSvc = new \App\Services\LocalWorkspaceService();
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

        $truncated = '';
        if (strlen($content) > self::MAX_FILE_BYTES) {
            $content = substr($content, 0, self::MAX_FILE_BYTES);
            $truncated = "\n\n… [truncated at " . self::MAX_FILE_BYTES . ' bytes]';
        }

        // Record for the UI Files panel (de-duplicated).
        if (!isset($this->openedFiles[$path])) {
            $this->openedFiles[$path] = [
                'name'    => basename($path),
                'path'    => $path,
                'content' => $content,
            ];
        }

        return "File: {$path}\n```\n" . $content . "\n```" . $truncated;
    }

    private function writeFile(string $path, string $content): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to write to.';
        }
        
        $localSvc = new \App\Services\LocalWorkspaceService();
        if ($localSvc->writeFile($this->localWorkspacePath, $path, $content)) {
            return "Successfully wrote to '{$path}'.";
        }

        return "Error: Failed to write to '{$path}'.";
    }

    private function editFile(string $path, string $search, string $replace): string
    {
        if ($this->localWorkspacePath === '') {
            return 'Error: No local workspace available to edit.';
        }

        $localSvc = new \App\Services\LocalWorkspaceService();
        if ($localSvc->replaceInFile($this->localWorkspacePath, $path, $search, $replace)) {
            return "Successfully edited '{$path}'.";
        }

        return "Error: Failed to edit '{$path}'. This may happen if the search string was not found exactly as provided (mind whitespace and indentation).";
    }

    private function searchCode(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return 'Error: search_code requires a non-empty "query".';
        }

        // Tier 1: match paths/filenames in the already-fetched tree (instant, no API).
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

        // Tier 2: GitHub content code-search (needs a token for most repos).
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
}

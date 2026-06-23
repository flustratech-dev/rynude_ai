<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Fetches file trees and file content from GitHub repos.
 * Supports public repos (no token) and private repos (with PAT).
 */
class GitHubService
{
    private ?string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * Parse owner/repo from a GitHub URL.
     * e.g. "https://github.com/laravel/framework" => ["laravel", "framework"]
     */
    public static function parseUrl(string $url): ?array
    {
        if (preg_match('#github\.com/([^/]+)/([^/\s\#\?]+)#', $url, $m)) {
            $repo = preg_replace('/\.git$/', '', $m[2]);
            return ['owner' => $m[1], 'repo' => $repo];
        }
        return null;
    }

    /**
     * Fetch the file tree (recursive) for a repo.
     * Returns flat array of [{path, type, size, sha}]
     * type = 'blob' (file) or 'tree' (dir)
     */
    public function fetchTree(string $owner, string $repo, string $branch = 'main'): array
    {
        // Try main first, then master as fallback
        $branches = [$branch];
        if ($branch === 'main') $branches[] = 'master';

        foreach ($branches as $b) {
            $response = $this->request("repos/{$owner}/{$repo}/git/trees/{$b}?recursive=1");
            if ($response->successful()) {
                $tree = $response->json('tree', []);
                // Filter out very large trees — limit to 500 entries
                return array_slice(array_map(fn($item) => [
                    'path' => $item['path'],
                    'type' => $item['type'], // blob or tree
                    'size' => $item['size'] ?? 0,
                    'sha'  => $item['sha'],
                ], $tree), 0, 500);
            }
        }

        return [];
    }

    /**
     * Fetch file content by path. Returns raw text content.
     * Max 1MB files (GitHub API limit for this endpoint).
     */
    public function fetchFileContent(string $owner, string $repo, string $path, string $branch = 'main'): ?string
    {
        $branches = [$branch];
        if ($branch === 'main') $branches[] = 'master';

        foreach ($branches as $b) {
            $response = $this->request("repos/{$owner}/{$repo}/contents/{$path}?ref={$b}");
            if ($response->successful()) {
                $data = $response->json();
                if (($data['encoding'] ?? '') === 'base64' && isset($data['content'])) {
                    return base64_decode($data['content']);
                }
                return $data['content'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get repo info (default branch, description, etc.)
     */
    public function getRepoInfo(string $owner, string $repo): ?array
    {
        $response = $this->request("repos/{$owner}/{$repo}");
        if ($response->successful()) {
            $data = $response->json();
            return [
                'name'           => $data['full_name'],
                'description'    => $data['description'] ?? '',
                'default_branch' => $data['default_branch'] ?? 'main',
                'language'       => $data['language'] ?? '',
                'private'        => $data['private'] ?? false,
            ];
        }
        return null;
    }

    private function request(string $endpoint): \Illuminate\Http\Client\Response
    {
        $http = Http::baseUrl('https://api.github.com')
            ->withHeaders([
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'Rynude-Code/1.0',
            ])
            ->timeout(15);

        if ($this->token) {
            $http = $http->withToken($this->token);
        }

        return $http->get($endpoint);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lightweight web search used to ground chat responses in current information.
 *
 * Defaults to a keyless DuckDuckGo HTML scrape (good enough for local/demo use).
 * If a SEARCH_API_KEY is configured it uses a pluggable JSON API provider
 * (Tavily or Serper) instead, which is more reliable for production.
 */
class WebSearchService
{
    /**
     * @return array<int, array{title: string, url: string, snippet: string}>
     */
    public function search(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        try {
            $provider = config('services.search.provider', 'duckduckgo');
            $key = config('services.search.key');

            if ($key && $provider === 'tavily') {
                return $this->tavily($query, $limit, $key);
            }
            if ($key && $provider === 'serper') {
                return $this->serper($query, $limit, $key);
            }

            return $this->duckduckgo($query, $limit);
        } catch (\Throwable $e) {
            Log::warning('WebSearchService failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format results as a context block for injection into a system prompt.
     */
    public function formatForPrompt(array $results): string
    {
        if (empty($results)) {
            return '';
        }

        $lines = ["\n\nWeb search results (use these for up-to-date facts and cite the URLs in your answer):"];
        foreach ($results as $i => $r) {
            $n = $i + 1;
            $lines[] = "\n[{$n}] {$r['title']}\nURL: {$r['url']}\n{$r['snippet']}";
        }
        return implode("\n", $lines);
    }

    private function client(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client([
            'timeout' => 15,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; RynudeBot/1.0; +https://localhost)',
            ],
        ]);
    }

    /** Keyless: parse DuckDuckGo's HTML endpoint. */
    private function duckduckgo(string $query, int $limit): array
    {
        $response = $this->client()->get('https://html.duckduckgo.com/html/', [
            'query' => ['q' => $query],
        ]);

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $html = (string) $response->getBody();
        $results = [];

        if (!preg_match_all('/<a[^>]*class="result__a"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/s', $html, $links, PREG_SET_ORDER)) {
            return [];
        }

        preg_match_all('/<a[^>]*class="result__snippet"[^>]*>(.*?)<\/a>/s', $html, $snips, PREG_SET_ORDER);

        foreach ($links as $i => $link) {
            if (count($results) >= $limit) {
                break;
            }
            $url = html_entity_decode($link[1]);
            // DuckDuckGo wraps target URLs in a redirect; pull out uddg param if present.
            if (str_contains($url, 'uddg=')) {
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
                if (!empty($params['uddg'])) {
                    $url = $params['uddg'];
                }
            }
            $title = trim(html_entity_decode(strip_tags($link[2])));
            $snippet = isset($snips[$i][1]) ? trim(html_entity_decode(strip_tags($snips[$i][1]))) : '';

            if ($title === '') {
                continue;
            }

            $results[] = [
                'title' => $title,
                'url' => $url,
                'snippet' => Str::limit($snippet, 300),
            ];
        }

        return $results;
    }

    private function tavily(string $query, int $limit, string $key): array
    {
        $response = $this->client()->post('https://api.tavily.com/search', [
            'json' => [
                'api_key' => $key,
                'query' => $query,
                'max_results' => $limit,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $out = [];
        foreach ($data['results'] ?? [] as $r) {
            $out[] = [
                'title' => $r['title'] ?? '',
                'url' => $r['url'] ?? '',
                'snippet' => Str::limit($r['content'] ?? '', 300),
            ];
        }
        return $out;
    }

    private function serper(string $query, int $limit, string $key): array
    {
        $response = $this->client()->post('https://google.serper.dev/search', [
            'headers' => ['X-API-KEY' => $key, 'Content-Type' => 'application/json'],
            'json' => ['q' => $query, 'num' => $limit],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $out = [];
        foreach (array_slice($data['organic'] ?? [], 0, $limit) as $r) {
            $out[] = [
                'title' => $r['title'] ?? '',
                'url' => $r['link'] ?? '',
                'snippet' => Str::limit($r['snippet'] ?? '', 300),
            ];
        }
        return $out;
    }
}

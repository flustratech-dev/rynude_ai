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

    /**
     * Fetch a web page and return its readable text (tags stripped, whitespace
     * collapsed, capped). Used for URLs the user pastes into the chat and for
     * following up on search results.
     */
    public function fetchUrl(string $url, int $maxChars = 15000): string
    {
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }

        try {
            $response = $this->client()->get($url, ['allow_redirects' => ['max' => 3]]);
            if ($response->getStatusCode() !== 200) {
                return '';
            }

            $contentType = $response->getHeaderLine('Content-Type');
            if ($contentType !== '' && !str_contains($contentType, 'text/') && !str_contains($contentType, 'html') && !str_contains($contentType, 'json') && !str_contains($contentType, 'xml')) {
                return '';
            }

            $html = (string) $response->getBody();
            // Drop non-content blocks before stripping tags.
            $html = preg_replace('#<(script|style|noscript|svg|nav|footer|header)[^>]*>.*?</\1>#si', ' ', $html) ?? $html;
            $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
            $text = trim(preg_replace('/[ \t]*\n[ \t\n]*/', "\n", preg_replace('/[ \t]+/', ' ', $text) ?? '') ?? '');

            return Str::limit($text, $maxChars, "\n[... halaman dipotong ...]");
        } catch (\Throwable $e) {
            Log::warning('WebSearchService fetchUrl failed: ' . $e->getMessage());
            return '';
        }
    }

    private function client(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client([
            'timeout' => 8,
            'connect_timeout' => 4,
            'http_errors' => false,
            'headers' => [
                // A real browser UA: DuckDuckGo silently serves an empty/anomaly
                // page to bot-looking agents, which read as "0 results".
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
            ],
        ]);
    }

    /**
     * Keyless search with a fallback chain: DuckDuckGo html → DuckDuckGo lite
     * → Bing HTML. DuckDuckGo is blocked by several Indonesian ISPs (Kominfo),
     * so Bing is essential as the last keyless resort.
     */
    private function duckduckgo(string $query, int $limit): array
    {
        $results = $this->duckduckgoHtml($query, $limit);
        if (!empty($results)) {
            return $results;
        }
        $results = $this->duckduckgoLite($query, $limit);
        if (!empty($results)) {
            return $results;
        }
        Log::warning("WebSearchService: DuckDuckGo returned 0 results for \"{$query}\" on both endpoints (blocked or markup changed) — trying Bing.");
        $results = $this->bing($query, $limit);
        if (empty($results)) {
            Log::warning("WebSearchService: Bing also returned 0 results for \"{$query}\" — all keyless engines failed. Consider SEARCH_PROVIDER=tavily with an API key.");
        }
        return $results;
    }

    /** Keyless last resort: parse Bing's HTML results page. */
    private function bing(string $query, int $limit): array
    {
        $response = $this->client()->get('https://www.bing.com/search', [
            'query' => ['q' => $query, 'setlang' => 'id', 'count' => max(10, $limit)],
        ]);
        if ($response->getStatusCode() !== 200) {
            Log::info('WebSearchService: bing.com status ' . $response->getStatusCode());
            return [];
        }

        $html = (string) $response->getBody();
        $results = [];

        // Each organic result lives in <li class="b_algo"> with an <h2><a href>.
        if (!preg_match_all('/<li class="b_algo"[^>]*>(.*?)<\/li>/s', $html, $items)) {
            return [];
        }

        foreach ($items[1] as $item) {
            if (count($results) >= $limit) {
                break;
            }
            if (!preg_match('/<h2[^>]*>\s*<a\b[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/s', $item, $lm)) {
                continue;
            }
            $url = html_entity_decode($lm[1]);
            $title = trim(html_entity_decode(strip_tags($lm[2])));
            if ($title === '' || !preg_match('#^https?://#i', $url) || str_contains($url, 'bing.com/')) {
                continue;
            }
            $snippet = '';
            if (preg_match('/<p[^>]*>(.*?)<\/p>/s', $item, $sm)) {
                $snippet = trim(html_entity_decode(strip_tags($sm[1])));
            }
            $results[] = ['title' => $title, 'url' => $url, 'snippet' => Str::limit($snippet, 300)];
        }

        return $results;
    }

    private function duckduckgoHtml(string $query, int $limit): array
    {
        $response = $this->client()->get('https://html.duckduckgo.com/html/', [
            'query' => ['q' => $query],
        ]);
        if ($response->getStatusCode() !== 200) {
            Log::info('WebSearchService: html.duckduckgo.com status ' . $response->getStatusCode());
            return [];
        }

        $html = (string) $response->getBody();
        $results = [];

        // Attribute-order-insensitive: find every anchor whose class contains
        // result__a, then pull the href from wherever it sits in the tag.
        if (!preg_match_all('/<a\b([^>]*class="[^"]*result__a[^"]*"[^>]*)>(.*?)<\/a>/s', $html, $links, PREG_SET_ORDER)) {
            return [];
        }
        preg_match_all('/<a\b[^>]*class="[^"]*result__snippet[^"]*"[^>]*>(.*?)<\/a>/s', $html, $snips, PREG_SET_ORDER);

        foreach ($links as $i => $link) {
            if (count($results) >= $limit) {
                break;
            }
            if (!preg_match('/href="([^"]+)"/', $link[1], $hm)) {
                continue;
            }
            $url = $this->unwrapDdgRedirect(html_entity_decode($hm[1]));
            $title = trim(html_entity_decode(strip_tags($link[2])));
            $snippet = isset($snips[$i][1]) ? trim(html_entity_decode(strip_tags($snips[$i][1]))) : '';
            if ($title === '' || $url === '') {
                continue;
            }
            $results[] = ['title' => $title, 'url' => $url, 'snippet' => Str::limit($snippet, 300)];
        }

        return $results;
    }

    private function duckduckgoLite(string $query, int $limit): array
    {
        $response = $this->client()->get('https://lite.duckduckgo.com/lite/', [
            'query' => ['q' => $query],
        ]);
        if ($response->getStatusCode() !== 200) {
            Log::info('WebSearchService: lite.duckduckgo.com status ' . $response->getStatusCode());
            return [];
        }

        $html = (string) $response->getBody();
        $results = [];

        // Lite markup: <a rel="nofollow" href="..." class='result-link'>Title</a>
        // followed by a snippet cell <td class='result-snippet'>...</td>.
        if (!preg_match_all('/<a\b([^>]*class=[\'"][^\'"]*result-link[^\'"]*[\'"][^>]*)>(.*?)<\/a>/s', $html, $links, PREG_SET_ORDER)) {
            return [];
        }
        preg_match_all('/<td\b[^>]*class=[\'"][^\'"]*result-snippet[^\'"]*[\'"][^>]*>(.*?)<\/td>/s', $html, $snips, PREG_SET_ORDER);

        foreach ($links as $i => $link) {
            if (count($results) >= $limit) {
                break;
            }
            if (!preg_match('/href=[\'"]([^\'"]+)[\'"]/', $link[1], $hm)) {
                continue;
            }
            $url = $this->unwrapDdgRedirect(html_entity_decode($hm[1]));
            $title = trim(html_entity_decode(strip_tags($link[2])));
            $snippet = isset($snips[$i][1]) ? trim(html_entity_decode(strip_tags($snips[$i][1]))) : '';
            if ($title === '' || $url === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $results[] = ['title' => $title, 'url' => $url, 'snippet' => Str::limit($snippet, 300)];
        }

        return $results;
    }

    /** DuckDuckGo wraps target URLs in a redirect; pull out the uddg param. */
    private function unwrapDdgRedirect(string $url): string
    {
        if (str_contains($url, 'uddg=')) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
            if (!empty($params['uddg'])) {
                return $params['uddg'];
            }
        }
        // Protocol-relative redirect links ("//duckduckgo.com/l/?uddg=…")
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        return $url;
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

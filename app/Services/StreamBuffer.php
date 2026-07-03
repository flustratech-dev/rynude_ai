<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache-backed progress buffer for a running chat generation.
 *
 * The send request keeps generating even after the browser disconnects
 * (ignore_user_abort) and mirrors every SSE event in here, cumulatively.
 * The stream-resume endpoint replays the missing tail to a reconnecting
 * client. Cumulative state (not an event log) keeps reads O(1) and lets a
 * late client catch up with a single delta.
 *
 * Writes are throttled to one cache put per ~0.4s so a fast token stream
 * doesn't hammer the cache store (SQLite on default installs); done/error/
 * artifact events always flush immediately.
 */
class StreamBuffer
{
    /** Seconds a finished/abandoned buffer lingers for late resumers. */
    protected const TTL = 600;

    protected const FLUSH_INTERVAL = 0.4;

    protected array $state = [];
    protected float $lastFlush = 0;

    public function __construct(protected int $conversationId)
    {
    }

    public static function key(int $conversationId): string
    {
        return 'stream_buf_' . $conversationId;
    }

    public function start(): void
    {
        $this->state = [
            'status' => 'running',
            'content' => '',
            'thinking' => '',
            'artifact' => null,
            'done' => null,
            'error' => null,
            'updated_at' => microtime(true),
        ];
        $this->flush();
    }

    /**
     * Mirror one SSE event into the cumulative state.
     */
    public function apply(array $event): void
    {
        if ($this->state === []) {
            return; // start() not called — never happens in practice
        }

        $type = $event['type'] ?? '';
        $data = $event['data'] ?? null;

        switch ($type) {
            case 'content':
                $this->state['content'] .= (string) $data;
                break;
            case 'thinking':
                $this->state['thinking'] .= (string) $data;
                break;
            case 'artifact':
                $this->state['artifact'] = $data;
                break;
            case 'done':
                $this->state['done'] = $data;
                $this->state['status'] = 'done';
                break;
            case 'error':
                $this->state['error'] = is_string($data) ? $data : 'Unknown error';
                $this->state['status'] = 'error';
                break;
            default:
                return;
        }

        $terminal = in_array($type, ['artifact', 'done', 'error'], true);
        if ($terminal || (microtime(true) - $this->lastFlush) >= self::FLUSH_INTERVAL) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->state['updated_at'] = microtime(true);
        Cache::put(self::key($this->conversationId), $this->state, self::TTL);
        $this->lastFlush = microtime(true);
    }

    public static function read(int $conversationId): ?array
    {
        $state = Cache::get(self::key($conversationId));

        return is_array($state) ? $state : null;
    }
}

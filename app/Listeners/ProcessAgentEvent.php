<?php

namespace App\Listeners;

use App\Events\AgentEventDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class ProcessAgentEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(AgentEventDispatched $event): void
    {
        $agentEvent = $event->agentEvent;
        
        $cacheKey = 'processed_event_' . $agentEvent->id;
        
        // Deduplicate using Cache (TTL 120 seconds)
        if (Cache::has($cacheKey)) {
            return; // Already processed
        }
        
        Cache::put($cacheKey, true, 120);

        // Publish to Redis for SSE Bridge
        $channel = 'agent-events.' . $agentEvent->workflowId;
        Redis::publish($channel, json_encode($agentEvent));
    }
}

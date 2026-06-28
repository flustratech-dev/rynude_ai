<?php

namespace App\Listeners;

use App\Events\AgentEventDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\AgentEventRepositoryInterface;
use Illuminate\Support\Facades\Redis;

class ProcessAgentEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(AgentEventDispatched $event): void
    {
        // 1. Save to EventStore (DB)
        $repository = app(AgentEventRepositoryInterface::class);
        $repository->save($event->agentEvent);

        // 2. Publish to Redis for Phase 2/3
        $channel = 'agent-events.' . $event->agentEvent->workflowId;
        Redis::publish($channel, json_encode($event->agentEvent));
    }
}

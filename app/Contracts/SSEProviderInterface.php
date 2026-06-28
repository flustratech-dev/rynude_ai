<?php

namespace App\Contracts;

use App\Models\AgentEvent;

interface SSEProviderInterface
{
    public function publish(AgentEvent $event): void;
    public function subscribe(string $channel, callable $callback): void;
}

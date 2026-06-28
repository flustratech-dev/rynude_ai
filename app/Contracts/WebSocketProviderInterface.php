<?php

namespace App\Contracts;

use App\Domain\AgentEvent;

interface WebSocketProviderInterface
{
    public function publish(AgentEvent $event): void;
    public function subscribe(string $channel, callable $callback): void;
}

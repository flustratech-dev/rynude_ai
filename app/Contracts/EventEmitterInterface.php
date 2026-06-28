<?php

namespace App\Contracts;

use App\Models\AgentEvent;

interface EventEmitterInterface
{
    public function dispatch(AgentEvent $event): void;
    public function subscribe(callable $listener): void;
    public function notifyListeners(AgentEvent $event): void;
}

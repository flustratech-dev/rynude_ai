<?php

namespace App\Services;

use App\Domain\AgentEvent;

use App\Contracts\EventEmitterInterface;

class EventEmitter implements EventEmitterInterface
{
    /** @var array<callable> */
    private array $subscribers = [];

    public function dispatch(AgentEvent $event): void
    {
        $this->notifyListeners($event);
    }

    public function subscribe(callable $listener): void
    {
        $this->subscribers[] = $listener;
    }

    public function notifyListeners(AgentEvent $event): void
    {
        foreach ($this->subscribers as $listener) {
            $listener($event);
        }
    }
}

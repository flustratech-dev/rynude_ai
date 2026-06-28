<?php

namespace App\Contracts;

use App\Domain\AgentEvent;

interface StreamProviderInterface
{
    public function connect(): void;
    public function disconnect(): void;
    public function publish(AgentEvent $event): void;
}

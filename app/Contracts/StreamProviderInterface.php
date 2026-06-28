<?php

namespace App\Contracts;

use App\Models\AgentEvent;

interface StreamProviderInterface
{
    public function connect(): void;
    public function disconnect(): void;
    public function publish(AgentEvent $event): void;
}

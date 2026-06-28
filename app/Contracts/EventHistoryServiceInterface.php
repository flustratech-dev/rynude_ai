<?php

namespace App\Contracts;

interface EventHistoryServiceInterface
{
    public function getHistory(string $sessionId, int $page = 1, int $perPage = 50): array;
    public function filterHistory(string $sessionId, array $criteria): array;
}

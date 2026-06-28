<?php

namespace App\Services\AI\Coordinator;

use App\Models\Conversation;
use App\Models\User;

final class GenerationContext
{
    public function __construct(
        public readonly User $user,
        public readonly Conversation $conversation,
        public readonly array $messages,
        public readonly string $model,
        public readonly array $attachments = [],
        public readonly bool $webSearch = false,
        public readonly ?int $projectId = null,
        public readonly GenerationOptions $options = new GenerationOptions(),
    ) {}
}

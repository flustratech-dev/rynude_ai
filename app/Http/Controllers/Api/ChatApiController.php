<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Chat REST surface (Phase 1 scaffold).
 *
 * Target logic source: App\Livewire\ChatInterface
 * Streaming (send/generate) is owned by Phase 5 (Streaming Migration); the rest
 * of the CRUD-style endpoints belong to Phase 4.
 */
class ChatApiController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->pendingMigration('chat.list', 'App\\Livewire\\ChatsPanel');
    }

    public function send(): JsonResponse
    {
        return $this->pendingMigration('chat.send', 'App\\Livewire\\ChatInterface::sendMessage', 'Phase 5');
    }

    public function stop(): JsonResponse
    {
        return $this->pendingMigration('chat.stop', 'App\\Livewire\\ChatInterface::stopGeneration', 'Phase 5');
    }

    public function show(int $conversation): JsonResponse
    {
        return $this->pendingMigration('chat.show', 'App\\Livewire\\ChatInterface');
    }

    public function update(int $conversation): JsonResponse
    {
        return $this->pendingMigration('chat.update', 'App\\Livewire\\ChatsPanel::renameConversation');
    }

    public function destroy(int $conversation): JsonResponse
    {
        return $this->pendingMigration('chat.destroy', 'App\\Livewire\\ChatsPanel::deleteConversation');
    }

    public function share(int $conversation): JsonResponse
    {
        return $this->pendingMigration('chat.share', 'App\\Livewire\\ChatsPanel::shareConversation');
    }
}

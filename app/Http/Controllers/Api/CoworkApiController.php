<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Cowork task REST surface (Phase 1 scaffold).
 *
 * Target logic source: App\Livewire\CoworkPanel
 */
class CoworkApiController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->pendingMigration('tasks.list', 'App\\Livewire\\CoworkPanel');
    }

    public function store(): JsonResponse
    {
        return $this->pendingMigration('tasks.store', 'App\\Livewire\\CoworkPanel::createTask');
    }

    public function update(int $task): JsonResponse
    {
        return $this->pendingMigration('tasks.update', 'App\\Livewire\\CoworkPanel::updateStatus');
    }

    public function destroy(int $task): JsonResponse
    {
        return $this->pendingMigration('tasks.destroy', 'App\\Livewire\\CoworkPanel::deleteTask');
    }

    public function run(int $task): JsonResponse
    {
        return $this->pendingMigration('tasks.run', 'App\\Livewire\\CoworkPanel::runTask');
    }
}

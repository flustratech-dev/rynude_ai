<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Project REST surface (Phase 1 scaffold).
 *
 * Target logic source: App\Livewire\ProjectsPanel
 */
class ProjectApiController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->pendingMigration('projects.list', 'App\\Livewire\\ProjectsPanel');
    }

    public function store(): JsonResponse
    {
        return $this->pendingMigration('projects.store', 'App\\Livewire\\ProjectsPanel::createProject');
    }

    public function update(int $project): JsonResponse
    {
        return $this->pendingMigration('projects.update', 'App\\Livewire\\ProjectsPanel::updateProject');
    }

    public function destroy(int $project): JsonResponse
    {
        return $this->pendingMigration('projects.destroy', 'App\\Livewire\\ProjectsPanel::deleteProject');
    }
}

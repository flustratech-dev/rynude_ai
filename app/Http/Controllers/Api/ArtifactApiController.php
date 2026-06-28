<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Artifact REST surface (Phase 1 scaffold).
 *
 * Target logic source: App\Livewire\ArtifactPanel
 */
class ArtifactApiController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->pendingMigration('artifacts.list', 'App\\Livewire\\ArtifactPanel::loadArtifacts');
    }

    public function store(): JsonResponse
    {
        return $this->pendingMigration('artifacts.store', 'App\\Livewire\\ArtifactPanel');
    }

    public function show(int $artifact): JsonResponse
    {
        return $this->pendingMigration('artifacts.show', 'App\\Livewire\\ArtifactPanel::loadCurrentArtifact');
    }

    public function update(int $artifact): JsonResponse
    {
        return $this->pendingMigration('artifacts.update', 'App\\Livewire\\ArtifactPanel::updateArtifact');
    }

    public function destroy(int $artifact): JsonResponse
    {
        return $this->pendingMigration('artifacts.destroy', 'App\\Livewire\\ArtifactPanel::deleteArtifact');
    }
}

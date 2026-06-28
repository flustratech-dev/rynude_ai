<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Design REST surface (Phase 1 scaffold).
 *
 * Target logic source: App\Livewire\DesignPanel
 */
class DesignApiController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->pendingMigration('designs.list', 'App\\Livewire\\DesignPanel::getDesignsProperty');
    }

    public function store(): JsonResponse
    {
        return $this->pendingMigration('designs.store', 'App\\Livewire\\DesignPanel::generate');
    }

    public function update(int $design): JsonResponse
    {
        return $this->pendingMigration('designs.update', 'App\\Livewire\\DesignPanel::toggleStar');
    }

    public function destroy(int $design): JsonResponse
    {
        return $this->pendingMigration('designs.destroy', 'App\\Livewire\\DesignPanel::deleteDesign');
    }
}

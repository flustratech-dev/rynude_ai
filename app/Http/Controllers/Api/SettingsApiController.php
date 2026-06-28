<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Settings REST surface (Phase 1 scaffold).
 *
 * Target logic source: App\Livewire\SettingsModal
 */
class SettingsApiController extends ApiController
{
    public function show(): JsonResponse
    {
        return $this->pendingMigration('settings.show', 'App\\Livewire\\SettingsModal');
    }

    public function update(): JsonResponse
    {
        return $this->pendingMigration('settings.update', 'App\\Livewire\\SettingsModal::updateProfile');
    }

    public function validateApiKey(): JsonResponse
    {
        return $this->pendingMigration('settings.validate-api-key', 'App\\Livewire\\SettingsModal::validateApiKey');
    }
}

<?php

use App\Http\Controllers\Api\ArtifactApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\CoworkApiController;
use App\Http\Controllers\Api\DesignApiController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\SettingsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Livewire -> Pure Laravel migration)
|--------------------------------------------------------------------------
|
| Phase 1 (Routing Migration) deliverable: the REST surface that future
| phases will progressively implement. These routes are loaded from
| bootstrap/app.php inside the "web" middleware group (so they share the same
| session-based authentication and CSRF protection the rest of the app uses)
| under the "/api" prefix and the "api." route-name prefix.
|
| Every endpoint is registered, authenticated and tested today, but returns a
| uniform 501 "not implemented" contract (see Api\ApiController) until the
| owning Livewire logic is migrated. None of the existing Livewire routes are
| removed — the old and new surfaces run in parallel.
|
*/

Route::middleware('auth')->group(function () {
    // ── Chat ────────────────────────────────────────────────────────────
    Route::get('chats', [ChatApiController::class, 'index'])->name('chats.index');
    Route::post('chat/send', [ChatApiController::class, 'send'])->name('chat.send');
    Route::post('chat/stop', [ChatApiController::class, 'stop'])->name('chat.stop');
    Route::get('chat/{conversation}', [ChatApiController::class, 'show'])->name('chat.show');
    Route::patch('chat/{conversation}', [ChatApiController::class, 'update'])->name('chat.update');
    Route::delete('chat/{conversation}', [ChatApiController::class, 'destroy'])->name('chat.destroy');
    Route::post('chat/{conversation}/share', [ChatApiController::class, 'share'])->name('chat.share');

    // ── Artifacts ───────────────────────────────────────────────────────
    Route::get('artifacts', [ArtifactApiController::class, 'index'])->name('artifacts.index');
    Route::post('artifacts', [ArtifactApiController::class, 'store'])->name('artifacts.store');
    Route::get('artifacts/{artifact}', [ArtifactApiController::class, 'show'])->name('artifacts.show');
    Route::patch('artifacts/{artifact}', [ArtifactApiController::class, 'update'])->name('artifacts.update');
    Route::delete('artifacts/{artifact}', [ArtifactApiController::class, 'destroy'])->name('artifacts.destroy');

    // ── Cowork tasks ────────────────────────────────────────────────────
    Route::get('tasks', [CoworkApiController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [CoworkApiController::class, 'store'])->name('tasks.store');
    Route::patch('tasks/{task}', [CoworkApiController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [CoworkApiController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/run', [CoworkApiController::class, 'run'])->name('tasks.run');

    // ── Settings ────────────────────────────────────────────────────────
    Route::get('settings', [SettingsApiController::class, 'show'])->name('settings.show');
    Route::patch('settings', [SettingsApiController::class, 'update'])->name('settings.update');
    Route::post('settings/validate-api-key', [SettingsApiController::class, 'validateApiKey'])->name('settings.validate-api-key');

    // ── Projects ────────────────────────────────────────────────────────
    Route::get('projects', [ProjectApiController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectApiController::class, 'store'])->name('projects.store');
    Route::patch('projects/{project}', [ProjectApiController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectApiController::class, 'destroy'])->name('projects.destroy');

    // ── Designs ─────────────────────────────────────────────────────────
    Route::get('designs', [DesignApiController::class, 'index'])->name('designs.index');
    Route::post('designs', [DesignApiController::class, 'store'])->name('designs.store');
    Route::patch('designs/{design}', [DesignApiController::class, 'update'])->name('designs.update');
    Route::delete('designs/{design}', [DesignApiController::class, 'destroy'])->name('designs.destroy');
});

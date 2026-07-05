<?php

use App\Http\Controllers\Api\ArtifactApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\CoworkApiController;
use App\Http\Controllers\Api\DesignApiController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ProviderTokenController;
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
    // ── Chat (resource: chats, route-key: conversation) ─────────────────
    // Plural resource noun, matching artifacts/tasks/projects/designs. The two
    // collection-level actions (send/stop) are declared before the {conversation}
    // wildcard routes for clarity (no method clash either way).
    Route::get('chats', [ChatApiController::class, 'index'])->name('chats.index');
    // Declared before the {conversation} wildcard so 'provider-mode' is never
    // captured as a conversation id. Tells the client whether a model must be
    // run through the browser extension (claude.ai tab) instead of the server.
    Route::get('chats/provider-mode', [ChatApiController::class, 'providerMode'])->name('chats.provider-mode');
    Route::post('chats/send', [ChatApiController::class, 'send'])->name('chats.send');
    Route::post('chats/stop', [ChatApiController::class, 'stop'])->name('chats.stop');
    Route::post('chats/connect-repo', [ChatApiController::class, 'connectRepo'])->name('chats.connect-repo');
    Route::post('chats/disconnect-repo', [ChatApiController::class, 'disconnectRepo'])->name('chats.disconnect-repo');
    // Declared before the {conversation} wildcard so 'messages' is never
    // captured as a conversation id.
    Route::patch('chats/messages/{message}/rating', [ChatApiController::class, 'rateMessage'])->name('chats.messages.rating');
    Route::post('chats/{conversation}/regenerate', [ChatApiController::class, 'regenerate'])->name('chats.regenerate');
    // Save + stream a reply that the browser extension generated (claude.ai tab).
    Route::post('chats/{conversation}/complete-extension', [ChatApiController::class, 'completeExtension'])->name('chats.complete-extension');
    Route::post('chats/{conversation}/switch-branch', [ChatApiController::class, 'switchBranch'])->name('chats.switch-branch');
    Route::get('chats/{conversation}/stream-resume', [ChatApiController::class, 'streamResume'])->name('chats.stream-resume');
    Route::get('chats/{conversation}', [ChatApiController::class, 'show'])->name('chats.show');
    Route::patch('chats/{conversation}', [ChatApiController::class, 'update'])->name('chats.update');
    Route::delete('chats/{conversation}', [ChatApiController::class, 'destroy'])->name('chats.destroy');
    Route::post('chats/{conversation}/share', [ChatApiController::class, 'share'])->name('chats.share');
    Route::get('chats/{conversation}/export', [ChatApiController::class, 'export'])->name('chats.export');

    // ── Artifacts ───────────────────────────────────────────────────────
    Route::get('artifacts', [ArtifactApiController::class, 'index'])->name('artifacts.index');
    Route::post('artifacts', [ArtifactApiController::class, 'store'])->name('artifacts.store');
    Route::get('artifacts/{artifact}', [ArtifactApiController::class, 'show'])->name('artifacts.show');
    Route::patch('artifacts/{artifact}', [ArtifactApiController::class, 'update'])->name('artifacts.update');
    Route::delete('artifacts/{artifact}', [ArtifactApiController::class, 'destroy'])->name('artifacts.destroy');
    Route::get('artifacts/{artifact}/download/pdf', [ArtifactApiController::class, 'downloadPdf'])->name('artifacts.download.pdf');
    Route::get('artifacts/{artifact}/download/docx', [ArtifactApiController::class, 'downloadDocx'])->name('artifacts.download.docx');
    Route::get('artifacts/{artifact}/download/markdown', [ArtifactApiController::class, 'downloadMarkdown'])->name('artifacts.download.markdown');
    Route::get('artifacts/{artifact}/download/file', [ArtifactApiController::class, 'downloadFile'])->name('artifacts.download.file');
    Route::get('artifacts/{artifact}/download/txt', [ArtifactApiController::class, 'downloadTxt'])->name('artifacts.download.txt');
    Route::get('artifacts/{artifact}/download/html', [ArtifactApiController::class, 'downloadHtml'])->name('artifacts.download.html');

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
    Route::get('token-usage', [SettingsApiController::class, 'tokenUsage'])->name('token-usage.index');

    // ── Projects ────────────────────────────────────────────────────────
    Route::get('projects', [ProjectApiController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectApiController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectApiController::class, 'show'])->name('projects.show');
    Route::patch('projects/{project}', [ProjectApiController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectApiController::class, 'destroy'])->name('projects.destroy');
    Route::post('projects/{project}/files', [ProjectApiController::class, 'uploadFile'])->name('projects.files.upload');
    Route::delete('projects/{project}/files/{file}', [ProjectApiController::class, 'deleteFile'])->name('projects.files.delete');
    Route::post('projects/{project}/duplicate', [ProjectApiController::class, 'duplicate'])->name('projects.duplicate');

    // ── Designs ─────────────────────────────────────────────────────────
    Route::get('designs', [DesignApiController::class, 'index'])->name('designs.index');
    Route::post('designs', [DesignApiController::class, 'store'])->name('designs.store');
    Route::patch('designs/{design}', [DesignApiController::class, 'update'])->name('designs.update');
    Route::delete('designs/{design}', [DesignApiController::class, 'destroy'])->name('designs.destroy');

    // ── Provider Web Tokens (from browser extension) ─────────────────────
    Route::post('provider-tokens', [ProviderTokenController::class, 'store'])->name('provider-tokens.store');
    Route::get('provider-tokens', [ProviderTokenController::class, 'index'])->name('provider-tokens.index');
    Route::delete('provider-tokens/{provider}', [ProviderTokenController::class, 'destroy'])->name('provider-tokens.destroy');
});

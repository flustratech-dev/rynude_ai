<?php

use App\Http\Controllers\ClaudeCodeController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Guests land on the login page; signed-in users go straight to the app.
    return auth()->check() ? view('chat') : redirect()->route('login');
})->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('chat');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/chat', function () {
    return view('chat');
})->name('chat');

// Phase 1 (Routing Migration): the /code and /design pages now through
// dedicated controllers; Livewire components fully retired.
Route::get('/code', [ClaudeCodeController::class, 'index'])->middleware(['auth', 'verified'])->name('code');
Route::get('/design', [DesignController::class, 'index'])->middleware(['auth', 'verified'])->name('design');

// Signal the active streaming generation to stop. Uses a cache flag that the
// streaming loop in ChatInterface::generateResponse() polls each chunk.
Route::post('/chat-stop', function (\Illuminate\Http\Request $request) {
    $conversationId = $request->input('conversation_id');
    if ($conversationId) {
        $owns = \App\Models\Conversation::where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->exists();
        if ($owns) {
            Cache::put('chat_stop_' . $conversationId, true, 120);
        }
    }
    return response()->noContent();
})->middleware('auth')->name('chat.stop');

// Public, read-only shared conversation. No auth required.
Route::get('/share/{token}', function (string $token) {
    $conversation = \App\Models\Conversation::with(['messages' => fn ($q) => $q->orderBy('id')])
        ->where('share_token', $token)
        ->firstOrFail();

    return view('shared-chat', ['conversation' => $conversation]);
})->name('chat.shared');

// Public, read-only published artifact. No auth required.
Route::get('/artifact/{token}', function (string $token) {
    $artifact = \App\Models\MessageArtifact::where('public_token', $token)
        ->where('is_public', true)
        ->firstOrFail();

    return view('shared-artifact', ['artifact' => $artifact]);
})->name('artifact.shared');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/artifact/{id}/preview.pdf', function ($id) {
        $model = \App\Models\MessageArtifact::where('id', $id)->firstOrFail();

        $userId = auth()->id();
        $owns = \App\Models\MessageArtifact::where('id', $id)
                ->whereHas('message.conversation', fn ($q) => $q->where('user_id', $userId))
                ->exists();

        if (!$owns) {
            abort(403);
        }

        // Extract mode from front-matter if present
        $mode = null;
        if (preg_match('/^---\r?\n(.*?)\r?\n---/s', $model->content, $matches)) {
            if (preg_match('/mode:\s*(skripsi|laporan|jurnal|document)/i', $matches[1], $m)) {
                $mode = strtolower(trim($m[1]));
            }
        }

        // Cache the rendered PDF for 1 hour, keyed by id + content hash + mode.
        // mPDF takes 1-30s for academic documents; switching tabs in the artifact
        // panel used to fire a fresh render every time. The hash key invalidates
        // automatically when the artifact content changes.
        $cacheKey = sprintf(
            'artifact_pdf:%d:%s:%s',
            $id,
            md5((string) $model->content),
            $mode ?? 'auto'
        );

        $binary = Cache::remember($cacheKey, 3600, function () use ($model, $mode) {
            return app(\App\Services\PdfRenderer::class)->render($model->toArray(), $mode);
        });

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
            // Browser-side hint: same id + same content == same bytes.
            'Cache-Control' => 'private, max-age=300',
            'ETag' => '"' . md5($cacheKey) . '"',
        ]);
    })->name('artifact.preview.pdf');
});

require __DIR__.'/auth.php';

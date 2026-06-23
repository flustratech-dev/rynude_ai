<?php

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

Route::get('/code', \App\Livewire\ClaudeCodeApp::class)->middleware(['auth', 'verified'])->name('code');
Route::get('/design', \App\Livewire\DesignPanel::class)->middleware(['auth', 'verified'])->name('design');

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
});

require __DIR__.'/auth.php';

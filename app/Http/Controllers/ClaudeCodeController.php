<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Phase 1 (Routing Migration) entry point for the Claude Code IDE page.
 *
 * Replaces the previous Livewire full-page route
 * (Route::get('/code', \App\Livewire\ClaudeCodeApp::class)). The controller is
 * now the route entry point, but it intentionally delegates all behaviour to the
 * existing Livewire component by mounting it inside the wrapper view
 * (resources/views/code.blade.php -> @livewire('claude-code-app')).
 *
 * No business logic lives here: the component keeps its full implementation so
 * the old Livewire experience continues to work unchanged. Later phases will
 * progressively move that logic into the API controllers under App\Http\Controllers\Api.
 */
class ClaudeCodeController extends Controller
{
    public function index(): \Illuminate\Http\RedirectResponse
    {
        // The Livewire claude-code-app component is being retired as part of the
        // Livewire-to-API migration. Redirect to /chat until a native replacement exists.
        return redirect()->route('chat');
    }
}

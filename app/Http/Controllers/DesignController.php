<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Phase 1 (Routing Migration) entry point for the Design page.
 *
 * Replaces the previous Livewire full-page route
 * (Route::get('/design', \App\Livewire\DesignPanel::class)). The controller is
 * the new route entry point and renders the wrapper view
 * (resources/views/design.blade.php -> @livewire('design-panel')), which keeps
 * the existing Livewire component and all of its logic intact.
 */
class DesignController extends Controller
{
    public function index(): \Illuminate\Http\RedirectResponse
    {
        // The Livewire design-panel component is being retired as part of the
        // Livewire-to-API migration. Redirect to /chat until a native replacement exists.
        return redirect()->route('chat');
    }
}

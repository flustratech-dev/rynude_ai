<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Entry point for the standalone Design page.
 *
 * Like the Claude Code page (/code), Design is its own full-screen route
 * rendered without the chat sidebar. It renders the wrapper view
 * (resources/views/design.blade.php), which mounts the design-panel and its
 * API-backed Alpine logic inside the sidebar-less app layout.
 */
class DesignController extends Controller
{
    public function index(): View
    {
        return view('design');
    }
}

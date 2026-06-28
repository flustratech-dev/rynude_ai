<?php

namespace Tests\Feature\Routing;

use App\Http\Controllers\ClaudeCodeController;
use App\Http\Controllers\DesignController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 (Routing Migration) — page entry points.
 *
 * Verifies that /code and /design now enter through the new controllers, and
 * that /code and /design redirect to chat now that Livewire components are retired.
 */
class PageRoutesTest extends TestCase
{
    use RefreshDatabase;

    // ── Authentication is still enforced on every page ──────────────────

    public function test_code_page_requires_authentication(): void
    {
        $this->get('/code')->assertRedirect('/login');
    }

    public function test_design_page_requires_authentication(): void
    {
        $this->get('/design')->assertRedirect('/login');
    }

    // ── Controller entry points redirect to chat ────────────────────────

    public function test_code_page_redirects_to_chat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/code')
            ->assertRedirect(route('chat'));
    }

    public function test_design_page_redirects_to_chat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/design')
            ->assertRedirect(route('chat'));
    }

    // ── The named routes resolve to the controllers ─────────────────────

    public function test_code_route_resolves_to_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('code');

        $this->assertNotNull($route);
        $this->assertSame(ClaudeCodeController::class.'@index', $route->getActionName());
    }

    public function test_design_route_resolves_to_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('design');

        $this->assertNotNull($route);
        $this->assertSame(DesignController::class.'@index', $route->getActionName());
    }
}

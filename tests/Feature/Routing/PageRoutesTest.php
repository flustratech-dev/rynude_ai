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
 * Verifies that /code and /design now enter through the new controllers, that
 * they still render the existing Livewire components (no behaviour change), and
 * that the original Livewire full-page routes keep working in parallel under
 * /legacy/*.
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

    public function test_legacy_code_route_requires_authentication(): void
    {
        $this->get('/legacy/code')->assertRedirect('/login');
    }

    public function test_legacy_design_route_requires_authentication(): void
    {
        $this->get('/legacy/design')->assertRedirect('/login');
    }

    // ── New controller entry points render the Livewire components ───────

    public function test_code_page_renders_via_controller(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/code')
            ->assertOk()
            ->assertSee('Rynude Code')   // claude-code-app component is mounted
            ->assertSee('wire:id', false); // proof a Livewire component rendered
    }

    public function test_design_page_renders_via_controller(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/design')
            ->assertOk()
            ->assertSee('Rynude Design'); // design-panel component is mounted
    }

    // ── Old Livewire routes still work in parallel (rollback path) ──────

    public function test_legacy_code_route_still_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/legacy/code')
            ->assertOk()
            ->assertSee('Rynude Code');
    }

    public function test_legacy_design_route_still_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/legacy/design')
            ->assertOk()
            ->assertSee('Rynude Design');
    }

    // ── The named routes resolve to the controllers, not components ─────

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

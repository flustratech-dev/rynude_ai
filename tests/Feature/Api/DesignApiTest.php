<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeAiService;
use Tests\TestCase;

/**
 * API Layer Migration — Designs API.
 *
 * Covers design CRUD + synchronous AI generation migrated out of
 * App\Livewire\DesignPanel into App\Http\Controllers\Api\DesignApiController.
 * AiService is faked so no network calls happen.
 */
class DesignApiTest extends TestCase
{
    use RefreshDatabase;

    private FakeAiService $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeAiService();
        $this->app->instance(AiService::class, $this->fake);
    }

    private function makeDesign(User $user, array $attributes = []): Design
    {
        return Design::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Demo Design',
            'type' => 'prototype',
            'prompt' => 'A demo prototype',
            'status' => 'ready',
            'content' => '<!DOCTYPE html><html></html>',
        ], $attributes));
    }

    public function test_index_lists_only_the_authenticated_users_designs(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeDesign($user, ['title' => 'Mine']);
        $this->makeDesign($other, ['title' => 'Theirs']);

        $this->actingAs($user)->getJson('/api/designs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine');
    }

    public function test_index_orders_by_newest_first(): void
    {
        $user = User::factory()->create();
        $old = $this->makeDesign($user, ['title' => 'Old']);
        $old->created_at = now()->subDay();
        $old->save();
        $this->makeDesign($user, ['title' => 'New']);

        $this->actingAs($user)->getJson('/api/designs')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'New');
    }

    public function test_index_filters_by_search(): void
    {
        $user = User::factory()->create();
        $this->makeDesign($user, ['title' => 'Landing Page', 'prompt' => 'hero section']);
        $this->makeDesign($user, ['title' => 'Dashboard', 'prompt' => 'charts']);

        $this->actingAs($user)->getJson('/api/designs?search=landing')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Landing Page');
    }

    public function test_index_yours_tab_excludes_draft_designs(): void
    {
        $user = User::factory()->create();
        $this->makeDesign($user, ['title' => 'Ready One', 'status' => 'ready']);
        $this->makeDesign($user, ['title' => 'Draft One', 'status' => 'draft']);

        $this->actingAs($user)->getJson('/api/designs?tab=yours')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Ready One');
    }

    public function test_store_creates_a_design_and_generates_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/designs', [
            'type' => 'prototype',
            'prompt' => 'A pricing page with three tiers',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'prototype')
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.content', $this->fake->cannedResponse)
            ->assertJsonPath('data.title', 'A pricing page with three tiers');

        $this->assertDatabaseHas('designs', [
            'user_id' => $user->id,
            'type' => 'prototype',
            'status' => 'ready',
        ]);
    }

    public function test_store_uses_the_design_type_label_in_the_system_prompt(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/designs', [
            'type' => 'slides',
            'prompt' => 'Investor pitch deck',
        ])->assertCreated();

        $this->assertStringContainsString('Slides', $this->fake->lastSystemPrompt());
    }

    public function test_store_strips_markdown_fences_from_generated_html(): void
    {
        $user = User::factory()->create();
        $this->fake->cannedResponse = "```html\n<!DOCTYPE html><h1>Hi</h1>\n```";

        $this->actingAs($user)->postJson('/api/designs', [
            'type' => 'prototype',
            'prompt' => 'simple page',
        ])->assertCreated()
            ->assertJsonPath('data.content', '<!DOCTYPE html><h1>Hi</h1>')
            ->assertJsonPath('data.status', 'ready');
    }

    public function test_store_marks_design_failed_when_generation_returns_error(): void
    {
        $user = User::factory()->create();
        $this->fake->cannedResponse = '[Error: API key is not configured]';

        $this->actingAs($user)->postJson('/api/designs', [
            'type' => 'prototype',
            'prompt' => 'whatever',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'failed');
    }

    public function test_store_validates_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/designs', [
            'type' => 'hologram',
            'prompt' => 'x',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_store_requires_a_prompt(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/designs', [
            'type' => 'prototype',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('prompt');
    }

    public function test_update_toggles_star_on_owned_design(): void
    {
        $user = User::factory()->create();
        $design = $this->makeDesign($user, ['is_starred' => false]);

        $this->actingAs($user)->patchJson("/api/designs/{$design->id}", [
            'is_starred' => true,
        ])->assertOk()
            ->assertJsonPath('data.is_starred', true);

        $this->assertDatabaseHas('designs', [
            'id' => $design->id,
            'is_starred' => true,
        ]);
    }

    public function test_update_can_rename_design(): void
    {
        $user = User::factory()->create();
        $design = $this->makeDesign($user, ['title' => 'Old Title']);

        $this->actingAs($user)->patchJson("/api/designs/{$design->id}", [
            'title' => 'New Title',
        ])->assertOk()
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_cannot_update_another_users_design(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $design = $this->makeDesign($other, ['is_starred' => false]);

        $this->actingAs($user)->patchJson("/api/designs/{$design->id}", [
            'is_starred' => true,
        ])->assertForbidden();

        $this->assertDatabaseHas('designs', [
            'id' => $design->id,
            'is_starred' => false,
        ]);
    }

    public function test_destroy_deletes_owned_design(): void
    {
        $user = User::factory()->create();
        $design = $this->makeDesign($user);

        $this->actingAs($user)->deleteJson("/api/designs/{$design->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('designs', ['id' => $design->id]);
    }

    public function test_cannot_delete_another_users_design(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $design = $this->makeDesign($other);

        $this->actingAs($user)->deleteJson("/api/designs/{$design->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('designs', ['id' => $design->id]);
    }

    public function test_update_missing_design_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/designs/99999', ['is_starred' => true])
            ->assertNotFound();
    }

    public function test_design_endpoints_require_authentication(): void
    {
        $this->getJson('/api/designs')->assertUnauthorized();
        $this->postJson('/api/designs', ['type' => 'prototype', 'prompt' => 'x'])->assertUnauthorized();
        $this->patchJson('/api/designs/1', ['is_starred' => true])->assertUnauthorized();
        $this->deleteJson('/api/designs/1')->assertUnauthorized();
    }
}

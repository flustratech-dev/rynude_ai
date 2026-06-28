<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * API Layer Migration — Projects API.
 *
 * Covers project CRUD migrated out of App\Livewire\ProjectsPanel into
 * App\Http\Controllers\Api\ProjectApiController.
 */
class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(User $user, array $attributes = []): Project
    {
        return $user->projects()->create(array_merge([
            'name' => 'Demo Project',
            'description' => 'A demo project',
            'color' => '#D97757',
            'icon' => '📁',
        ], $attributes));
    }

    public function test_index_lists_only_the_authenticated_users_projects(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeProject($user, ['name' => 'Mine']);
        $this->makeProject($other, ['name' => 'Theirs']);

        $response = $this->actingAs($user)->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_index_returns_chat_count(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        Conversation::create(['user_id' => $user->id, 'project_id' => $project->id, 'title' => 'A']);
        Conversation::create(['user_id' => $user->id, 'project_id' => $project->id, 'title' => 'B']);

        $this->actingAs($user)->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('data.0.chat_count', 2);
    }

    public function test_index_filters_by_search_query(): void
    {
        $user = User::factory()->create();
        $this->makeProject($user, ['name' => 'Python Scraper']);
        $this->makeProject($user, ['name' => 'Marketing Site']);

        $this->actingAs($user)->getJson('/api/projects?search=python')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Python Scraper');
    }

    public function test_index_floats_starred_projects_to_the_top(): void
    {
        $user = User::factory()->create();
        $this->makeProject($user, ['name' => 'Plain', 'is_starred' => false]);
        $this->makeProject($user, ['name' => 'Starred', 'is_starred' => true]);

        $this->actingAs($user)->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Starred');
    }

    public function test_index_can_sort_by_name(): void
    {
        $user = User::factory()->create();
        $this->makeProject($user, ['name' => 'Zebra']);
        $this->makeProject($user, ['name' => 'Apple']);

        $this->actingAs($user)->getJson('/api/projects?sort=name')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Apple');
    }

    public function test_store_creates_a_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/projects', [
            'name' => 'New Project',
            'description' => 'Build something',
            'color' => '#5E72E4',
            'icon' => '🚀',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Project')
            ->assertJsonPath('data.color', '#5E72E4')
            ->assertJsonPath('data.icon', '🚀')
            ->assertJsonPath('data.chat_count', 0);

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'New Project',
        ]);
    }

    public function test_store_defaults_color_and_icon(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/projects', ['name' => 'Bare'])
            ->assertCreated()
            ->assertJsonPath('data.color', '#D97757')
            ->assertJsonPath('data.icon', '📁');
    }

    public function test_store_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/projects', ['description' => 'no name'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_modifies_owned_project(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user, ['name' => 'Before']);

        $this->actingAs($user)->patchJson("/api/projects/{$project->id}", [
            'name' => 'After',
            'is_starred' => true,
        ])->assertOk()
            ->assertJsonPath('data.name', 'After')
            ->assertJsonPath('data.is_starred', true);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'After',
            'is_starred' => true,
        ]);
    }

    public function test_update_can_save_custom_instructions(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        $this->actingAs($user)->patchJson("/api/projects/{$project->id}", [
            'custom_instructions' => 'Use TypeScript everywhere.',
        ])->assertOk()
            ->assertJsonPath('data.custom_instructions', 'Use TypeScript everywhere.');
    }

    public function test_cannot_update_another_users_project(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = $this->makeProject($other);

        $this->actingAs($user)->patchJson("/api/projects/{$project->id}", [
            'name' => 'Hijacked',
        ])->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Demo Project',
        ]);
    }

    public function test_update_missing_project_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/projects/99999', ['name' => 'X'])
            ->assertNotFound();
    }

    public function test_destroy_deletes_owned_project_and_its_files(): void
    {
        Storage::fake();
        $user = User::factory()->create();
        $project = $this->makeProject($user);

        Storage::put('project_knowledge/doc.txt', 'hello');
        ProjectFile::create([
            'project_id' => $project->id,
            'file_name' => 'doc.txt',
            'file_path' => 'project_knowledge/doc.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
        ]);

        $this->actingAs($user)->deleteJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        Storage::assertMissing('project_knowledge/doc.txt');
    }

    public function test_cannot_delete_another_users_project(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = $this->makeProject($other);

        $this->actingAs($user)->deleteJson("/api/projects/{$project->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_project_endpoints_require_authentication(): void
    {
        $this->getJson('/api/projects')->assertUnauthorized();
        $this->postJson('/api/projects', ['name' => 'x'])->assertUnauthorized();
        $this->patchJson('/api/projects/1', ['name' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/projects/1')->assertUnauthorized();
    }
}

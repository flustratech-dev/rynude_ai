<?php

namespace Tests\Feature\Api;

use App\Models\CoworkTask;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeAiService;
use Tests\TestCase;

/**
 * API Layer Migration — Cowork Tasks API.
 *
 * Covers task CRUD + synchronous AI execution migrated out of
 * App\Livewire\CoworkPanel into App\Http\Controllers\Api\CoworkApiController.
 * AiService is faked so no network calls happen.
 */
class CoworkApiTest extends TestCase
{
    use RefreshDatabase;

    private FakeAiService $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeAiService();
        $this->app->instance(AiService::class, $this->fake);
    }

    private function makeTask(User $user, array $attributes = []): CoworkTask
    {
        return CoworkTask::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Demo Task',
            'description' => 'A demo task description',
            'priority' => 'medium',
            'model' => 'claude-haiku-4-5',
            'status' => 'pending',
        ], $attributes));
    }

    public function test_index_lists_only_the_authenticated_users_tasks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeTask($user, ['title' => 'Mine']);
        $this->makeTask($other, ['title' => 'Theirs']);

        $this->actingAs($user)->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine');
    }

    public function test_index_returns_stats(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['status' => 'pending']);
        $this->makeTask($user, ['status' => 'in_progress']);
        $this->makeTask($user, ['status' => 'completed']);

        $this->actingAs($user)->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('stats.total', 3)
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('stats.in_progress', 1)
            ->assertJsonPath('stats.completed', 1);
    }

    public function test_index_orders_by_status_priority_then_newest(): void
    {
        $user = User::factory()->create();

        $completed = $this->makeTask($user, ['title' => 'Completed', 'status' => 'completed']);
        $inProgress = $this->makeTask($user, ['title' => 'In Progress', 'status' => 'in_progress']);
        $pending = $this->makeTask($user, ['title' => 'Pending', 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson('/api/tasks')
            ->assertOk();

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertEquals(['In Progress', 'Pending', 'Completed'], $titles);
    }

    public function test_index_filters_by_status(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'Pending Task', 'status' => 'pending']);
        $this->makeTask($user, ['title' => 'Completed Task', 'status' => 'completed']);

        $this->actingAs($user)->getJson('/api/tasks?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Pending Task');
    }

    public function test_store_creates_a_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'New Task',
            'description' => 'Task description',
            'priority' => 'high',
            'model' => 'claude-sonnet-4-6',
            'scheduled_for' => '2026-12-25',
        ])->assertCreated();

        $this->assertDatabaseHas('cowork_tasks', [
            'user_id' => $user->id,
            'title' => 'New Task',
            'description' => 'Task description',
            'priority' => 'high',
            'model' => 'claude-sonnet-4-6',
            'status' => 'pending',
        ]);

        $response->assertJsonPath('data.title', 'New Task')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/tasks', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'priority', 'model']);
    }

    public function test_store_validates_priority_enum(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'Task',
            'priority' => 'invalid',
            'model' => 'claude-haiku-4-5',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_update_changes_task_fields(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['title' => 'Old Title', 'priority' => 'low']);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'title' => 'New Title',
            'priority' => 'high',
        ])->assertOk()
            ->assertJsonPath('data.title', 'New Title')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('cowork_tasks', [
            'id' => $task->id,
            'title' => 'New Title',
            'priority' => 'high',
        ]);
    }

    public function test_update_can_change_status_to_completed(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['status' => 'pending']);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])->assertOk();

        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_update_clears_completed_at_when_status_changes_from_completed(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'status' => 'pending',
        ])->assertOk();

        $task->refresh();
        $this->assertEquals('pending', $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_update_validates_status_enum(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'status' => 'invalid',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_rejects_tasks_belonging_to_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->makeTask($other);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_destroy_deletes_a_task(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('cowork_tasks', ['id' => $task->id]);
    }

    public function test_destroy_rejects_tasks_belonging_to_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->makeTask($other);

        $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('cowork_tasks', ['id' => $task->id]);
    }

    public function test_run_executes_task_and_stores_result(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, [
            'title' => 'Write a haiku',
            'description' => 'About coding',
            'status' => 'pending',
        ]);

        $this->fake->cannedResponse = 'Code flows like water';

        $this->actingAs($user)->postJson("/api/tasks/{$task->id}/run")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.result', 'Code flows like water');

        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertEquals('Code flows like water', $task->result);
        $this->assertNotNull($task->completed_at);
    }

    public function test_run_marks_task_as_failed_on_error_response(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        $this->fake->cannedResponse = '[Error: API key is not configured]';

        $this->actingAs($user)->postJson("/api/tasks/{$task->id}/run")
            ->assertOk();

        $task->refresh();
        $this->assertEquals('failed', $task->status);
        $this->assertStringContainsString('[Error', $task->result);
        $this->assertNull($task->completed_at);
    }

    public function test_run_catches_exceptions_and_marks_as_failed(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        // Create a throwing mock for this specific test
        $throwingService = $this->createMock(AiService::class);
        $throwingService->method('streamResponse')
            ->willThrowException(new \RuntimeException('Network error'));
        $this->app->instance(AiService::class, $throwingService);

        $this->actingAs($user)->postJson("/api/tasks/{$task->id}/run")
            ->assertOk();

        $task->refresh();
        $this->assertEquals('failed', $task->status);
        $this->assertStringContainsString('Network error', $task->result);
    }

    public function test_run_rejects_tasks_belonging_to_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->makeTask($other);

        $this->actingAs($user)->postJson("/api/tasks/{$task->id}/run")
            ->assertForbidden();
    }

    public function test_run_uses_task_model_for_ai_service(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['model' => 'claude-opus-4-8']);

        $this->fake->cannedResponse = 'Result';

        $this->actingAs($user)->postJson("/api/tasks/{$task->id}/run")
            ->assertOk();

        $this->assertCount(1, $this->fake->calls);
        $this->assertEquals('claude-opus-4-8', $this->fake->calls[0]['model']);
    }

    public function test_requires_authentication_for_all_endpoints(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        $this->getJson('/api/tasks')->assertUnauthorized();
        $this->postJson('/api/tasks', [])->assertUnauthorized();
        $this->patchJson("/api/tasks/{$task->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/tasks/{$task->id}")->assertUnauthorized();
        $this->postJson("/api/tasks/{$task->id}/run")->assertUnauthorized();
    }
}

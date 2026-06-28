<?php

namespace Tests\Feature\Routing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API route structure for the Livewire -> pure Laravel migration.
 *
 * Every endpoint in the new /api surface is exercised here. These tests lock in:
 *   - the route exists and resolves (no 404),
 *   - authentication is enforced (guest -> 401 for JSON requests),
 *   - endpoints whose logic has NOT yet been migrated still return the uniform
 *     501 "not implemented" contract.
 *
 * As each endpoint group gains real behaviour (see the dedicated *ApiTest
 * feature tests), it moves out of the pending provider below. Implemented
 * groups so far: Settings, Projects, Designs, Chat (CRUD + share + streaming),
 * Artifacts (CRUD), Cowork (tasks). Still pending: artifact store (Phase 5 —
 * artifacts are created by the streamed generation).
 */
class ApiRouteStructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every registered /api endpoint, regardless of implementation status.
     * Used to assert authentication is enforced across the whole surface.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function apiEndpointProvider(): array
    {
        return array_merge(static::pendingApiEndpointProvider(), [
            // Chat (implemented: CRUD + share + streaming)
            'chat list'        => ['get', '/api/chats'],
            'chat send'        => ['post', '/api/chats/send'],
            'chat stop'        => ['post', '/api/chats/stop'],
            'chat show'        => ['get', '/api/chats/1'],
            'chat update'      => ['patch', '/api/chats/1'],
            'chat destroy'     => ['delete', '/api/chats/1'],
            'chat share'       => ['post', '/api/chats/1/share'],

            // Artifacts (implemented: CRUD)
            'artifacts list'   => ['get', '/api/artifacts'],
            'artifacts show'   => ['get', '/api/artifacts/1'],
            'artifacts update' => ['patch', '/api/artifacts/1'],
            'artifacts delete' => ['delete', '/api/artifacts/1'],

            // Settings (implemented)
            'settings show'    => ['get', '/api/settings'],
            'settings update'  => ['patch', '/api/settings'],
            'settings vkey'    => ['post', '/api/settings/validate-api-key'],

            // Projects (implemented)
            'projects list'    => ['get', '/api/projects'],
            'projects store'   => ['post', '/api/projects'],
            'projects update'  => ['patch', '/api/projects/1'],
            'projects delete'  => ['delete', '/api/projects/1'],

            // Designs (implemented)
            'designs list'     => ['get', '/api/designs'],
            'designs store'    => ['post', '/api/designs'],
            'designs update'   => ['patch', '/api/designs/1'],
            'designs delete'   => ['delete', '/api/designs/1'],

            // Cowork tasks (implemented)
            'tasks list'       => ['get', '/api/tasks'],
            'tasks store'      => ['post', '/api/tasks'],
            'tasks update'     => ['patch', '/api/tasks/1'],
            'tasks delete'     => ['delete', '/api/tasks/1'],
            'tasks run'        => ['post', '/api/tasks/1/run'],
        ]);
    }

    /**
     * Endpoints whose business logic still lives in a Livewire component and
     * therefore still return the 501 "not implemented" contract.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pendingApiEndpointProvider(): array
    {
        return [
            // Artifacts — creation happens inside the streamed generation (Phase 5).
            'artifacts store'  => ['post', '/api/artifacts'],
        ];
    }

    /**
     * @dataProvider apiEndpointProvider
     */
    public function test_api_endpoint_requires_authentication(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized(); // 401 for JSON guests
    }

    /**
     * @dataProvider pendingApiEndpointProvider
     */
    public function test_api_endpoint_returns_not_implemented_contract(string $method, string $uri): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json($method, $uri);

        $response->assertStatus(501)
            ->assertJson(['status' => 'not_implemented'])
            ->assertJsonStructure([
                'status',
                'feature',
                'message',
                'migration' => ['phase', 'source'],
            ]);
    }

    public function test_api_route_count_is_registered(): void
    {
        $apiRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'));

        // 36 endpoints make up the migrated API surface.
        $this->assertCount(36, $apiRoutes);
    }
}

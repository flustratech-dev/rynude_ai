<?php

namespace Tests\Feature\Routing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 (Routing Migration) — API route structure for the future migration.
 *
 * Every endpoint in the new /api surface is exercised here. In Phase 1 each one
 * is registered, authenticated and returns a uniform 501 "not implemented"
 * contract (its logic is migrated in later phases). These tests lock in:
 *   - the route exists and resolves (no 404),
 *   - authentication is enforced (guest -> 401 for JSON requests),
 *   - the not-implemented contract shape is stable.
 *
 * As each endpoint gains real behaviour in Phase 4/5, the corresponding
 * not-implemented assertion will be replaced by a behavioural one.
 */
class ApiRouteStructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function apiEndpointProvider(): array
    {
        return [
            // Chat
            'chat list'        => ['get', '/api/chats'],
            'chat send'        => ['post', '/api/chat/send'],
            'chat stop'        => ['post', '/api/chat/stop'],
            'chat show'        => ['get', '/api/chat/1'],
            'chat update'      => ['patch', '/api/chat/1'],
            'chat destroy'     => ['delete', '/api/chat/1'],
            'chat share'       => ['post', '/api/chat/1/share'],

            // Artifacts
            'artifacts list'   => ['get', '/api/artifacts'],
            'artifacts store'  => ['post', '/api/artifacts'],
            'artifacts show'   => ['get', '/api/artifacts/1'],
            'artifacts update' => ['patch', '/api/artifacts/1'],
            'artifacts delete' => ['delete', '/api/artifacts/1'],

            // Cowork tasks
            'tasks list'       => ['get', '/api/tasks'],
            'tasks store'      => ['post', '/api/tasks'],
            'tasks update'     => ['patch', '/api/tasks/1'],
            'tasks delete'     => ['delete', '/api/tasks/1'],
            'tasks run'        => ['post', '/api/tasks/1/run'],

            // Settings
            'settings show'    => ['get', '/api/settings'],
            'settings update'  => ['patch', '/api/settings'],
            'settings vkey'    => ['post', '/api/settings/validate-api-key'],

            // Projects
            'projects list'    => ['get', '/api/projects'],
            'projects store'   => ['post', '/api/projects'],
            'projects update'  => ['patch', '/api/projects/1'],
            'projects delete'  => ['delete', '/api/projects/1'],

            // Designs
            'designs list'     => ['get', '/api/designs'],
            'designs store'    => ['post', '/api/designs'],
            'designs update'   => ['patch', '/api/designs/1'],
            'designs delete'   => ['delete', '/api/designs/1'],
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
     * @dataProvider apiEndpointProvider
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

        // 28 endpoints make up the Phase 1 API surface.
        $this->assertCount(28, $apiRoutes);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ModelHubApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_model_hub_endpoints(): void
    {
        $this->getJson('/api/models')->assertStatus(401);
        $this->postJson('/api/models/download', ['model_id' => 'qwen-2.5-0.5b'])->assertStatus(401);
        $this->getJson('/api/models/progress')->assertStatus(401);
        $this->deleteJson('/api/models/qwen-2.5-0.5b')->assertStatus(401);
    }

    public function test_authenticated_user_can_get_models_catalog(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/models');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'free_space_gb',
                'models' => [
                    '*' => [
                        'id',
                        'name',
                        'parameter_size',
                        'required_ram_gb',
                        'file_size_label',
                        'filename',
                        'is_downloaded',
                        'status',
                        'progress',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('models'));
    }

    public function test_download_model_validates_model_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/models/download', [
            'model_id' => 'invalid-model-id-12345',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_download_model_starts_simulation_in_test_env(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/models/download', [
            'model_id' => 'llama-3.2-3b',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'downloading',
            ]);

        $cache = Cache::get('model_download_llama-3.2-3b');
        $this->assertNotNull($cache);
        $this->assertEquals('downloading', $cache['status']);
    }

    public function test_progress_endpoint_returns_cache_state(): void
    {
        $user = User::factory()->create();

        Cache::put('model_download_qwen-2.5-0.5b', [
            'status' => 'downloading',
            'progress' => 50.0,
        ], 3600);

        $response = $this->actingAs($user)->getJson('/api/models/progress');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'progress' => [
                    'qwen-2.5-0.5b' => [
                        'status' => 'downloading',
                        'progress' => 50.0,
                    ],
                ],
            ]);
    }

    public function test_destroy_model_removes_cache(): void
    {
        $user = User::factory()->create();

        Cache::put('model_download_qwen-2.5-0.5b', [
            'status' => 'completed',
            'progress' => 100.0,
        ], 3600);

        $response = $this->actingAs($user)->deleteJson('/api/models/qwen-2.5-0.5b');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNull(Cache::get('model_download_qwen-2.5-0.5b'));
    }
}

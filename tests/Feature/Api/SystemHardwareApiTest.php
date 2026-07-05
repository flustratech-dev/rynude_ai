<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHardwareApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hardware_detection_requires_authentication(): void
    {
        $response = $this->getJson('/api/system/hardware');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_detect_hardware(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/system/hardware');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'os',
                'total_ram_gb',
                'has_gpu',
                'recommendation' => [
                    'status',
                    'max_parameter_size',
                    'message',
                ],
                'fallback_used',
            ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertIsNumeric($data['total_ram_gb']);
        $this->assertGreaterThan(0, $data['total_ram_gb']);
    }
}

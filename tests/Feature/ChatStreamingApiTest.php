<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatStreamingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'anthropic_api_key' => 'test-key',
        ]);
    }

    /** @test */
    public function it_can_stop_generation()
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chats/stop', [
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk()
            ->assertJson(['stopped' => true]);

        // Verify cache flag was set
        $this->assertTrue(Cache::has('chat_stop_' . $conversation->id));
    }

    /** @test */
    public function stop_requires_conversation_id()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/chats/stop', []);

        $response->assertStatus(400)
            ->assertJson(['error' => 'conversation_id required']);
    }

    /** @test */
    public function stop_requires_ownership()
    {
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chats/stop', [
                'conversation_id' => $conversation->id,
            ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Conversation not found']);
    }

    /** @test */
    public function it_requires_authentication_for_stop()
    {
        $response = $this->postJson('/api/chats/stop', [
            'conversation_id' => 1,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function send_requires_authentication()
    {
        $response = $this->postJson('/api/chats/send', [
            'prompt' => 'Hello',
            'model' => 'claude-haiku-4-5',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function send_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/chats/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prompt', 'model']);
    }

    /** @test */
    public function send_validates_prompt_length()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/chats/send', [
                'prompt' => str_repeat('a', 10001),
                'model' => 'claude-haiku-4-5',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    }

    /** @test */
    public function send_creates_new_conversation_if_not_provided()
    {
        // Mock the AI service to avoid actual API calls
        $this->mock(\App\Services\AI\AiService::class, function ($mock) {
            $mock->shouldReceive('streamResponse')
                ->andReturn(new \ArrayIterator(['Hello from AI']));
        });

        $this->assertEquals(0, Conversation::count());

        $response = $this->actingAs($this->user)
            ->post('/api/chats/send', [
                'prompt' => 'Hello',
                'model' => 'claude-haiku-4-5',
            ]);

        $response->assertOk();

        // Verify conversation was created
        $this->assertEquals(1, Conversation::count());
        $conversation = Conversation::first();
        $this->assertEquals($this->user->id, $conversation->user_id);
        $this->assertEquals('New Chat', $conversation->title);
    }

    /** @test */
    public function send_uses_existing_conversation_if_provided()
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Existing Chat',
        ]);

        $this->mock(\App\Services\AI\AiService::class, function ($mock) {
            $mock->shouldReceive('streamResponse')
                ->andReturn(new \ArrayIterator(['Reply from AI']));
        });

        $response = $this->actingAs($this->user)
            ->post('/api/chats/send', [
                'prompt' => 'Hello',
                'model' => 'claude-haiku-4-5',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk();
        $this->assertEquals(1, Conversation::count());
    }

    /** @test */
    public function send_requires_conversation_ownership()
    {
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chats/send', [
                'prompt' => 'Hello',
                'model' => 'claude-haiku-4-5',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function send_creates_user_message()
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->mock(\App\Services\AI\AiService::class, function ($mock) {
            $mock->shouldReceive('streamResponse')
                ->andReturn(new \ArrayIterator(['Reply from AI']));
        });

        $this->assertEquals(0, Message::count());

        $response = $this->actingAs($this->user)
            ->post('/api/chats/send', [
                'prompt' => 'Hello AI',
                'model' => 'claude-haiku-4-5',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk();
        $this->assertEquals(1, Message::count());

        $message = Message::first();
        $this->assertEquals('user', $message->role);
        $this->assertEquals('Hello AI', $message->content);
        $this->assertEquals($conversation->id, $message->conversation_id);
    }

    /** @test */
    public function send_returns_sse_headers()
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->mock(\App\Services\AI\AiService::class, function ($mock) {
            $mock->shouldReceive('streamResponse')
                ->andReturn(new \ArrayIterator(['Reply from AI']));
        });

        $response = $this->actingAs($this->user)
            ->post('/api/chats/send', [
                'prompt' => 'Hello',
                'model' => 'claude-haiku-4-5',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk();
        $this->assertTrue(
            str_starts_with($response->headers->get('Content-Type'), 'text/event-stream'),
            'Content-Type header should start with text/event-stream'
        );
        $this->assertTrue(
            str_contains($response->headers->get('Cache-Control'), 'no-cache'),
            'Cache-Control header should contain no-cache'
        );
        $response->assertHeader('X-Accel-Buffering', 'no');
    }

    /** @test */
    public function send_clears_draft_prompt()
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'draft_prompt' => 'This is a draft',
        ]);

        $this->mock(\App\Services\AI\AiService::class, function ($mock) {
            $mock->shouldReceive('streamResponse')
                ->andReturn(new \ArrayIterator(['Reply from AI']));
        });

        $response = $this->actingAs($this->user)
            ->post('/api/chats/send', [
                'prompt' => 'Hello',
                'model' => 'claude-haiku-4-5',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk();
        $this->assertNull($conversation->fresh()->draft_prompt);
    }
}

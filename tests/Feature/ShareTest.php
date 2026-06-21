<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use App\Livewire\ChatsPanel;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_sharing_generates_a_token_and_public_page_is_readable(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'My shared chat',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'A memorable question',
        ]);

        Livewire::actingAs($user)
            ->test(ChatsPanel::class)
            ->call('shareConversation', $conversation->id);

        $conversation->refresh();
        $this->assertNotEmpty($conversation->share_token);

        // Public page accessible without authentication.
        $this->get(route('chat.shared', $conversation->share_token))
            ->assertOk()
            ->assertSee('My shared chat')
            ->assertSee('A memorable question');
    }

    public function test_unknown_share_token_returns_404(): void
    {
        $this->get('/share/' . Str::random(32))->assertNotFound();
    }

    public function test_published_artifact_is_publicly_viewable(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $user->id, 'title' => 'c']);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '',
        ]);
        $artifact = MessageArtifact::create([
            'message_id' => $message->id,
            'identifier' => 'art-1',
            'type' => 'text',
            'language' => 'markdown',
            'title' => 'Published Doc',
            'content' => '# Hello World',
            'is_public' => true,
            'public_token' => Str::random(32),
        ]);

        $this->get(route('artifact.shared', $artifact->public_token))
            ->assertOk()
            ->assertSee('Published Doc');
    }

    public function test_unpublished_artifact_is_not_viewable(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $user->id, 'title' => 'c']);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '',
        ]);
        $token = Str::random(32);
        MessageArtifact::create([
            'message_id' => $message->id,
            'identifier' => 'art-2',
            'type' => 'text',
            'language' => 'markdown',
            'title' => 'Secret Doc',
            'content' => 'secret',
            'is_public' => false,
            'public_token' => $token,
        ]);

        $this->get(route('artifact.shared', $token))->assertNotFound();
    }
}

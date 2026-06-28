<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Layer Migration — Chat API.
 *
 * Covers the conversation CRUD + share endpoints migrated out of
 * App\Livewire\ChatsPanel and App\Livewire\ChatInterface into
 * App\Http\Controllers\Api\ChatApiController.
 *
 * The send()/stop() streaming endpoints are owned by Phase 5 and still return
 * the 501 contract — asserted here so the boundary is locked in.
 */
class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeConversation(User $user, array $attributes = []): Conversation
    {
        return Conversation::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Demo Chat',
        ], $attributes));
    }

    /** Force a deterministic updated_at without tripping the touch-on-message logic. */
    private function touchUpdatedAt(Conversation $conversation, \DateTimeInterface $when): void
    {
        Conversation::where('id', $conversation->id)->update(['updated_at' => $when]);
    }

    // ── index ───────────────────────────────────────────────────────────

    public function test_index_lists_only_the_authenticated_users_conversations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeConversation($user, ['title' => 'Mine']);
        $this->makeConversation($other, ['title' => 'Theirs']);

        $this->actingAs($user)->getJson('/api/chats')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine');
    }

    public function test_index_excludes_archived_conversations_by_default(): void
    {
        $user = User::factory()->create();
        $this->makeConversation($user, ['title' => 'Active']);
        $this->makeConversation($user, ['title' => 'Archived', 'archived_at' => now()]);

        $this->actingAs($user)->getJson('/api/chats')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active');
    }

    public function test_index_can_list_archived_conversations(): void
    {
        $user = User::factory()->create();
        $this->makeConversation($user, ['title' => 'Active']);
        $this->makeConversation($user, ['title' => 'Archived', 'archived_at' => now()]);

        $this->actingAs($user)->getJson('/api/chats?archived=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Archived')
            ->assertJsonPath('data.0.archived', true);
    }

    public function test_index_orders_by_most_recently_updated(): void
    {
        $user = User::factory()->create();
        $old = $this->makeConversation($user, ['title' => 'Old']);
        $new = $this->makeConversation($user, ['title' => 'New']);

        $this->touchUpdatedAt($old, now()->subDay());
        $this->touchUpdatedAt($new, now());

        $this->actingAs($user)->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'New')
            ->assertJsonPath('data.1.title', 'Old');
    }

    public function test_index_filters_by_title_search(): void
    {
        $user = User::factory()->create();
        $this->makeConversation($user, ['title' => 'Python tips']);
        $this->makeConversation($user, ['title' => 'Marketing plan']);

        $this->actingAs($user)->getJson('/api/chats?search=python')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Python tips');
    }

    public function test_index_includes_preview_and_share_state(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user, ['share_token' => 'tok-123456']);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'The capital of France is Paris.',
        ]);

        $this->actingAs($user)->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('data.0.preview', 'The capital of France is Paris.')
            ->assertJsonPath('data.0.shared', true)
            ->assertJsonPath('data.0.group', 'Today');
    }

    // ── show ─────────────────────────────────────────────────────────────

    public function test_show_returns_messages_with_artifacts_and_attachments(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user, ['draft_prompt' => 'half typed']);

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Write a hello world',
        ]);
        MessageAttachment::create([
            'message_id' => $userMessage->id,
            'file_path' => 'attachments/spec.txt',
            'file_type' => 'text/plain',
            'file_name' => 'spec.txt',
        ]);

        $assistant = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Here you go',
            'rating' => 'up',
        ]);
        MessageArtifact::create([
            'message_id' => $assistant->id,
            'identifier' => 'art-hello',
            'type' => 'code',
            'language' => 'python',
            'title' => 'Hello',
            'content' => "print('hello')",
        ]);

        $this->actingAs($user)->getJson("/api/chats/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $conversation->id)
            ->assertJsonPath('data.draft_prompt', 'half typed')
            ->assertJsonPath('data.messages.0.role', 'user')
            ->assertJsonPath('data.messages.0.attachments.0.file_name', 'spec.txt')
            ->assertJsonPath('data.messages.1.role', 'assistant')
            ->assertJsonPath('data.messages.1.rating', 'up')
            ->assertJsonPath('data.messages.1.artifact.title', 'Hello')
            ->assertJsonPath('data.messages.1.artifact.content', "print('hello')");
    }

    public function test_cannot_show_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->makeConversation($other);

        $this->actingAs($user)->getJson("/api/chats/{$conversation->id}")
            ->assertForbidden();
    }

    public function test_show_missing_conversation_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/chats/99999')
            ->assertNotFound();
    }

    // ── update ───────────────────────────────────────────────────────────

    public function test_update_renames_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user, ['title' => 'Old Title']);

        $this->actingAs($user)->patchJson("/api/chats/{$conversation->id}", [
            'title' => 'New Title',
        ])->assertOk()
            ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => 'New Title',
        ]);
    }

    public function test_update_can_archive_and_unarchive(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user);

        $this->actingAs($user)->patchJson("/api/chats/{$conversation->id}", ['archived' => true])
            ->assertOk()
            ->assertJsonPath('data.archived', true);
        $this->assertNotNull($conversation->fresh()->archived_at);

        $this->actingAs($user)->patchJson("/api/chats/{$conversation->id}", ['archived' => false])
            ->assertOk()
            ->assertJsonPath('data.archived', false);
        $this->assertNull($conversation->fresh()->archived_at);
    }

    public function test_update_rejects_an_empty_title(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user);

        $this->actingAs($user)->patchJson("/api/chats/{$conversation->id}", ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_cannot_update_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->makeConversation($other, ['title' => 'Theirs']);

        $this->actingAs($user)->patchJson("/api/chats/{$conversation->id}", ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => 'Theirs',
        ]);
    }

    // ── destroy ──────────────────────────────────────────────────────────

    public function test_destroy_deletes_conversation_and_its_messages(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'hi',
        ]);

        $this->actingAs($user)->deleteJson("/api/chats/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_cannot_delete_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->makeConversation($other);

        $this->actingAs($user)->deleteJson("/api/chats/{$conversation->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
    }

    // ── share ────────────────────────────────────────────────────────────

    public function test_share_generates_a_token_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $conversation = $this->makeConversation($user);

        $first = $this->actingAs($user)->postJson("/api/chats/{$conversation->id}/share")
            ->assertOk()
            ->assertJsonPath('data.shared', true);

        $token = $first->json('data.share_token');
        $this->assertNotEmpty($token);
        $this->assertStringContainsString($token, $first->json('data.share_url'));

        // Calling again must not mint a fresh token.
        $this->actingAs($user)->postJson("/api/chats/{$conversation->id}/share")
            ->assertOk()
            ->assertJsonPath('data.share_token', $token);
    }

    public function test_cannot_share_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->makeConversation($other);

        $this->actingAs($user)->postJson("/api/chats/{$conversation->id}/share")
            ->assertForbidden();

        $this->assertNull($conversation->fresh()->share_token);
    }

    // ── streaming endpoints (Phase 5) ────────────────────────────────────

    public function test_send_and_stop_remain_not_implemented_pending_streaming(): void
    {
        $user = User::factory()->create();

        foreach (['/api/chats/send', '/api/chats/stop'] as $uri) {
            $this->actingAs($user)->postJson($uri)
                ->assertStatus(501)
                ->assertJsonPath('status', 'not_implemented')
                ->assertJsonPath('migration.phase', 'Phase 5');
        }
    }

    // ── auth ─────────────────────────────────────────────────────────────

    public function test_chat_endpoints_require_authentication(): void
    {
        $this->getJson('/api/chats')->assertUnauthorized();
        $this->getJson('/api/chats/1')->assertUnauthorized();
        $this->patchJson('/api/chats/1', ['title' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/chats/1')->assertUnauthorized();
        $this->postJson('/api/chats/1/share')->assertUnauthorized();
    }
}

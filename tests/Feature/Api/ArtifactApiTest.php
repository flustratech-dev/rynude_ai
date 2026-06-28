<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Layer Migration — Artifacts API.
 *
 * Covers the artifact read / rename / publish / delete endpoints migrated out of
 * App\Livewire\ArtifactPanel into App\Http\Controllers\Api\ArtifactApiController.
 *
 * store() is owned by Phase 5 (artifacts are created by the streamed generation),
 * and still returns the 501 contract — asserted here so the boundary is locked in.
 *
 * Ownership is resolved through message -> conversation -> user_id, so the
 * helper deliberately does NOT set the denormalized message_artifacts.user_id.
 */
class ArtifactApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeArtifact(User $owner, array $overrides = []): MessageArtifact
    {
        $conversation = Conversation::create(['user_id' => $owner->id, 'title' => 'c']);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '',
        ]);

        return MessageArtifact::create(array_merge([
            'message_id' => $message->id,
            'identifier' => 'art-' . uniqid(),
            'type' => 'text',
            'language' => 'markdown',
            'title' => 'Owned Doc',
            'content' => '# secret content',
        ], $overrides));
    }

    /** Add another version (same identifier) under the same owner's conversation. */
    private function addVersion(User $owner, MessageArtifact $original, array $overrides = []): MessageArtifact
    {
        $conversation = Conversation::create(['user_id' => $owner->id, 'title' => 'c2']);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '',
        ]);

        return MessageArtifact::create(array_merge([
            'message_id' => $message->id,
            'identifier' => $original->identifier,
            'type' => $original->type,
            'language' => $original->language,
            'title' => $original->title,
            'content' => '# v2 content',
        ], $overrides));
    }

    // ── index ───────────────────────────────────────────────────────────

    public function test_index_lists_only_owned_artifacts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeArtifact($user, ['title' => 'Mine']);
        $this->makeArtifact($other, ['title' => 'Theirs']);

        $this->actingAs($user)->getJson('/api/artifacts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine');
    }

    public function test_index_never_serialises_artifact_content(): void
    {
        $user = User::factory()->create();
        $this->makeArtifact($user, ['content' => 'HUGE_SENSITIVE_BODY']);

        $response = $this->actingAs($user)->getJson('/api/artifacts')->assertOk();

        $this->assertArrayNotHasKey('content', $response->json('data.0'));
        $response->assertJsonMissing(['content' => 'HUGE_SENSITIVE_BODY']);
    }

    public function test_index_dedupes_versions_by_identifier(): void
    {
        $user = User::factory()->create();
        $artifact = $this->makeArtifact($user, ['title' => 'Doc']);
        $this->addVersion($user, $artifact);

        $this->actingAs($user)->getJson('/api/artifacts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_search_on_title_or_language(): void
    {
        $user = User::factory()->create();
        $this->makeArtifact($user, ['title' => 'Landing Page', 'language' => 'html']);
        $this->makeArtifact($user, ['title' => 'Data script', 'language' => 'python']);

        $this->actingAs($user)->getJson('/api/artifacts?search=python')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Data script');
    }

    public function test_index_orders_newest_first(): void
    {
        $user = User::factory()->create();
        $old = $this->makeArtifact($user, ['title' => 'Old']);
        $old->created_at = now()->subDay();
        $old->save();
        $this->makeArtifact($user, ['title' => 'New']);

        $this->actingAs($user)->getJson('/api/artifacts')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'New');
    }

    // ── show ─────────────────────────────────────────────────────────────

    public function test_show_returns_content_and_versions(): void
    {
        $user = User::factory()->create();
        $artifact = $this->makeArtifact($user, ['title' => 'Doc', 'content' => '# Heading']);
        $this->addVersion($user, $artifact);

        $this->actingAs($user)->getJson("/api/artifacts/{$artifact->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $artifact->id)
            ->assertJsonPath('data.content', '# Heading')
            ->assertJsonCount(2, 'data.versions')
            ->assertJsonPath('data.versions.0.is_current', true)
            ->assertJsonPath('data.versions.1.version_number', 2);
    }

    public function test_cannot_show_another_users_artifact(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $artifact = $this->makeArtifact($other);

        $this->actingAs($user)->getJson("/api/artifacts/{$artifact->id}")
            ->assertForbidden();
    }

    public function test_show_missing_artifact_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/artifacts/99999')
            ->assertNotFound();
    }

    // ── store (Phase 5) ──────────────────────────────────────────────────

    public function test_store_remains_not_implemented_pending_streaming(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/artifacts', ['title' => 'x'])
            ->assertStatus(501)
            ->assertJsonPath('status', 'not_implemented')
            ->assertJsonPath('migration.phase', 'Phase 5');
    }

    // ── update ───────────────────────────────────────────────────────────

    public function test_update_renames_every_version_sharing_the_identifier(): void
    {
        $user = User::factory()->create();
        $artifact = $this->makeArtifact($user, ['title' => 'Old Title']);
        $version2 = $this->addVersion($user, $artifact, ['title' => 'Old Title']);

        $this->actingAs($user)->patchJson("/api/artifacts/{$artifact->id}", [
            'title' => 'New Title',
        ])->assertOk()
            ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('message_artifacts', ['id' => $artifact->id, 'title' => 'New Title']);
        $this->assertDatabaseHas('message_artifacts', ['id' => $version2->id, 'title' => 'New Title']);
    }

    public function test_update_publishes_artifact_and_returns_public_url(): void
    {
        $user = User::factory()->create();
        $artifact = $this->makeArtifact($user, ['is_public' => false]);

        $response = $this->actingAs($user)->patchJson("/api/artifacts/{$artifact->id}", [
            'is_public' => true,
        ])->assertOk()
            ->assertJsonPath('data.is_public', true);

        $token = $response->json('data.public_token');
        $this->assertNotEmpty($token);
        $this->assertStringContainsString($token, $response->json('data.public_url'));

        $this->assertDatabaseHas('message_artifacts', [
            'id' => $artifact->id,
            'is_public' => true,
        ]);
    }

    public function test_update_unpublishes_artifact(): void
    {
        $user = User::factory()->create();
        $artifact = $this->makeArtifact($user, ['is_public' => true, 'public_token' => 'tok-123456']);

        $this->actingAs($user)->patchJson("/api/artifacts/{$artifact->id}", [
            'is_public' => false,
        ])->assertOk()
            ->assertJsonPath('data.is_public', false)
            ->assertJsonPath('data.public_url', null);

        $this->assertDatabaseHas('message_artifacts', [
            'id' => $artifact->id,
            'is_public' => false,
        ]);
    }

    public function test_cannot_update_another_users_artifact(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $artifact = $this->makeArtifact($other, ['title' => 'Original']);

        $this->actingAs($user)->patchJson("/api/artifacts/{$artifact->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();

        $this->assertDatabaseHas('message_artifacts', [
            'id' => $artifact->id,
            'title' => 'Original',
        ]);
    }

    // ── destroy ──────────────────────────────────────────────────────────

    public function test_destroy_deletes_all_versions_sharing_the_identifier(): void
    {
        $user = User::factory()->create();
        $artifact = $this->makeArtifact($user);
        $version2 = $this->addVersion($user, $artifact);

        $this->actingAs($user)->deleteJson("/api/artifacts/{$artifact->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('message_artifacts', ['id' => $artifact->id]);
        $this->assertDatabaseMissing('message_artifacts', ['id' => $version2->id]);
    }

    public function test_cannot_delete_another_users_artifact(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $artifact = $this->makeArtifact($other);

        $this->actingAs($user)->deleteJson("/api/artifacts/{$artifact->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('message_artifacts', ['id' => $artifact->id]);
    }

    public function test_destroy_missing_artifact_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/artifacts/99999')
            ->assertNotFound();
    }

    // ── auth ─────────────────────────────────────────────────────────────

    public function test_artifact_endpoints_require_authentication(): void
    {
        $this->getJson('/api/artifacts')->assertUnauthorized();
        $this->getJson('/api/artifacts/1')->assertUnauthorized();
        $this->patchJson('/api/artifacts/1', ['title' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/artifacts/1')->assertUnauthorized();
    }
}

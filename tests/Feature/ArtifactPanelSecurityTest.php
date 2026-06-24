<?php

namespace Tests\Feature;

use App\Livewire\ArtifactPanel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArtifactPanelSecurityTest extends TestCase
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

    public function test_user_cannot_delete_another_users_artifact(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $artifact = $this->makeArtifact($owner);

        Livewire::actingAs($attacker)
            ->test(ArtifactPanel::class)
            ->call('deleteArtifact', $artifact->id);

        $this->assertDatabaseHas('message_artifacts', ['id' => $artifact->id]);
    }

    public function test_user_cannot_rename_another_users_artifact(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $artifact = $this->makeArtifact($owner, ['title' => 'Original']);

        Livewire::actingAs($attacker)
            ->test(ArtifactPanel::class)
            ->call('renameArtifact', $artifact->id, 'Hacked');

        $this->assertDatabaseHas('message_artifacts', [
            'id' => $artifact->id,
            'title' => 'Original',
        ]);
    }

    public function test_user_cannot_open_another_users_artifact(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $artifact = $this->makeArtifact($owner);

        Livewire::actingAs($attacker)
            ->test(ArtifactPanel::class)
            ->call('openArtifact', $artifact->id)
            ->assertSet('currentArtifact', null)
            ->assertSet('isOpen', false);
    }

    public function test_owner_can_delete_and_rename_own_artifact(): void
    {
        $owner = User::factory()->create();
        $artifact = $this->makeArtifact($owner, ['title' => 'Mine']);

        Livewire::actingAs($owner)
            ->test(ArtifactPanel::class)
            ->call('renameArtifact', $artifact->id, 'Renamed')
            ->call('deleteArtifact', $artifact->id);

        $this->assertDatabaseMissing('message_artifacts', ['id' => $artifact->id]);
    }

    public function test_artifact_list_does_not_serialise_content(): void
    {
        $owner = User::factory()->create();
        $this->makeArtifact($owner, ['content' => 'HUGE_SENSITIVE_BODY']);

        $component = Livewire::actingAs($owner)->test(ArtifactPanel::class);

        $artifacts = $component->get('artifacts');
        $this->assertCount(1, $artifacts);
        $this->assertArrayNotHasKey('content', $artifacts[0]);
    }
}

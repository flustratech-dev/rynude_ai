<?php

namespace Tests\Feature;

use App\Livewire\ChatInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FakeAiService;
use Tests\TestCase;

class ConversationMemoryTest extends TestCase
{
    use RefreshDatabase;

    private FakeAiService $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeAiService();
        $this->app->instance(AiService::class, $this->fake);
    }

    /**
     * Stored memory must be injected into the system prompt on every turn. This is
     * what lets the assistant keep its memory even after the user switches models —
     * the memory lives on the conversation, not in any model's context.
     */
    public function test_stored_memory_is_injected_into_the_system_prompt(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Existing chat',
            'memory' => "- The user's name is Budi.\n- Preferred stack: Laravel + Livewire.",
            'memory_synced_count' => 4,
        ]);

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->call('loadSelectedConversation', $conversation->id)
            // Simulate the user having switched to a different model mid-chat.
            ->set('selectedModel', 'claude-sonnet-4-6')
            ->set('prompt', 'What stack do I prefer?')
            ->call('sendMessage')
            ->call('generateResponse');

        $systemPrompt = $this->fake->lastSystemPrompt();
        $this->assertStringContainsString('PERSISTENT CONVERSATION MEMORY', $systemPrompt);
        $this->assertStringContainsString("The user's name is Budi.", $systemPrompt);
        $this->assertStringContainsString('Laravel + Livewire', $systemPrompt);
    }

    /**
     * Once enough new messages accumulate, the durable memory is rebuilt and the
     * synced counter advances so we don't re-summarize on every single turn.
     */
    public function test_memory_is_refreshed_after_enough_messages(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Long chat',
        ]);

        // Seed five prior messages so the next send pushes the total past the refresh
        // threshold (6).
        for ($i = 0; $i < 5; $i++) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => $i % 2 === 0 ? 'user' : 'assistant',
                'content' => "Earlier message {$i}",
            ]);
        }

        $this->fake->cannedResponse = 'Distilled memory: user is building a chat app.';

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->call('loadSelectedConversation', $conversation->id)
            ->set('prompt', 'Keep going')
            ->call('sendMessage')
            ->call('generateResponse');

        $conversation->refresh();

        $this->assertNotNull($conversation->memory);
        $this->assertStringContainsString('Distilled memory', $conversation->memory);
        // 5 seeded + 1 user + 1 assistant = 7 messages folded into memory.
        $this->assertSame(7, $conversation->memory_synced_count);
        $this->assertNotNull($conversation->memory_updated_at);
    }

    /**
     * The user can open the memory editor, edit the text, and persist it by hand.
     */
    public function test_user_can_edit_and_save_memory(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Editable chat',
            'memory' => 'Old note.',
        ]);

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->call('loadSelectedConversation', $conversation->id)
            ->call('openMemory')
            ->assertSet('showMemory', true)
            ->assertSet('memoryDraft', 'Old note.')
            ->set('memoryDraft', 'User prefers TypeScript.')
            ->call('saveMemory')
            ->assertSet('showMemory', false);

        $this->assertSame('User prefers TypeScript.', $conversation->fresh()->memory);
    }

    /**
     * Saving an empty draft clears the stored memory.
     */
    public function test_saving_empty_memory_clears_it(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Clearable chat',
            'memory' => 'Some memory.',
        ]);

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->call('loadSelectedConversation', $conversation->id)
            ->call('openMemory')
            ->set('memoryDraft', '   ')
            ->call('saveMemory');

        $this->assertNull($conversation->fresh()->memory);
    }

    /**
     * A user must not be able to read or edit another user's conversation memory.
     */
    public function test_cannot_open_memory_of_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $owner->id,
            'title' => 'Private chat',
            'memory' => 'Secret note.',
        ]);

        Livewire::actingAs($intruder)
            ->test(ChatInterface::class)
            ->set('conversationId', $conversation->id)
            ->call('openMemory')
            ->assertSet('showMemory', false)
            ->assertSet('memoryDraft', '');
    }

    /**
     * Short conversations should not trigger a (cost-incurring) memory rebuild.
     */
    public function test_memory_is_not_refreshed_for_short_conversations(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->set('prompt', 'Hi')
            ->call('sendMessage')
            ->call('generateResponse');

        $conversation = Conversation::where('user_id', $user->id)->first();
        $this->assertNull($conversation->memory);
        $this->assertSame(0, $conversation->memory_synced_count);
    }
}

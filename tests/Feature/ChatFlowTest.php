<?php

namespace Tests\Feature;

use App\Livewire\ChatInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Skill;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FakeAiService;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakeAiService $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeAiService();
        $this->app->instance(AiService::class, $this->fake);
    }

    public function test_sending_a_message_creates_conversation_and_messages(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->set('prompt', 'Hello there')
            ->call('sendMessage')
            ->call('generateResponse');

        $this->assertDatabaseHas('conversations', ['user_id' => $user->id]);

        $conversation = Conversation::where('user_id', $user->id)->first();
        $this->assertNotNull($conversation);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello there',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $this->fake->cannedResponse,
        ]);
    }

    public function test_active_skill_is_injected_into_the_system_prompt(): void
    {
        $user = User::factory()->create();

        Skill::create([
            'user_id' => $user->id,
            'name' => 'Pirate Voice',
            'instructions' => 'Always respond like a swashbuckling pirate.',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->set('prompt', 'Tell me a joke')
            ->call('sendMessage')
            ->call('generateResponse');

        $systemPrompt = $this->fake->lastSystemPrompt();
        $this->assertStringContainsString('Pirate Voice', $systemPrompt);
        $this->assertStringContainsString('swashbuckling pirate', $systemPrompt);
    }

    public function test_inactive_skill_is_not_injected(): void
    {
        $user = User::factory()->create();

        Skill::create([
            'user_id' => $user->id,
            'name' => 'Disabled Skill',
            'instructions' => 'This should never appear.',
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ChatInterface::class)
            ->set('prompt', 'Hi')
            ->call('sendMessage')
            ->call('generateResponse');

        $this->assertStringNotContainsString('This should never appear.', $this->fake->lastSystemPrompt());
    }
}

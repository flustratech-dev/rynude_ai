<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'project_id' => null,
            'draft_prompt' => null,
            'archived_at' => null,
            'share_token' => null,
            'memory' => null,
            'memory_updated_at' => null,
        ];
    }

    /**
     * Indicate that the conversation is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }

    /**
     * Indicate that the conversation is shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'share_token' => \Illuminate\Support\Str::random(32),
        ]);
    }

    /**
     * Indicate that the conversation has a draft prompt.
     */
    public function withDraft(string $draft = null): static
    {
        return $this->state(fn (array $attributes) => [
            'draft_prompt' => $draft ?? $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the conversation has memory.
     */
    public function withMemory(string $memory = null): static
    {
        return $this->state(fn (array $attributes) => [
            'memory' => $memory ?? $this->faker->paragraph(),
            'memory_updated_at' => now(),
        ]);
    }
}

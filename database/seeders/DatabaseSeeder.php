<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        \App\Models\AiModel::insert([
            ['name' => 'Claude 3.5 Sonnet', 'code' => 'claude-3-5-sonnet', 'is_active' => true],
            ['name' => 'Claude 3 Opus', 'code' => 'claude-3-opus', 'is_active' => true],
            ['name' => 'Claude 3 Haiku', 'code' => 'claude-3-haiku', 'is_active' => true],
        ]);

        \App\Models\Conversation::insert([
            ['user_id' => $user->id, 'title' => 'Project architecture discussion', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'title' => 'Debugging Livewire events', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
            ['user_id' => $user->id, 'title' => 'API integration brainstorm', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
        ]);
    }
}

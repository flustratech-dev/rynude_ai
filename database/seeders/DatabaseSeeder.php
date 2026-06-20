<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Maya',
            'email' => 'maya@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->call(AiModelSeeder::class);

        \App\Models\Conversation::insert([
            ['user_id' => $user->id, 'title' => 'Project architecture discussion', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'title' => 'Debugging Livewire events', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
            ['user_id' => $user->id, 'title' => 'API integration brainstorm', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
        ]);
    }
}

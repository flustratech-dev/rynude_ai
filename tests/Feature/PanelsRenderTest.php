<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelsRenderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider componentProvider
     */
    public function test_livewire_component_renders_without_error(string $component): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test($component)
            ->assertOk();
    }

    public static function componentProvider(): array
    {
        return [
            [\App\Livewire\ChatInterface::class],
            [\App\Livewire\Sidebar::class],
            [\App\Livewire\ChatsPanel::class],
            [\App\Livewire\ProjectsPanel::class],
            [\App\Livewire\ArtifactPanel::class],
            [\App\Livewire\CustomizePanel::class],
            [\App\Livewire\CoworkPanel::class],
            [\App\Livewire\DesignPanel::class],
            [\App\Livewire\SettingsModal::class],
            [\App\Livewire\CodePanel::class],
            [\App\Livewire\HelpModal::class],
            [\App\Livewire\QuotaWarningModal::class],
        ];
    }
}

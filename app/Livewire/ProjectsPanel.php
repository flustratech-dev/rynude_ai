<?php

namespace App\Livewire;

use Livewire\Component;

class ProjectsPanel extends Component
{
    public array $projects = [];
    public bool $showCreateForm = false;
    public string $newProjectName = '';
    public string $newProjectDescription = '';

    public function mount()
    {
        $this->projects = [
            ['id' => 1, 'name' => 'Rynude App', 'description' => 'Main application project', 'chat_count' => 5, 'created_at' => '2026-06-10'],
            ['id' => 2, 'name' => 'API Documentation', 'description' => 'Auto-generated docs from codebase', 'chat_count' => 2, 'created_at' => '2026-06-14'],
            ['id' => 3, 'name' => 'UI Redesign', 'description' => 'Claude-inspired interface overhaul', 'chat_count' => 8, 'created_at' => '2026-06-16'],
        ];
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->resetValidation();
    }

    public function createProject()
    {
        $this->validate([
            'newProjectName' => 'required|string|min:1|max:100',
        ], [
            'newProjectName.required' => 'Project name is required.',
        ]);

        $this->projects[] = [
            'id' => count($this->projects) + 1,
            'name' => $this->newProjectName,
            'description' => $this->newProjectDescription,
            'chat_count' => 0,
            'created_at' => now()->toDateString(),
        ];

        $this->newProjectName = '';
        $this->newProjectDescription = '';
        $this->showCreateForm = false;
    }

    public function deleteProject($id)
    {
        $this->projects = array_values(array_filter($this->projects, fn($p) => $p['id'] !== $id));
    }

    public function closePanel()
    {
        $this->dispatch('close-panel');
    }

    public function render()
    {
        return view('livewire.projects-panel');
    }
}

<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

use Livewire\Attributes\On;

class ProjectsPanel extends Component
{
    use WithFileUploads;

    public array $projects = [];
    public bool $showCreateForm = false;
    public string $newProjectName = '';
    public string $newProjectDescription = '';
    public string $newProjectColor = '#D97757';
    public string $newProjectIcon = '📁';

    public ?int $selectedProjectId = null;
    public ?array $selectedProject = null;

    // Project View States
    public string $customInstructions = '';
    public $newKnowledgeFiles = [];
    public array $projectFiles = [];
    public array $projectChats = [];

    public string $sortBy = 'updated_at';
    public string $searchQuery = '';

    /** Predefined color palette for project icons. */
    public array $projectColors = [
        '#D97757', '#5E72E4', '#11998E', '#E0529C',
        '#F5A623', '#8B5CF6', '#0EA5E9', '#64748B',
    ];

    /** Emoji choices for project icons. */
    public array $projectIcons = [
        '📁', '🚀', '💡', '📊', '✍️', '🎨', '🔬', '💻', '📚', '🎯',
    ];

    public function mount()
    {
        $this->loadProjects();
    }

    public function updatedSearchQuery()
    {
        $this->loadProjects();
    }

    public function setSortBy($sort)
    {
        $this->sortBy = $sort;
        $this->loadProjects();
    }

    public function loadProjects()
    {
        if (Auth::check()) {
            $query = Auth::user()->projects()->withCount('conversations as chat_count');

            if (!empty(trim($this->searchQuery))) {
                $search = trim($this->searchQuery);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Starred projects always float to the top.
            $query->orderByDesc('is_starred');

            if ($this->sortBy === 'name') {
                $query->orderBy('name', 'asc');
            } else {
                $query->orderByDesc($this->sortBy);
            }

            $this->projects = $query->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'color' => $project->color ?: '#D97757',
                        'icon' => $project->icon ?: '📁',
                        'is_starred' => (bool) $project->is_starred,
                        'chat_count' => $project->chat_count ?? 0,
                        'created_at' => $project->updated_at->diffForHumans(),
                    ];
                })->toArray();
        }
    }

    public function selectProject($id)
    {
        $this->selectedProjectId = $id;
        
        $project = Project::with(['files', 'conversations' => function($q) {
            $q->orderByDesc('updated_at')->take(5);
        }])->where('id', $id)->where('user_id', Auth::id())->first();

        if ($project) {
            $this->selectedProject = [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'color' => $project->color ?: '#D97757',
                'icon' => $project->icon ?: '📁',
                'is_starred' => (bool) $project->is_starred,
                'chat_count' => $project->conversations->count(),
            ];
            $this->customInstructions = $project->custom_instructions ?? '';
            $this->projectFiles = $project->files->toArray();
            $this->projectChats = $project->conversations->toArray();
        } else {
            $this->backToList();
        }
    }

    public function backToList()
    {
        $this->selectedProjectId = null;
        $this->selectedProject = null;
        $this->customInstructions = '';
        $this->projectFiles = [];
        $this->projectChats = [];
        $this->loadProjects();
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

        $project = Auth::user()->projects()->create([
            'name' => $this->newProjectName,
            'description' => $this->newProjectDescription,
            'color' => $this->newProjectColor,
            'icon' => $this->newProjectIcon,
        ]);

        $this->newProjectName = '';
        $this->newProjectDescription = '';
        $this->newProjectColor = '#D97757';
        $this->newProjectIcon = '📁';
        $this->showCreateForm = false;

        $this->loadProjects();
        $this->selectProject($project->id);
    }

    public function starProject($id)
    {
        $project = Project::where('id', $id)->where('user_id', Auth::id())->first();
        if ($project) {
            $project->is_starred = !$project->is_starred;
            $project->save();
            $this->loadProjects();
        }
    }

    public function duplicateProject($id)
    {
        $project = Project::with('files')->where('id', $id)->where('user_id', Auth::id())->first();
        if (!$project) {
            return;
        }

        $copy = Auth::user()->projects()->create([
            'name' => $project->name . ' (Copy)',
            'description' => $project->description,
            'custom_instructions' => $project->custom_instructions,
            'color' => $project->color,
            'icon' => $project->icon,
        ]);

        foreach ($project->files as $file) {
            $newPath = null;
            if ($file->file_path && Storage::exists($file->file_path)) {
                $newPath = 'project_knowledge/' . \Illuminate\Support\Str::random(40);
                Storage::copy($file->file_path, $newPath);
            }
            ProjectFile::create([
                'project_id' => $copy->id,
                'file_name' => $file->file_name,
                'file_path' => $newPath ?? $file->file_path,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
            ]);
        }

        $this->loadProjects();
    }

    public function deleteProject($id)
    {
        $project = Project::where('id', $id)->where('user_id', Auth::id())->first();
        if ($project) {
            // Delete associated files
            foreach ($project->files as $file) {
                Storage::delete($file->file_path);
            }
            $project->delete();
        }
        
        if ($this->selectedProjectId === $id) {
            $this->backToList();
        } else {
            $this->loadProjects();
        }
    }

    public function saveInstructions()
    {
        if ($this->selectedProjectId) {
            Project::where('id', $this->selectedProjectId)
                ->where('user_id', Auth::id())
                ->update(['custom_instructions' => $this->customInstructions]);
        }
    }

    public function updatedNewKnowledgeFiles()
    {
        $this->validate([
            'newKnowledgeFiles.*' => 'max:10240', // 10MB Max per file
        ]);

        if ($this->selectedProjectId && !empty($this->newKnowledgeFiles)) {
            foreach ($this->newKnowledgeFiles as $file) {
                $path = $file->store('project_knowledge');
                ProjectFile::create([
                    'project_id' => $this->selectedProjectId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
            
            $this->newKnowledgeFiles = []; // Reset input
            
            // Reload files
            $this->projectFiles = ProjectFile::where('project_id', $this->selectedProjectId)->get()->toArray();
        }
    }

    public function deleteKnowledgeFile($fileId)
    {
        $file = ProjectFile::where('id', $fileId)->where('project_id', $this->selectedProjectId)->first();
        if ($file) {
            Storage::delete($file->file_path);
            $file->delete();
            $this->projectFiles = ProjectFile::where('project_id', $this->selectedProjectId)->get()->toArray();
        }
    }

    public function startNewChatInProject()
    {
        if ($this->selectedProjectId) {
            // Close the panel and signal ChatLayout to open a new chat with this project context
            $this->dispatch('close-panel');
            $this->dispatch('startProjectChat', projectId: $this->selectedProjectId);
        }
    }
    
    public function openProjectChat($chatId)
    {
        $this->dispatch('close-panel');
        $this->dispatch('openChat', chatId: $chatId);
    }

    public function closePanel()
    {
        $this->dispatch('close-panel');
    }

    #[On('chatCreated')]
    #[On('messageAdded')]
    public function refreshData()
    {
        $this->loadProjects();
        if ($this->selectedProjectId) {
            $this->selectProject($this->selectedProjectId);
        }
    }

    public function render()
    {
        return view('livewire.projects-panel');
    }
}

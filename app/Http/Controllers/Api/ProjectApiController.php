<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Project REST surface.
 *
 * Migrated from App\Livewire\ProjectsPanel:
 *   - GET    /api/projects                 -> loadProjects() (search + sort)
 *   - POST   /api/projects                 -> createProject()
 *   - GET    /api/projects/{project}       -> selectProject() (detail with files/chats)
 *   - PATCH  /api/projects/{project}       -> saveInstructions()/starProject()/edit
 *   - DELETE /api/projects/{project}       -> deleteProject() (incl. file cleanup)
 *   - POST   /api/projects/{project}/files -> updatedNewKnowledgeFiles()
 *   - DELETE /api/projects/{project}/files/{file} -> deleteKnowledgeFile()
 *   - POST   /api/projects/{project}/duplicate -> duplicateProject()
 */
class ProjectApiController extends Controller
{
    /** Sort keys accepted on the index endpoint. */
    private const SORTABLE = ['updated_at', 'created_at', 'name'];

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort', 'updated_at');
        if (!in_array($sort, self::SORTABLE, true)) {
            $sort = 'updated_at';
        }

        $query = Auth::user()->projects()->withCount('conversations as chat_count');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Starred projects always float to the top (mirrors loadProjects()).
        $query->orderByDesc('is_starred');
        if ($sort === 'name') {
            $query->orderBy('name', 'asc');
        } else {
            $query->orderByDesc($sort);
        }

        $projects = $query->get()->map(fn (Project $project) => $this->transform($project));

        return response()->json(['data' => $projects]);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorizeOwnership($project);

        $project->load(['files', 'conversations' => function ($q) {
            $q->orderByDesc('updated_at')->take(5);
        }]);

        return response()->json(['data' => $this->transformDetail($project)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'custom_instructions' => ['nullable', 'string', 'max:10000'],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:32'],
        ], [
            'name.required' => 'Project name is required.',
        ]);

        $project = Auth::user()->projects()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'custom_instructions' => $validated['custom_instructions'] ?? null,
            'color' => $validated['color'] ?? '#D97757',
            'icon' => $validated['icon'] ?? '📁',
        ]);

        $project->loadCount('conversations as chat_count');

        return response()->json(['data' => $this->transform($project)], 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwnership($project);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:1', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'custom_instructions' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:32'],
            'is_starred' => ['sometimes', 'boolean'],
        ]);

        $project->fill($validated);
        $project->save();

        $project->loadCount('conversations as chat_count');

        return response()->json(['data' => $this->transform($project)]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeOwnership($project);

        // Remove stored knowledge files
        foreach ($project->files as $file) {
            if ($file->file_path) {
                Storage::delete($file->file_path);
            }
        }

        $project->delete();

        return response()->json(['deleted' => true]);
    }

    public function uploadFile(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwnership($project);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('project_knowledge');

        $projectFile = ProjectFile::create([
            'project_id' => $project->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json(['data' => [
            'id' => $projectFile->id,
            'file_name' => $projectFile->file_name,
            'mime_type' => $projectFile->mime_type,
            'size' => $projectFile->size,
        ]], 201);
    }

    public function deleteFile(Project $project, ProjectFile $file): JsonResponse
    {
        $this->authorizeOwnership($project);
        abort_unless($file->project_id === $project->id, 404);

        Storage::delete($file->file_path);
        $file->delete();

        return response()->json(['deleted' => true]);
    }

    public function duplicate(Project $project): JsonResponse
    {
        $this->authorizeOwnership($project);

        $project->load('files');

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
                $newPath = 'project_knowledge/' . Str::random(40);
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

        $copy->loadCount('conversations as chat_count');

        return response()->json(['data' => $this->transform($copy)], 201);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function authorizeOwnership(Project $project): void
    {
        abort_unless($project->user_id === Auth::id(), 403);
    }

    private function transform(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'custom_instructions' => $project->custom_instructions,
            'color' => $project->color ?: '#D97757',
            'icon' => $project->icon ?: '📁',
            'is_starred' => (bool) $project->is_starred,
            'chat_count' => (int) ($project->chat_count ?? 0),
            'created_at' => optional($project->created_at)->toIso8601String(),
            'updated_at' => optional($project->updated_at)->toIso8601String(),
        ];
    }

    private function transformDetail(Project $project): array
    {
        $files = $project->files->map(fn ($f) => [
            'id' => $f->id,
            'file_name' => $f->file_name,
            'mime_type' => $f->mime_type,
            'size' => $f->size,
        ])->values();

        $chats = $project->conversations->map(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title ?? 'Untitled chat',
            'updated_at' => optional($c->updated_at)->toIso8601String(),
        ])->values();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'custom_instructions' => $project->custom_instructions,
            'color' => $project->color ?: '#D97757',
            'icon' => $project->icon ?: '📁',
            'is_starred' => (bool) $project->is_starred,
            'chat_count' => (int) ($project->chat_count ?? 0),
            'files' => $files,
            'chats' => $chats,
            'created_at' => optional($project->created_at)->toIso8601String(),
            'updated_at' => optional($project->updated_at)->toIso8601String(),
        ];
    }
}

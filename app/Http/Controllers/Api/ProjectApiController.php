<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Project REST surface.
 *
 * Migrated from App\Livewire\ProjectsPanel:
 *   - GET    /api/projects        -> loadProjects() (search + sort)
 *   - POST   /api/projects        -> createProject()
 *   - PATCH  /api/projects/{id}   -> saveInstructions()/starProject()/edit
 *   - DELETE /api/projects/{id}   -> deleteProject() (incl. file cleanup)
 *
 * Knowledge-file uploads (ProjectsPanel::updatedNewKnowledgeFiles) depend on the
 * Livewire WithFileUploads trait and are intentionally left to the file-upload
 * migration; this controller covers project CRUD only.
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

        // Remove stored knowledge files before the cascade delete (mirrors
        // ProjectsPanel::deleteProject()).
        foreach ($project->files as $file) {
            if ($file->file_path) {
                Storage::delete($file->file_path);
            }
        }

        $project->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function authorizeOwnership(Project $project): void
    {
        abort_unless($project->user_id === Auth::id(), 403);
    }

    /**
     * Shape a project for the API, matching the array ProjectsPanel built for
     * the Blade view (plus the raw timestamps for clients that want them).
     *
     * @return array<string, mixed>
     */
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
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoworkTask;
use App\Services\AI\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Cowork task REST surface.
 *
 * Migrated from App\Livewire\CoworkPanel:
 *   - GET    /api/tasks           -> getTasksProperty() (list with status filter)
 *   - POST   /api/tasks           -> createTask() (create new task)
 *   - PATCH  /api/tasks/{task}    -> updateStatus() (update status/fields)
 *   - DELETE /api/tasks/{task}    -> deleteTask() (delete)
 *   - POST   /api/tasks/{task}/run -> runTask() (execute task with AI)
 *
 * run() reuses AiService exactly the way the Livewire component did — it
 * consumes the streamResponse() generator internally to accumulate the result.
 * AiService is injected so tests can substitute a fake.
 */
class CoworkApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $statusFilter = $request->query('status', 'all');

        $query = CoworkTask::where('user_id', Auth::id());

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $tasks = $query->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CoworkTask $task) => $this->transform($task));

        $stats = $this->calculateStats();

        return response()->json([
            'data' => $tasks,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'model' => ['required', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $task = CoworkTask::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'model' => $validated['model'],
            'scheduled_for' => $validated['scheduled_for'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $this->transform($task)], 201);
    }

    public function update(Request $request, CoworkTask $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed', 'failed'])],
        ]);

        // If status is being set to completed, update completed_at
        if (isset($validated['status']) && $validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        // If status is being changed away from completed, clear completed_at
        if (isset($validated['status']) && $validated['status'] !== 'completed' && $task->status === 'completed') {
            $validated['completed_at'] = null;
        }

        $task->fill($validated);
        $task->save();

        return response()->json(['data' => $this->transform($task)]);
    }

    public function destroy(CoworkTask $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        $task->delete();

        return response()->json(['deleted' => true]);
    }

    public function run(CoworkTask $task, AiService $ai): JsonResponse
    {
        $this->authorizeOwnership($task);

        $task->update(['status' => 'in_progress']);

        try {
            $messages = [
                ['role' => 'system', 'content' => 'You are an autonomous assistant completing a delegated task. Produce a thorough, polished, ready-to-use result.'],
                ['role' => 'user', 'content' => trim($task->title . "\n\n" . ($task->description ?? ''))],
            ];

            $output = '';
            foreach ($ai->streamResponse($messages, $task->model ?: 'claude-haiku-4-5') as $chunk) {
                $output .= $chunk;
            }

            $isError = str_starts_with(trim($output), '[Error') || str_contains($output, 'API key is not configured');

            $task->update([
                'result' => $output,
                'status' => $isError ? 'failed' : 'completed',
                'completed_at' => $isError ? null : now(),
            ]);
        } catch (\Throwable $e) {
            $task->update([
                'result' => '[Error: ' . $e->getMessage() . ']',
                'status' => 'failed',
            ]);
        }

        return response()->json(['data' => $this->transform($task->fresh())]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function authorizeOwnership(CoworkTask $task): void
    {
        abort_unless($task->user_id === Auth::id(), 403);
    }

    /**
     * Calculate task statistics for the authenticated user.
     *
     * @return array<string, int>
     */
    private function calculateStats(): array
    {
        $base = CoworkTask::where('user_id', Auth::id());

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(CoworkTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'model' => $task->model,
            'status' => $task->status,
            'priority' => $task->priority,
            'result' => $task->result,
            'scheduled_for' => optional($task->scheduled_for)->toIso8601String(),
            'completed_at' => optional($task->completed_at)->toIso8601String(),
            'created_at' => optional($task->created_at)->toIso8601String(),
            'updated_at' => optional($task->updated_at)->toIso8601String(),
        ];
    }
}

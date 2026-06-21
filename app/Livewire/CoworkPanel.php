<?php

namespace App\Livewire;

use App\Models\CoworkTask;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CoworkPanel extends Component
{
    public string $view = 'landing'; // 'landing' | 'list' | 'create' | 'detail'
    public ?int $activeTaskId = null;
    public string $statusFilter = 'all'; // all | pending | in_progress | completed | failed

    // Form fields
    public string $title = '';
    public string $description = '';
    public string $priority = 'medium';
    public string $model = 'claude-haiku-4-5';
    public ?string $scheduledFor = null;

    public array $modelOptions = [
        'claude-opus-4-8' => 'Opus 4.8',
        'claude-sonnet-4-6' => 'Sonnet 4.6',
        'claude-haiku-4-5' => 'Haiku 4.5',
    ];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'model' => 'required|string',
            'scheduledFor' => 'nullable|date',
        ];
    }

    public function showCreate(): void
    {
        $this->reset(['title', 'description', 'priority', 'model', 'scheduledFor']);
        $this->priority = 'medium';
        $this->model = 'claude-haiku-4-5';
        $this->resetValidation();
        $this->view = 'create';
    }

    public function getStarted(): void
    {
        $this->view = 'list';
    }

    public function showList(): void
    {
        $this->view = 'list';
        $this->activeTaskId = null;
    }

    public function openTask(int $id): void
    {
        $this->activeTaskId = $id;
        $this->view = 'detail';
    }

    public function createTask(): void
    {
        if (!Auth::check()) {
            session()->flash('coworkMessage', 'Please log in to create tasks.');
            return;
        }

        $this->validate();

        $task = CoworkTask::create([
            'user_id' => Auth::id(),
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'model' => $this->model,
            'scheduled_for' => $this->scheduledFor ?: null,
            'status' => 'pending',
        ]);

        session()->flash('coworkMessage', 'Task created successfully.');
        $this->openTask($task->id);
    }

    public function runTask(int $id): void
    {
        $task = CoworkTask::where('user_id', Auth::id())->find($id);
        if (!$task) {
            return;
        }

        $task->update(['status' => 'in_progress']);

        try {
            $messages = [
                ['role' => 'system', 'content' => 'You are an autonomous assistant completing a delegated task. Produce a thorough, polished, ready-to-use result.'],
                ['role' => 'user', 'content' => trim($task->title . "\n\n" . ($task->description ?? ''))],
            ];

            $aiService = new AiService();
            $output = '';
            foreach ($aiService->streamResponse($messages, $task->model ?: 'claude-haiku-4-5') as $chunk) {
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
    }

    public function updateStatus(int $id, string $status): void
    {
        $task = CoworkTask::where('user_id', Auth::id())->find($id);
        if ($task) {
            $task->update([
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);
        }
    }

    public function deleteTask(int $id): void
    {
        CoworkTask::where('user_id', Auth::id())->where('id', $id)->delete();
        if ($this->activeTaskId === $id) {
            $this->showList();
        }
    }

    public function getTasksProperty()
    {
        $query = CoworkTask::where('user_id', Auth::id());
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        return $query->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();
    }

    public function getActiveTaskProperty()
    {
        if (!$this->activeTaskId) {
            return null;
        }
        return CoworkTask::where('user_id', Auth::id())->find($this->activeTaskId);
    }

    public function getStatsProperty(): array
    {
        $base = CoworkTask::where('user_id', Auth::id());
        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.cowork-panel');
    }
}

<?php

namespace App\Livewire;

use App\Models\Design;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DesignPanel extends Component
{
    public string $currentTab = 'recent'; // recent | yours | examples
    public string $search = '';

    // Generation dialog
    public bool $showDialog = false;
    public string $dialogType = 'prototype';
    public string $dialogPrompt = '';

    // Viewer
    public ?int $viewingId = null;

    public array $designTypes = [
        'slides' => ['label' => 'Slides', 'sub' => 'Presentations & pitch decks'],
        'prototype' => ['label' => 'Product prototype', 'sub' => 'Interactive app mockups'],
        'wireframe' => ['label' => 'Product wireframe', 'sub' => 'Lo-fi screens & flows'],
        'document' => ['label' => 'Document', 'sub' => 'Resumes, PDFs, etc'],
        'animation' => ['label' => 'Animation', 'sub' => 'Motion graphics & loops'],
        'blank' => ['label' => 'Blank canvas', 'sub' => 'Start from scratch'],
    ];

    public function openDialog(string $type): void
    {
        $this->dialogType = $type;
        $this->dialogPrompt = '';
        $this->showDialog = true;
    }

    public function closeDialog(): void
    {
        $this->showDialog = false;
        $this->dialogPrompt = '';
    }

    public function generate(): void
    {
        if (!Auth::check()) {
            $this->showDialog = false;
            session()->flash('designMessage', 'Please log in to generate designs.');
            return;
        }

        $this->validate([
            'dialogPrompt' => 'required|string|max:2000',
        ]);

        $typeLabel = $this->designTypes[$this->dialogType]['label'] ?? 'Design';

        $design = Design::create([
            'user_id' => Auth::id(),
            'title' => \Illuminate\Support\Str::limit($this->dialogPrompt, 60),
            'type' => $this->dialogType,
            'prompt' => $this->dialogPrompt,
            'status' => 'generating',
        ]);

        $this->showDialog = false;
        $this->dialogPrompt = '';
        $this->currentTab = 'yours';
        $this->viewingId = $design->id;

        try {
            $system = "You are an expert UI/UX designer and front-end engineer. Generate a single, self-contained HTML document (inline CSS, no external assets, no explanations) that fulfils the user's request for a {$typeLabel}. Use modern, clean, responsive design. Output ONLY the raw HTML starting with <!DOCTYPE html>.";

            $aiService = new AiService();
            $output = '';
            foreach ($aiService->streamResponse([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $this->dialogPromptForDesign($design)],
            ], 'claude-haiku-4-5') as $chunk) {
                $output .= $chunk;
            }

            $output = $this->extractHtml($output);
            $isError = str_starts_with(trim($output), '[Error') || str_contains($output, 'API key is not configured');

            $design->update([
                'content' => $output,
                'status' => $isError ? 'failed' : 'ready',
            ]);
        } catch (\Throwable $e) {
            $design->update(['status' => 'failed', 'content' => '[Error: ' . $e->getMessage() . ']']);
        }
    }

    private function dialogPromptForDesign(Design $design): string
    {
        return $design->prompt;
    }

    /** Strip markdown code fences if the model wrapped the HTML. */
    private function extractHtml(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/```(?:html)?\s*(.*?)```/s', $raw, $m)) {
            return trim($m[1]);
        }
        return $raw;
    }

    public function viewDesign(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeViewer(): void
    {
        $this->viewingId = null;
    }

    public function toggleStar(int $id): void
    {
        $design = Design::where('user_id', Auth::id())->find($id);
        if ($design) {
            $design->update(['is_starred' => !$design->is_starred]);
        }
    }

    public function deleteDesign(int $id): void
    {
        Design::where('user_id', Auth::id())->where('id', $id)->delete();
        if ($this->viewingId === $id) {
            $this->viewingId = null;
        }
    }

    public function getDesignsProperty()
    {
        $query = Design::where('user_id', Auth::id());
        if (trim($this->search) !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('prompt', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->currentTab === 'yours') {
            $query->whereIn('status', ['ready', 'generating', 'failed']);
        }
        return $query->orderByDesc('created_at')->get();
    }

    public function getViewingProperty()
    {
        if (!$this->viewingId) {
            return null;
        }
        return Design::where('user_id', Auth::id())->find($this->viewingId);
    }

    public function render()
    {
        return view('livewire.design-panel')->layout('layouts.app');
    }
}

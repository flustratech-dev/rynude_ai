<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class ArtifactPanel extends Component
{
    public $isOpen = false;
    public $currentArtifact = null;
    public $artifacts = [];
    public $copied = false;
    public $activeTab = 'code'; // 'code' or 'preview'
    public $versions = [];
    public $fullscreen = false;
    public $searchQuery = '';

    public function mount()
    {
        $this->loadArtifacts();
    }

    /**
     * Load the lightweight artifact list for the grid. Deliberately omits the
     * (potentially huge) `content` column: it would otherwise be serialised into the
     * Livewire snapshot on every request — typing in search, switching tabs, etc. —
     * which is the main source of lag. Full content is fetched on demand in
     * openArtifact() / switchVersion().
     */
    private function loadArtifacts(): void
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if (! $userId) {
            $this->artifacts = [];
            return;
        }

        $this->artifacts = \App\Models\MessageArtifact::query()
            ->whereHas('message.conversation', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('created_at', 'desc')
            ->get(['id', 'identifier', 'title', 'language', 'type', 'created_at', 'is_public'])
            ->unique('identifier')
            ->values()
            ->toArray();
    }

    public function generateTemplate($type)
    {
        $prompt = "Create a new artifact for: " . $type . ". Please generate a full, working example.";
        $this->dispatch('sendPromptFromArtifact', prompt: $prompt);
    }

    #[On('openArtifact')]
    public function showArtifact($artifact)
    {
        // Only trust the id from the event payload: re-load the artifact from the DB
        // with an ownership check so a spoofed browser event can't inject arbitrary
        // content into the panel. A brand-new artifact (no id yet) is accepted inline.
        $id = is_array($artifact) ? ($artifact['id'] ?? null) : null;

        if ($id) {
            $model = \App\Models\MessageArtifact::find($id);
            if (! $model || ! $this->ownsArtifact($model)) {
                return;
            }
            $this->currentArtifact = $model->toArray();
            $this->loadVersions($id);
        } else {
            $this->currentArtifact = is_array($artifact) ? $artifact : null;
        }

        $this->isOpen = true;
        $this->loadArtifacts();
        $this->dispatch('showArtifactPanel');
    }

    public function openArtifact($id)
    {
        // Always load full content fresh from the DB (the list omits it) and verify
        // ownership before exposing the artifact.
        $model = \App\Models\MessageArtifact::find($id);
        if (! $model || ! $this->ownsArtifact($model)) {
            return;
        }

        $this->currentArtifact = $model->toArray();
        $this->isOpen = true;
        $this->loadVersions($id);
        $this->dispatch('showArtifactPanel');
    }

    public function loadVersions($id)
    {
        $this->versions = [];
        $model = \App\Models\MessageArtifact::find($id);
        if (!$model || !$this->ownsArtifact($model)) {
            return;
        }
        if ($model && $model->identifier) {
            $allVersions = \App\Models\MessageArtifact::where('identifier', $model->identifier)
                ->orderBy('created_at', 'asc')
                ->get();
                
            foreach ($allVersions as $index => $v) {
                $this->versions[] = [
                    'id' => $v->id,
                    'version_number' => $index + 1,
                    'is_current' => $v->id == $id,
                ];
            }
        }
    }

    /**
     * Load a specific version of the currently open artifact (same identifier).
     */
    public function switchVersion($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if ($model && $this->ownsArtifact($model)) {
            $this->currentArtifact = $model->toArray();
            $this->isOpen = true;
            $this->loadVersions($id);
        }
    }

    /**
     * Publish the current artifact to a public, read-only URL and copy the link.
     */
    public function publishArtifact($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if (!$model || !$this->ownsArtifact($model)) {
            return;
        }

        if (empty($model->public_token)) {
            $model->public_token = \Illuminate\Support\Str::random(32);
        }
        $model->is_public = true;
        $model->save();

        if ($this->currentArtifact && ($this->currentArtifact['id'] ?? null) == $id) {
            $this->currentArtifact['is_public'] = true;
            $this->currentArtifact['public_token'] = $model->public_token;
        }

        $this->dispatch('copyToClipboard', content: route('artifact.shared', $model->public_token));
    }

    public function unpublishArtifact($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if (!$model || !$this->ownsArtifact($model)) {
            return;
        }

        $model->is_public = false;
        $model->save();

        if ($this->currentArtifact && ($this->currentArtifact['id'] ?? null) == $id) {
            $this->currentArtifact['is_public'] = false;
        }
    }

    /** Ensure the artifact belongs to the authenticated user's conversation. */
    private function ownsArtifact(\App\Models\MessageArtifact $artifact): bool
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return false;
        }
        return \App\Models\MessageArtifact::where('id', $artifact->id)
            ->whereHas('message.conversation', fn ($q) => $q->where('user_id', $userId))
            ->exists();
    }

    public function closeArtifact()
    {
        $this->isOpen = false;
        $this->currentArtifact = null;
        $this->dispatch('closeArtifactPanel');
    }

    public function deleteArtifact($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if (! $model || ! $this->ownsArtifact($model)) {
            return;
        }

        $this->ownedByIdentifier($model->identifier)->delete();
        $this->loadArtifacts();

        if ($this->currentArtifact && isset($this->currentArtifact['identifier']) && $this->currentArtifact['identifier'] === $model->identifier) {
            $this->closeArtifact();
        }
    }

    public function renameArtifact($id, $newTitle)
    {
        $newTitle = trim((string) $newTitle);
        $model = \App\Models\MessageArtifact::find($id);
        if (! $model || ! $this->ownsArtifact($model) || $newTitle === '') {
            return;
        }

        // Cap the title length to avoid unbounded input being persisted/rendered.
        $newTitle = \Illuminate\Support\Str::limit($newTitle, 120, '');

        $this->ownedByIdentifier($model->identifier)->update(['title' => $newTitle]);
        $this->loadArtifacts();

        if ($this->currentArtifact && isset($this->currentArtifact['identifier']) && $this->currentArtifact['identifier'] === $model->identifier) {
            $this->currentArtifact['title'] = $newTitle;
        }
    }

    /**
     * Query scoped to artifacts that share an identifier AND belong to the
     * authenticated user's conversations — so a bulk delete/rename can never reach
     * another user's rows even if identifiers collide.
     */
    private function ownedByIdentifier(?string $identifier): \Illuminate\Database\Eloquent\Builder
    {
        $userId = \Illuminate\Support\Facades\Auth::id();

        return \App\Models\MessageArtifact::query()
            ->where('identifier', $identifier)
            ->whereHas('message.conversation', fn ($q) => $q->where('user_id', $userId));
    }

    public function createNewArtifact()
    {
        $this->currentArtifact = [
            'id' => null,
            'title' => 'Untitled',
            'language' => 'new',
            'type' => 'new',
            'content' => ''
        ];
        $this->isOpen = true;
        $this->dispatch('showArtifactPanel'); // Custom event to ensure it opens in split screen
    }

    public function copyCode()
    {
        if ($this->currentArtifact) {
            // In a real app, this would use clipboard API via Alpine.js
            $this->copied = true;
            $this->dispatch('copyToClipboard', content: $this->currentArtifact['content']);
        }
    }

    public function downloadAsPdf($mode = null)
    {
        if (! $this->currentArtifact) {
            return;
        }

        $binary = app(\App\Services\PdfRenderer::class)->render($this->currentArtifact, $mode);
        $filename = \Illuminate\Support\Str::slug($this->currentArtifact['title'] ?: 'document') . '.pdf';

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function downloadAsDocx($mode = null)
    {
        if (! $this->currentArtifact) {
            return;
        }

        $binary = app(\App\Services\DocxRenderer::class)->render($this->currentArtifact, $mode);
        $filename = \Illuminate\Support\Str::slug($this->currentArtifact['title'] ?: 'document') . '.docx';

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    public function downloadAsMarkdown()
    {
        if (! $this->currentArtifact) {
            return;
        }

        // Match the on-screen preview: strip the YAML front-matter so the .md is the
        // clean document body (same behaviour as the artifact preview blades).
        $content = \App\Services\PdfRenderer::stripFrontMatter($this->currentArtifact['content'] ?? '');
        $filename = \Illuminate\Support\Str::slug($this->currentArtifact['title'] ?: 'document') . '.md';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    public function toggleFullscreen()
    {
        $this->fullscreen = !$this->fullscreen;
    }

    /**
     * Map an artifact language to a real file extension.
     */
    private function extensionForLanguage(string $language): string
    {
        $map = [
            'html' => 'html', 'javascript' => 'js', 'js' => 'js', 'jsx' => 'jsx',
            'typescript' => 'ts', 'ts' => 'ts', 'tsx' => 'tsx', 'react' => 'jsx',
            'python' => 'py', 'py' => 'py', 'php' => 'php', 'css' => 'css',
            'json' => 'json', 'markdown' => 'md', 'md' => 'md', 'svg' => 'svg',
            'sql' => 'sql', 'java' => 'java', 'go' => 'go', 'rust' => 'rs',
            'ruby' => 'rb', 'c' => 'c', 'cpp' => 'cpp', 'csharp' => 'cs',
            'bash' => 'sh', 'shell' => 'sh', 'yaml' => 'yaml', 'xml' => 'xml',
        ];

        return $map[strtolower($language)] ?? 'txt';
    }

    public function downloadAsFile()
    {
        if (!$this->currentArtifact) {
            return;
        }

        $ext = $this->extensionForLanguage($this->currentArtifact['language'] ?? 'txt');
        $filename = \Illuminate\Support\Str::slug($this->currentArtifact['title'] ?: 'artifact') . '.' . $ext;
        $content = $this->currentArtifact['content'] ?? '';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function getFilteredArtifactsProperty(): array
    {
        if (empty(trim($this->searchQuery))) {
            return $this->artifacts;
        }

        $search = strtolower(trim($this->searchQuery));

        return array_values(array_filter($this->artifacts, function ($a) use ($search) {
            return str_contains(strtolower($a['title'] ?? ''), $search)
                || str_contains(strtolower($a['language'] ?? ''), $search);
        }));
    }

    public function render()
    {
        return view('livewire.artifact-panel');
    }
}

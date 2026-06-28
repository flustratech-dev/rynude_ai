<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * Presents the open artifact (or the grid of all artifacts when nothing is open).
 *
 * Visibility is OWNED BY THE PARENT (`ChatLayout::$openArtifactId`) and passed
 * down as a reactive prop. This component does NOT decide whether the panel is
 * visible; it only renders the artifact whose id it receives.
 *
 * Why: the previous design had three sources of truth for "is the panel open?"
 * (this component's `$isOpen`, ChatLayout's `$artifactPanelOpen`, Alpine's
 * entangled copy). They drifted, raced, and the panel would auto-reopen or
 * flicker. Now: one int on the parent, reactive prop down, never out of sync.
 */
class ArtifactPanel extends Component
{
    /** Comes from ChatLayout. Null = panel hidden. Int = id of the open artifact. */
    #[Reactive]
    public ?int $openArtifactId = null;

    public $currentArtifact = null;        // metadata only (no content) for the open artifact
    public array $artifacts = [];          // lightweight list for the grid
    public bool $copied = false;
    public string $activeTab = 'code';     // 'code' or 'preview'
    public array $versions = [];
    public bool $fullscreen = false;
    public string $searchQuery = '';

    public function mount()
    {
        $this->loadArtifacts();
        if ($this->openArtifactId) {
            $this->loadCurrentArtifact($this->openArtifactId);
        }
    }

    /**
     * Reactive prop changed (parent set/cleared `openArtifactId`). Sync the
     * locally-cached `currentArtifact` accordingly. No event ping-pong: the
     * parent already decided what's open.
     */
    public function updatedOpenArtifactId($value): void
    {
        $id = $value === null || $value === '' ? null : (int) $value;
        if ($id === null) {
            $this->currentArtifact = null;
            $this->versions = [];
            return;
        }
        if ($id === 0) {
            // Sentinel used by ChatLayout::showArtifactPanel() to make the panel
            // visible for the unsaved "New artifact" form. createNewArtifact()
            // already populated $currentArtifact in memory — do not clobber it.
            return;
        }
        $this->loadCurrentArtifact($id);
    }

    /**
     * Load the lightweight artifact list for the grid. Deliberately omits the
     * (potentially huge) `content` column: it would otherwise be serialised into the
     * Livewire snapshot on every request — typing in search, switching tabs, etc. —
     * which is the main source of lag. Full content is fetched on demand by the
     * computed `artifactContent`.
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

    /**
     * Load metadata for a single artifact (ownership-checked). Content is
     * read lazily by the computed `artifactContent` so toggling tabs/versions
     * doesn't serialise the full body into the Livewire snapshot.
     */
    private function loadCurrentArtifact(int $id): void
    {
        $model = \App\Models\MessageArtifact::find($id);
        if (! $model || ! $this->ownsArtifact($model)) {
            $this->currentArtifact = null;
            $this->versions = [];
            return;
        }
        $array = $model->toArray();
        unset($array['content']);
        $this->currentArtifact = $array;
        $this->loadVersions($id);
    }

    public function generateTemplate($type)
    {
        $prompt = "Create a new artifact for: " . $type . ". Please generate a full, working example.";
        $this->dispatch('sendPromptFromArtifact', prompt: $prompt);
    }

    #[Computed]
    public function artifactContent()
    {
        if ($this->openArtifactId) {
            return \App\Models\MessageArtifact::where('id', $this->openArtifactId)->value('content') ?? '';
        }
        return $this->currentArtifact['content'] ?? '';
    }

    /**
     * Open an artifact from the grid. We don't mutate `openArtifactId` here
     * (it's reactive from the parent); we bubble an `openArtifact` event up so
     * ChatLayout becomes the single mutator of that state.
     */
    public function openArtifact($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if (! $model || ! $this->ownsArtifact($model)) {
            return;
        }
        $this->dispatch('openArtifact', id: (int) $id);
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
     * Bubbles via the parent so the URL/state stays in sync.
     */
    public function switchVersion($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if ($model && $this->ownsArtifact($model)) {
            $this->dispatch('openArtifact', id: (int) $id);
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

    /** Close the open artifact — bubbles to the parent which owns the state. */
    public function closeArtifact()
    {
        $this->dispatch('closeArtifactPanel');
    }

    public function deleteArtifact($id)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if (! $model || ! $this->ownsArtifact($model)) {
            return;
        }

        $identifier = $model->identifier;
        $this->ownedByIdentifier($identifier)->delete();
        $this->loadArtifacts();

        if ($this->currentArtifact && isset($this->currentArtifact['identifier']) && $this->currentArtifact['identifier'] === $identifier) {
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
        // For brand-new artifacts (no id yet) we keep the current in-memory shape
        // and rely on the parent's `openArtifactId` staying null. The view's
        // `currentArtifact && language === 'new'` branch handles the empty state.
        $this->currentArtifact = [
            'id' => null,
            'title' => 'Untitled',
            'language' => 'new',
            'type' => 'new',
            'content' => '',
        ];
        // Bubble a sentinel open with id=0 so the parent shows the panel; the
        // updatedOpenArtifactId hook treats unknown ids as a no-op for current state.
        // (We use the existing dispatch path so the URL/state still stays consistent.)
        $this->dispatch('showArtifactPanel');
    }


    public function copyCode()
    {
        if ($this->currentArtifact) {
            $this->dispatch('copyToClipboard', content: $this->artifactContent);
        }
    }

    public function downloadAsPdf($mode = null)
    {
        if (! $this->currentArtifact) {
            return;
        }

        $artifactData = $this->currentArtifact;
        $artifactData['content'] = $this->artifactContent;
        $binary = app(\App\Services\PdfRenderer::class)->render($artifactData, $mode);
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

        $artifactData = $this->currentArtifact;
        $artifactData['content'] = $this->artifactContent;
        $binary = app(\App\Services\DocxRenderer::class)->render($artifactData, $mode);
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
        $content = \App\Services\PdfRenderer::stripFrontMatter($this->artifactContent);
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
        $content = $this->artifactContent;

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

    /**
     * Computed flag the view uses to decide between "show open artifact" and
     * "show the grid". `currentArtifact` covers the brand-new (unsaved) case
     * which has no `openArtifactId` but still wants the open layout.
     */
    public function getIsOpenProperty(): bool
    {
        return $this->openArtifactId !== null || $this->currentArtifact !== null;
    }

    public function render()
    {
        return view('livewire.artifact-panel');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;

/**
 * Owns the *layout* state: which left panel is active, which artifact is open.
 *
 * The artifact-open state lives here (and only here) as `$openArtifactId`:
 *   - null   → panel hidden
 *   - int id → panel showing that artifact
 * The child `ArtifactPanel` receives this as a reactive prop and renders
 * accordingly. This avoids the triple-listener / triple-source-of-truth bug
 * the old `isOpen` + `artifactPanelOpen` + Alpine entangle setup created.
 */
class ChatLayout extends Component
{
    public bool $sidebarOpen = true;

    public $settingsOpen = false;

    /** Persisted in the URL so a refresh keeps the open artifact / open panel. */
    #[Url(as: 'artifact')]
    public ?int $openArtifactId = null;

    #[Url(as: 'panel')]
    public ?string $activePanel = null; // null | 'chats' | 'projects' | 'code' | 'artifacts' | ...

    public function mount()
    {
        if (request()->has('panel')) {
            $this->activePanel = request()->query('panel');
        }
        if ($this->activePanel === 'customize') {
            $this->sidebarOpen = false;
        }
    }

    public function openSettingsModal()
    {
        $this->settingsOpen = true;
    }

    public function closeSettingsModal()
    {
        $this->settingsOpen = false;
    }

    #[\Livewire\Attributes\On('open-panel')]
    public function togglePanel($panel)
    {
        // Alpine may pass either a string or {panel: 'x'}; normalise.
        $panelName = is_array($panel) && isset($panel['panel'])
            ? $panel['panel']
            : (is_string($panel) ? $panel : null);

        if (!$panelName) return;

        if ($panelName === 'artifacts') {
            // The full-screen artifacts grid panel toggles independently of the
            // open-artifact id; opening the grid does NOT close a viewed artifact.
            $this->activePanel = $this->activePanel === 'artifacts' ? null : 'artifacts';
            return;
        }

        $next = ($this->activePanel === $panelName) ? null : $panelName;
        $this->activePanel = $next;
        // Navigating to a non-chat panel should close the open artifact (the
        // user explicitly moved away — keeping it floating felt buggy).
        if ($next !== null) {
            $this->openArtifactId = null;
        }
    }

    /**
     * Streaming finished and produced an artifact. Auto-open ONLY when the
     * user is still on the chat (no side panel active) — otherwise we'd
     * yank them back to a panel they navigated away from.
     */
    #[\Livewire\Attributes\On('artifactReady')]
    public function artifactReady($id = null)
    {
        if (!$id) return;
        if ($this->activePanel !== null) {
            // User has moved to another panel — leave the artifact for them
            // to open manually from the "View artifact" badge in the message.
            return;
        }
        $this->openArtifactId = (int) $id;
    }

    /**
     * Sentinel "show the panel" event — used by the in-panel "New artifact"
     * flow where the child populates an in-memory unsaved artifact and just
     * wants the parent to make the panel visible. The id 0 has no DB row,
     * so the child's updatedOpenArtifactId hook leaves its draft alone.
     */
    #[\Livewire\Attributes\On('showArtifactPanel')]
    public function showArtifactPanel()
    {
        if ($this->openArtifactId === null) {
            $this->openArtifactId = 0;
        }
        if ($this->activePanel === 'artifacts') {
            $this->activePanel = null;
        }
    }

    #[\Livewire\Attributes\On('close-panel')]
    public function closePanel()
    {
        $this->activePanel = null;
    }

    /**
     * Close the open artifact (split-view panel). Does not affect the
     * full-screen Artifacts grid (activePanel='artifacts').
     */
    #[\Livewire\Attributes\On('closeArtifactPanel')]
    public function closeArtifact()
    {
        $this->openArtifactId = null;
    }

    /**
     * Open an artifact. Accepts either a bare id or `{id: ...}` payload so it
     * tolerates the legacy `dispatch('openArtifact', artifact: [...])` shape.
     */
    #[\Livewire\Attributes\On('openArtifact')]
    public function openArtifact($artifact = null, $id = null)
    {
        $resolvedId = $id;
        if ($resolvedId === null && $artifact !== null) {
            if (is_array($artifact)) {
                $resolvedId = $artifact['id'] ?? null;
            } elseif (is_numeric($artifact)) {
                $resolvedId = (int) $artifact;
            }
        }
        if (!$resolvedId) {
            return;
        }
        $this->openArtifactId = (int) $resolvedId;
        // Switch out of the full-screen artifacts grid so the split-view panel is visible.
        if ($this->activePanel === 'artifacts') {
            $this->activePanel = null;
        }
    }

    public function render()
    {
        return view('livewire.chat-layout');
    }
}

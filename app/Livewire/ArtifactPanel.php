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
        $user_id = \Illuminate\Support\Facades\Auth::id();
        if ($user_id) {
            $this->artifacts = \App\Models\MessageArtifact::whereHas('message.conversation', function($q) use ($user_id) {
                $q->where('user_id', $user_id);
            })->orderBy('created_at', 'desc')->get()->unique('identifier')->values()->toArray();
        } else {
            $this->artifacts = [];
        }
    }

    public function generateTemplate($type)
    {
        $prompt = "Create a new artifact for: " . $type . ". Please generate a full, working example.";
        $this->dispatch('sendPromptFromArtifact', prompt: $prompt);
    }

    #[On('openArtifact')]
    public function showArtifact($artifact)
    {
        $this->currentArtifact = $artifact;
        $this->isOpen = true;
        
        $this->loadVersions($artifact['id']);

        // Add to artifacts list if not already there
        $exists = collect($this->artifacts)->firstWhere('id', $artifact['id']);
        if (!$exists) {
            $this->artifacts[] = $artifact;
        }
    }

    public function openArtifact($id)
    {
        $this->currentArtifact = collect($this->artifacts)->firstWhere('id', $id);
        if (!$this->currentArtifact) {
            $model = \App\Models\MessageArtifact::find($id);
            if ($model && $this->ownsArtifact($model)) {
                $this->currentArtifact = $model->toArray();
            } else {
                return;
            }
        }

        $this->isOpen = true;
        $this->loadVersions($id);
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
        if ($model) {
            \App\Models\MessageArtifact::where('identifier', $model->identifier)->delete();
            $this->mount();
            if ($this->currentArtifact && isset($this->currentArtifact['identifier']) && $this->currentArtifact['identifier'] === $model->identifier) {
                $this->closeArtifact();
            }
        }
    }

    public function renameArtifact($id, $newTitle)
    {
        $model = \App\Models\MessageArtifact::find($id);
        if ($model && !empty(trim($newTitle))) {
            \App\Models\MessageArtifact::where('identifier', $model->identifier)->update(['title' => trim($newTitle)]);
            $this->mount();
            if ($this->currentArtifact && isset($this->currentArtifact['identifier']) && $this->currentArtifact['identifier'] === $model->identifier) {
                $this->currentArtifact['title'] = trim($newTitle);
            }
        }
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

    public function downloadAsPdf()
    {
        if ($this->currentArtifact) {
            $rendered = null;
            $lang = strtolower($this->currentArtifact['language']);
            
            if ($lang === 'html') {
                $rendered = $this->currentArtifact['content'];
            } elseif (in_array($lang, ['markdown', 'md']) || $this->currentArtifact['type'] !== 'code') {
                $md = \Illuminate\Support\Str::markdown($this->currentArtifact['content']);
                // Add formal styling for the print window to match the UI preview
                $css = '@page { margin: 3cm 3cm 3cm 4cm; } body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; text-align: justify; margin: 0; padding: 0; } h1 { text-align: center; font-size: 16pt; font-weight: bold; margin-top: 24pt; margin-bottom: 24pt; text-transform: uppercase; } h2 { font-size: 14pt; font-weight: bold; margin-top: 18pt; margin-bottom: 12pt; } h3, h4, h5 { font-size: 12pt; font-weight: bold; margin-top: 12pt; margin-bottom: 12pt; } p { margin: 0; text-indent: 1.27cm; } ul, ol { margin-top: 0; margin-bottom: 0; padding-left: 2.5cm; } li { text-align: justify; margin-bottom: 0; } pre { background:#f4f4f4; padding:1rem; border-radius:8px; font-size: 10pt; text-indent: 0; } code { font-family: monospace; }';
                $rendered = '<html><head><title>'.$this->currentArtifact['title'].'</title><style>' . $css . '</style></head><body>' . $md . '</body></html>';
            } else {
                $rendered = '<html><head><title>'.$this->currentArtifact['title'].'</title></head><body><pre style="white-space: pre-wrap; font-family: monospace;">' . htmlspecialchars($this->currentArtifact['content']) . '</pre></body></html>';
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($rendered);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = \Illuminate\Support\Str::slug($this->currentArtifact['title']) . '.pdf';
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, $filename);
        }
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

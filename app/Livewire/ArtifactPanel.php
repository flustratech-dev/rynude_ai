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

    public function mount()
    {
        $this->artifacts = [];
    }

    #[On('openArtifact')]
    public function showArtifact($artifact)
    {
        $this->currentArtifact = $artifact;
        $this->isOpen = true;

        // Add to artifacts list if not already there
        $exists = collect($this->artifacts)->firstWhere('id', $artifact['id']);
        if (!$exists) {
            $this->artifacts[] = $artifact;
        }
    }

    public function openArtifact($id)
    {
        $this->currentArtifact = collect($this->artifacts)->firstWhere('id', $id);
        $this->isOpen = true;
    }

    public function closeArtifact()
    {
        $this->isOpen = false;
        $this->currentArtifact = null;
        $this->dispatch('closeArtifactPanel');
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
            if ($this->currentArtifact['language'] === 'html') {
                $rendered = $this->currentArtifact['content'];
            } elseif ($this->currentArtifact['type'] !== 'code') {
                $md = \Illuminate\Support\Str::markdown($this->currentArtifact['content']);
                $rendered = '<html><head><title>'.$this->currentArtifact['title'].'</title><style>body{font-family:sans-serif;padding:2rem;line-height:1.6;color:#333} pre{background:#f4f4f4;padding:1rem;border-radius:8px;} code{font-family:monospace;}</style></head><body>' . $md . '</body></html>';
            }

            $this->dispatch('downloadPdf', [
                'content' => $this->currentArtifact['content'], 
                'title' => $this->currentArtifact['title'],
                'rendered' => $rendered,
                'language' => $this->currentArtifact['language']
            ]);
        }
    }

    public function render()
    {
        return view('livewire.artifact-panel');
    }
}

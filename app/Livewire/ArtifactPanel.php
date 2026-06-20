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

    public function mount()
    {
        $this->artifacts = [];
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
            if ($model) {
                $this->currentArtifact = $model->toArray();
            }
        }
        
        $this->isOpen = true;
        $this->loadVersions($id);
    }
    
    public function loadVersions($id)
    {
        $this->versions = [];
        $model = \App\Models\MessageArtifact::find($id);
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

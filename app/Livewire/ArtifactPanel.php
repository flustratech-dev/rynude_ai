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
        if ($this->isOpen) {
            // Close the artifact and go back to the list
            $this->isOpen = false;
            $this->currentArtifact = null;
        } else {
            // Close the entire panel
            $this->dispatch('closeArtifactPanel');
        }
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

    public function render()
    {
        return view('livewire.artifact-panel');
    }
}

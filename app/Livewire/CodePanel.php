<?php

namespace App\Livewire;

use Livewire\Component;

class CodePanel extends Component
{
    public bool $isPremium = false;

    public function mount()
    {
        // Future: $this->isPremium = auth()->user()->plan !== 'Free';
        $this->isPremium = false;
    }

    public function closePanel()
    {
        $this->dispatch('close-panel');
    }

    public function openUpgradeModal()
    {
        $this->dispatch('close-panel');
        $this->dispatch('open-settings-modal', tab: 'billing');
    }

    public function render()
    {
        return view('livewire.code-panel');
    }
}

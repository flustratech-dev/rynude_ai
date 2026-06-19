<?php

namespace App\Livewire;

use Livewire\Component;

class HelpModal extends Component
{
    public bool $isOpen = false;
    public string $activeTab = 'help'; // 'help' | 'apps'
    public ?string $expandedFaq = null;

    #[\Livewire\Attributes\On('open-help-modal')]
    public function openModal($tab = 'help')
    {
        $this->activeTab = is_array($tab) && isset($tab['tab']) ? $tab['tab'] : (is_string($tab) ? $tab : 'help');
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->expandedFaq = null;
    }

    public function toggleFaq($id)
    {
        $this->expandedFaq = ($this->expandedFaq === $id) ? null : $id;
    }

    public function render()
    {
        return view('livewire.help-modal');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class QuotaWarningModal extends Component
{
    public $isOpen = false;

    #[On('open-quota-warning')]
    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function render()
    {
        return view('livewire.quota-warning-modal');
    }
}

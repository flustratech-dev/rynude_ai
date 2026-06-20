<?php

namespace App\Livewire;

use Livewire\Component;

class SystemUpdateModal extends Component
{
    public $isOpen = false;
    public $updateCommand = 'npx install-rynude@latest';

    protected $listeners = ['openUpdateModal' => 'openModal'];

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
        return view('livewire.system-update-modal');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;

class CustomizePanel extends Component
{
    public $activeTab = 'dashboard'; // 'dashboard', 'skills_list', 'create_skill'
    public $skills = [];
    
    // Form fields
    public $skillName = '';
    public $skillDescription = '';
    public $skillInstructions = '';

    public function mount()
    {
        $this->loadSkills();
    }

    public function loadSkills()
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            $this->skills = \App\Models\Skill::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        }
    }

    public function openSkillsList()
    {
        $this->activeTab = 'skills_list';
        $this->loadSkills();
    }

    public function openCreateSkill()
    {
        $this->skillName = '';
        $this->skillDescription = '';
        $this->skillInstructions = '';
        $this->activeTab = 'create_skill';
    }

    public function saveSkill()
    {
        $this->validate([
            'skillName' => 'required|string|max:255',
            'skillInstructions' => 'required|string',
        ]);

        $userId = \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            \App\Models\Skill::create([
                'user_id' => $userId,
                'name' => $this->skillName,
                'description' => $this->skillDescription,
                'instructions' => $this->skillInstructions,
                'is_active' => true,
            ]);
            $this->openSkillsList();
        }
    }

    public function deleteSkill($id)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            \App\Models\Skill::where('id', $id)->where('user_id', $userId)->delete();
            $this->loadSkills();
        }
    }

    public function toggleSkill($id)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            $skill = \App\Models\Skill::where('id', $id)->where('user_id', $userId)->first();
            if ($skill) {
                $skill->is_active = !$skill->is_active;
                $skill->save();
                $this->loadSkills();
            }
        }
    }

    public function render()
    {
        return view('livewire.customize-panel');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;

class CustomizePanel extends Component
{
    public $activeTab = 'dashboard'; // 'dashboard', 'skills_list', 'create_skill', 'templates'
    public $skills = [];

    // Form fields
    public $skillName = '';
    public $skillDescription = '';
    public $skillInstructions = '';
    public $skillIcon = '🛠️';

    public $skillIcons = ['🛠️', '🌐', '🔍', '✍️', '📊', '🧑‍💻', '🎯', '🧠', '📚', '⚡'];

    /** Pre-built skill templates users can start from. */
    public array $skillTemplates = [
        [
            'icon' => '🌐',
            'name' => 'Translator',
            'category' => 'Language',
            'description' => 'Translate text between languages while preserving tone.',
            'instructions' => "You are a professional translator. When I provide text, translate it accurately into the requested target language. Preserve the original tone, formatting, and intent. If no target language is specified, ask for one. Provide only the translation unless I ask for explanations.",
        ],
        [
            'icon' => '🔍',
            'name' => 'Code Reviewer',
            'category' => 'Engineering',
            'description' => 'Review code for bugs, style, and best practices.',
            'instructions' => "You are an expert code reviewer. When I share code, identify bugs, security issues, and performance problems. Suggest concrete improvements with code examples. Comment on readability and adherence to best practices. Be direct and prioritize the most important issues first.",
        ],
        [
            'icon' => '✍️',
            'name' => 'Writing Coach',
            'category' => 'Writing',
            'description' => 'Improve clarity, grammar, and style of your writing.',
            'instructions' => "You are a skilled writing coach. When I provide text, improve its clarity, grammar, flow, and word choice while keeping my voice. Explain significant changes briefly. Offer a polished version followed by a short list of the key edits you made.",
        ],
        [
            'icon' => '📊',
            'name' => 'Data Analyst',
            'category' => 'Analytics',
            'description' => 'Analyze data and surface actionable insights.',
            'instructions' => "You are a meticulous data analyst. When I share data, summarize the key trends, outliers, and correlations. Suggest charts that would best visualize the findings and provide clear, actionable recommendations. Show your reasoning step by step.",
        ],
        [
            'icon' => '🧠',
            'name' => 'Brainstorm Partner',
            'category' => 'Ideation',
            'description' => 'Generate creative ideas and explore possibilities.',
            'instructions' => "You are an energetic brainstorming partner. When I give you a topic, generate a diverse range of creative ideas, including unconventional ones. Group ideas by theme, build on the strongest options, and ask questions that push the thinking further.",
        ],
        [
            'icon' => '📚',
            'name' => 'Study Tutor',
            'category' => 'Education',
            'description' => 'Explain concepts simply and quiz your understanding.',
            'instructions' => "You are a patient tutor. Explain concepts clearly using simple language and analogies. Break complex topics into steps, check my understanding with short questions, and adapt your explanations to my level. Encourage me when I make progress.",
        ],
    ];

    public function mount()
    {
        $this->loadSkills();
    }

    public function openTemplates()
    {
        $this->activeTab = 'templates';
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
        $this->skillIcon = '🛠️';
        $this->resetValidation();
        $this->activeTab = 'create_skill';
    }

    public function useTemplate($index)
    {
        $template = $this->skillTemplates[$index] ?? null;
        if ($template) {
            $this->skillName = $template['name'];
            $this->skillDescription = $template['description'];
            $this->skillInstructions = $template['instructions'];
            $this->skillIcon = $template['icon'];
            $this->resetValidation();
            $this->activeTab = 'create_skill';
        }
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
                'icon' => $this->skillIcon,
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

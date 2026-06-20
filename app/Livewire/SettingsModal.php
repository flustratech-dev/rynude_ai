<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Collection;

class SettingsModal extends Component
{
    public $isOpen = false;
    public $activeTab = 'general';

    public $name = 'Guest User';
    public $email = 'guest@example.com';
    public $language = 'en';
    public $customInstructions = '';

    // Appearance
    public $theme = 'light';

    // API Key fields
    public $anthropicApiKey = '';
    public $openaiApiKey = '';
    public $nineRouterApiKey = '';
    public $useProxy = false;
    public $proxyBaseUrl = '';
    public $proxyApiKey = '';
    public $huggingfaceApiKey = '';
    public $huggingfaceBaseUrl = 'https://api-inference.huggingface.co/v1';
    public $apiKeyStatus = null; // null, 'saved', 'error'
    public $hfStatus = null;

    // Billing fields
    public $plan = 'Free';
    public $tokensUsed = 45200;
    public $tokensLimit = 100000;
    public $resetDate = '2026-07-01';

    public function mount()
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->customInstructions = $user->custom_instructions ?? '';
            $this->anthropicApiKey = $user->anthropic_api_key ?? '';
            $this->openaiApiKey = $user->openai_api_key ?? '';
            $this->nineRouterApiKey = $user->nine_router_api_key ?? '';
            $this->useProxy = $user->use_proxy ?? false;
            $this->proxyBaseUrl = $user->proxy_base_url ?? '';
            $this->proxyApiKey = $user->proxy_api_key ?? '';
            $this->huggingfaceApiKey = $user->huggingface_api_key ?? '';
            $this->huggingfaceBaseUrl = $user->huggingface_base_url ?? 'https://api-inference.huggingface.co/v1';
            $this->tokensLimit = $user->token_balance ?? 0;
        }
        $this->loadModels();
    }

    #[\Livewire\Attributes\On('open-settings-modal')]
    public function openModal($tab = 'general')
    {
        $this->activeTab = is_array($tab) && isset($tab['tab']) ? $tab['tab'] : (is_string($tab) ? $tab : 'general');
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveProfile()
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            $user->name = $this->name;
            $user->custom_instructions = $this->customInstructions;
            $user->save();
        }
        $this->dispatch('profileSaved');
    }

    public function updateTheme($theme)
    {
        $this->theme = $theme;
        $this->dispatch('themeChanged', $theme);
    }

    public function saveAppearance()
    {
        $this->dispatch('themeChanged', $this->theme);
    }

    public function saveApiKeys()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return;
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $user->anthropic_api_key = $this->anthropicApiKey;
        $user->openai_api_key = $this->openaiApiKey;
        $user->nine_router_api_key = $this->nineRouterApiKey;
        $user->use_proxy = $this->useProxy;
        $user->proxy_base_url = $this->proxyBaseUrl;
        $user->proxy_api_key = $this->proxyApiKey;
        $user->save();

        $this->apiKeyStatus = 'saved';
        $this->dispatch('apiKeysSaved');
    }

    public function saveHuggingface()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return;
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $user->huggingface_api_key = $this->huggingfaceApiKey;
        $user->huggingface_base_url = $this->huggingfaceBaseUrl;
        $user->save();

        $this->hfStatus = 'saved';
        $this->dispatch('hfSaved');
    }

    // Models Management
    public Collection $aiModels;
    public $isModelModalOpen = false;
    public $editModelId = null;
    public $modelCode = '';
    public $modelName = '';
    public $modelIsActive = true;
    public $modelProvider = 'huggingface';

    protected $rules = [
        'modelCode' => 'required|string',
        'modelName' => 'required|string|max:255',
    ];

    public function loadModels()
    {
        $this->aiModels = \App\Models\AiModel::orderBy('created_at', 'desc')->get();
    }

    public function createModel()
    {
        $this->resetModelInputFields();
        $this->isModelModalOpen = true;
    }

    public function closeModelModal()
    {
        $this->isModelModalOpen = false;
        $this->resetModelInputFields();
    }

    public function resetModelInputFields()
    {
        $this->editModelId = null;
        $this->modelCode = '';
        $this->modelName = '';
        $this->modelIsActive = true;
        $this->modelProvider = 'huggingface';
        $this->resetValidation();
    }

    public function storeModel()
    {
        $rules = $this->rules;
        if ($this->editModelId) {
            $rules['modelCode'] = 'required|string|unique:ai_models,code,' . $this->editModelId;
        } else {
            $rules['modelCode'] = 'required|string|unique:ai_models,code';
        }

        $this->validate($rules);

        \App\Models\AiModel::updateOrCreate(['id' => $this->editModelId], [
            'code' => $this->modelCode,
            'name' => $this->modelName,
            'is_active' => $this->modelIsActive,
            'provider' => $this->modelProvider,
        ]);

        session()->flash('modelMessage', $this->editModelId ? 'Model Updated Successfully.' : 'Model Created Successfully.');
        $this->closeModelModal();
        $this->loadModels();
    }

    public function editModel($id)
    {
        $model = \App\Models\AiModel::findOrFail($id);
        $this->editModelId = $id;
        $this->modelCode = $model->code;
        $this->modelName = $model->name;
        $this->modelIsActive = $model->is_active;
        $this->modelProvider = $model->provider ?? 'huggingface';

        $this->isModelModalOpen = true;
    }

    public function deleteModel($id)
    {
        \App\Models\AiModel::find($id)->delete();
        session()->flash('modelMessage', 'Model Deleted Successfully.');
        $this->loadModels();
    }

    public function toggleModelActive($id)
    {
        $model = \App\Models\AiModel::find($id);
        if ($model) {
            $model->is_active = !$model->is_active;
            $model->save();
            $this->loadModels();
        }
    }

    public function render()
    {
        if ($this->activeTab === 'models' && empty($this->aiModels)) {
            $this->loadModels();
        }
        return view('livewire.settings-modal');
    }
}

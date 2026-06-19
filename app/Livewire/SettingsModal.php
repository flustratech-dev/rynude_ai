<?php

namespace App\Livewire;

use Livewire\Component;

class SettingsModal extends Component
{
    public $isOpen = false;
    public $activeTab = 'general';

    // General fields
    public $name = 'Guest User';
    public $email = 'guest@example.com';
    public $language = 'en';

    // Appearance
    public $theme = 'light';

    // API Key fields
    public $anthropicApiKey = '';
    public $openaiApiKey = '';
    public $useProxy = false;
    public $proxyBaseUrl = '';
    public $proxyApiKey = '';
    public $apiKeyStatus = null; // null, 'saved', 'error'

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
            $this->anthropicApiKey = $user->anthropic_api_key ?? '';
            $this->openaiApiKey = $user->openai_api_key ?? '';
            $this->useProxy = $user->use_proxy ?? false;
            $this->proxyBaseUrl = $user->proxy_base_url ?? '';
            $this->proxyApiKey = $user->proxy_api_key ?? '';
        }
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
        // Mock save — real implementation will use User model
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
        $user->use_proxy = $this->useProxy;
        $user->proxy_base_url = $this->proxyBaseUrl;
        $user->proxy_api_key = $this->proxyApiKey;
        $user->save();

        $this->apiKeyStatus = 'saved';
        $this->dispatch('apiKeysSaved');
    }

    public function render()
    {
        return view('livewire.settings-modal');
    }
}

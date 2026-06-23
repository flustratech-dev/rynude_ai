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
    public $fontSize = 'medium';   // small | medium | large
    public $accentColor = '#D97757';
    public $compactMode = false;

    public $accentColors = ['#D97757', '#5E72E4', '#11998E', '#E0529C', '#F5A623', '#8B5CF6'];

    // API Key fields
    public $anthropicApiKey = '';
    public $openaiApiKey = '';
    public $nineRouterApiKey = '';
    public $googleApiKey = '';
    public $mistralApiKey = '';
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

    // Tracked token usage (from token_usages table)
    public int $trackedTokens = 0;
    public array $tokenBreakdown = [];

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
            $this->googleApiKey = $user->google_api_key ?? '';
            $this->mistralApiKey = $user->mistral_api_key ?? '';
            $this->useProxy = $user->use_proxy ?? false;
            $this->proxyBaseUrl = $user->proxy_base_url ?? '';
            $this->proxyApiKey = $user->proxy_api_key ?? '';
            $this->huggingfaceApiKey = $user->huggingface_api_key ?? '';
            $this->huggingfaceBaseUrl = $user->huggingface_base_url ?? 'https://api-inference.huggingface.co/v1';
            $this->tokensLimit = $user->token_balance ?? 0;

            $prefs = $user->preferences ?? [];
            $this->fontSize = $prefs['font_size'] ?? 'medium';
            $this->accentColor = $prefs['accent_color'] ?? '#D97757';
            $this->compactMode = (bool) ($prefs['compact_mode'] ?? false);
            $this->language = $prefs['language'] ?? 'en';

            // Real billing usage: prefer tracked token usage, fall back to estimate.
            $this->loadTokenUsage($user->id);
            $this->tokensUsed = $this->trackedTokens > 0
                ? $this->trackedTokens
                : $this->estimateTokensUsed($user->id);
        }
        $this->loadModels();
    }

    private function loadTokenUsage($userId): void
    {
        $rows = \App\Models\TokenUsage::where('user_id', $userId)
            ->selectRaw('model, provider, SUM(input_tokens) as input_total, SUM(output_tokens) as output_total')
            ->groupBy('model', 'provider')
            ->orderByRaw('SUM(input_tokens + output_tokens) DESC')
            ->get();

        $total = 0;
        $breakdown = [];
        foreach ($rows as $row) {
            $sum = (int) $row->input_total + (int) $row->output_total;
            $total += $sum;
            $breakdown[] = [
                'model' => $row->model,
                'provider' => $row->provider,
                'input' => (int) $row->input_total,
                'output' => (int) $row->output_total,
                'total' => $sum,
            ];
        }

        $this->trackedTokens = $total;
        $this->tokenBreakdown = $breakdown;
    }

    private function estimateTokensUsed($userId): int
    {
        $charCount = \App\Models\Message::whereHas('conversation', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->sum(\Illuminate\Support\Facades\DB::raw('LENGTH(content)'));

        // Rough heuristic: ~4 characters per token.
        return (int) ceil($charCount / 4);
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

    public function saveLanguage()
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            $prefs = $user->preferences ?? [];
            $prefs['language'] = $this->language;
            $user->preferences = $prefs;
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
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            $prefs = $user->preferences ?? [];
            $prefs['font_size'] = $this->fontSize;
            $prefs['accent_color'] = $this->accentColor;
            $prefs['compact_mode'] = $this->compactMode;
            $user->preferences = $prefs;
            $user->save();
        }

        $this->dispatch('themeChanged', $this->theme);
        $this->dispatch('appearanceChanged', fontSize: $this->fontSize, accentColor: $this->accentColor, compactMode: $this->compactMode);
    }

    // ── Data & Privacy ──────────────────────────────────────────
    public function exportAllChats($format = 'json')
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return;
        }

        $userId = \Illuminate\Support\Facades\Auth::id();
        $conversations = \App\Models\Conversation::with('messages')
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get();

        $payload = $conversations->map(fn ($c) => [
            'title' => $c->title,
            'created_at' => optional($c->created_at)->toIso8601String(),
            'messages' => $c->messages->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => optional($m->created_at)->toIso8601String(),
            ])->toArray(),
        ])->toArray();

        $data = json_encode([
            'exported_at' => now()->toIso8601String(),
            'conversation_count' => count($payload),
            'conversations' => $payload,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, 'rynude-chats-export-' . now()->format('Y-m-d') . '.json', ['Content-Type' => 'application/json']);
    }

    public function deleteAllChats()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return;
        }

        $userId = \Illuminate\Support\Facades\Auth::id();
        $conversations = \App\Models\Conversation::where('user_id', $userId)->get();
        foreach ($conversations as $c) {
            $c->messages()->delete();
            $c->delete();
        }

        $this->tokensUsed = 0;
        $this->dispatch('chatCreated'); // refresh other panels
        session()->flash('dataMessage', 'All chats have been deleted.');
    }

    public function buildDesktopApp()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) return;

        $batPath = base_path('build-exe.bat');
        if (file_exists($batPath) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Jalankan tanpa terminal (hidden) tapi tetap meminta akses Administrator (UAC)
            $command = 'powershell.exe Start-Process -FilePath "' . $batPath . '" -Verb RunAs -WindowStyle Hidden';
            pclose(popen('start /B ' . $command, 'r'));
            session()->flash('dataMessage', 'Proses Build berjalan secara gaib di latar belakang! Silakan klik "Yes" jika muncul pop-up Windows. Anda akan mendapat notifikasi saat selesai (± 3 menit).');
        } else {
            session()->flash('dataMessage', 'File build-exe.bat tidak ditemukan.');
        }
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
        $user->google_api_key = $this->googleApiKey;
        $user->mistral_api_key = $this->mistralApiKey;
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

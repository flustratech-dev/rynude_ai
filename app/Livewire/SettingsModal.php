<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingsModal extends Component
{
    public $isOpen = false;
    public $activeTab = 'general';

    // ── Profile ──────────────────────────────────────────────────
    public $name = '';
    public $email = '';
    public $nickname = '';
    public $profession = '';
    public $customInstructions = '';

    // ── Preferences ──────────────────────────────────────────────
    public $language = 'en';
    public $chatFont = 'default';
    public $theme = 'light';
    public $fontSize = 'medium';
    public $accentColor = '#D97757';
    public $compactMode = false;

    public $accentColors = ['#D97757', '#5E72E4', '#11998E', '#E0529C', '#F5A623', '#8B5CF6'];

    public $professionOptions = [
        '' => 'Select...',
        'developer' => 'Software Developer',
        'designer' => 'Designer',
        'data_scientist' => 'Data Scientist',
        'product_manager' => 'Product Manager',
        'student' => 'Student',
        'researcher' => 'Researcher',
        'writer' => 'Writer / Content Creator',
        'marketer' => 'Marketer',
        'business' => 'Business / Entrepreneur',
        'other' => 'Other',
    ];

    // ── Privacy & Capabilities ───────────────────────────────────
    public $allowTraining = false;
    public $capWebSearch = true;
    public $capArtifacts = true;
    public $capCodeExecution = false;

    // ── API Key fields ───────────────────────────────────────────
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
    public $apiKeyStatus = null;
    public $hfStatus = null;

    // ── Billing fields ───────────────────────────────────────────
    public $plan = 'Free';
    public $tokensUsed = 0;
    public $tokensLimit = 0;

    // Tracked token usage (from token_usages table)
    public int $trackedTokens = 0;
    public array $tokenBreakdown = [];

    // ── Flash messages ───────────────────────────────────────────
    public $settingsMessage = '';
    public $settingsMessageType = 'success'; // success | error

    public function mount()
    {
        if (Auth::check()) {
            $user = Auth::user();
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

            // Load all preferences from JSON
            $prefs = $user->preferences ?? [];
            $this->nickname = $prefs['nickname'] ?? '';
            $this->profession = $prefs['profession'] ?? '';
            $this->fontSize = $prefs['font_size'] ?? 'medium';
            $this->accentColor = $prefs['accent_color'] ?? '#D97757';
            $this->compactMode = (bool) ($prefs['compact_mode'] ?? false);
            $this->language = $prefs['language'] ?? 'en';
            $this->chatFont = $prefs['chat_font'] ?? 'default';
            $this->theme = $prefs['theme'] ?? 'light';
            $this->allowTraining = (bool) ($prefs['allow_training'] ?? false);
            $this->capWebSearch = (bool) ($prefs['cap_web_search'] ?? true);
            $this->capArtifacts = (bool) ($prefs['cap_artifacts'] ?? true);
            $this->capCodeExecution = (bool) ($prefs['cap_code_execution'] ?? false);

            // Real billing usage
            $this->loadTokenUsage($user->id);
            $this->tokensUsed = $this->trackedTokens > 0
                ? $this->trackedTokens
                : $this->estimateTokensUsed($user->id);
        }
        $this->loadModels();
    }

    // ── Auto-save hooks ──────────────────────────────────────────
    // These fire automatically when the property changes via wire:model.live

    public function updatedName()
    {
        $this->savePreferenceField('name', $this->name, true);
    }

    public function updatedNickname()
    {
        $this->savePref('nickname', $this->nickname);
    }

    public function updatedProfession()
    {
        $this->savePref('profession', $this->profession);
    }

    public function updatedCustomInstructions()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->custom_instructions = $this->customInstructions;
            $user->save();
        }
    }

    public function updatedLanguage()
    {
        $this->savePref('language', $this->language);
    }

    public function updatedChatFont()
    {
        $this->savePref('chat_font', $this->chatFont);
    }

    public function updatedAllowTraining()
    {
        $this->savePref('allow_training', $this->allowTraining);
    }

    public function updatedCapWebSearch()
    {
        $this->savePref('cap_web_search', $this->capWebSearch);
    }

    public function updatedCapArtifacts()
    {
        $this->savePref('cap_artifacts', $this->capArtifacts);
    }

    public function updatedCapCodeExecution()
    {
        $this->savePref('cap_code_execution', $this->capCodeExecution);
    }

    /**
     * Save a single key into the user's preferences JSON column.
     */
    private function savePref(string $key, mixed $value): void
    {
        if (!Auth::check()) return;

        $user = Auth::user();
        $prefs = $user->preferences ?? [];
        $prefs[$key] = $value;
        $user->preferences = $prefs;
        $user->save();
    }

    /**
     * Save a field directly on the user model (not in preferences).
     */
    private function savePreferenceField(string $field, mixed $value, bool $isDirectColumn = false): void
    {
        if (!Auth::check()) return;

        $user = Auth::user();
        if ($isDirectColumn) {
            $user->{$field} = $value;
        }
        $user->save();
    }

    // ── Token Usage ──────────────────────────────────────────────

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
        })->sum(DB::raw('LENGTH(content)'));

        return (int) ceil($charCount / 4);
    }

    // ── Modal open/close ─────────────────────────────────────────

    #[\Livewire\Attributes\On('open-settings-modal')]
    public function openModal($tab = 'general')
    {
        $this->activeTab = is_array($tab) && isset($tab['tab']) ? $tab['tab'] : (is_string($tab) ? $tab : 'general');
        $this->isOpen = true;
        $this->settingsMessage = '';
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->settingsMessage = '';
    }

    // ── Profile save (explicit button) ───────────────────────────

    public function saveProfile()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->name = $this->name;
            $user->custom_instructions = $this->customInstructions;
            $user->save();

            $this->savePref('nickname', $this->nickname);
            $this->savePref('profession', $this->profession);
        }
        $this->flashMessage('Profile saved successfully.');
        $this->dispatch('profileSaved');
    }

    // ── Theme ────────────────────────────────────────────────────

    public function updateTheme($theme)
    {
        $this->theme = $theme;
        $this->savePref('theme', $theme);
        $this->dispatch('themeChanged', $theme);
    }

    // ── Appearance ───────────────────────────────────────────────

    public function saveAppearance()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $prefs = $user->preferences ?? [];
            $prefs['font_size'] = $this->fontSize;
            $prefs['accent_color'] = $this->accentColor;
            $prefs['compact_mode'] = $this->compactMode;
            $prefs['theme'] = $this->theme;
            $user->preferences = $prefs;
            $user->save();
        }

        $this->dispatch('themeChanged', $this->theme);
        $this->dispatch('appearanceChanged', fontSize: $this->fontSize, accentColor: $this->accentColor, compactMode: $this->compactMode);
        $this->flashMessage('Appearance saved successfully.');
    }

    // ── Data & Privacy ───────────────────────────────────────────

    public function exportAllChats($format = 'json')
    {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
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
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $conversations = \App\Models\Conversation::where('user_id', $userId)->get();
        foreach ($conversations as $c) {
            $c->messages()->delete();
            $c->delete();
        }

        $this->tokensUsed = 0;
        $this->dispatch('chatCreated');
        $this->flashMessage('All chats have been deleted.', 'success');
    }

    // ── Account Deletion ─────────────────────────────────────────

    public function deleteAccount()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $userId = $user->id;

        // Delete all related data
        \App\Models\Conversation::where('user_id', $userId)->each(function ($c) {
            $c->messages()->delete();
            $c->delete();
        });

        \App\Models\TokenUsage::where('user_id', $userId)->delete();
        \App\Models\Project::where('user_id', $userId)->delete();
        \App\Models\CoworkTask::where('user_id', $userId)->delete();
        \App\Models\Design::where('user_id', $userId)->delete();

        // Log out and delete user
        Auth::logout();
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    // ── API Keys ─────────────────────────────────────────────────

    public function saveApiKeys()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
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
        $this->flashMessage('API Keys saved successfully.');
        $this->dispatch('apiKeysSaved');
    }

    public function saveHuggingface()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $user->huggingface_api_key = $this->huggingfaceApiKey;
        $user->huggingface_base_url = $this->huggingfaceBaseUrl;
        $user->save();

        $this->hfStatus = 'saved';
        $this->flashMessage('Hugging Face settings saved successfully.');
        $this->dispatch('hfSaved');
    }

    // ── Models Management ────────────────────────────────────────

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

        $this->flashMessage($this->editModelId ? 'Model updated successfully.' : 'Model created successfully.');
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
        $this->flashMessage('Model deleted successfully.');
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

    // ── Helpers ──────────────────────────────────────────────────

    private function flashMessage(string $message, string $type = 'success'): void
    {
        $this->settingsMessage = $message;
        $this->settingsMessageType = $type;
    }

    /**
     * Get user initials for the avatar.
     */
    public function getInitialsProperty(): string
    {
        $name = trim($this->name);
        if (empty($name)) return '?';

        $parts = preg_split('/\s+/', $name);
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }
        return strtoupper(mb_substr($name, 0, 2));
    }

    public function render()
    {
        if ($this->activeTab === 'models' && empty($this->aiModels)) {
            $this->loadModels();
        }
        return view('livewire.settings-modal');
    }
}

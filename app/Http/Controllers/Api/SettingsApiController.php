<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\TokenUsage;
use App\Services\AI\CostTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * Settings REST surface.
 *
 * Migrated from App\Livewire\SettingsModal. The Livewire component auto-saves
 * individual fields via wire:model.live "updated*" hooks and a handful of
 * explicit save buttons (saveProfile, saveAppearance, saveApiKeys). The REST
 * surface collapses those into:
 *   - GET   /api/settings                    -> SettingsModal::mount() snapshot
 *   - PATCH /api/settings                    -> the various save / updated hooks
 *   - POST  /api/settings/validate-api-key   -> key format validation
 *
 * Security: raw API keys are never returned to the client. show() only reports
 * whether each provider key is configured; update() accepts new keys but the
 * response echoes presence booleans, never the secret itself.
 */
class SettingsApiController extends Controller
{
    /**
     * Preference keys stored inside the user's `preferences` JSON column, mapped
     * to their default value. Mirrors SettingsModal::mount().
     */
    private const PREFERENCE_DEFAULTS = [
        'nickname' => '',
        'profession' => '',
        'language' => 'en',
        'chat_font' => 'default',
        'theme' => 'light',
        'font_size' => 'medium',
        'accent_color' => '#D97757',
        'compact_mode' => false,
        'allow_training' => false,
        'cap_web_search' => true,
        'cap_artifacts' => true,
        'cap_code_execution' => false,
    ];

    /** Provider key => the User column that stores it. */
    private const API_KEY_COLUMNS = [
        'anthropic' => 'anthropic_api_key',
        'openai' => 'openai_api_key',
        'nine_router' => 'nine_router_api_key',
        'google' => 'google_api_key',
        'mistral' => 'mistral_api_key',
        'glm' => 'glm_api_key',
        'kimi' => 'kimi_api_key',
        'qwen' => 'qwen_api_key',
        'huggingface' => 'huggingface_api_key',
        'github' => 'github_token',
    ];

    public function show(): JsonResponse
    {
        $user = Auth::user();

        return response()->json($this->settingsPayload($user));
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            // Profile (direct columns)
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'custom_instructions' => ['sometimes', 'nullable', 'string', 'max:10000'],

            // Profile (preferences)
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:255'],

            // Preferences
            'language' => ['sometimes', 'string', 'max:10'],
            'chat_font' => ['sometimes', 'string', 'max:50'],
            'theme' => ['sometimes', 'string', Rule::in(['light', 'dark', 'system', 'auto'])],
            'font_size' => ['sometimes', 'string', Rule::in(['small', 'medium', 'large'])],
            'accent_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'compact_mode' => ['sometimes', 'boolean'],
            'allow_training' => ['sometimes', 'boolean'],
            'cap_web_search' => ['sometimes', 'boolean'],
            'cap_artifacts' => ['sometimes', 'boolean'],
            'cap_code_execution' => ['sometimes', 'boolean'],

            // API keys + proxy
            'anthropic_api_key' => ['sometimes', 'nullable', 'string'],
            'openai_api_key' => ['sometimes', 'nullable', 'string'],
            'nine_router_api_key' => ['sometimes', 'nullable', 'string'],
            'google_api_key' => ['sometimes', 'nullable', 'string'],
            'mistral_api_key' => ['sometimes', 'nullable', 'string'],
            'glm_api_key' => ['sometimes', 'nullable', 'string'],
            'kimi_api_key' => ['sometimes', 'nullable', 'string'],
            'qwen_api_key' => ['sometimes', 'nullable', 'string'],
            'huggingface_api_key' => ['sometimes', 'nullable', 'string'],
            'huggingface_base_url' => ['sometimes', 'nullable', 'url'],
            'github_token' => ['sometimes', 'nullable', 'string'],
            'use_proxy' => ['sometimes', 'boolean'],
            'proxy_base_url' => ['sometimes', 'nullable', 'url'],
            'proxy_api_key' => ['sometimes', 'nullable', 'string'],

            // Custom model / action fields
            '_action' => ['sometimes', 'string'],
            'model_id' => ['sometimes', 'nullable', 'integer'],
            'model_code' => ['sometimes', 'required_if:_action,store_model', 'string'],
            'model_name' => ['sometimes', 'required_if:_action,store_model', 'string'],
            'model_provider' => ['sometimes', 'string'], // default ollama
            'model_is_active' => ['sometimes', 'boolean'],
        ]);

        // ── Actions ─────────────────────────────────────────────────────
        $action = $request->input('_action');

        if ($action === 'store_model') {
            $modelId = $request->input('model_id');
            $code = $request->input('model_code');
            $name = $request->input('model_name');
            $provider = $request->input('model_provider', 'huggingface');
            $isActive = $request->input('model_is_active', true);

            // Validation for unique code
            if ($modelId) {
                $exists = \App\Models\AiModel::where('code', $code)->where('id', '!=', $modelId)->exists();
            } else {
                $exists = \App\Models\AiModel::where('code', $code)->exists();
            }

            if ($exists) {
                return response()->json(['errors' => ['model_code' => ['Model code must be unique.']]], 422);
            }

            $modelData = [
                'code' => $code,
                'name' => $name,
                'provider' => $provider,
                'is_active' => $isActive,
            ];
            if (!$modelId) {
                // New custom models sort to the bottom of the picker.
                $modelData['sort_order'] = 9999;
            }
            \App\Models\AiModel::updateOrCreate(['id' => $modelId], $modelData);
        } elseif ($action === 'toggle_model') {
            $modelId = $request->input('model_id');
            $model = \App\Models\AiModel::find($modelId);
            if ($model) {
                $model->is_active = !$model->is_active;
                $model->save();
            }
        } elseif ($action === 'delete_model') {
            $modelId = $request->input('model_id');
            \App\Models\AiModel::destroy($modelId);
        } elseif ($action === 'delete_chats') {
            $conversations = \App\Models\Conversation::where('user_id', $user->id)->get();
            foreach ($conversations as $c) {
                $c->messages()->delete();
                $c->delete();
            }
        } elseif ($action === 'delete_account') {
            // Delete all related data
            \App\Models\Conversation::where('user_id', $user->id)->each(function ($c) {
                $c->messages()->delete();
                $c->delete();
            });

            \App\Models\TokenUsage::where('user_id', $user->id)->delete();
            \App\Models\Project::where('user_id', $user->id)->delete();
            \App\Models\CoworkTask::where('user_id', $user->id)->delete();
            \App\Models\Design::where('user_id', $user->id)->delete();

            Auth::logout();
            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['redirect' => route('login')]);
        }

        // ── Direct columns on the user model ────────────────────────────
        $directColumns = [
            'name', 'email', 'custom_instructions',
            'anthropic_api_key', 'openai_api_key', 'nine_router_api_key',
            'google_api_key', 'mistral_api_key', 'glm_api_key', 'kimi_api_key', 'qwen_api_key',
            'huggingface_api_key',
            'huggingface_base_url', 'use_proxy', 'proxy_base_url', 'proxy_api_key',
            'github_token',
        ];
        foreach ($directColumns as $column) {
            if (array_key_exists($column, $validated)) {
                $user->{$column} = $validated[$column];
            }
        }

        // ── Preference keys merged into the JSON column ─────────────────
        $prefs = $user->preferences ?? [];
        foreach (array_keys(self::PREFERENCE_DEFAULTS) as $key) {
            if (array_key_exists($key, $validated)) {
                $prefs[$key] = $validated[$key];
            }
        }
        $user->preferences = $prefs;

        $user->save();

        return response()->json($this->settingsPayload($user->fresh()));
    }

    public function validateApiKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(array_keys(self::API_KEY_COLUMNS))],
            'key' => ['required', 'string'],
        ]);

        $valid = $this->keyFormatLooksValid($validated['provider'], $validated['key']);

        return response()->json([
            'provider' => $validated['provider'],
            'valid' => $valid,
            'message' => $valid
                ? 'API key format looks valid.'
                : 'API key format is not valid for this provider.',
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Build the settings snapshot. Mirrors the state SettingsModal::mount()
     * hydrates, minus the raw secrets (only presence booleans are exposed).
     *
     * @return array<string, mixed>
     */
    private function settingsPayload($user): array
    {
        $prefs = $user->preferences ?? [];

        $preferences = [];
        foreach (self::PREFERENCE_DEFAULTS as $key => $default) {
            $value = $prefs[$key] ?? $default;
            // Normalise the booleans the way the Livewire component did.
            if (is_bool($default)) {
                $value = (bool) $value;
            }
            $preferences[$key] = $value;
        }

        $apiKeys = [];
        foreach (self::API_KEY_COLUMNS as $provider => $column) {
            $apiKeys[$provider] = !empty($user->{$column});
        }
        $apiKeys['use_proxy'] = (bool) ($user->use_proxy ?? false);
        $apiKeys['proxy_base_url'] = $user->proxy_base_url ?? '';
        $apiKeys['proxy_api_key_set'] = !empty($user->proxy_api_key);
        $apiKeys['huggingface_api_key_set'] = !empty($user->huggingface_api_key);
        $apiKeys['huggingface_base_url'] = $user->huggingface_base_url ?? 'https://api-inference.huggingface.co/v1';

        $u = $user;
        $hasAnthropic = !empty($u->anthropic_api_key);
        $hasOpenAI = !empty($u->openai_api_key);
        $useProxy = (bool)($u->use_proxy && $u->proxy_base_url);
        $hasNineRouter = !empty($u->nine_router_api_key);
        $hasHuggingFace = !empty($u->huggingface_api_key);
        $hasGoogle = !empty($u->google_api_key);
        $hasMistral = !empty($u->mistral_api_key);
        $hasGlm = !empty($u->glm_api_key);
        $hasKimi = !empty($u->kimi_api_key);
        $hasQwen = !empty($u->qwen_api_key);

        $available = $hasAnthropic || $useProxy || $hasNineRouter || $hasHuggingFace || $hasGoogle || $hasMistral || $hasGlm || $hasKimi || $hasQwen;

        $models = [
            [
                'code' => 'fable-5',
                'name' => 'Fable 5',
                'description' => 'For your toughest challenges',
                'is_available' => $available,
            ],
            [
                'code' => 'claude-sonnet-5',
                'name' => 'Sonnet 5',
                'description' => 'Next-gen balanced performance',
                'is_available' => $available,
            ],
            [
                'code' => 'claude-opus-4-8',
                'name' => 'Opus 4.8',
                'description' => 'For complex tasks',
                'is_available' => $available,
            ],
        ];

        $moreModels = [];
        // Order follows the seeder-defined sequence so the picker isn't scrambled.
        $allModels = \App\Models\AiModel::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();
        foreach ($allModels as $model) {
            $isAnthropic = str_starts_with($model->code, 'claude');
            $isOpenAI = str_starts_with($model->code, 'gpt');

            $is_available = false;
            if (str_starts_with($model->code, 'kr/claude')) {
                $is_available = true;
            } elseif ($model->provider === 'ollama') {
                // Ollama is a local provider — no API key required, always available
                $is_available = true;
            } elseif ($useProxy || $hasNineRouter) {
                $is_available = true;
            } elseif ($model->provider === 'huggingface' && $hasHuggingFace) {
                $is_available = true;
            } elseif ($model->provider === 'google' && $hasGoogle) {
                $is_available = true;
            } elseif ($model->provider === 'mistral' && $hasMistral) {
                $is_available = true;
            } elseif ($model->provider === 'glm' && $hasGlm) {
                $is_available = true;
            } elseif ($model->provider === 'kimi' && $hasKimi) {
                $is_available = true;
            } elseif ($model->provider === 'qwen' && $hasQwen) {
                $is_available = true;
            } elseif ($isAnthropic && $hasAnthropic) {
                $is_available = true;
            } elseif ($hasOpenAI && !$isAnthropic) {
                $is_available = true;
            }

            if (!in_array($model->code, ['fable-5', 'claude-sonnet-5', 'claude-opus-4-8'])) {
                $moreModels[] = [
                    'code' => $model->code,
                    'name' => $model->name,
                    'description' => $model->name,
                    'is_available' => $is_available,
                ];
            }
        }

        // No category regrouping: $moreModels already follows the seeder's
        // sort_order (set above), so the picker shows models exactly as arranged.

        // Fallback: ensure fugu-ultra appears (after haiku entries) even without DB seed
        $fuguInDb = $allModels->firstWhere('code', 'fugu-ultra');
        $fuguInMore = collect($moreModels)->firstWhere('code', 'fugu-ultra');
        if (!$fuguInDb && !$fuguInMore) {
            $insertAt = 0;
            foreach ($moreModels as $i => $m) {
                if (str_contains($m['code'], 'haiku')) {
                    $insertAt = $i + 1;
                }
            }
            array_splice($moreModels, $insertAt, 0, [[
                'code' => 'fugu-ultra',
                'name' => 'Fugu Ultra',
                'description' => 'Fugu Ultra',
                'is_available' => $available,
            ]]);
        }

        $aiModels = \App\Models\AiModel::orderBy('created_at', 'desc')->get();

        return [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'nickname' => $preferences['nickname'],
                'profession' => $preferences['profession'],
                'custom_instructions' => $user->custom_instructions ?? '',
            ],
            'preferences' => $preferences,
            'api_keys' => $apiKeys,
            'models' => $models,
            'more_models' => $moreModels,
            'ai_models' => $aiModels,
            'billing' => $this->billingPayload($user),
        ];
    }

    /**
     * Token usage / billing summary. Mirrors SettingsModal::loadTokenUsage()
     * with the estimateTokensUsed() fallback.
     *
     * @return array<string, mixed>
     */
    private function billingPayload($user): array
    {
        $rows = TokenUsage::where('user_id', $user->id)
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

        $tokensUsed = $total > 0 ? $total : $this->estimateTokensUsed($user->id);

        return [
            'plan' => 'Free',
            'tokens_used' => $tokensUsed,
            'tokens_limit' => (int) ($user->token_balance ?? 0),
            'tracked_tokens' => $total,
            'token_breakdown' => $breakdown,
        ];
    }

    private function estimateTokensUsed($userId): int
    {
        $charCount = Message::whereHas('conversation', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->sum(DB::raw('LENGTH(content)'));

        return (int) ceil($charCount / 4);
    }

    /**
     * Token Usage Analytics endpoint.
     * GET /api/token-usage?range=7d
     * Returns: summary, by_model, daily_trend
     */
    public function tokenUsage(Request $request): JsonResponse
    {
        $user = Auth::user();
        $range = $request->get('range', '7d');

        $query = TokenUsage::where('user_id', $user->id);

        switch ($range) {
            case 'today':
                $query->whereDate('usage_date', Carbon::today());
                $days = 1;
                break;
            case '30d':
                $query->where('usage_date', '>=', Carbon::now()->subDays(29)->toDateString());
                $days = 30;
                break;
            case 'all':
                $days = null;
                break;
            case '7d':
            default:
                $query->where('usage_date', '>=', Carbon::now()->subDays(6)->toDateString());
                $days = 7;
                break;
        }

        // ── Per-model breakdown ──────────────────────────────────────────
        $rows = (clone $query)
            ->selectRaw('model, provider, SUM(input_tokens) as input_total, SUM(output_tokens) as output_total, SUM(COALESCE(rtk_saved_chars, 0)) as rtk_saved, SUM(COALESCE(rtk_original_chars, 0)) as rtk_original, COUNT(DISTINCT usage_date) as days_active')
            ->groupBy('model', 'provider')
            ->orderByRaw('SUM(input_tokens + output_tokens) DESC')
            ->get();

        $totalInput = 0;
        $totalOutput = 0;
        $totalRtkSaved = 0;
        $totalRtkOriginal = 0;
        $totalCost = 0.0;
        $maxModelTokens = 1;
        $byModel = [];

        foreach ($rows as $row) {
            $inp = (int) $row->input_total;
            $out = (int) $row->output_total;
            $modelTotal = $inp + $out;
            if ($modelTotal > $maxModelTokens) $maxModelTokens = $modelTotal;
            $totalInput += $inp;
            $totalOutput += $out;
            $totalRtkSaved += (int) $row->rtk_saved;
            $totalRtkOriginal += (int) $row->rtk_original;

            // Calculate cost using CostTracker pricing
            $costUsd = $this->estimateCost($row->model, $inp, $out);
            $totalCost += $costUsd;

            $rtkSaved = (int) $row->rtk_saved;
            $rtkOrig = (int) $row->rtk_original;
            $rtkPct = $rtkOrig > 0 ? round(($rtkSaved / $rtkOrig) * 100) : 0;
            $rtkTokens = intdiv($rtkSaved, 4);

            $byModel[] = [
                'model'       => $row->model,
                'provider'    => $row->provider ?? 'unknown',
                'input_tokens'  => $inp,
                'output_tokens' => $out,
                'total_tokens'  => $modelTotal,
                'cost_usd'    => round($costUsd, 4),
                'days_active' => (int) $row->days_active,
                'rtk_saved_chars' => $rtkSaved,
                'rtk_saved_tokens' => $rtkTokens,
                'rtk_saved_pct'   => $rtkPct,
                'bar_pct'     => 0, // filled below
            ];
        }

        // Compute bar percentages after we know the max
        foreach ($byModel as &$m) {
            $m['bar_pct'] = $maxModelTokens > 0 ? round($m['total_tokens'] / $maxModelTokens * 100) : 0;
        }
        unset($m);

        // ── RTK savings summary ──────────────────────────────────────────
        $rtkSavedPct = 0;
        $rtkSavedTokensEst = 0;
        $rtkSavedCostEst = 0.0;
        if ($totalRtkOriginal > 0) {
            $rtkSavedPct = round($totalRtkSaved / $totalRtkOriginal * 100, 1);
            // Rough token estimate: chars/4
            $rtkSavedTokensEst = (int) round($totalRtkSaved / 4);
            // Cost for saved tokens, using average blended rate
            $avgInputRate = 3.0; // default $3/M tokens
            $rtkSavedCostEst = round($rtkSavedTokensEst * $avgInputRate / 1_000_000, 4);
        }

        // ── Daily trend ──────────────────────────────────────────────────
        $dailyRows = (clone $query)
            ->selectRaw('usage_date, SUM(input_tokens) as input_total, SUM(output_tokens) as output_total')
            ->groupBy('usage_date')
            ->orderBy('usage_date', 'asc')
            ->get()
            ->keyBy('usage_date');

        $dailyTrend = [];
        if ($days !== null) {
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->toDateString();
                $row = $dailyRows->get($date);
                $inp = $row ? (int) $row->input_total : 0;
                $out = $row ? (int) $row->output_total : 0;
                $dailyTrend[] = [
                    'date'         => $date,
                    'total_tokens' => $inp + $out,
                    'input_tokens' => $inp,
                    'output_tokens'=> $out,
                    'cost_usd'     => round($this->estimateCostBlended($inp + $out), 4),
                ];
            }
        } else {
            // All time — group by month for readability
            foreach ($dailyRows as $date => $row) {
                $inp = (int) $row->input_total;
                $out = (int) $row->output_total;
                $dailyTrend[] = [
                    'date'         => $date,
                    'total_tokens' => $inp + $out,
                    'input_tokens' => $inp,
                    'output_tokens'=> $out,
                    'cost_usd'     => round($this->estimateCostBlended($inp + $out), 4),
                ];
            }
        }

        // Bar heights for chart
        $maxDailyTokens = max(1, collect($dailyTrend)->max('total_tokens'));
        foreach ($dailyTrend as &$day) {
            $day['bar_pct'] = $maxDailyTokens > 0 ? round($day['total_tokens'] / $maxDailyTokens * 100) : 0;
        }
        unset($day);

        // ── Quota / plan ─────────────────────────────────────────────────
        $totalTokens = $totalInput + $totalOutput;
        $tokensLimit = (int) ($user->token_balance ?? 0);

        return response()->json([
            'range'    => $range,
            'summary'  => [
                'total_input'       => $totalInput,
                'total_output'      => $totalOutput,
                'total_tokens'      => $totalTokens,
                'estimated_cost_usd'=> round($totalCost, 4),
                'rtk_saved_chars'   => $totalRtkSaved,
                'rtk_original_chars'=> $totalRtkOriginal,
                'rtk_saved_pct'     => $rtkSavedPct,
                'rtk_saved_tokens_est' => $rtkSavedTokensEst,
                'rtk_saved_cost_est'   => $rtkSavedCostEst,
            ],
            'by_model'    => $byModel,
            'daily_trend' => $dailyTrend,
            'quota'       => [
                'tokens_used'  => $totalTokens,
                'tokens_limit' => $tokensLimit,
                'pct_used'     => $tokensLimit > 0 ? round($totalTokens / $tokensLimit * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Estimate cost using CostTracker pricing data.
     */
    private function estimateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        // Re-use CostTracker logic
        $pricing = [
            'claude-3-5-sonnet-20241022' => ['input' => 3.0, 'output' => 15.0],
            'claude-3-5-haiku-20241022'  => ['input' => 0.8, 'output' => 4.0],
            'claude-3-opus-20240229'     => ['input' => 15.0, 'output' => 75.0],
            'claude-sonnet-4-6'          => ['input' => 3.0, 'output' => 15.0],
            'claude-haiku-4-6'           => ['input' => 0.8, 'output' => 4.0],
            'claude-sonnet-5'            => ['input' => 3.0, 'output' => 15.0],
            'claude-opus-4-8'            => ['input' => 15.0, 'output' => 75.0],
            'fable-5'                    => ['input' => 15.0, 'output' => 75.0],
            'fugu-ultra'                 => ['input' => 25.0, 'output' => 100.0],
            'gpt-4o'                     => ['input' => 2.5, 'output' => 10.0],
            'gpt-4o-mini'                => ['input' => 0.15, 'output' => 0.6],
            'gpt-4-turbo'                => ['input' => 10.0, 'output' => 30.0],
            'gemini-2.0-flash'           => ['input' => 0.1, 'output' => 0.4],
            'gemini-1.5-pro'             => ['input' => 1.25, 'output' => 5.0],
            'mistral-large-latest'       => ['input' => 3.0, 'output' => 9.0],
            'default'                    => ['input' => 3.0, 'output' => 15.0],
        ];

        $rates = $pricing[$model] ?? $pricing['default'];
        return (($inputTokens * $rates['input']) + ($outputTokens * $rates['output'])) / 1_000_000;
    }

    /**
     * Blended cost estimate for daily trend (input-only average rate).
     */
    private function estimateCostBlended(int $totalTokens): float
    {
        // Use average blended rate of ~$4/M total
        return $totalTokens * 4.0 / 1_000_000;
    }

    /**
     * Lightweight, offline format validation for a provider's API key. Avoids
     * making live network calls (which would be non-deterministic in tests and
     * leak the key off-box); checks the recognisable provider prefixes/length.
     */
    private function keyFormatLooksValid(string $provider, string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }

        return match ($provider) {
            'anthropic' => str_starts_with($key, 'sk-ant-') && strlen($key) >= 20,
            'openai' => str_starts_with($key, 'sk-') && strlen($key) >= 20,
            'huggingface' => str_starts_with($key, 'hf_') && strlen($key) >= 8,
            'google' => strlen($key) >= 20,
            'mistral' => strlen($key) >= 20,
            'glm' => strlen($key) >= 8,
            'kimi' => strlen($key) >= 8,
            'qwen' => strlen($key) >= 8,
            'nine_router' => strlen($key) >= 8,
            default => false,
        };
    }
}

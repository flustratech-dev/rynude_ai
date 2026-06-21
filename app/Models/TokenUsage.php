<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUsage extends Model
{
    protected $fillable = [
        'user_id',
        'model',
        'provider',
        'input_tokens',
        'output_tokens',
        'usage_date',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record token usage for a user, aggregating into a single daily row per model.
     */
    public static function record(int $userId, string $model, ?string $provider, int $inputTokens, int $outputTokens): void
    {
        if ($inputTokens <= 0 && $outputTokens <= 0) {
            return;
        }

        $row = static::firstOrNew([
            'user_id' => $userId,
            'model' => $model,
            'usage_date' => now()->toDateString(),
        ]);

        $row->provider = $provider;
        $row->input_tokens = ($row->input_tokens ?? 0) + $inputTokens;
        $row->output_tokens = ($row->output_tokens ?? 0) + $outputTokens;
        $row->save();
    }
}
